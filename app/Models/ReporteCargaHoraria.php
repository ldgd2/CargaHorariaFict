<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteCargaHoraria extends Model
{
    protected $table = 'reporte_carga_horaria';
    protected $primaryKey = 'id_reporte';
    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_periodo',
        'total_horas_programadas',
        'total_horas_ausencia',
        'fecha_generacion',
        'tipo_reporte',
    ];

    protected $casts = [
        'total_horas_programadas' => 'decimal:2',
        'total_horas_ausencia'    => 'decimal:2',
        'fecha_generacion'        => 'datetime',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'id_periodo', 'id_periodo');
    }
}
