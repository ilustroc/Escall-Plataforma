<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Pago extends Model {
    protected $table = 'pagos';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['monto'=>'decimal:2']; }
}
