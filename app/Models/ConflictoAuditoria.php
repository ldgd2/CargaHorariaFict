<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PeriodoAcademico;
use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\CargaHoraria;

class ConflictoAuditoria extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'conflictos_auditoria';

    // Clave primaria no estándar (id_conflicto en lugar de id)
    protected $primaryKey = 'id_conflicto';
    
    // Permitir asignación masiva de todos los campos (ajustar según tu política de seguridad)
    protected $fillable = [
        'periodo_id',
        'carrera_id',
        'grupo_id',
        'tipo',
        'descripcion',
        'carga1_id',
        'carga2_id',
    ];

    // Relaciones (Opcional, pero recomendado)
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id', 'id_periodo');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id', 'id_carrera');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id', 'id_grupo');
    }

    // **¡NUEVAS RELACIONES REQUERIDAS!**
    public function carga1(): BelongsTo
    {
        return $this->belongsTo(CargaHoraria::class, 'carga1_id', 'id_carga');
    }

    public function carga2(): BelongsTo
    {
        return $this->belongsTo(CargaHoraria::class, 'carga2_id', 'id_carga');
    }
}
