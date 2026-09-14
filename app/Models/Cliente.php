<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Cliente extends Model {
    use HasFactory;
    protected $table = 'cartera_clientes';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['deuda_total'=>'decimal:2','deuda_capital'=>'decimal:2']; }
}
