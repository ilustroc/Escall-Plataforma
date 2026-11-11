<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Cliente extends Model {
    use HasFactory;
    protected $table = 'Cartera_Mensual';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $appends = ['dni', 'titular'];

    protected function casts(): array { return ['deuda_total'=>'decimal:2','deuda_capital'=>'decimal:2','fecha_carga'=>'datetime']; }

    // Transitional aliases keep existing Word, Excel and Vue screens compatible while
    // all cartera reads and writes use documento/nombre.
    public function getDniAttribute(): ?string { return $this->attributes['documento'] ?? null; }
    public function getTitularAttribute(): ?string { return $this->attributes['nombre'] ?? null; }
}
