<?php
// app/Services/WordService.php
declare(strict_types=1);
namespace App\Services;
use RuntimeException;

use PhpOffice\PhpWord\TemplateProcessor;

class WordService
{
    private string $tplDir;
    private string $tmpDir;

    public function __construct(?string $templateDir = null, ?string $tempDir = null)
    {
        $this->tplDir = rtrim($templateDir ?? storage_path('app/templates'), '/\\') . '/';
        $this->tmpDir = $tempDir ?? storage_path('app/temp');
        if (!is_dir($this->tmpDir)) mkdir($this->tmpDir, 0700, true);
    }

    // --- helpers ---
    private function limpiarMonto($valor): float {
        if (empty($valor)) return 0.0;
        $valor = str_replace(['S/', 'S/ '], '', trim((string)$valor));
        if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
            $ultimaComa  = strrpos($valor, ',');
            $ultimoPunto = strrpos($valor, '.');
            if ($ultimoPunto > $ultimaComa) $valor = str_replace(',', '', $valor);
            else { $valor = str_replace('.', '', $valor); $valor = str_replace(',', '.', $valor); }
        } elseif (strpos($valor, ',') !== false) {
            $p = explode(',', $valor);
            $valor = (count($p) === 2 && strlen($p[1]) <= 2) ? str_replace(',', '.', $valor) : str_replace(',', '', $valor);
        }
        return (float)$valor;
    }
    private function soles($v): string {
        $n = $this->limpiarMonto($v);
        return $n == 0.0 ? '' : ('S/ ' . number_format($n, 2, '.', ','));
    }
    private function genCode(int $len=8): string {
        $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $out='';
        for($i=0;$i<$len;$i++) $out.=$chars[random_int(0,strlen($chars)-1)];
        return $out;
    }
    private function safeName(string $s): string {
        $s = str_replace(' ', '_', $s);
        $s = preg_replace('/[^A-Za-z0-9._-]+/','_', $s);
        return $s === '' ? 'usuario' : $s;
    }
    private function pickTemplate(string $tipo, int $n, bool $usarCuotas): string {
        if ($tipo === 'cancelacion') {
            if ($usarCuotas) {
                $map = [1=>'plantilla2cuota.docx',2=>'plantilla3cuota.docx',3=>'plantilla4cuota.docx',4=>'plantilla5cuota.docx'];
                return $map[$n] ?? 'plantilla6cuota.docx';
            } else {
                $map = [1=>'plantilla2.docx',2=>'plantilla22.docx',3=>'plantilla33.docx',4=>'plantilla44.docx'];
                return $map[$n] ?? 'plantilla55.docx';
            }
        }
        $map = [1=>'plantilla.docx',2=>'plantilla2c.docx',3=>'plantilla3c.docx',4=>'plantilla4c.docx'];
        return $map[$n] ?? 'plantilla5c.docx';
    }

    /**
     * Genera el DOCX en memoria y devuelve metadatos.
     * @return array{bytes:string,mime:string,filename:string,numeroCuentas:int,codigo:string,cliente_id:int}
     */
    public function generate(array $data): array
    {
        $tipo = $data['tipo_documento'] ?? 'convenio';
        $cuentas = json_decode($data['cuentas_seleccionadas'] ?? '[]', true) ?: [];
        $n = count($cuentas);
        if ($n < 1 || $n > 5) throw new RuntimeException('No se han seleccionado cuentas.');

        $usarCuotas = !empty($data['usar_cuotas']) && $data['usar_cuotas'] === 'on';
        $tplFile = $this->pickTemplate($tipo, $n, $usarCuotas);
        $tplPath = $this->tplDir . $tplFile;
        if (!is_file($tplPath)) throw new RuntimeException("Plantilla no encontrada: {$tplFile}");

        $codigo = $data['codigo_documento'] ?? $this->genCode(8);
        $t = new TemplateProcessor($tplPath);

        // comunes
        $nombre  = (string)($data['nombre'] ?? '');
        $dni     = (string)($data['dni'] ?? '');
        $deuda   = (string)($data['deuda_total'] ?? '0');
        $tipoFac = (string)($data['tipo_facilidad'] ?? '');
        $fsol    = (string)($data['fecha_solicitud'] ?? '');

        $t->setValue('nombre', htmlspecialchars($nombre));
        $t->setValue('dni', htmlspecialchars($dni));
        $t->setValue('codigo', htmlspecialchars((string)($cuentas[0]['codigo'] ?? '')));
        $t->setValue('deuda_total', $this->soles($deuda));
        $t->setValue('producto', htmlspecialchars((string)($cuentas[0]['producto'] ?? '')));
        $t->setValue('tipo_facilidad', htmlspecialchars($tipoFac));
        $t->setValue('fecha_solicitud', htmlspecialchars($fsol));
        $t->setValue('codigo_documento', htmlspecialchars($codigo));

        // multicuentas 2..5
        for ($i=2; $i<=5; $i++) {
            if (isset($cuentas[$i-1])) {
                $t->setValue("codigo{$i}",   htmlspecialchars((string)$cuentas[$i-1]['codigo']));
                $t->setValue("producto{$i}", htmlspecialchars((string)$cuentas[$i-1]['producto']));
            }
        }

        if ($tipo === 'cancelacion') {
            $t->setValue('cancelacion', $this->soles($data['cancelacion'] ?? ''));
            $t->setValue('fecha_pago', htmlspecialchars((string)($data['fecha_pago'] ?? '')));
            $t->setValue('cuenta_banco', htmlspecialchars((string)($data['cuenta_banco'] ?? '')));

            if ($usarCuotas) {
                $t->setValue('cuota1',       $this->soles($data['cuota_1'] ?? ''));
                $t->setValue('fecha_cuota1', htmlspecialchars((string)($data['fecha_cuota_1'] ?? '')));
                $t->setValue('cuota2',       $this->soles($data['cuota_2'] ?? ''));
                $t->setValue('fecha_cuota2', htmlspecialchars((string)($data['fecha_cuota_2'] ?? '')));
            }
        } else {
            $t->setValue('f_cuota',        htmlspecialchars((string)($data['f_cuota'] ?? '')));
            $t->setValue('primera_cuota',  $this->soles($data['primera_cuota'] ?? ''));
            $t->setValue('cuota_inicial',  $this->soles($data['cuota_inicial'] ?? ''));
            $t->setValue('monto_convenio', $this->soles($data['monto_convenio'] ?? ''));
            $t->setValue('numero_cuotas',  htmlspecialchars((string)($data['numero_cuotas'] ?? '')));
            $t->setValue('cuenta_banco',   htmlspecialchars((string)($data['cuenta_banco'] ?? '')));
            $t->setValue('fecha_pago',     htmlspecialchars((string)($data['fecha_pago_hidden'] ?? ($data['fecha_pago'] ?? ''))));
        }

        // generar en tmp y devolver bytes
        $tmp = $this->tmpDir . '/tmp_' . bin2hex(random_bytes(6)) . '.docx';
        try {
            $t->saveAs($tmp);
            $bytes = file_get_contents($tmp);
            if ($bytes === false) throw new RuntimeException('No se pudo generar el documento.');
        } finally {
            if (is_file($tmp)) unlink($tmp);
        }

        $pref = ($tipo === 'cancelacion') ? 'CC_' : 'CP_';
        $filename = $pref . $this->safeName($nombre) . '.docx';
        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        return [
            'bytes'          => $bytes,
            'mime'           => $mime,
            'filename'       => $filename,
            'numeroCuentas'  => $n,
            'codigo'         => $codigo,
            'cliente_id'     => (int)($cuentas[0]['id'] ?? 0),
        ];
    }
}
