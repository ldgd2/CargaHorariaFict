<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Grupo; 
use App\Models\ConflictoAuditoria; 
use App\Models\CargaHoraria; 
use Illuminate\Support\Carbon;

class CargaValidationService
{
    /**
     * Ejecuta el análisis de auditoría para un periodo dado.
     * Ahora persiste los conflictos en la nueva tabla 'conflictos_auditoria'.
     */
    public function runAudit(int $periodoId): int // Cambiamos el retorno a int (número de conflictos)
    {
        // 1. RE-INTRODUCIDO: Limpiar conflictos antiguos para este periodo
        ConflictoAuditoria::where('periodo_id', $periodoId)->delete();
        
        $newConflicts = [];

        // --- LÓGICA DE AUDITORÍA 1: FALTANTES ---
        // ... (Lógica Faltantes sin cambios en esta sección)
        
        $gruposConCarga = DB::table('carga_horaria')
            ->select('id_grupo')
            ->distinct();

        $faltantes = DB::table('grupo')
            ->where('id_periodo', $periodoId)
            ->whereNotIn('id_grupo', $gruposConCarga)
            ->join('carrera', 'grupo.id_carrera', '=', 'carrera.id_carrera')
            ->join('materia', 'grupo.id_materia', '=', 'materia.id_materia')
            ->select('grupo.id_grupo', 'grupo.id_carrera', 'materia.nombre as materia_nombre', 'grupo.nombre_grupo', 'carrera.nombre as carrera_nombre')
            ->get();
            
        foreach ($faltantes as $grupo) {
            $newConflicts[] = [
                'tipo' => 'Faltante',
                'descripcion' => "El grupo {$grupo->nombre_grupo} ({$grupo->materia_nombre}) de la carrera '{$grupo->carrera_nombre}' no tiene asignación horaria (Docente/Aula).",
                'periodo_id' => $periodoId,
                'carrera_id' => $grupo->id_carrera,
                'grupo_id' => $grupo->id_grupo,
                'carga1_id' => null, // Añadido
                'carga2_id' => null, // Añadido
            ];
        }


        // --- LÓGICA DE AUDITORÍA 2: SOLAPES (Conflictos) ---
        // ... (La lógica de $asignaciones se mantiene)
        $asignaciones = DB::table('carga_horaria') 
                            ->join('grupo', 'carga_horaria.id_grupo', '=', 'grupo.id_grupo')
                            ->where('grupo.id_periodo', $periodoId)
                            ->select('carga_horaria.*', 'grupo.id_carrera')
                            ->get();

        // 2.1 Solape por Docente
        $newConflicts = array_merge($newConflicts, $this->checkTeacherOverlaps($asignaciones, $periodoId));
        
        // 2.2 Solape por Aula
        $newConflicts = array_merge($newConflicts, $this->checkClassroomOverlaps($asignaciones, $periodoId));


        // --- PASO 5: Persistencia ---
        // RE-INTRODUCIDO: Persistir los conflictos detectados
        if (!empty($newConflicts)) {
            $timestamp = Carbon::now(); // Usamos Carbon para los timestamps
            $newConflicts = array_map(function($conflict) use ($timestamp) {
                // Añadimos los timestamps para la inserción masiva
                $conflict['created_at'] = $timestamp;
                $conflict['updated_at'] = $timestamp;
                return $conflict;
            }, $newConflicts);

            ConflictoAuditoria::insert($newConflicts);
        }
        
        // RETORNO: Ahora devolvemos el conteo
        return count($newConflicts); 
    }
    
