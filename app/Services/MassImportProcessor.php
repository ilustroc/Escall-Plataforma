<?php

namespace App\Services;

use App\Models\Importacion;
use App\Models\ImportacionError;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use RuntimeException;
use Throwable;
use ZipArchive;

class MassImportProcessor
{
    public const MAX_ROWS = 500000;
    public const BATCH_SIZE = 1000;

    private const HEADERS = [
        'asignacion' => ['cartera','documento','nombre','codigo','entidad','cosecha','producto','sub_producto','departamento','deuda_total','deuda_capital','campana','fec_pro'],
        'pagos' => ['codigo','fecha','monto','asesor','operacion','observacion'],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $errorBuffer = [];

    public function process(Importacion $importacion): void
    {
        $this->errorBuffer = [];
        if (! in_array($importacion->tipo, array_keys(self::HEADERS), true)) throw new RuntimeException('Tipo de importación no soportado.');
        $path = Storage::disk('local')->path($importacion->archivo_path);
        if (! is_file($path)) throw new RuntimeException('El archivo temporal ya no está disponible.');

        $importacion->update(['estado' => 'validando', 'etapa' => 'validando', 'iniciada_en' => now(), 'mensaje' => null]);
        try {
            $result = $this->validateFile($importacion, $path);
        } catch (RuntimeException $exception) {
            // Structural errors (headers, damaged input, encoding, row limit)
            // are deterministic validation failures, not retryable queue faults.
            $this->recordError($importacion, 1, [
                'campo' => null,
                'codigo' => 'FILE_INVALID',
                'motivo' => $exception->getMessage(),
                'valor' => null,
            ]);
            $this->flushErrors();
            $importacion->update([
                'estado' => 'fallido', 'etapa' => 'validacion',
                'mensaje' => 'El archivo no superó las validaciones. No se modificó la base de datos.',
                'fallidas' => max(1, (int) $importacion->fallidas),
                'finalizada_en' => now(),
            ]);

            return;
        }
        $this->flushErrors();
        if ($result['errors'] > 0) {
            $importacion->update([
                'estado' => 'fallido', 'etapa' => 'validacion', 'total_filas' => $result['rows'],
                'filas_procesadas' => $result['rows'], 'filas_validas' => $result['valid'],
                'fallidas' => $result['errors'], 'periodo' => $result['period'],
                'mensaje' => 'El archivo tiene errores de validación. No se modificó la base de datos.',
                'finalizada_en' => now(),
            ]);
            return;
        }

        $importacion->update([
            'estado' => 'procesando', 'etapa' => 'procesando', 'total_filas' => $result['rows'],
            'filas_procesadas' => 0, 'filas_validas' => $result['valid'], 'periodo' => $result['period'],
        ]);
        $this->applyFile($importacion, $path, $result['headers']);
        $importacion->update(['estado' => 'completado', 'etapa' => 'completado', 'finalizada_en' => now(), 'mensaje' => null]);
    }

    private function validateFile(Importacion $importacion, string $path): array
    {
        $headers = null; $rows = 0; $valid = 0; $errors = 0; $period = null; $keys = [];
        foreach ($this->rows($path) as [$line, $row, $formula]) {
            if ($headers === null) {
                $headers = $this->validateHeaders($row, $importacion->tipo);
                continue;
            }
            if ($this->emptyRow($row)) continue;
            $rows++;
            if ($rows > self::MAX_ROWS) throw new RuntimeException('El archivo supera el límite de 500 000 filas.');
            $data = $this->mapRow($headers, $row);
            $rowErrors = $formula ? [['campo' => null, 'codigo' => 'FORMULA_NOT_ALLOWED', 'motivo' => 'No se permiten fórmulas en archivos de importación.', 'valor' => null]] : $this->validateRow($data, $importacion->tipo, $period);
            if (! $rowErrors && $importacion->tipo === 'asignacion') {
                $key = $data['codigo'].'|'.$data['fec_pro'];
                if (isset($keys[$key])) $rowErrors[] = ['campo' => 'CODIGO', 'codigo' => 'DUPLICATE_IN_FILE', 'motivo' => 'El código y FEC_PRO se repiten dentro del archivo.', 'valor' => $data['codigo']];
                else $keys[$key] = true;
                $period ??= $data['fec_pro'];
            }
            if ($rowErrors) {
                $errors += count($rowErrors);
                foreach ($rowErrors as $error) $this->recordError($importacion, $line, $error);
            } else $valid++;
            if ($rows % 500 === 0) $importacion->update(['total_filas' => $rows, 'filas_procesadas' => $rows, 'filas_validas' => $valid, 'fallidas' => $errors, 'periodo' => $period]);
        }
        if ($headers === null) throw new RuntimeException('El archivo está vacío o no contiene encabezados.');
        return compact('headers', 'rows', 'valid', 'errors', 'period');
    }

    private function applyFile(Importacion $importacion, string $path, array $headers): void
    {
        $batch = []; $processed = 0; $inserted = 0; $updated = 0; $skipped = 0;
        foreach ($this->rows($path) as [$line, $row]) {
            if ($line === 1 || $this->emptyRow($row)) continue;
            $batch[] = $this->normalize($this->mapRow($headers, $row), $importacion->tipo, $importacion->usuario_id);
            if (count($batch) === self::BATCH_SIZE) {
                [$new, $changed, $omitted] = $this->upsert($batch, $importacion->tipo);
                $processed += count($batch); $inserted += $new; $updated += $changed; $skipped += $omitted; $batch = [];
                $importacion->update(['filas_procesadas' => $processed, 'insertadas' => $inserted, 'actualizadas' => $updated, 'omitidas' => $skipped]);
            }
        }
        if ($batch) {
            [$new, $changed, $omitted] = $this->upsert($batch, $importacion->tipo);
            $processed += count($batch); $inserted += $new; $updated += $changed; $skipped += $omitted;
        }
        $importacion->update(['filas_procesadas' => $processed, 'insertadas' => $inserted, 'actualizadas' => $updated, 'omitidas' => $skipped]);
    }

    private function upsert(array $rows, string $type): array
    {
        return DB::transaction(function () use ($rows, $type): array {
            if ($type === 'asignacion') {
                $period = $rows[0]['FEC_PRO'];
                $columns = ['cartera','documento','nombre','codigo','entidad','cosecha','producto','sub_producto','departamento','deuda_total','deuda_capital','campana','FEC_PRO'];
                $existing = DB::table('Cartera_Mensual')->where('FEC_PRO', $period)->whereIn('codigo', array_column($rows, 'codigo'))->get($columns)->keyBy('codigo');
                $updates = ['cartera','documento','nombre','entidad','cosecha','producto','sub_producto','departamento','deuda_total','deuda_capital','campana','fecha_carga'];
                [$write, $inserted, $updated, $omitted] = $this->changes($rows, $existing->all(), 'codigo', array_diff($updates, ['fecha_carga']));
                if ($write) DB::table('Cartera_Mensual')->upsert($write, ['codigo', 'FEC_PRO'], $updates);
            } else {
                $codes = array_unique(array_column($rows, 'codigo'));
                $existing = DB::table('pagos')->whereIn('codigo', $codes)->get(['codigo','fecha','monto','operacion','asesor','observacion'])->mapWithKeys(fn ($row) => [$this->paymentKey((array) $row) => $row])->all();
                [$write, $inserted, $updated, $omitted] = $this->changes($rows, $existing, null, ['asesor','observacion'], fn (array $row) => $this->paymentKey($row));
                if ($write) DB::table('pagos')->upsert($write, ['codigo','fecha','monto','operacion'], ['asesor','observacion']);
            }
            return [$inserted, $updated, $omitted];
        });
    }

    /**
     * Split a chunk into inserts, meaningful updates and idempotent no-ops.
     * Keeping unchanged rows out of the UPSERT also preserves fecha_carga.
     *
     * @param array<string, object> $existing
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int, 3: int}
     */
    private function changes(array $rows, array $existing, ?string $keyField, array $compare, ?callable $key = null): array
    {
        $write = []; $inserted = 0; $updated = 0; $omitted = 0;
        foreach ($rows as $row) {
            $identity = $key ? $key($row) : $row[$keyField];
            $current = $existing[$identity] ?? null;
            if ($current === null) { $write[] = $row; $inserted++; continue; }
            foreach ($compare as $field) {
                if (! $this->sameValue($current->{$field}, $row[$field] ?? null)) { $write[] = $row; $updated++; continue 2; }
            }
            $omitted++;
        }
        return [$write, $inserted, $updated, $omitted];
    }

    private function paymentKey(array $row): string
    {
        return (string) $row['codigo'].'|'.(string) $row['fecha'].'|'.number_format((float) $row['monto'], 2, '.', '').'|'.(string) $row['operacion'];
    }

    private function sameValue(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) return $left === $right;
        if (is_numeric($left) && is_numeric($right)) return bccomp((string) $left, (string) $right, 2) === 0;
        return (string) $left === (string) $right;
    }

