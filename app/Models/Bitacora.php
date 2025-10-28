<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'fecha_hora',
        'entidad',
        'entidad_id',
        'accion',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
    ];

    protected $casts = [
        'fecha_hora'       => 'datetime',
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }
}
