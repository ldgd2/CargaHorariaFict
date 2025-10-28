<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReaperturaHistorial extends Model
{
    protected $table = 'reapertura_historial';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;

    protected $fillable = [
        'id_periodo',
        'fecha_hora',
        'motivo',
        'autorizado_por',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'id_periodo', 'id_periodo');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(Usuario::class, 'autorizado_por', 'id_usuario');
    }
}
