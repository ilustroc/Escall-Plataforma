<?php
declare(strict_types=1);
namespace App\Services;
use RuntimeException;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelService
{
    private string $templatesDir;

    public function __construct(?string $templatesDir = null)
    {
        // resources/templates
        $this->templatesDir = $templatesDir ?: storage_path('app/templates');
    }

    /**
     * Genera el Excel de propuesta usando tus plantillas plantillapropuesta{1..5}.xlsx
     * @return array{mime:string, filename:string, bytes:string, num_cuentas:int}
     */
    public function propuestaDesdePlantilla(array $cliente, array $cuentas, array $meta): array
    {
        // Escoge plantilla por cantidad (cap a 5)
        if (count($cuentas) < 1 || count($cuentas) > 5) throw new RuntimeException('Selecciona entre 1 y 5 cuentas.'); $n = count($cuentas);
        $tpl = $this->templatesDir . "/plantillapropuesta{$n}.xlsx";
        if (!is_file($tpl)) {
            throw new RuntimeException("No se encontró la plantilla: {$tpl}");
        }

        $spreadsheet = (new XlsxReader())->load($tpl);
        $sheet = $spreadsheet->getActiveSheet();

        // Tus plantillas comienzan a llenar desde la fila 6
        $startRow = 6;

        foreach ($cuentas as $i => $c) {
            $r = $startRow + $i;

            // La propuesta puede venir como 'propuesta' o 'propuesta_individual'
            $prop = (float)($c['propuesta'] ?? $c['propuesta_individual'] ?? 0);

            // Mapeo de columnas según tu script original
            $sheet->setCellValueExplicit("B{$r}", (string)($c['codigo'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$r}", (string)($c['cosecha'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$r}", (string)($cliente['titular'] ?? $c['titular'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$r}", (float)($c['deuda_capital'] ?? 0));
            $sheet->setCellValue("F{$r}", (float)($c['deuda_total'] ?? 0));
            $sheet->setCellValue("G{$r}", $prop);
            $sheet->setCellValueExplicit("K{$r}", (string)($meta['fecha_pago'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("L{$r}", (string)($meta['observacion'] ?? ''), DataType::TYPE_STRING);
        }

        // Salida a memoria
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $bytes = (string)ob_get_clean();

        $filename = 'Propuesta_' . preg_replace('/\W+/', '_', (string)($cliente['titular'] ?? 'cliente')) . '.xlsx';


        return [
            'mime'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'filename'    => $filename,
            'bytes'       => $bytes,
            'num_cuentas' => count($cuentas),
        ];
    }
}
