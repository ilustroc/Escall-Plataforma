<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SeguimientoEvolutivo extends Model {
    protected $table = 'seguimientos_evolutivo';
    protected $guarded = ['id'];
    protected function casts(): array { return ['monto'=>'decimal:2']; }
}
