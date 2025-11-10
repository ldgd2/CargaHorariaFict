<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Request;
class Docente extends Model
{
    protected $table = 'docente';
    protected $primaryKey = 'id_docente';
    public $timestamps = false;
    protected $appends = ['nombre'];
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

    public function getNombreAttribute()
    {
   
        if (array_key_exists('nombre', $this->attributes) && $this->attributes['nombre']) {
            return $this->attributes['nombre'];
        }
      
        $n = trim(($this->attributes['nombres'] ?? '').' '.($this->attributes['apellidos'] ?? ''));
        if ($n !== '') return $n;

        return 'Docente #'.$this->attributes[$this->primaryKey];
    }
    public function disponibilidad(Request $r)
{
    // columna de estado disponible
    $col = Schema::hasColumn('periodo_academico','estado')
        ? 'estado'
        : (Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);

    $validos = ['EnAsignacion','Reabierto','Activo','Publicado','publicado','borrador','Borrador'];

    $periodos = \App\Models\PeriodoAcademico::query()
        ->when($col, fn($q) => $q->whereIn($col, $validos))
        ->orderByDesc('fecha_inicio')
        ->get(['id_periodo','nombre','fecha_inicio','fecha_fin']);

    $ultimo = \App\Models\PeriodoAcademico::query()
        ->when($col, fn($q) => $q->whereIn($col, $validos))
        ->orderByDesc('id_periodo')
        ->first();

    $idPeriodo = (int) ($r->query('id_periodo') ?? ($ultimo?->id_periodo ?? 0));

    return view('docente.disponibilidad', compact('periodos','idPeriodo'));
}
}
