<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PropuestaRequest extends FormRequest {
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['cliente_id'=>'required|integer|exists:Cartera_Mensual,id','fecha_pago'=>'required|date_format:Y-m-d','observacion'=>'nullable|string|max:10000','sustento'=>'nullable|string|max:10000','cuentas_seleccionadas'=>'required|array|min:1|max:5','cuentas_seleccionadas.*'=>'required|string|max:100|distinct','propuesta_por_cuenta'=>'required|array|max:5','propuesta_por_cuenta.*'=>'required|numeric|min:0.01|max:999999999999']; }
    public function after(): array { return [function ($validator) { foreach ($this->input('cuentas_seleccionadas',[]) as $codigo) if (!array_key_exists($codigo, $this->input('propuesta_por_cuenta',[]))) $validator->errors()->add('propuesta_por_cuenta','Indica el importe de cada cuenta seleccionada.'); }]; }
}
