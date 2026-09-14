<?php
declare(strict_types=1);
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use finfo;
use ZipArchive;
use XMLReader;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class SpreadsheetUpload
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_UNPACKED_BYTES = 50 * 1024 * 1024;
    public const MAX_ROWS = 10000;
    public const MAX_COLUMNS = 64;
    public const MAX_CELLS = 200000;

    public static function load(mixed $file): Worksheet
    {
        if (!$file instanceof UploadedFile || !$file->isValid() || !is_file($file->getRealPath())) self::invalid();
        $size = filesize($file->getRealPath());
        if (!$size) self::invalid();
        if ($size > self::MAX_BYTES || $file->getSize() > self::MAX_BYTES) self::limit();
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx','xls','csv'], true)) self::invalid();
        $stored = $file->storeAs('imports', bin2hex(random_bytes(16)).'.'.$ext, 'local');
        if (!$stored) self::invalid();
        $tmp = Storage::disk('local')->path($stored);
        try {
            return self::loadStored($tmp, $ext);
        } finally { Storage::disk('local')->delete($stored); }
    }

    private static function loadStored(string $tmp, string $ext): Worksheet
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $allowed = [
            'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2', 'application/x-cdf'],
            // libmagic puede devolver octet-stream para CSV grandes: se exige texto UTF-8 sin controles binarios abajo.
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/octet-stream'],
        ];
        if (!isset($allowed[$ext]) || !in_array($mime, $allowed[$ext], true)) self::invalid();

        if ($ext === 'xlsx') self::inspectXlsx($tmp);
        if ($ext === 'xls') self::inspectXls($tmp);
        if ($ext === 'csv') {
            $delimiter = self::inspectCsv($tmp);
            $reader = new Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter($delimiter);
            $reader->setEnclosure('"');
            $reader->setSheetIndex(0);
        } else {
            $reader = $ext === 'xlsx' ? new Xlsx() : new Xls();
            if (!$reader->canRead($tmp)) self::invalid();
        }
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $info = $reader->listWorksheetInfo($tmp);
        if (!$info || count($info) > 20) self::limit();
        $cells = 0;
        foreach ($info as $sheet) {
            $rows = (int)$sheet['totalRows'];
            $cols = (int)$sheet['totalColumns'];
            $cells += $rows * $cols;
            if ($rows > self::MAX_ROWS || $cols > self::MAX_COLUMNS || $cells > self::MAX_CELLS) self::limit();
        }
        $book = $reader->load($tmp);
        if ($book->hasMacros()) self::invalid();
        foreach ($book->getWorksheetIterator() as $sheet) {
            if ($sheet->getHighestRow() > self::MAX_ROWS
                || Coordinate::columnIndexFromString($sheet->getHighestColumn()) > self::MAX_COLUMNS) self::limit();
            foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                if ($sheet->getCell($coordinate)->getDataType() === DataType::TYPE_FORMULA) {
                    throw new InvalidArgumentException('Importa valores, sin fórmulas.', 422);
                }
            }
        }
        return $book->getActiveSheet();
    }

    private static function inspectCsv(string $path): string
    {
        $text = file_get_contents($path);
        if ($text === false || !mb_check_encoding($text, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $text)) self::invalid();
        $first = strtok($text, "\r\n") ?: '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $handle = fopen($path, 'rb');
        if (!$handle) self::invalid();
        $rows = 0; $cells = 0;
        try {
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $rows++; $cells += count($row);
                if ($rows > self::MAX_ROWS || count($row) > self::MAX_COLUMNS || $cells > self::MAX_CELLS) self::limit();
            }
        } finally { fclose($handle); }
        return $delimiter;
    }

    private static function inspectXls(string $path): void
    {
        $bytes = file_get_contents($path);
        if (!is_string($bytes) || strlen($bytes) < 512 || substr($bytes, 0, 8) !== hex2bin('d0cf11e0a1b11ae1')) self::invalid();
        // El lector BIFF de esta rama utiliza sectores de 512 bytes.
        if (unpack('v', substr($bytes, 30, 2))[1] !== 9) self::invalid();
        $sectors = intdiv(strlen($bytes), 512);
        foreach ([44, 72] as $offset) {
            if (unpack('V', substr($bytes, $offset, 4))[1] > $sectors) self::limit();
        }
        // Entradas de directorio OLE, alineadas a 128 bytes: no aceptar VBA ni objetos incrustados.
        for ($offset = 512; $offset + 128 <= strlen($bytes); $offset += 128) {
            $length = unpack('v', substr($bytes, $offset + 64, 2))[1];
            $type = ord($bytes[$offset + 66]);
            if ($length < 2 || $length > 64 || $length % 2 || !in_array($type, [1,2], true)) continue;
            $name = strtoupper(mb_convert_encoding(substr($bytes, $offset, $length - 2), 'UTF-8', 'UTF-16LE'));
            if (preg_match('/VBA|^PROJECT$|^OBJECTPOOL$|^MBD/', $name)) self::invalid();
        }
        $ole = new \PhpOffice\PhpSpreadsheet\Shared\OLERead();
        $ole->read($path);
        if ($ole->wrkbook === null) self::invalid();
        $stream = $ole->getStream($ole->wrkbook);
        if (!is_string($stream)) self::invalid();
        $records = 0;
        for ($offset = 0; $offset + 4 <= strlen($stream);) {
            $head = unpack('vtype/vlength', substr($stream, $offset, 4));
            $offset += 4;
            if (++$records > 500000) self::limit();
            if ($offset + $head['length'] > strlen($stream)) self::invalid();
            // FORMULA y FILEPASS (archivos cifrados).
            if (in_array($head['type'], [0x0006, 0x002F], true)) self::invalid();
            // SUPBOOK: únicamente referencias internas; rechaza libros externos y add-ins.
            if ($head['type'] === 0x01AE && ($head['length'] !== 4 || substr($stream, $offset + 2, 2) !== "\x01\x04")) self::invalid();
            $offset += $head['length'];
        }
    }

    private static function inspectXlsx(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) self::invalid();
        try {
            if ($zip->numFiles > 1000) self::limit();
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('xl/workbook.xml') === false) self::invalid();
            $total = 0; $elements = 0; $cells = 0; $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) self::invalid();
                $name = strtolower($stat['name']);
                if (isset($names[$name]) || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/')) self::invalid();
                $names[$name] = true;
                if (preg_match('~vbaproject|xl/(externallinks|embeddings|activex)/~', $name)) self::invalid();
                if (!empty($stat['encryption_method'])) self::invalid();
                $total += $stat['size'];
                if ($total > self::MAX_UNPACKED_BYTES || $stat['size'] > self::MAX_BYTES
                    || $stat['size'] > max(1, $stat['comp_size']) * 200) self::limit();
                if (!str_ends_with($name, '.xml') && !str_ends_with($name, '.rels')) continue;
                $xml = $zip->getFromIndex($i);
                if (!is_string($xml) || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) self::invalid();
                $r = new XMLReader();
                $previous = libxml_use_internal_errors(true);
                try {
                    if (!$r->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT)) self::invalid();
                    while ($r->read()) {
                        if ($r->nodeType === XMLReader::DOC_TYPE) self::invalid();
                        if ($r->nodeType !== XMLReader::ELEMENT) continue;
                        if (++$elements > 500000) self::limit();
                        if (strtolower((string)$r->getAttribute('TargetMode')) === 'external') self::invalid();
                        if ($r->localName === 'Override' && str_contains(strtolower((string)$r->getAttribute('ContentType')), 'macroenabled')) self::invalid();
                        if (str_starts_with($name, 'xl/worksheets/')) {
                            if ($r->localName === 'f') throw new InvalidArgumentException('Importa valores, sin fórmulas.', 422);
                            if ($r->localName === 'row' && (int)$r->getAttribute('r') > self::MAX_ROWS) self::limit();
                            if ($r->localName === 'col' && (int)$r->getAttribute('max') > self::MAX_COLUMNS) self::limit();
                            if ($r->localName === 'c' && ++$cells > self::MAX_CELLS) self::limit();
                            if (in_array($r->localName, ['c', 'dimension'], true)) {
                                $ref = (string)$r->getAttribute($r->localName === 'c' ? 'r' : 'ref');
                                preg_match_all('/([A-Z]+)([0-9]+)/i', $ref, $matches, PREG_SET_ORDER);
                                foreach ($matches as $match) {
                                    if (strlen($match[1]) > 3 || Coordinate::columnIndexFromString(strtoupper($match[1])) > self::MAX_COLUMNS
                                        || (float)$match[2] > self::MAX_ROWS) self::limit();
                                }
                            }
                        }
                    }
                    if (libxml_get_errors()) self::invalid();
                } finally {
                    $r->close(); libxml_clear_errors(); libxml_use_internal_errors($previous);
                }
            }
        } finally { $zip->close(); }
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Archivo inválido. Usa XLSX, XLS o CSV UTF-8, sin macros ni enlaces externos.', 422);
    }

    private static function limit(): never
    {
        throw new InvalidArgumentException('El archivo supera los límites: 10 MiB, 10000 filas, 64 columnas o 200000 celdas.', 413);
    }
}
