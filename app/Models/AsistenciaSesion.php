<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaSesion extends Model
{
    protected $table = 'asistencia_sesion';
    protected $primaryKey = 'id_asistencia';
    public $timestamps = false;

    protected $fillable = [
        'id_carga','fecha_sesion','hora_registro','tipo_registro','registrado_por','estado','motivo'
    ];

    protected $casts = [
        'fecha_sesion' => 'date',
    ];

    public function carga()
    {
        return $this->belongsTo(CargaHoraria::class, 'id_carga', 'id_carga');
    }

    public function registradoPor()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por', 'id_usuario');
    }

    /**
     * Usa el índice: idx_asistencia_carga_fecha (id_carga, fecha_sesion)
     */
    public function scopeDeCargaEnFecha($query, int $cargaId, $fechaYmd)
    {
        return $query->where('id_carga', $cargaId)
                     ->whereDate('fecha_sesion', $fechaYmd);
    }
}
