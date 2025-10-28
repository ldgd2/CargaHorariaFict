<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table = 'aula';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;
    protected $fillable = [
        'nombre_aula',
        'capacidad',
        'tipo_aula',
        'ubicacion',
        'habilitado',
    ];
}
