<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\CargaHoraria;
use App\Models\BloqueoAula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteUsoAulaController extends Controller
{
    public function view(Request $request)
    {
        // Periodos con nombre y fechas
        $periodos = DB::table('periodo_academico')
            ->select('id_periodo','nombre','fecha_inicio','fecha_fin')
            ->orderByDesc('fecha_inicio')
            ->get();

        // Tipo de aula
        $tipos = Aula::query()
            ->select('tipo_aula')
            ->distinct()
            ->orderBy('tipo_aula')
            ->pluck('tipo_aula');

        // Aulas (todas, pero podrían filtrarse luego)
        $aulasList = Aula::query()
            ->orderBy('nombre_aula')
            ->get([
                'id_aula',
                'nombre_aula',
                'capacidad',
                'tipo_aula',
            ]);

        // Capacidades disponibles
        $capacidades = Aula::query()
            ->select('capacidad')
            ->distinct()
            ->orderBy('capacidad')
            ->pluck('capacidad');

        // Motivos de bloqueo disponibles
        $motivos = BloqueoAula::query()
            ->select('motivo')
            ->distinct()
            ->orderBy('motivo')
            ->pluck('motivo');

        $reporte = collect();
        $error   = null;

        // Solo calculamos si ya eligió periodo y rango de fechas
        if ($request->filled('id_periodo') && $request->filled('desde') && $request->filled('hasta')) {
            try {
                $reporte = $this->buildReporte($request);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('reportes.uso_aulas', compact(
            'periodos',
            'tipos',
            'aulasList',
            'capacidades',
            'motivos',
            'reporte',
            'error'
        ));
    }

    public function index(Request $request)
{
    if (!$request->filled('id_periodo')) {
        return response()->json(['error' => 'Debe especificar un período académico.'], 422);
    }

    $formato = strtolower($request->get('formato', 'json'));

    try {
        $reporte = $this->buildReporte($request);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }

    $periodo = DB::table('periodo_academico')
        ->select('id_periodo','nombre','fecha_inicio','fecha_fin')
        ->where('id_periodo', $request->id_periodo)
        ->first();
    // === EXPORTAR A EXCEL CON ENCABEZADOS LINDOS ==========================
    if ($formato === 'xlsx') {

        // Mapeamos a nombres de columnas amigables (sin _)
        $exportData = $reporte->map(function ($r) {
            return [
                'Código'           => $r['codigo'],
                'Nombre'           => $r['nombre'],
                'Tipo'             => $r['tipo'] ?? '',
                'Capacidad'        => (int) $r['capacidad'],
                'Horas asignadas'  => (float) $r['horas_asignadas'],
                'Horas bloqueadas' => (float) $r['horas_bloqueadas'],
                'Horas libres'     => (float) $r['horas_libres'],
                '% ocupación'      => (float) $r['ocupacion'],
                'Estado'           => $r['estado'],
            ];
        });

        return (new FastExcel($exportData))
            ->download('reporte-uso-aulas.xlsx');
    }

    // === EXPORTAR A PDF ===================================================
    if ($formato === 'pdf') {

        $pdf = Pdf::loadView('reportes.uso_aulas_pdf', [
                'reporte' => $reporte,
                'filtros' => $request->all(),
                'periodo' => $periodo,
            ])
            ->setPaper('a4', 'landscape'); // horizontal para que quepa mejor

        return $pdf->download('reporte-uso-aulas.pdf');
    }

    // === Respuesta JSON por defecto =======================================
    return response()->json($reporte->values());
}


    /**
     * Lógica central del CU14: calcula horas ocupadas/libres por aula
     */
    private function buildReporte(Request $request)
    {
        $periodoId = (int) $request->get('id_periodo');
        $tipoAula  = $request->get('tipo_aula');
        $capMin    = (int) $request->get('cap_min', 0);
        $aulaId    = $request->get('id_aula');
        $estado    = $request->get('estado', 'todas');   // todas | ocupadas | libres
        $origen    = $request->get('origen', 'todos');   // todos | clases | bloqueos
        $motivo    = $request->get('motivo');            // motivo bloqueo
        $desde     = $request->get('desde');             // YYYY-MM-DD
        $hasta     = $request->get('hasta');             // YYYY-MM-DD

        // Aulas filtradas
        $aulas = Aula::query()
            ->when($aulaId,  fn ($q) => $q->where('id_aula', $aulaId))
            ->when($tipoAula, fn ($q) => $q->where('tipo_aula', $tipoAula))
            ->when($capMin,   fn ($q) => $q->where('capacidad', '>=', $capMin))
            ->orderBy('nombre_aula')
            ->get([
                'id_aula',
                'nombre_aula',
                'capacidad',
                'tipo_aula',
            ]);

        // P1: Horas totales asignables (ejemplo simple)
        // Más adelante lo puedes reemplazar por el cálculo real
        $horasAsignables = 60.0;

        $reporte = $aulas->map(function ($aula) use (
            $periodoId,
            $horasAsignables,
            $desde,
            $hasta,
            $motivo,
            $origen
        ) {
            // ----- P2: Horas ocupadas por CLASES -----
            $horasAsignadas = 0.0;

            if ($origen === 'todos' || $origen === 'clases') {
                $cargaQ = CargaHoraria::query()
                    ->where('id_aula', $aula->id_aula)
                    // aquí está el cambio importante: filtramos por id_periodo
                    // a través de la relación grupo
                    ->whereHas('grupo', function ($q) use ($periodoId) {
                        $q->where('id_periodo', $periodoId);
                    });

                $horasAsignadas = (float) $cargaQ
                    ->selectRaw('COALESCE(SUM(EXTRACT(EPOCH FROM (hora_fin - hora_inicio)) / 3600), 0) as horas')
                    ->value('horas');
            }

            // ----- P3: Horas ocupadas por BLOQUEOS -----
            $horasBloqueadas = 0.0;

            if ($origen === 'todos' || $origen === 'bloqueos') {
                $bloqQ = BloqueoAula::query()
                    ->where('id_aula', $aula->id_aula);

                // Solape con el rango [desde, hasta]
                if ($desde) {
                    $bloqQ->whereDate('fecha_fin', '>=', $desde);
                }
                if ($hasta) {
                    $bloqQ->whereDate('fecha_inicio', '<=', $hasta);
                }
                if ($motivo) {
                    $bloqQ->where('motivo', $motivo);
                }

                $horasBloqueadas = (float) $bloqQ
                    ->selectRaw("COALESCE(SUM( (fecha_fin - fecha_inicio + 1) * 24 ), 0) as horas")
                    ->value('horas');
            }

            // ----- P4: % ocupación -----
            $totalOcupadas = $horasAsignadas + $horasBloqueadas;
            $ocupacion     = $horasAsignables > 0
                ? round(($totalOcupadas / $horasAsignables) * 100, 2)
                : 0.0;

            $horasLibres = max(0, $horasAsignables - $totalOcupadas);
            $estadoFila  = $totalOcupadas > 0 ? 'Ocupada' : 'Libre';

            return [
                'codigo'           => $aula->id_aula,
                'nombre'           => $aula->nombre_aula,
                'tipo'             => $aula->tipo_aula,
                'capacidad'        => (int) $aula->capacidad,
                'horas_asignadas'  => $horasAsignadas,
                'horas_bloqueadas' => $horasBloqueadas,
                'horas_libres'     => $horasLibres,
                'ocupacion'        => $ocupacion,
                'estado'           => $estadoFila,
            ];
        });

        if ($estado === 'ocupadas') {
            $reporte = $reporte->where('estado', 'Ocupada');
        } elseif ($estado === 'libres') {
            $reporte = $reporte->where('estado', 'Libre');
        }

        return $reporte->values();
    }
}
