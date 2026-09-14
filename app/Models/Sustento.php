<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sustento extends Model {
    protected $table = 'sustentos';
    public $timestamps = false;
    protected $guarded = ['id'];
}
