<?php
namespace App\Policies;
use App\Models\{Seguimiento, User};
class SeguimientoPolicy {
    public function delete(User $user, Seguimiento $seguimiento): bool { return $user->isAdmin() || $user->id === (int)$seguimiento->usuario_id; }
}