    private function normalize(array $data, string $type, int $userId): array
    {
        if ($type === 'asignacion') {
            return [
                'cartera' => $data['cartera'] ?: null, 'documento' => $data['documento'], 'nombre' => $data['nombre'], 'codigo' => $data['codigo'],
                'entidad' => $data['entidad'] ?: null, 'cosecha' => $data['cosecha'] ?: null, 'producto' => $data['producto'] ?: null,
                'sub_producto' => $data['sub_producto'] ?: null, 'departamento' => $data['departamento'] ?: null,
                'deuda_total' => ImportService::number($data['deuda_total']), 'deuda_capital' => ImportService::number($data['deuda_capital']),
                'campana' => $data['campana'] ?: null, 'FEC_PRO' => $data['fec_pro'], 'fecha_carga' => now(),
            ];
        }
        return ['codigo' => $data['codigo'], 'fecha' => ImportService::date($data['fecha']), 'monto' => ImportService::number($data['monto']), 'asesor' => $data['asesor'] ?: null, 'operacion' => $data['operacion'] ?: '', 'observacion' => $data['observacion'] ?: null, 'usuario_id' => $userId];
    }

    private function validateHeaders(array $headers, string $type): array
    {
        $normalized = array_map(fn ($value) => $this->header((string) $value), $headers);
        if (count($normalized) !== count(array_unique($normalized))) throw new RuntimeException('El archivo contiene encabezados duplicados.');
        $expected = self::HEADERS[$type];
        if ($normalized !== $expected) throw new RuntimeException('Los encabezados no corresponden a la plantilla oficial de esta importación.');
        return $normalized;
    }

