<?php

namespace App\Http\Controllers;

use App\Models\BloqueoAula;
use Illuminate\Http\Request;

class BloqueoAulaController extends Controller
{
    public function index(Request $request)
    {
        $q = BloqueoAula::query();

        if ($request->filled('id_aula')) {
            $q->where('id_aula', $request->id_aula);
        }
        if ($request->filled('desde')) {
            $q->whereDate('fecha_inicio', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $q->whereDate('fecha_fin', '<=', $request->hasta);
        }

        return response()->json($q->orderBy('fecha_inicio', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_aula'       => ['required','integer'],
            'fecha_inicio'  => ['required','date'],
            'fecha_fin'     => ['required','date','after_or_equal:fecha_inicio'],
            'motivo'        => ['required','string'],
            'registrado_por'=> ['nullable','integer'],
        ]);

        $bloqueo = BloqueoAula::create($data);
        return response()->json($bloqueo, 201);
    }

    public function show($id)
    {
        $bloqueo = BloqueoAula::find($id);
        if (!$bloqueo) return response()->json(['message'=>'No encontrado'], 404);
        return response()->json($bloqueo);
    }

    public function update(Request $request, $id)
    {
        $bloqueo = BloqueoAula::find($id);
        if (!$bloqueo) return response()->json(['message'=>'No encontrado'], 404);

        $data = $request->validate([
            'id_aula'       => ['sometimes','integer'],
            'fecha_inicio'  => ['sometimes','date'],
            'fecha_fin'     => ['sometimes','date'],
            'motivo'        => ['sometimes','string'],
            'registrado_por'=> ['nullable','integer'],
        ]);

        // Validación extra: si vienen ambas fechas, que fin >= inicio
        if (isset($data['fecha_inicio']) && isset($data['fecha_fin']) &&
            (strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio']))) {
            return response()->json(['message'=>'fecha_fin debe ser >= fecha_inicio'], 422);
        }

        $bloqueo->update($data);
        return response()->json($bloqueo);
    }

    public function destroy($id)
    {
        $bloqueo = BloqueoAula::find($id);
        if (!$bloqueo) return response()->json(['message'=>'No encontrado'], 404);

        $bloqueo->delete();
        return response()->json(['message'=>'Eliminado']);
    }
}
