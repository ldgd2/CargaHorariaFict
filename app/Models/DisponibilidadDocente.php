<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisponibilidadDocente extends Model
{
    protected $table = 'disponibilidad_docente';
    protected $primaryKey = 'id_disponibilidad';
    public $timestamps = false;

    protected $fillable = [
        'id_docente','id_periodo','dia_semana','hora_inicio','hora_fin','observaciones','prioridad'
    ];

    protected $casts = [
        // horas como string; la BD usa TIME
        'prioridad' => 'integer',
        'dia_semana'=> 'integer',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'id_periodo', 'id_periodo');
    }

    public function scopeClave($query, int $docenteId, int $periodoId, int $diaSemana)
    {
        return $query->where('id_docente', $docenteId)
                     ->where('id_periodo', $periodoId)
                     ->where('dia_semana', $diaSemana);
    }
}
