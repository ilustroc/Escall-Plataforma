<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('usuario'))) $this->merge(['usuario'=>mb_strtolower(trim($this->input('usuario')), 'UTF-8')]);
    }
    public function rules(): array { return ['usuario'=>['required','string','max:190'],'contrasena'=>['required','string','max:72']]; }
    public function messages(): array { return ['*'=>\App\Services\LoginService::ERROR]; }
}