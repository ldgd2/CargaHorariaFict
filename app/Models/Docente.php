<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'docente';
    protected $primaryKey = 'id_docente';
    public $timestamps = false;

    protected $fillable = [
        'id_docente','nro_documento','tipo_contrato',
        'carrera_principal','tope_horas_semana','habilitado'
    ];

    protected $casts = [
        'tope_horas_semana' => 'decimal:2',
        'habilitado' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_docente', 'id_usuario');
    }
}