    private function validateRow(array $data, string $type, ?string $period): array
    {
        $errors = [];
        foreach ($data as $field => $value) if (mb_strlen($value) > 255 && ! in_array($field, ['observacion'], true)) $errors[] = $this->error(strtoupper($field), 'VALUE_TOO_LONG', 'El valor supera la longitud permitida.', $value);
        foreach ($type === 'asignacion' ? ['documento','nombre','codigo','fec_pro'] : ['codigo','fecha','monto'] as $field) if ($data[$field] === '') $errors[] = $this->error(strtoupper($field), 'REQUIRED', 'El campo es obligatorio.', null);
        if ($data['codigo'] !== '' && preg_match('/[eE][+-]?\d+/', $data['codigo'])) $errors[] = $this->error('CODIGO', 'SCIENTIFIC_NOTATION', 'El código no puede utilizar notación científica.', $data['codigo']);
        if ($type === 'asignacion') {
            if (! $this->processDate($data['fec_pro'])) $errors[] = $this->error('FEC_PRO', 'INVALID_PROCESS_DATE', 'FEC_PRO debe ser una fecha válida YYYYMMDD.', $data['fec_pro']);
            if ($period !== null && $data['fec_pro'] !== '' && $data['fec_pro'] !== $period) $errors[] = $this->error('FEC_PRO', 'CONFLICTING_PERIOD', 'El archivo debe contener un único FEC_PRO.', $data['fec_pro']);
            foreach (['deuda_total','deuda_capital'] as $field) if ($data[$field] !== '' && ImportService::number($data[$field]) === 'INVALID_NUMBER') $errors[] = $this->error(strtoupper($field), 'INVALID_AMOUNT', 'El importe no es numérico.', $data[$field]);
        } else {
            if (! ImportService::date($data['fecha'])) $errors[] = $this->error('FECHA', 'INVALID_DATE', 'La fecha de pago es inválida.', $data['fecha']);
            if (ImportService::number($data['monto']) === 'INVALID_NUMBER' || (float) ImportService::number($data['monto']) < 0) $errors[] = $this->error('MONTO', 'INVALID_AMOUNT', 'El importe debe ser numérico y no negativo.', $data['monto']);
        }
        return $errors;
    }

