<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carrera';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = ['nombre','jefe_docente_id','habilitado'];
    protected $casts = ['habilitado' => 'boolean'];

    public function jefeDocente()
    {
        return $this->belongsTo(Docente::class, 'jefe_docente_id', 'id_docente');
    }
}
