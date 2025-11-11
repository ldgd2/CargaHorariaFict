<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisponibilidadDocente extends Model
{
    protected $table = 'disponibilidad_docente';
    protected $primaryKey = 'id_disponibilidad';
    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_periodo',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'observaciones',
        'prioridad'
    ];

    protected $casts = [
        'dia_semana'  => 'integer',
        'hora_inicio' => 'string',
        'hora_fin'    => 'string',
        'prioridad'   => 'integer',
    ];

    // Relaciones
    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'id_periodo', 'id_periodo');
    }

    // Scopes
    public function scopeDeDocentePeriodo($q, int $docenteId, int $periodoId) {
        return $q->where('id_docente',$docenteId)
                 ->where('id_periodo',$periodoId);
    }

    /** Regla de solape: (iniA < finB) AND (iniB < finA) */
    public function scopeSolapaCon($q, string $hIni, string $hFin)
    {
        return $q->where('hora_inicio', '<', $hFin)
                 ->where('hora_fin',    '>', $hIni);
    }

    public function scopeCubre($q, string $ini, string $fin) {
    return $q->where('hora_inicio','<=',$ini)->where('hora_fin','>=',$fin);
}

}
