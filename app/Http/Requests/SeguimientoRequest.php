<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SeguimientoRequest extends FormRequest {
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['codigo'=>'required|string|max:100|exists:cartera_clientes,codigo','fecha'=>'required|date_format:Y-m-d','hora'=>'required|date_format:H:i','tipo'=>'required|in:seguimiento,recordatorio','descripcion'=>'nullable|string|max:5000','estado'=>'nullable|string|max:255']; }
}
