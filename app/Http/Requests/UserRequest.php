<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UserRequest extends FormRequest {
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }
    public function rules(): array {
        $update = $this->is('admin/actualizar');
        return ['id'=> $update ? 'required|integer|exists:usuarios,id':'prohibited','nombre'=>'required|string|max:255','usuario'=>['required','string','max:255',Rule::unique('usuarios','usuario')->ignore($update ? (int)$this->input('id') : null)],'rol'=>'required|in:administrador,asesor,supervisor','contrasena'=>[$update ? 'nullable':'required','string','min:12','max:128'],'activo'=>'required|boolean'];
    }
}
