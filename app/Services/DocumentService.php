<?php
namespace App\Services;
use App\Models\{Cliente, Documento, Propuesta, User};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class DocumentService
{
    public const XLSX='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    public const DOCX='application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    public function __construct(private WordService $word, private ExcelService $excel) {}

    private function selected(Cliente $client, array $codes): array
    {
        $accounts=Cliente::where('dni',$client->dni)->whereIn('codigo',$codes)->get()->keyBy('codigo');
        if ($accounts->count()!==count($codes)) throw ValidationException::withMessages(['cuentas_seleccionadas'=>'Las cuentas deben pertenecer al cliente seleccionado.']);
        return array_map(fn($code)=>$accounts[$code]->toArray(),$codes);
    }

    public function word(array $data, User $user): Documento
    {
        $client=Cliente::findOrFail($data['cliente_id']);
        $accounts=$this->selected($client,$data['cuentas_seleccionadas']);
        $data['nombre']=$client->titular; $data['dni']=$client->dni;
        $data['deuda_total']=array_sum(array_column($accounts,'deuda_total'));
        $data['cuentas_seleccionadas']=json_encode($accounts,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $data['cuenta_banco']=config('platform.bank_accounts.'.$data['cuenta_banco'].'.value');
        if (!$data['cuenta_banco']) throw ValidationException::withMessages(['cuenta_banco'=>'Cuenta de depósito no configurada.']);
        $data['usar_cuotas']=!empty($data['usar_cuotas'])?'on':'';
        foreach (['fecha_solicitud','f_cuota','fecha_pago','fecha_cuota_1','fecha_cuota_2'] as $field) if (!empty($data[$field])) $data[$field]=\DateTimeImmutable::createFromFormat('!Y-m-d',$data[$field])->format('d/m/Y');
        if ($data['tipo_documento']==='convenio') $data['fecha_pago_hidden']=substr($data['f_cuota'],0,2).' de cada mes';
        do { $data['codigo_documento']=strtoupper(Str::random(8)); } while (Documento::where('codigo',$data['codigo_documento'])->exists());
        $file=$this->word->generate($data);
        return DB::transaction(fn()=>Documento::create(['codigo'=>$file['codigo'],'cliente_id'=>$file['cliente_id'],'usuario_id'=>$user->id,'nombre_archivo'=>$file['filename'],'tipo_mime'=>$file['mime'],'contenido'=>$file['bytes'],'creado_en'=>now()]));
    }

    public function proposal(array $data, User $user): Propuesta
    {
        $client=Cliente::findOrFail($data['cliente_id']);
        $accounts=$this->selected($client,$data['cuentas_seleccionadas']);
        $selected=[]; $total=0;
        foreach ($accounts as &$account) {
            $account['propuesta']=(float)$data['propuesta_por_cuenta'][$account['codigo']];
            $total+=$account['propuesta'];
            $selected[]=['codigo'=>$account['codigo'],'propuesta'=>$account['propuesta']];
        }
        unset($account);
        $observation=trim(($data['observacion']??'').(!empty($data['sustento'])?"\nSustento: ".$data['sustento']:''));
        $file=$this->excel->propuestaDesdePlantilla($client->toArray(),$accounts,['fecha_pago'=>$data['fecha_pago'],'observacion'=>$observation]);
        return DB::transaction(fn()=>Propuesta::create(['cliente_id'=>$client->id,'usuario_id'=>$user->id,'propuesta_cliente'=>round($total,2),'fecha_pago'=>$data['fecha_pago'],'observacion'=>$observation,'cuentas_seleccionadas'=>$selected,'archivo_blob'=>$file['bytes'],'nombre_archivo'=>$file['filename'],'tipo_archivo'=>$file['mime'],'creado_en'=>now()]));
    }

    public function proposalFile(Propuesta $proposal): array
    {
        if ($proposal->archivo_blob !== null && $proposal->archivo_blob !== '') return ['bytes'=>$this->bytes($proposal->archivo_blob),'filename'=>$proposal->nombre_archivo,'mime'=>$proposal->tipo_archivo];
        // Compatibility: a legacy row may predate blob generation. Build download in memory, without GET writes.
        if ($proposal->cliente) {
            $selected=$proposal->cuentas_seleccionadas ?? [];
            $accounts=$this->selected($proposal->cliente,array_column($selected,'codigo'));
            $amounts=array_column($selected,'propuesta','codigo');
            foreach ($accounts as &$account) $account['propuesta']=$amounts[$account['codigo']]??0;
            unset($account);
            return $this->excel->propuestaDesdePlantilla($proposal->cliente->toArray(),$accounts,['fecha_pago'=>$proposal->fecha_pago,'observacion'=>$proposal->observacion]);
        }
        abort(404,'Archivo no disponible.');
    }

    public function download(array $file)
    {
        $allowed=[self::DOCX=>'docx',self::XLSX=>'xlsx','application/pdf'=>'pdf'];
        abort_unless(isset($allowed[$file['mime']]),415);
        $bytes=$this->bytes($file['bytes']);
        if ($file['mime']==='application/pdf') abort_unless(str_starts_with($bytes,'%PDF-'),415);
        $filename=preg_replace('/[^A-Za-z0-9._-]+/','_',basename(str_replace('\\','/',(string)$file['filename']))) ?: 'documento.'.$allowed[$file['mime']];
        return response($bytes,200,['Content-Type'=>$file['mime'],'Content-Disposition'=>'attachment; filename="'.$filename.'"','Content-Length'=>(string)strlen($bytes),'Cache-Control'=>'private, no-store']);
    }
    private function bytes(mixed $bytes): string { return is_resource($bytes) ? (string)stream_get_contents($bytes) : (string)$bytes; }
}
