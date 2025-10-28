<?php

namespace App\Http\Controllers;

use App\Models\DisponibilidadDocente;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class DisponibilidadDocenteController extends Controller
{
    public function index(Request $request)
    {
        $q = DisponibilidadDocente::query();

        if ($request->filled('id_docente')) {
            $q->where('id_docente', $request->id_docente);
        }
        if ($request->filled('id_periodo')) {
            $q->where('id_periodo', $request->id_periodo);
        }
        if ($request->filled('dia_semana')) {
            $q->where('dia_semana', $request->dia_semana);
        }

        return response()->json($q->orderBy('dia_semana')->orderBy('hora_inicio')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_docente'   => ['required','integer'],
            'id_periodo'   => ['required','integer'],
            'dia_semana'   => ['required','integer','between:1,7'],
            'hora_inicio'  => ['required','date_format:H:i'],
            'hora_fin'     => ['required','date_format:H:i','after:hora_inicio'],
            'observaciones'=> ['nullable','string'],
            'prioridad'    => ['nullable','integer'],
        ]);

        try {
            $row = DisponibilidadDocente::create($data);
            return response()->json($row, 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se pudo registrar (¿duplicado por la restricción única?).',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        $row = DisponibilidadDocente::find($id);
        if (!$row) return response()->json(['message'=>'No encontrado'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = DisponibilidadDocente::find($id);
        if (!$row) return response()->json(['message'=>'No encontrado'], 404);

        $data = $request->validate([
            'dia_semana'   => ['sometimes','integer','between:1,7'],
            'hora_inicio'  => ['sometimes','date_format:H:i'],
            'hora_fin'     => ['sometimes','date_format:H:i'],
            'observaciones'=> ['nullable','string'],
            'prioridad'    => ['nullable','integer'],
        ]);

        if (isset($data['hora_inicio'], $data['hora_fin']) &&
            (strtotime($data['hora_fin']) <= strtotime($data['hora_inicio']))) {
            return response()->json(['message'=>'hora_fin debe ser mayor que hora_inicio'], 422);
        }

        try {
            $row->update($data);
            return response()->json($row);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Violación de unicidad/validación en BD.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $row = DisponibilidadDocente::find($id);
        if (!$row) return response()->json(['message'=>'No encontrado'], 404);

        $row->delete();
        return response()->json(['message'=>'Eliminado']);
    }
}
