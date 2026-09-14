<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ClienteSearchRequest extends FormRequest {
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['dni'=>'nullable|string|max:30','codigo'=>'nullable|string|max:100','titular'=>'nullable|string|max:255','cartera'=>'nullable|string|max:255','page'=>'nullable|integer|min:1']; }
}
