<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre','apellido','email','contrasena_hash',
        'telefono','direccion','activo','fecha_creacion','entidad'
    ];

    protected $hidden = ['contrasena_hash', 'remember_token'];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_creacion' => 'datetime',
    ];

    /** <-- CLAVE para que Auth use contrasena_hash */
    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    /** Relaciones (ajusta FK si tu esquema usa otro nombre) */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'id_usuario', 'id_rol');
    }

    // Suponiendo FK usuario_id en docente/estudiante
    public function docente()
    {
        return $this->hasOne(Docente::class, 'usuario_id', 'id_usuario');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'usuario_id', 'id_usuario');
    }
}
