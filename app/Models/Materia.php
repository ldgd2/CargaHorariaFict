<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materia';
    protected $primaryKey = 'id_materia';
    public $timestamps = false;
    protected $fillable = [
        'cod_materia',
        'nombre',
        'creditos',
        'horas_semanales',
        'programa',
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_materia', 'id_materia');
    }
}
