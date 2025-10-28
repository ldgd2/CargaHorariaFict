<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiante';
    protected $primaryKey = 'id_estudiante';
    public $timestamps = false;
    protected $fillable = [
        'id_estudiante', // PK = FK a usuario
        'codigo_universitario',
        'carrera',
        'semestre',
    ];

    public $incrementing = true; // SERIAL en BD

    // Relación (si usas tabla MATRICULA)
    public function grupos()
    {
        return $this->belongsToMany(Grupo::class, 'matricula', 'id_estudiante', 'id_grupo')
                    ->withPivot(['fecha_inscripcion']);
    }
}
