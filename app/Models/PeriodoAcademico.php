<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    protected $table = 'periodo_academico';
    protected $primaryKey = 'id_periodo';
    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'estado_publicacion',
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_periodo', 'id_periodo');
    }
}
