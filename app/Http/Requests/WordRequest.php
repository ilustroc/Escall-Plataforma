<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class WordRequest extends FormRequest {
    public function authorize(): bool { return $this->user() !== null; }
    protected function prepareForValidation(): void {
        $accounts = $this->input('cuentas_seleccionadas');
        if (is_string($accounts)) $accounts = json_decode($accounts,true);
        if (is_array($accounts)) $this->merge(['cuentas_seleccionadas'=>array_map(fn($c)=>is_array($c)?($c['codigo']??null):(string)$c,$accounts)]);
        $this->merge(['usar_cuotas'=>$this->boolean('usar_cuotas')]);
    }
    public function rules(): array {
        $rules = ['cliente_id'=>'required|integer|exists:cartera_clientes,id','tipo_documento'=>'required|in:convenio,cancelacion','cuentas_seleccionadas'=>'required|array|min:1|max:5','cuentas_seleccionadas.*'=>'required|string|max:100|distinct','tipo_facilidad'=>'required|string|max:255','fecha_solicitud'=>'required|date_format:Y-m-d','cuenta_banco'=>['required','string',Rule::in(array_keys(config('platform.bank_accounts',[])))],'usar_cuotas'=>'boolean','numero_cuotas'=>'nullable|required_if:tipo_documento,convenio|integer|min:1|max:600'];
        foreach (['cuota_inicial','monto_convenio','primera_cuota','cancelacion','cuota_1','cuota_2'] as $field) $rules[$field] = 'nullable|numeric|min:0|max:999999999999';
        foreach (['f_cuota','fecha_pago','fecha_cuota_1','fecha_cuota_2'] as $field) $rules[$field] = 'nullable|date_format:Y-m-d';
        foreach (['monto_convenio','primera_cuota','f_cuota'] as $field) $rules[$field] .= '|required_if:tipo_documento,convenio';
        foreach (['cancelacion','fecha_pago'] as $field) $rules[$field] .= '|required_if:tipo_documento,cancelacion';
        foreach (['cuota_1','cuota_2','fecha_cuota_1','fecha_cuota_2'] as $field) $rules[$field] .= '|required_if:usar_cuotas,1';
        return $rules;
    }
    public function after(): array { return [function ($validator) {
        if ($this->boolean('usar_cuotas') && ($this->input('tipo_documento') !== 'cancelacion' || (float)$this->input('cuota_1') <= 0 || (float)$this->input('cuota_2') <= 0 || abs((float)$this->input('cuota_1') + (float)$this->input('cuota_2') - (float)$this->input('cancelacion')) > 0.01)) $validator->errors()->add('cuota_2','Las cuotas deben ser positivas y sumar la cancelación.');
        if (!config('platform.bank_accounts.'.$this->input('cuenta_banco').'.value')) $validator->errors()->add('cuenta_banco','La cuenta de depósito no está configurada.');
    }]; }
}
