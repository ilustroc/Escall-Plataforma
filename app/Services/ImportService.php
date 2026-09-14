<?php
namespace App\Services;

use App\Models\{Cliente, Pago, Seguimiento, SeguimientoEvolutivo, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use InvalidArgumentException;

class ImportService
{
    public const HEADERS = [
        'data'=>['cartera','dni','titular','codigo','entidad','cosecha','producto','sub_producto','historico','departamento','rango','deuda_total','deuda_capital','campana'],
        'pagos'=>['codigo','fecha','monto','asesor','operacion','observacion'],
        'seguimientos'=>['codigo','fecha','hora','tipo','descripcion','estado'],
        'evolutivo'=>['dni','tipo_acuerdo','fecha','monto','estado','gestion'],
    ];

    public function import(UploadedFile $file, string $type, User $user, string $mode = 'merge'): array
    {
        abort_unless(isset(self::HEADERS[$type]), 422);
        abort_if(in_array($type,['data','pagos'],true) && !$user->isAdmin(),403);
        try { $sheet = SpreadsheetUpload::load($file); }
        catch (InvalidArgumentException $e) { throw ValidationException::withMessages(['file'=>$e->getMessage()]); }
        catch (\Throwable $e) { report($e); throw ValidationException::withMessages(['file'=>'No se pudo interpretar el archivo.']); }
        try {
            $map = [];
            $aliases = ['documento'=>'dni','fechapago'=>'fecha','fecha_pago'=>'fecha','montopago'=>'monto','monto_pago'=>'monto','tipo_de_acuerdo'=>'tipo_acuerdo','tipodeacuerdo'=>'tipo_acuerdo'];
            for ($column=1; $column<=Coordinate::columnIndexFromString($sheet->getHighestColumn()); $column++) {
                $raw = (string)$sheet->getCell(Coordinate::stringFromColumnIndex($column).'1')->getValue();
                $key = trim(preg_replace('/[^a-z0-9]+/i','_',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',mb_strtolower(trim($raw, "\xEF\xBB\xBF \t\r\n")))),'_');
                $key = $aliases[$key] ?? $key;
                if (isset($map[$key])) throw ValidationException::withMessages(['file'=>'El encabezado contiene columnas duplicadas.']);
                $map[$key] = $column;
            }
            if (array_diff(self::HEADERS[$type],array_keys($map))) throw ValidationException::withMessages(['file'=>'Encabezados inválidos o incompletos.']);
            $buffer=[]; $skipped=0;
            for ($row=2; $row<=$sheet->getHighestRow(); $row++) {
                $data=[];
                foreach (self::HEADERS[$type] as $key) $data[$key] = trim((string)$sheet->getCell(Coordinate::stringFromColumnIndex($map[$key]).$row)->getValue());
                if (!array_filter($data,fn($value)=>$value!=='')) { $skipped++; continue; }
                foreach (['deuda_total','deuda_capital','monto'] as $field) if (array_key_exists($field,$data)) $data[$field] = self::number($data[$field]);
                if (isset($data['fecha'])) $data['fecha'] = self::date($data['fecha']);
                if ($type==='seguimientos') {
                    $data['tipo'] = $data['tipo'] ?: 'seguimiento';
                    if (is_numeric($data['hora']) && (float)$data['hora']>=0 && (float)$data['hora']<1) $data['hora'] = gmdate('H:i:s',(int)round((float)$data['hora']*86400));
                    if (preg_match('/^\d{2}:\d{2}$/',$data['hora'])) $data['hora'].=':00';
                    $data['hora'] = $data['hora'] ?: null;
                }
                if ($type==='evolutivo') $data['gestion'] = $data['gestion'] ?: (mb_strtoupper($data['estado'])==='CUMPLIDO'?'Realizado':'Pendiente');
                $rules=[];
                foreach (self::HEADERS[$type] as $key) $rules[$key] = 'nullable|string|max:255';
                foreach (['codigo','dni'] as $key) if (array_key_exists($key,$data)) $rules[$key] = 'required|string|max:'.($key==='dni'?30:100);
                foreach (['deuda_total','deuda_capital','monto'] as $key) if (array_key_exists($key,$data)) $rules[$key] = ($type==='pagos'?'required':'nullable').'|numeric|min:0|max:9999999999999999';
                if (isset($rules['fecha'])) $rules['fecha']='required|date_format:Y-m-d';
                if (isset($rules['tipo_acuerdo'])) $rules['tipo_acuerdo']='required|string|max:100';
                if (isset($rules['hora'])) $rules['hora']='nullable|date_format:H:i:s';
                if (isset($rules['tipo'])) $rules['tipo']='required|in:seguimiento,recordatorio';
                foreach (['descripcion','observacion'] as $key) if (isset($rules[$key])) $rules[$key]='nullable|string|max:5000';
                $validation=Validator::make($data,$rules);
                if ($validation->fails()) throw ValidationException::withMessages(['file'=>'Fila '.$row.': '.$validation->errors()->first()]);
                $buffer[]=$data;
            }
            if (!$buffer) throw ValidationException::withMessages(['file'=>'El archivo no contiene filas válidas.']);
            return DB::transaction(function () use ($buffer,$type,$user,$mode,$skipped) {
                $counts=['inserted'=>0,'updated'=>0,'skipped'=>$skipped];
                if ($type==='evolutivo' && $mode==='replace') {
                    SeguimientoEvolutivo::query()->whereIn('fecha',array_unique(array_column($buffer,'fecha')))->when(!$user->isAdmin(),fn($q)=>$q->where('usuario_id',$user->id))->delete();
                }
                foreach ($buffer as $data) {
                    if ($type==='seguimientos') { Seguimiento::create($data+['usuario_id'=>$user->id]); $counts['inserted']++; continue; }
                    [$class,$key] = match($type) {
                        'data'=>[Cliente::class,['codigo'=>$data['codigo']]],
                        'pagos'=>[Pago::class,array_intersect_key($data,array_flip(['codigo','fecha','monto','operacion']))],
                        'evolutivo'=>[SeguimientoEvolutivo::class,array_intersect_key($data,array_flip(['dni','tipo_acuerdo','fecha']))+['usuario_id'=>$user->id]],
                    };
                    $model=$class::firstOrNew($key);
                    $existed=$model->exists;
                    if ($type==='pagos' && $existed) $data=array_intersect_key($data,array_flip(['asesor','observacion']));
                    elseif ($type!=='data') $data['usuario_id']=$user->id;
                    $model->fill($data);
                    if (!$existed) $counts['inserted']++; elseif ($model->isDirty()) $counts['updated']++; else $counts['skipped']++;
                    $model->save();
                }
                return $counts;
            });
        } finally { $sheet->getParent()?->disconnectWorksheets(); }
    }

    public static function number(mixed $value): mixed
    {
        $value=trim((string)$value); if ($value==='') return null;
        $value=str_replace(['S/',' ',"\u{00A0}"],'',$value);
        if (str_contains($value,',') && str_contains($value,'.')) {
            $decimal=strrpos($value,',')>strrpos($value,'.')?',':'.';
            $value=str_replace($decimal===','?'.':',','',$value);
            $value=str_replace(',','.',$value);
        } elseif (str_contains($value,',')) {
            $parts=explode(',',$value);
            $value=count($parts)===2 && strlen($parts[1])<=2 ? implode('.',$parts) : implode('',$parts);
        }
        return is_numeric($value) ? $value : 'INVALID_NUMBER';
    }

    public static function date(mixed $value): ?string
    {
        if (is_numeric($value)) { try { return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d'); } catch (\Throwable) { return null; } }
        foreach (['!Y-m-d','!d/m/Y','!d-m-Y','!d.m.Y'] as $format) {
            $date=\DateTimeImmutable::createFromFormat($format,trim((string)$value));
            $errors=\DateTimeImmutable::getLastErrors();
            if ($date && ($errors===false || (!$errors['warning_count'] && !$errors['error_count']))) return $date->format('Y-m-d');
        }
        return null;
    }

    public function template(string $type): array
    {
        abort_unless(isset(self::HEADERS[$type]),422);
        $book=new Spreadsheet(); $sheet=$book->getActiveSheet()->setTitle('Plantilla');
        $sheet->fromArray(array_map('strtoupper',self::HEADERS[$type]));
        foreach (self::HEADERS[$type] as $index=>$header) {
            $letter=Coordinate::stringFromColumnIndex($index+1);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            if ($header==='fecha') $sheet->getStyle($letter.'2:'.$letter.'999')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }
        ob_start();
        try { (new Xlsx($book))->save('php://output'); $bytes=(string)ob_get_contents(); }
        finally { ob_end_clean(); $book->disconnectWorksheets(); }
        return ['bytes'=>$bytes,'mime'=>DocumentService::XLSX,'filename'=>'plantilla_'.$type.'.xlsx'];
    }
}