    /**
     * Detecta conflictos por horario de docentes.
     */
    private function checkTeacherOverlaps($asignaciones, $periodoId): array
    {
        $conflicts = [];
        
        $solapesDocente = DB::table('carga_horaria as ch1')
            ->select(
                'ch1.id_docente', 
                'ch1.dia_semana', 
                'ch1.hora_inicio', 
                'ch1.hora_fin', 
                'ch1.id_carga as carga1', 
                'ch2.id_carga as carga2',
                'ch1.id_grupo as grupo1_id', // Para obtener el grupo_id
                'g1.id_carrera'
            )
            ->join('carga_horaria as ch2', function ($join) {
                // Mismo docente, mismo día, diferente carga (para evitar auto-solape)
                $join->on('ch1.id_docente', '=', 'ch2.id_docente')
                     ->on('ch1.dia_semana', '=', 'ch2.dia_semana')
                     ->whereColumn('ch1.id_carga', '<', 'ch2.id_carga'); 
            })
            ->join('grupo as g1', 'ch1.id_grupo', '=', 'g1.id_grupo')
            ->where('g1.id_periodo', $periodoId)
            // Lógica de solape:
            ->where(function ($query) {
                $query->whereRaw('ch1.start_min < ch2.end_min AND ch2.start_min < ch1.end_min');
            })
            ->get();
            
        foreach ($solapesDocente as $solape) {
            $conflicts[] = [
                'tipo' => 'Solape Docente',
                'descripcion' => "Docente ID {$solape->id_docente} solapa el día {$solape->dia_semana} entre {$solape->hora_inicio} y {$solape->hora_fin}. Cargas: #{$solape->carga1} y #{$solape->carga2}.",
                'periodo_id' => $periodoId,
                'carrera_id' => $solape->id_carrera,
                'grupo_id' => $solape->grupo1_id, // Usamos el grupo de la carga 1 como referencia
                'carga1_id' => $solape->carga1, // Añadido
                'carga2_id' => $solape->carga2, // Añadido
            ];
        }

        return $conflicts;
    }
    
    /**
     * Detecta conflictos por horario de aulas.
     */
    private function checkClassroomOverlaps($asignaciones, $periodoId): array
    {
        $conflicts = [];
        
        // Similar al solape de docente, buscamos solapes de aula
        $solapesAula = DB::table('carga_horaria as ch1')
            ->select(
                'ch1.id_aula', 
                'ch1.dia_semana', 
                'ch1.hora_inicio', 
                'ch1.hora_fin', 
                'ch1.id_carga as carga1', 
                'ch2.id_carga as carga2',
                'ch1.id_grupo as grupo1_id', // Para obtener el grupo_id
                'g1.id_carrera'
            )
            ->join('carga_horaria as ch2', function ($join) {
                // Mismo aula, mismo día, diferente carga
                $join->on('ch1.id_aula', '=', 'ch2.id_aula')
                     ->on('ch1.dia_semana', '=', 'ch2.dia_semana')
                     ->whereColumn('ch1.id_carga', '<', 'ch2.id_carga'); 
            })
            ->join('grupo as g1', 'ch1.id_grupo', '=', 'g1.id_grupo')
            ->where('g1.id_periodo', $periodoId)
            // Lógica de solape:
            ->where(function ($query) {
                $query->whereRaw('ch1.start_min < ch2.end_min AND ch2.start_min < ch1.end_min');
            })
            ->get();
            
        foreach ($solapesAula as $solape) {
            $conflicts[] = [
                'tipo' => 'Solape Aula',
                'descripcion' => "Aula ID {$solape->id_aula} solapa el día {$solape->dia_semana} entre {$solape->hora_inicio} y {$solape->hora_fin}. Cargas: #{$solape->carga1} y #{$solape->carga2}.",
                'periodo_id' => $periodoId,
                'carrera_id' => $solape->id_carrera,
                'grupo_id' => $solape->grupo1_id, // Usamos el grupo de la carga 1 como referencia
                'carga1_id' => $solape->carga1, 
                'carga2_id' => $solape->carga2, 
            ];
        }
        
        return $conflicts;
    }

    /**
     * Recupera los conflictos almacenados para la visualización.
     * RE-INTRODUCIDO (Necesario para la vista de auditoría)
     */
    public function getStoredConflicts(int $periodoId, ?string $carreraId)
    {
        $query = ConflictoAuditoria::where('periodo_id', $periodoId);
        
        if ($carreraId && $carreraId !== 'all') {
            $query->where('carrera_id', $carreraId);
        }

        return $query->with(['periodo', 'carrera', 'grupo', 'carga1', 'carga2'])
                      ->orderBy('tipo')
                      ->get();
    }
}