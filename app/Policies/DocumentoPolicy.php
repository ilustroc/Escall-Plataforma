<?php
namespace App\Policies;
use App\Models\{Documento, User};
class DocumentoPolicy {
    public function view(User $user, Documento $documento): bool { return $user->isAdmin() || $user->id === (int)$documento->usuario_id; }
    public function delete(User $user, Documento $documento): bool { return $this->view($user,$documento); }
}