    private function rows(string $path): \Generator
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            $handle = fopen($path, 'rb');
            if (! $handle) throw new RuntimeException('No se pudo abrir el CSV.');
            $first = fgets($handle);
            if ($first === false || ! mb_check_encoding($first, 'UTF-8')) throw new RuntimeException('El CSV debe estar codificado en UTF-8.');
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            rewind($handle); $line = 0;
            try {
                while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                    $line++;
                    foreach ($row as $value) if (! mb_check_encoding((string) $value, 'UTF-8')) throw new RuntimeException('El CSV contiene una codificación inválida.');
                    yield [$line, array_map(fn ($value) => trim((string) $value), $row), false];
                }
            } finally { fclose($handle); }
            return;
        }
        if ($extension !== 'xlsx') throw new RuntimeException('Solo se permiten CSV UTF-8 y XLSX.');
        $this->inspectXlsx($path);
        $reader = ReaderFactory::createFromFile($path); $reader->open($path); $line = 0; $sheets = 0;
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if (++$sheets > 1) throw new RuntimeException('El XLSX debe contener una sola hoja.');
                foreach ($sheet->getRowIterator() as $row) {
                    $line++; $formula = false; $values = [];
                    foreach ($row->getCells() as $cell) { $formula = $formula || $cell instanceof FormulaCell; $values[] = trim((string) $cell->getValue()); }
                    yield [$line, $values, $formula];
                }
            }
        } finally { $reader->close(); }
    }

    private function inspectXlsx(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('El XLSX está dañado.');
        try {
            if ($zip->numFiles > 1000 || $zip->locateName('[Content_Types].xml') === false || $zip->locateName('xl/workbook.xml') === false) throw new RuntimeException('El XLSX no tiene una estructura válida.');
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i); $name = strtolower((string) ($stat['name'] ?? ''));
                if ($name === '' || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/') || preg_match('~vbaproject|externallinks|embeddings|activex~', $name)) throw new RuntimeException('El XLSX contiene contenido no permitido.');
                $size += (int) ($stat['size'] ?? 0);
                if ($size > 512 * 1024 * 1024 || (int) ($stat['size'] ?? 0) > max(1, (int) ($stat['comp_size'] ?? 1)) * 200) throw new RuntimeException('El XLSX excede los límites seguros de descompresión.');
            }
        } finally { $zip->close(); }
    }

    private function mapRow(array $headers, array $row): array
    {
        $data = array_fill_keys($headers, '');
        foreach ($headers as $index => $header) $data[$header] = trim((string) ($row[$index] ?? ''));
        return $data;
    }
    private function header(string $value): string { return trim(preg_replace('/[^a-z0-9]+/i', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($value, "\xEF\xBB\xBF \t\r\n")))) ?? '', '_'); }
    private function emptyRow(array $row): bool { return ! array_filter($row, fn ($value) => trim((string) $value) !== ''); }
    private function processDate(string $value): bool { if (! preg_match('/^\d{8}$/', $value)) return false; $date = DateTimeImmutable::createFromFormat('!Ymd', $value); $errors = DateTimeImmutable::getLastErrors(); return $date && ($errors === false || (! $errors['warning_count'] && ! $errors['error_count'])) && $date->format('Ymd') === $value; }
    private function error(?string $field, string $code, string $reason, ?string $value): array { return ['campo' => $field, 'codigo' => $code, 'motivo' => $reason, 'valor' => $value === null ? null : mb_substr($value, 0, 255)]; }
    private function recordError(Importacion $importacion, int $line, array $error): void
    {
        $this->errorBuffer[] = ['importacion_id' => $importacion->id, 'fila' => $line, 'campo' => $error['campo'], 'codigo' => $error['codigo'], 'motivo' => $error['motivo'], 'valor' => $error['valor'], 'creado_en' => now()];
        if (count($this->errorBuffer) >= self::BATCH_SIZE) $this->flushErrors();
    }

    private function flushErrors(): void
    {
        if (! $this->errorBuffer) return;
        ImportacionError::query()->insert($this->errorBuffer);
        $this->errorBuffer = [];
    }
}
