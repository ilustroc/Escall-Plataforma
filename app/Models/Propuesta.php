<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Propuesta extends Model {
    protected $table = 'propuestas';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $hidden = ['archivo_blob','ruta_archivo'];
    protected function casts(): array { return ['cuentas_seleccionadas'=>'array','propuesta_cliente'=>'decimal:2']; }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class,'cliente_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class,'usuario_id'); }
}
