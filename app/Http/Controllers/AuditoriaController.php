<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log; // Importar Log para manejo de excepciones
// use App\Services\CargaValidationService; // No usado en este fragmento
use App\Models\PeriodoAcademico;
use App\Models\Carrera;
// Asegúrate de importar cualquier otro modelo o servicio que uses para el análisis
// use App\Services\AuditoriaService; 

class AuditoriaController extends Controller
{
    /**
     * Muestra la vista principal de Auditoría y Conflictos (coordinador.auditoria.index).
     * Corresponde a la ruta: GET /coordinador/auditoria
     */
    public function index(): View
    {
        // 1. Obtener los datos necesarios para los filtros de la vista
        $periodos = PeriodoAcademico::orderBy('fecha_inicio', 'desc')->get();
        $carreras = Carrera::orderBy('nombre', 'asc')->get();

        // 2. Retornar la vista 'auditoria/index.blade.php'
        return view('auditoria.index', compact('periodos', 'carreras'));
    }

    /**
     * Ejecuta el proceso pesado de análisis de conflictos y guarda los resultados.
     * Corresponde a la ruta: POST /coordinador/auditoria/refrescar
     * Utilizado por la función 'refrescarAuditoria()' en AJAX.
     */
    public function refrescar(Request $request)
    {
        $request->validate([
            // ✅ CORREGIDO: De plural 'periodos_academicos' a singular 'periodo_academico'
            'periodo_id' => 'required|exists:periodo_academico,id_periodo',
            // Si quieres permitir que se filtre por carrera, usa 'nullable' y el nombre de tabla correcto
            'carrera_id' => 'nullable|exists:carrera,id_carrera', 
        ]);

        $periodoId = $request->input('periodo_id');
        // $carreraId = $request->input('carrera_id'); 

        try {
            // ** LÓGICA DE AUDITORÍA **
            // Aquí iría la llamada real al servicio de auditoría
            
            // Simulación de proceso exitoso
            sleep(2); // Simular una operación larga

            return response()->json([
                'success' => true,
                'message' => 'Análisis de auditoría ejecutado y guardado correctamente para el período ' . $periodoId . '.',
            ], 200);

        } catch (\Exception $e) {
            // Manejo de errores y logging de la excepción real
            Log::error('Auditoria::refrescar falló: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el análisis de auditoría. Consulte el log para más detalles.',
            ], 500);
        }
    }

    /**
     * Carga y devuelve el fragmento HTML (la tabla) con los resultados de conflictos.
     * Corresponde a la ruta: GET /coordinador/auditoria/listar
     * Utilizado por la función 'listAuditoria()' en AJAX.
     */
    public function listar(Request $request)
    {
        $request->validate([
            // ✅ CORREGIDO: De plural 'periodos_academicos' a singular 'periodo_academico'
            'periodo_id' => 'required|exists:periodo_academico,id_periodo',
            
            // ⚠️ CORRECCIÓN LÓGICA: 'in:all' no funciona si pides 'required' y 'exists' a la vez. 
            // Si 'carrera_id' es 'all', debe ser 'nullable'. Si solo se validan IDs, se usa 'nullable' o se elimina el 'in:all'.
            // Asumo que 'all' significa que es opcional/nulo.
            'carrera_id' => [
                'nullable', // Permite que el valor sea nulo (o 'all' si lo manejas luego)
                // Se usa la regla 'exclude_if' para no validar la existencia si el valor es 'all'
                'exclude_if:carrera_id,all', 
                'exists:carrera,id_carrera' // El nombre de la tabla de carreras debe ser correcto (asumo 'carrera')
            ],
        ]);
        
        $periodoId = $request->input('periodo_id');
        $carreraId = $request->input('carrera_id'); // Puede ser un ID o la cadena 'all'
        
        // Lógica para recuperar conflictos...
        
        // Simulación de datos vacíos
        $conflictos = collect([]); 
        
        if ($conflictos->isEmpty()) {
            return view('auditoria.partials.no_results');
        }

        // Retornar la vista parcial con la tabla de resultados.
        return view('auditoria.partials.conflict_table', [
            'conflictos' => $conflictos,
            // ...
        ]);
    }
}