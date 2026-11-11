<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Importacion extends Model
{
    protected $table = 'importaciones';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['iniciada_en' => 'datetime', 'finalizada_en' => 'datetime'];
    }

    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'usuario_id'); }
    public function errores(): HasMany { return $this->hasMany(ImportacionError::class, 'importacion_id'); }
    public function canBeViewedBy(User $user): bool { return $user->isAdmin() || $user->id === (int) $this->usuario_id; }
}
