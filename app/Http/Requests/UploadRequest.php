<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UploadRequest extends FormRequest {
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['file'=>'required|file|max:10240|extensions:xlsx,xls,csv','modo'=>'nullable|in:merge,replace','return'=>'nullable|in:/cargas,/seguimientos']; }
}
