<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Bitacora; 
use Exception;

class HorarioCicloController extends Controller
{
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
     */
    public function index()
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

        // 2. Obtener la lista de períodos y las carreras con oferta de grupos
        $periodos_carrera = DB::table('periodo_academico AS pa')
            ->join('grupo AS g', 'pa.id_periodo', '=', 'g.id_periodo')
            ->join('carrera AS c', 'g.id_carrera', '=', 'c.id_carrera')
            ->select([
                'pa.id_periodo AS id',
                'pa.nombre AS periodo',
                // CORRECCIÓN PREVIA: Usar STRING_AGG para PostgreSQL
                DB::raw("STRING_AGG(DISTINCT c.nombre, ', ') AS carrera"),
                'pa.estado_publicacion AS estado', 
            ])
            ->groupBy('pa.id_periodo', 'pa.nombre', 'pa.estado_publicacion') 
            ->orderBy('pa.nombre', 'desc')
            ->get();

        // 3. Pasar los datos y roles a la vista
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

        // Verificación de Permisos (Admin O Coordinador)
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
                DB::rollBack();
                return response()->json(['message' => 'Período no encontrado.'], 404);
            }
            
            // CORRECCIÓN 1: Usar minúsculas y strtolower para verificar el estado,
            // aunque el valor 'publicado' se usa en la base de datos en minúsculas.
            if (strtolower($periodo->estado_publicacion) === 'publicado') {
                DB::rollBack();
                return response()->json(['message' => 'El ciclo ya se encuentra publicado.'], 400);
            }
            
            $estado_anterior = $periodo->estado_publicacion;
            $activo_anterior = $periodo->activo; // Capturar el estado 'activo' anterior
            $nombre_periodo = $periodo->nombre;

            // 3. Actualizar el estado a 'publicado' y 'activo' a false
            // CORRECCIÓN 2: Se establece 'activo' = false (0) para SATISFACER la restricción CHECK.
            DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->update([
                    'estado_publicacion' => 'publicado', // Usar minúsculas
                    'activo' => false, // Obligatorio por la restricción CHECK
                ]);

            // 4. Registrar en la Bitácora
            // CORRECCIÓN 3: Usar minúsculas y registrar el cambio en 'activo'.
            Bitacora::create([
                'user_id' => $user_id,
                'fecha_hora' => now(),
                'entidad' => 'periodo_academico',
                'entidad_id' => $id_periodo,
                'accion' => 'PUBLICACION_HORARIO',
                'descripcion' => "Publicación oficial del Período Académico {$nombre_periodo}. Los horarios ahora están visibles.",
                'datos_anteriores' => [
                    'estado_publicacion' => $estado_anterior,
                    'activo' => $activo_anterior,
                ], 
                'datos_nuevos' => [
                    'estado_publicacion' => 'publicado',
                    'activo' => false,
                ], 
            ]);

            DB::commit();
            return response()->json(['message' => 'Horarios publicados con éxito.'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            // Mostrar la excepción de manera segura en el log del servidor
            \Log::error("Error al publicar horarios: " . $e->getMessage(), ['id_periodo' => $id_periodo, 'user_id' => $user_id]);
            return response()->json(['message' => 'Error al publicar los horarios. Inténtelo de nuevo.'], 500);
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

        // Verificación de Permisos: Admin O Coordinador
        $is_admin = $this->hasRole($user->id_usuario, $this->ADMIN_ROL_ID);
        $is_coordinator = $this->hasRole($user->id_usuario, $this->COORDINATOR_ROL_ID);
        
        if (!$is_admin && !$is_coordinator) {
            return response()->json(['message' => 'Permiso denegado. Solo Coordinadores o Administradores pueden reabrir horarios.'], 403);
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

            // 2. Obtener y validar el estado actual (Debe ser 'publicado')
            $periodo = DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->first();

            if (!$periodo) {
                DB::rollBack();
                return response()->json(['message' => 'Período no encontrado.'], 404);
            }
            // CORRECCIÓN 4: Usar minúsculas en el chequeo de estado
            if (strtolower($periodo->estado_publicacion) !== 'publicado') {
                DB::rollBack();
                return response()->json(['message' => 'El ciclo debe estar en estado "publicado" para ser reabierto.'], 400);
            }
            
            $estado_anterior = $periodo->estado_publicacion;
            $activo_anterior = $periodo->activo; // Capturar el estado 'activo' anterior
            $nombre_periodo = $periodo->nombre;

            // 3. Actualizar el estado a 'borrador' y reactivarlo
            // CORRECCIÓN 5: Usar 'borrador' (estado de edición) y 'activo' = true (1) para que sea editable.
            DB::table('periodo_academico')
                ->where('id_periodo', $id_periodo)
                ->update([
                    'estado_publicacion' => 'borrador', 
                    'activo' => true // Reactiva el período, permitiendo que el estado sea 'borrador'
                ]);

            // 4. Registrar en la tabla reapertura_historial 
            DB::table('reapertura_historial')->insert([
                'id_periodo' => $id_periodo,
                'fecha_hora' => now(), // Asegurar el uso de now()
                'motivo' => $motivo,
                'autorizado_por' => $user_id,
            ]);
            
            // 5. Registrar en la Bitácora
            // CORRECCIÓN 6: Usar 'borrador' y registrar el cambio en 'activo'.
            Bitacora::create([
                'user_id' => $user_id,
                'fecha_hora' => now(),
                'entidad' => 'periodo_academico',
                'entidad_id' => $id_periodo,
                'accion' => 'REAPERTURA_HORARIO',
                'descripcion' => "Reabierto el ciclo {$nombre_periodo} para edición (Estado: Borrador). Motivo: {$motivo}",
                'datos_anteriores' => [
                    'estado_publicacion' => $estado_anterior,
                    'activo' => $activo_anterior,
                ],
                'datos_nuevos' => [
                    'estado_publicacion' => 'borrador', 
                    'activo' => true, 
                    'motivo_reapertura' => $motivo
                ],
            ]);

            DB::commit();
            return response()->json(['message' => 'Horarios reabiertos para modificación.'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error("Error al reabrir horarios: " . $e->getMessage(), ['id_periodo' => $id_periodo, 'user_id' => $user_id]);
            return response()->json(['message' => 'Error al reabrir los horarios. Inténtelo de nuevo.'], 500);
        }
    }
}