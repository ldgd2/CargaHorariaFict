<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueoAula extends Model
{
    protected $table = 'bloqueo_aula';
    protected $primaryKey = 'id_bloqueo';
    public $timestamps = false;

    protected $fillable = [
        'id_aula', 'fecha_inicio', 'fecha_fin', 'motivo', 'registrado_por'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function aula()
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }

    public function registradoPor()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por', 'id_usuario');
    }

     public function scopeDeAulaEntreFechas($query, int $aulaId, $desdeYmd, $hastaYmd)
    {
        return $query->where('id_aula', $aulaId)
                     ->where('fecha_inicio', '<=', $hastaYmd)
                     ->where('fecha_fin', '>=', $desdeYmd);
    }
}
