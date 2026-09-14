<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
    protected $table = 'usuarios';
    public $timestamps = false;
    protected $fillable = ['nombre','usuario','contrasena','rol','activo'];
    protected $hidden = ['contrasena','remember_token'];
    protected function casts(): array { return ['activo'=>'integer']; }
    public function getAuthPasswordName(): string { return 'contrasena'; }
    public function getRememberTokenName(): string { return ''; }
    public function isAdmin(): bool { return $this->rol === 'administrador'; }
    public function isActive(): bool { return $this->activo === null || (int)$this->activo === 1; }
}