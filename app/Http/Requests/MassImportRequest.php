<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassImportRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:51200', 'extensions:csv,xlsx']];
    }
}
