<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Seguimiento extends Model {
    protected $table = 'seguimientos';
    protected $guarded = ['id'];
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class,'codigo','codigo'); }
}
