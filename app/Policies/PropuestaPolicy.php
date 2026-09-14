<?php
namespace App\Policies;
use App\Models\{Propuesta, User};
class PropuestaPolicy {
    public function view(User $user, Propuesta $propuesta): bool { return $user->isAdmin() || $user->id === (int)$propuesta->usuario_id; }
    public function delete(User $user, Propuesta $propuesta): bool { return $this->view($user,$propuesta); }
}
