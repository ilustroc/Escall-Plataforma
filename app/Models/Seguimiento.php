<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Seguimiento extends Model {
    protected $table = 'seguimientos';
    protected $guarded = ['id'];
    public function cliente(): HasOne { return $this->hasOne(Cliente::class,'codigo','codigo')->ofMany(['FEC_PRO' => 'max', 'id' => 'max']); }
}
