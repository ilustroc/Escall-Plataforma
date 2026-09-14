<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Documento extends Model {
    protected $table = 'documentos';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $hidden = ['contenido'];
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class,'cliente_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class,'usuario_id'); }
}
