<?php

namespace App\Http\Controllers;

use App\Models\ReporteCargaHoraria;
use Illuminate\Http\Request;

class ReporteCargaHorariaController extends Controller
{
    public function index(Request $request)
    {
        $q = ReporteCargaHoraria::query()
            ->when($request->id_docente, fn($qq) => $qq->where('id_docente', $request->id_docente))
            ->when($request->id_periodo, fn($qq) => $qq->where('id_periodo', $request->id_periodo))
            ->when($request->tipo_reporte, fn($qq) => $qq->where('tipo_reporte', $request->tipo_reporte));

        return $q->orderByDesc('fecha_generacion')->paginate($request->integer('per_page', 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_docente'                => ['required','integer','exists:docente,id_docente'],
            'id_periodo'                => ['required','integer','exists:periodo_academico,id_periodo'],
            'total_horas_programadas'   => ['required','numeric','min:0'],
            'total_horas_ausencia'      => ['nullable','numeric','min:0'],
            'fecha_generacion'          => ['nullable','date'],
            'tipo_reporte'              => ['nullable','string','max:50'],
        ]);

        $row = ReporteCargaHoraria::create($data);
        return response()->json($row, 201);
    }

    public function show(ReporteCargaHoraria $reporteCargaHorarium) // nombre inferido por Laravel
    {
        return $reporteCargaHorarium;
    }

    public function update(Request $request, ReporteCargaHoraria $reporteCargaHorarium)
    {
        $data = $request->validate([
            'id_docente'                => ['sometimes','integer','exists:docente,id_docente'],
            'id_periodo'                => ['sometimes','integer','exists:periodo_academico,id_periodo'],
            'total_horas_programadas'   => ['sometimes','numeric','min:0'],
            'total_horas_ausencia'      => ['nullable','numeric','min:0'],
            'fecha_generacion'          => ['nullable','date'],
            'tipo_reporte'              => ['nullable','string','max:50'],
        ]);

        $reporteCargaHorarium->update($data);
        return $reporteCargaHorarium;
    }

    public function destroy(ReporteCargaHoraria $reporteCargaHorarium)
    {
        $reporteCargaHorarium->delete();
        return response()->noContent();
    }
}
