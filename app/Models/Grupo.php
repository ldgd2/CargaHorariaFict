<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupo';
    protected $primaryKey = 'id_grupo';
    public $timestamps = false;
    protected $fillable = [
        'id_periodo',
        'id_materia',
        'id_carrera',
        'nombre_grupo',
        'capacidad_estudiantes',
        'estado',
    ];

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'id_periodo', 'id_periodo');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    // Si ya tienes el modelo Carrera:
    public function carrera()
    {
        return $this->belongsTo(\App\Models\Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function scopePorPeriodoMateriaCarrera($query, int $periodoId, int $materiaId, int $carreraId)
    {
        return $query->where('id_periodo', $periodoId)
                     ->where('id_materia', $materiaId)
                     ->where('id_carrera', $carreraId);
    }
}
