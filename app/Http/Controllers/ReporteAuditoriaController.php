<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PeriodoAcademico;
use App\Models\Carrera;
use Illuminate\Support\Facades\DB; // Para consultas flexibles
use App\Models\CargaHoraria; // Modelo de ejemplo para un reporte
use Barryvdh\DomPDF\Facade\Pdf; // IMPORTACIÓN CLAVE para Dompdf
use Maatwebsite\Excel\Facades\Excel; // Facade del paquete Maatwebsite
use App\Exports\CargaHorariaExport;
use App\Exports\AsistenciaExport;
use App\Exports\HorariosAulaExport;


class ReporteAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        // 1. Obtener datos para los filtros (selects)
        $periodos = PeriodoAcademico::select('id_periodo', 'nombre')
                                       ->orderBy('fecha_inicio', 'desc') 
                                       ->get();

        $carreras = Carrera::select('id_carrera', 'nombre')
                           ->where('habilitado', true)
                           ->orderBy('nombre', 'asc')
                           ->get();
        
        // 2. Determinar la pestaña activa
        $tabActivo = $request->get('tab', 'reportes');

        // 3. Renderizar la vista
        // VISTA CORREGIDA: Apunta a resources/views/reportes/index.blade.php
        return view('reportes.index', [
            'tab_activo' => $tabActivo,
            'periodos' => $periodos,
            'carreras' => $carreras,
        ]);
    }
    
    /**
     * Maneja la solicitud AJAX para obtener la vista previa del reporte.
     */
    public function preview(Request $request)
    {
        // 1. Obtener filtros
        $tipoReporte = $request->input('tipo');
        $periodoId = $request->input('periodo_id');
        $carreraId = $request->input('carrera_id');
        
        // 2. Cargar los datos específicos
        $datosReporte = $this->obtenerDatosParaReporte($tipoReporte, $periodoId, $carreraId);
        
        // 3. Definir la vista Blade a renderizar
        $vistaBlade = '';
        $tituloReporte = '';

        switch ($tipoReporte) {
            case 'Carga':
                // VISTA CORREGIDA
                $vistaBlade = 'reportes.cargaHorariaexport'; 
                $tituloReporte = 'Reporte de Carga';
                break;
            case 'Asistencia':
                // VISTA CORREGIDA
                $vistaBlade = 'reportes.asistencia'; 
                $tituloReporte = 'Reporte de Asistencia';
                break;
            case 'Horarios':
                // VISTA CORREGIDA
                $vistaBlade = 'reportes.horariosaulaexport';
                $tituloReporte = 'Reporte de Horarios';
                break;
            default:
                return response('<p class="text-center py-4 text-gray-500">Seleccione un tipo de reporte válido.</p>', 400);
        }

        // 4. Renderizar la vista y devolver el HTML
        return view($vistaBlade, [
            'datos' => $datosReporte, 
            'filtros' => [
                'periodo_id' => $periodoId,
                'carrera_id' => $carreraId,
            ],
            'titulo' => $tituloReporte,
        ]); 
    }

    // ==========================================================
    // MÉTODO PARA GENERAR EL PDF (CU9)
    // ==========================================================

    /**
     * Genera y descarga el reporte solicitado en formato PDF.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generar(Request $request)
    {
        // 1. Obtener filtros
        $tipoReporte = $request->input('tipo');
        $periodoId = $request->input('periodo_id');
        $carreraId = $request->input('carrera_id');
        
        // 2. Cargar los datos específicos del reporte
        $datosReporte = $this->obtenerDatosParaReporte($tipoReporte, $periodoId, $carreraId);
        
        // 3. Definir vista y nombre del archivo según el tipo
        $vistaPDF = '';
        $nombreArchivo = '';
        $nombrePeriodo = ($periodoId !== 'all' && $periodoId !== null) 
                             ? PeriodoAcademico::find($periodoId)?->nombre 
                             : 'Todos';

        switch ($tipoReporte) {
            case 'Carga':
                // VISTA CORREGIDA
                $vistaPDF = 'reportes.carga_docente'; 
                $nombreArchivo = 'Reporte_Carga_Docente_' . $nombrePeriodo . '.pdf';
                break;
            case 'Asistencia':
                // VISTA CORREGIDA
                $vistaPDF = 'reportes.asistencia';
                $nombreArchivo = 'Reporte_Asistencia_' . $nombrePeriodo . '.pdf';
                break;
            case 'Horarios':
                // VISTA CORREGIDA
                $vistaPDF = 'reportes.horarios_aula';
                $nombreArchivo = 'Reporte_Horarios_Aula_' . $nombrePeriodo . '.pdf';
                break;
            default:
                return redirect()->back()->with('error', 'Debe seleccionar un tipo de reporte.');
        }

        // 4. Generar y descargar el PDF
        $pdf = Pdf::loadView($vistaPDF, [
            'datos' => $datosReporte, 
            'filtros' => $request->all(),
            'titulo' => 'Reporte de ' . $tipoReporte
        ]);

        return $pdf->download($nombreArchivo);
    }

    // ==========================================================
    // MÉTODO PARA GENERAR Y DESCARGAR XLSX
    // ==========================================================

    /**
     * Genera y descarga el reporte solicitado en formato XLSX.
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportXLSX(Request $request)
    {
        // 1. Obtener filtros
        $tipoReporte = $request->input('tipo');
        $periodoId = $request->input('periodo_id');
        $carreraId = $request->input('carrera_id');
        
        // 2. Cargar los datos específicos del reporte (reutilizando la función existente)
        $datosReporte = $this->obtenerDatosParaReporte($tipoReporte, $periodoId, $carreraId);
        
        // 3. Definir la CLASE de exportación y nombre del archivo según el tipo
        $exportClass = null;
        $nombreArchivo = '';
        $nombrePeriodo = ($periodoId !== 'all' && $periodoId !== null) 
                             ? PeriodoAcademico::find($periodoId)?->nombre 
                             : 'Todos';
        
        switch ($tipoReporte) {
            case 'Carga':
                $exportClass = new CargaHorariaExport($datosReporte);
                $nombreArchivo = 'Reporte_Carga_Docente_' . $nombrePeriodo . '.xlsx';
                break;
            case 'Asistencia':
                $exportClass = new AsistenciaExport($datosReporte);
                $nombreArchivo = 'Reporte_Asistencia_' . $nombrePeriodo . '.xlsx';
                break;
            case 'Horarios':
                $exportClass = new HorariosAulaExport($datosReporte);
                $nombreArchivo = 'Reporte_Horarios_Aula_' . $nombrePeriodo . '.xlsx';
                break;
            default:
                return redirect()->back()->with('error', 'Debe seleccionar un tipo de reporte.');
        }

        // 4. Generar y descargar el XLSX
        if ($exportClass) {
            return Excel::download($exportClass, $nombreArchivo);
        }

        return redirect()->back()->with('error', 'Error al preparar la exportación XLSX.');
    }


    /**
     * Lógica para consultar la base de datos según el tipo de reporte y los filtros. (Sin cambios)
     * @return \Illuminate\Support\Collection
     */
    protected function obtenerDatosParaReporte($tipo, $periodoId, $carreraId)
    {
        if ($tipo === 'Carga') {
            $query = DB::table('carga_horaria AS ch')
                ->select(
                    // Seleccionar datos de USUARIO para nombre y apellido
                    'u.nombre AS docente_nombre', 
                    'u.apellido AS docente_apellido', 
                    // Seleccionar datos de DOCENTE para documento
                    'd.nro_documento AS docente_documento',
                    // Seleccionar datos de MATERIA
                    'm.nombre AS materia_nombre', 
                    // Seleccionar datos de GRUPO
                    'g.nombre_grupo AS grupo_nombre'
                    // Se pueden agregar 'ch.dia_semana', 'ch.hora_inicio', etc.
                )
                // JOIN 1: DOCENTE (usando 'docente' en singular)
                // id_docente de docente es id_usuario de usuario
                ->join('docente AS d', 'ch.id_docente', '=', 'd.id_docente')
                // JOIN 2: USUARIO (para obtener el nombre)
                ->join('usuario AS u', 'd.id_docente', '=', 'u.id_usuario')
                // JOIN 3: GRUPO (usando 'grupo' en singular)
                ->join('grupo AS g', 'ch.id_grupo', '=', 'g.id_grupo')
                // JOIN 4: MATERIA (usando 'materia' en singular)
                ->join('materia AS m', 'g.id_materia', '=', 'm.id_materia');

            // Aplicar filtro de Periodo
            if ($periodoId !== 'all') {
                 // El ID del periodo está en la tabla 'grupo'
                 $query->where('g.id_periodo', $periodoId); 
            }
            
            // Aplicar filtro de Carrera
            if ($carreraId !== 'all') {
                 // El ID de la carrera está en la tabla 'grupo'
                 $query->where('g.id_carrera', $carreraId); 
            }

            return $query->get();
        }

        if ($tipo === 'Asistencia') {
            // Lógica para el reporte de Asistencia: 
            // JOIN: asistencia_sesion -> carga_horaria -> grupo -> materia, docente -> usuario
            return DB::table('asistencia_sesion AS a')
                ->select(
                    'u.nombre AS docente_nombre', 'u.apellido AS docente_apellido',
                    'm.nombre AS materia_nombre', 'g.nombre_grupo AS grupo_nombre',
                    'a.fecha_sesion', 'a.hora_registro', 'a.estado', 'a.tipo_registro'
                )
                ->join('carga_horaria AS ch', 'a.id_carga', '=', 'ch.id_carga')
                ->join('docente AS d', 'ch.id_docente', '=', 'd.id_docente')
                ->join('usuario AS u', 'd.id_docente', '=', 'u.id_usuario')
                ->join('grupo AS g', 'ch.id_grupo', '=', 'g.id_grupo')
                ->join('materia AS m', 'g.id_materia', '=', 'm.id_materia')
                // Se filtra por periodo y carrera a través de 'g' (grupo)
                ->when($periodoId !== 'all', function ($q) use ($periodoId) {
                    return $q->where('g.id_periodo', $periodoId);
                })
                ->when($carreraId !== 'all', function ($q) use ($carreraId) {
                    return $q->where('g.id_carrera', $carreraId);
                })
                ->get();
        }

        if ($tipo === 'Horarios') {
            // Lógica para el reporte de Horarios por Aula:
            // JOIN: carga_horaria -> aula, grupo -> materia, docente -> usuario
            return DB::table('carga_horaria AS ch')
                ->select(
                    'a.nombre_aula AS aula_nombre',
                    'ch.dia_semana', 'ch.hora_inicio', 'ch.hora_fin',
                    DB::raw("CONCAT(m.nombre, ' / ', g.nombre_grupo) AS materia_grupo"), // Concatenamos materia y grupo
                    'u.nombre AS docente_nombre', 'u.apellido AS docente_apellido'
                )
                ->join('aula AS a', 'ch.id_aula', '=', 'a.id_aula')
                ->join('docente AS d', 'ch.id_docente', '=', 'd.id_docente')
                ->join('usuario AS u', 'd.id_docente', '=', 'u.id_usuario')
                ->join('grupo AS g', 'ch.id_grupo', '=', 'g.id_grupo')
                ->join('materia AS m', 'g.id_materia', '=', 'm.id_materia')
                // Se filtra por periodo y carrera a través de 'g' (grupo)
                 ->when($periodoId !== 'all', function ($q) use ($periodoId) {
                     return $q->where('g.id_periodo', $periodoId);
                 })
                 ->when($carreraId !== 'all', function ($q) use ($carreraId) {
                     return $q->where('g.id_carrera', $carreraId);
                 })
                ->orderBy('a.nombre_aula')
                ->orderBy('ch.dia_semana')
                ->orderBy('ch.hora_inicio')
                ->get();
        }
        
        return collect([]); 
    }
}