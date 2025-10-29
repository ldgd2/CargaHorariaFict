<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiante';

    // PK del negocio
    protected $primaryKey = 'codigo_universitario';
    public $incrementing = false;      // PK string
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'codigo_universitario', // PK
        'id_usuario',           // FK -> usuario.id_usuario 
        'carrera',
        'semestre',
    ];

    // Relación 1:1 con Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    
    public function grupos()
    {
        return $this->belongsToMany(
            Grupo::class,
            'matricula',
            'codigo_universitario', // FK en matrícula hacia Estudiante
            'id_grupo'              // FK en matrícula hacia Grupo
        )->withPivot(['fecha_inscripcion']);
    }

  
    protected $casts = [
        'semestre' => 'integer',
    ];
}
