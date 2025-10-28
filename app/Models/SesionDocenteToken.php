<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionDocenteToken extends Model
{
    protected $table = 'sesion_docente_token';
    protected $primaryKey = 'id_token';
    public $timestamps = false;

    protected $fillable = [
        'id_carga',
        'fecha_sesion',
        'token',
        'vence_en',
        'creado_en',
    ];

    protected $casts = [
        'fecha_sesion' => 'date',
        'vence_en'     => 'datetime',
        'creado_en'    => 'datetime',
    ];

    public function cargaHoraria()
    {
        return $this->belongsTo(CargaHoraria::class, 'id_carga', 'id_carga');
    }
}
