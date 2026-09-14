<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Validation\ValidationException;
class UserService {
    public function save(array $data, User $actor, ?User $target=null): User {
        return DB::transaction(function() use ($data,$actor,$target) {
            if ($target && $target->id===$actor->id && (!$data['activo'] || $data['rol']!=='administrador')) throw ValidationException::withMessages(['rol'=>'No puedes desactivar ni quitar tu propio acceso administrativo.']);
            if (!empty($data['contrasena'])) $data['contrasena']=Hash::make($data['contrasena']); else unset($data['contrasena']);
            unset($data['id']);
            $target??=new User(); $target->fill($data); $target->save(); return $target;
        });
    }
    public function toggle(User $target, User $actor): void {
        if ($target->id===$actor->id) throw ValidationException::withMessages(['id'=>'No puedes desactivarte.']);
        $target->activo=!($target->activo??true); $target->save();
    }
    public function delete(User $target, User $actor): void {
        if ($target->id===$actor->id) throw ValidationException::withMessages(['id'=>'No puedes eliminarte.']);
        $target->delete();
    }
}
