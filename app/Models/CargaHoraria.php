<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
class CargaHoraria extends Model
{
    protected $table = 'carga_horaria';
    protected $primaryKey = 'id_carga';
    public $timestamps = false;

    protected $fillable = [
        'id_grupo','id_docente','id_aula',
        'dia_semana','hora_inicio','hora_fin',
        'estado','observaciones'
    ];

    // --- Relaciones ---
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }

    public function asistencias()
    {
        return $this->hasMany(AsistenciaSesion::class, 'id_carga', 'id_carga');
    }

    // --- Scopes útiles ---
    public function scopeDelPeriodo($query, int $idPeriodo)
    {
        return $query->whereHas('grupo', function ($q) use ($idPeriodo) {
            $q->where('id_periodo', $idPeriodo);
        });
    }

    public function scopeDelDocente($query, int $idDocente)
    {
        return $query->where('id_docente', $idDocente);
    }

    public function scopeDelAula($query, int $idAula)
    {
        return $query->where('id_aula', $idAula);
    }

    public function scopeDePeriodo($q, int $pid) { 
        return $q->where('id_periodo',$pid); 
    }
    public function scopeDelDia($q, int $d) { 
        return $q->where('dia_semana',$d); 
    }
    public function scopeSolapaCon($q, string $ini, string $fin) {
        return $q->where('hora_inicio','<',$fin)->where('hora_fin','>',$ini);
    }
     public function scopeDeAulaDiaRango($query, int $aulaId, int $diaSemana, ?int $desdeMin = null, ?int $hastaMin = null)
    {
        $query->where('id_aula', $aulaId)
              ->where('dia_semana', $diaSemana);

        if (!is_null($desdeMin) && !is_null($hastaMin)) {
            $query->where('start_min', '<', $hastaMin)
                  ->where('end_min', '>', $desdeMin);
        }

        return $query;
    }

    public function scopeDeDocenteDiaRango($query, int $docenteId, int $diaSemana, ?int $desdeMin = null, ?int $hastaMin = null)
    {
        $query->where('id_docente', $docenteId)
              ->where('dia_semana', $diaSemana);

        if (!is_null($desdeMin) && !is_null($hastaMin)) {
            // Solape: start_min < hasta AND end_min > desde
            $query->where('start_min', '<', $hastaMin)
                  ->where('end_min', '>', $desdeMin);
        }

        return $query;
    }
    public static function tieneAsignacionesAbiertas(int $periodoId): bool
        {
        if (!Schema::hasTable('carga_horaria')) return false;
        return static::where(function ($q) {
                $q->whereNull('estado')
                  ->orWhereIn('estado', ['Vigente','Modificado']);
            })
            ->whereHas('grupo', fn($q) => $q->where('id_periodo', $periodoId))
            ->exists();
        }

    }

    

