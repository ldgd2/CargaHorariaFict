<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaCarrera extends Model
{
    protected $table = 'materia_carrera';
    public $timestamps = false;
    public $incrementing = false; // PK compuesta
    protected $primaryKey = null;

    protected $fillable = ['id_materia', 'id_carrera'];

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}
