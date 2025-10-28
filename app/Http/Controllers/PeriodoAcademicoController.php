<?php

namespace App\Http\Controllers;

use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeriodoAcademicoController extends Controller
{
    public function index(Request $request)
    {
        $activos = $request->query('activo');
        $estado  = $request->query('estado_publicacion');

        $periodos = PeriodoAcademico::query()
            ->when(!is_null($activos), fn($qb) => $qb->where('activo', filter_var($activos, FILTER_VALIDATE_BOOLEAN)))
            ->when($estado, fn($qb) => $qb->where('estado_publicacion', $estado))
            ->orderByDesc('fecha_inicio')
            ->paginate($request->integer('per_page', 20));

        return response()->json($periodos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'             => ['required','string','max:50','unique:periodo_academico,nombre'],
            'fecha_inicio'       => ['required','date'],
            'fecha_fin'          => ['required','date','after_or_equal:fecha_inicio'],
            'activo'             => ['boolean'],
            'estado_publicacion' => ['nullable', Rule::in(['Borrador','Publicado','Reabierto'])],
        ]);

        $periodo = PeriodoAcademico::create($data);
        return response()->json($periodo, 201);
    }

    public function show(PeriodoAcademico $periodoAcademico)
    {
        return response()->json($periodoAcademico);
    }

    public function update(Request $request, PeriodoAcademico $periodoAcademico)
    {
        $data = $request->validate([
            'nombre'             => ['sometimes','string','max:50', Rule::unique('periodo_academico','nombre')->ignore($periodoAcademico->id_periodo,'id_periodo')],
            'fecha_inicio'       => ['sometimes','date'],
            'fecha_fin'          => ['sometimes','date','after_or_equal:fecha_inicio'],
            'activo'             => ['sometimes','boolean'],
            'estado_publicacion' => ['sometimes', Rule::in(['Borrador','Publicado','Reabierto'])],
        ]);

        $periodoAcademico->update($data);
        return response()->json($periodoAcademico);
    }

    public function destroy(PeriodoAcademico $periodoAcademico)
    {
        $periodoAcademico->delete();
        return response()->json(null, 204);
    }
}
