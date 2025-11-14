<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HorarioCicloController extends Controller
{
    // ⚠️ CRÍTICO: Reemplazar estos valores con los ID reales de tu tabla 'rol'
    private $ADMIN_ROL_ID = 1;      // ID de 'Administrador'
    private $COORDINATOR_ROL_ID = 2; // ID de 'Coordinador'


    /**
     * Verifica si el usuario autenticado tiene un rol específico.
     * @param int $userId ID del usuario.
     * @param int $rolId ID del rol a verificar.
     * @return bool
     */
    private function hasRole($userId, $rolId)
    {
        return DB::table('usuario_rol')
            ->where('id_usuario', $userId)
            ->where('id_rol', $rolId)
            ->exists();
    }


    /**
     * Muestra la vista de gestión de publicación y reapertura de horarios. (CU10/CU11)
     * MÉTODO RENOMBRADO A 'index' para coincidir con la ruta 'coordinador.gestion_ciclo.index'.
     */
    public function index() // <--- ¡CORREGIDO!
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/login')->withErrors('Debe iniciar sesión para acceder a esta función.');
        }

        // 1. Determinar roles del usuario autenticado
        $is_admin = $this->hasRole($user->id_usuario, $this->ADMIN_ROL_ID);
        $is_coordinator = $this->hasRole($user->id_usuario, $this->COORDINATOR_ROL_ID);

        // Si el usuario no es Admin ni Coordinador, denegar el acceso a la vista.
        if (!$is_admin && !$is_coordinator) {
             return response()->view('errors.403', ['message' => 'No tiene permisos para acceder a la gestión de ciclos.'], 403);
        }

        // 2. Obtener la lista de períodos y carreras con oferta de grupos
        $periodos_carrera = DB::table('periodo_academico AS pa')
            ->join('grupo AS g', 'pa.id_periodo', '=', 'g.id_periodo')
            ->join('carrera AS c', 'g.id_carrera', '=', 'c.id_carrera')
            ->select([
                'pa.id_periodo AS id',
                'pa.nombre AS periodo',
                'c.nombre AS carrera',
                'pa.estado_publicacion AS estado', 
            ])
            ->distinct() // Evita duplicados si una carrera tiene varios grupos en un periodo
            ->orderBy('pa.nombre', 'desc')
            ->orderBy('c.nombre', 'asc')
            ->get();

        // 3. Pasar los datos y roles a la vista
        // La vista apunta a resources/views/horarios/publicacion_reapertura.blade.php
        return view('horarios.publicacion_reapertura', [ 
            'periodos_carrera' => $periodos_carrera,
            'is_admin' => $is_admin,
            'is_coordinator' => $is_coordinator,
        ]);
    }

    // --------------------------------------------------------------------------------
    // Lógica para el CU10: Publicar Horarios
    // --------------------------------------------------------------------------------
    public function publicarHorarios(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Acceso denegado. No autenticado.'], 403);
        }

        // Verificación de Permisos
        $is_admin = $this->hasRole($user->id_usuario, $this->ADMIN_ROL_ID);
        $is_coordinator = $this->hasRole($user->id_usuario, $this->COORDINATOR_ROL_ID);
        
        if (!$is_admin && !$is_coordinator) {
            return response()->json(['message' => 'Permiso denegado. Solo Coordinadores o Administradores pueden publicar.'], 403);
        }
        
        // 1. Validar la entrada
        $request->validate([
            'id_periodo' => 'required|integer|exists:periodo_academico,id_periodo',
        ]);

        $id_periodo = $request->input('id_periodo');
        $user_id = $user->id_usuario;

        try {
            DB::beginTransaction();

            // 2. Obtener y validar el estado actual
            $periodo = DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->first();

            if (!$periodo) {
                return response()->json(['message' => 'Período no encontrado.'], 404);
            }
            if ($periodo->estado_publicacion === 'Publicado') {
                return response()->json(['message' => 'El ciclo ya se encuentra Publicado.'], 400);
            }

            // 3. Actualizar el estado a 'Publicado'
            DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->update([
                    'estado_publicacion' => 'Publicado',
                    'fecha_publicacion' => now(), 
                ]);

            // 4. Registrar en la Bitácora
            DB::table('bitacora')->insert([
                'user_id' => $user_id,
                'fecha_hora' => now(),
                'entidad' => 'periodo_academico',
                'entidad_id' => $id_periodo,
                'accion' => 'PUBLICACION_HORARIO',
                'descripcion' => "Publicado el ciclo {$periodo->nombre}. Estado final: Publicado. Ejecutado por " . ($is_admin ? 'Admin' : 'Coordinador') . ".",
                'datos_anteriores' => json_encode(['estado_publicacion_anterior' => $periodo->estado_publicacion]),
                'datos_nuevos' => json_encode(['estado_publicacion' => 'Publicado']),
            ]);

            DB::commit();
            return response()->json(['message' => 'Horarios publicados con éxito.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al publicar los horarios: ' . $e->getMessage()], 500);
        }
    }

    // --------------------------------------------------------------------------------
    // Lógica para el CU11: Reabrir Horarios
    // --------------------------------------------------------------------------------
    public function reabrirHorarios(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Acceso denegado. No autenticado.'], 403);
        }

        // Verificación de Permisos: Solo Administrador puede Reabrir
        if (!$this->hasRole($user->id_usuario, $this->ADMIN_ROL_ID)) {
            return response()->json(['message' => 'Permiso denegado. Solo Administradores pueden reabrir horarios.'], 403);
        }
        
        // 1. Validar la entrada (id_periodo y motivo obligatorio)
        $request->validate([
            'id_periodo' => 'required|integer|exists:periodo_academico,id_periodo',
            'motivo' => 'required|string|min:10',
        ]);

        $id_periodo = $request->input('id_periodo');
        $motivo = $request->input('motivo');
        $user_id = $user->id_usuario;

        try {
            DB::beginTransaction();

            // 2. Obtener y validar el estado actual (Debe ser 'Publicado')
            $periodo = DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->first();

            if (!$periodo) {
                return response()->json(['message' => 'Período no encontrado.'], 404);
            }
            if ($periodo->estado_publicacion !== 'Publicado') {
                return response()->json(['message' => 'El ciclo debe estar en estado "Publicado" para ser reabierto.'], 400);
            }

            // 3. Actualizar el estado a 'Reabierto'
            DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->update(['estado_publicacion' => 'Reabierto']);

            // 4. Registrar en la tabla reapertura_historial
            DB::table('reapertura_historial')->insert([
                'id_periodo' => $id_periodo,
                'fecha_hora' => now(),
                'motivo' => $motivo,
                'autorizado_por' => $user_id,
            ]);
            
            // 5. Registrar en la Bitácora
            DB::table('bitacora')->insert([
                'user_id' => $user_id,
                'fecha_hora' => now(),
                'entidad' => 'periodo_academico',
                'entidad_id' => $id_periodo,
                'accion' => 'REAPERTURA_HORARIO',
                'descripcion' => "Reabierto el ciclo {$periodo->nombre}. Motivo: {$motivo}",
                'datos_anteriores' => json_encode(['estado_publicacion_anterior' => $periodo->estado_publicacion]),
                'datos_nuevos' => json_encode(['estado_publicacion' => 'Reabierto', 'motivo_reapertura' => $motivo]),
            ]);

            DB::commit();
            return response()->json(['message' => 'Horarios reabiertos para modificación.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al reabrir los horarios: ' . $e->getMessage()], 500);
        }
    }
}