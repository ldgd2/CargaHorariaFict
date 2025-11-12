<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\CargaValidationService;
use App\Models\PeriodoAcademico;
use App\Models\Carrera;

class AuditoriaController extends Controller
{
    protected $validationService;

    /**
     * Inyección de dependencias del servicio
     */
    public function __construct(CargaValidationService $validationService)
    {
        $this->validationService = $validationService;
    }
    
    /**
     * Muestra la vista principal dedicada de Auditoría y Conflictos (CU12).
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Carga los datos necesarios para los filtros de la vista (Periodos y Carreras)
        $periodos = PeriodoAcademico::select('id_periodo', 'nombre')
                                     ->orderBy('fecha_inicio', 'desc') 
                                     ->get();

        $carreras = Carrera::select('id_carrera', 'nombre')
                           ->where('habilitado', true)
                           ->orderBy('nombre', 'asc')
                           ->get();
        
        // Retorna la vista Blade que se encuentra en la ruta:
        // resources/views/usuarios/admin/admin/auditoria/index.blade.php
        return view('usuarios.admin.admin.auditoria.index', [
            'periodos' => $periodos,
            'carreras' => $carreras,
        ]);
    }

    /**
     * Ejecuta el proceso de auditoría y refresca los conflictos en la base de datos.
     */
    public function refrescarAuditoria(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|integer',
            // 'carrera_id' => 'nullable|integer', // Opcional, si el refresco se limita a una carrera
        ]);

        $periodoId = $request->input('periodo_id');
        
        try {
            // --- PASO 3: El Controller llama al Validation Service ---
            $this->validationService->runAudit($periodoId);

            return response()->json([
                'status' => 'success',
                'message' => 'Auditoría ejecutada y conflictos actualizados correctamente.',
            ]);

        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json([
                'status' => 'error',
                'message' => 'Error al ejecutar la auditoría: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Carga y lista los conflictos de auditoría persistentes.
     */
    public function listarAuditoria(Request $request)
    {
        $periodoId = $request->input('periodo_id');
        $carreraId = $request->input('carrera_id');
        
        if (!$periodoId) {
            // Si falta el periodo, retorna la vista de no resultados con la RUTA CORREGIDA
            return view('usuarios.admin.admin.auditoria.partials.no_results');
        }

        // Recuperar los conflictos almacenados para el periodo y carrera.
        $conflictos = $this->validationService->getStoredConflicts($periodoId, $carreraId);

        // Retornar la vista parcial con la tabla de resultados con la RUTA CORREGIDA.
        return view('usuarios.admin.admin.auditoria.partials.conflict_table', compact('conflictos'));
    }
}