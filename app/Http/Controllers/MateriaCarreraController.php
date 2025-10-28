<?php

namespace App\Http\Controllers;

use App\Models\MateriaCarrera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class MateriaCarreraController extends Controller
{
    public function index(Request $request)
    {
        $query = MateriaCarrera::query();

        if ($request->filled('id_materia')) {
            $query->where('id_materia', $request->id_materia);
        }
        if ($request->filled('id_carrera')) {
            $query->where('id_carrera', $request->id_carrera);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_materia' => ['required','integer'],
            'id_carrera' => ['required','integer'],
        ]);

        try {
            $mc = MateriaCarrera::create($data);
            return response()->json($mc, 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se pudo crear la relación (¿duplicada o FK inválida?).',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    // Para claves compuestas, recibimos ambos ids en la ruta
    public function show($idMateria, $idCarrera)
    {
        $mc = MateriaCarrera::where('id_materia', $idMateria)
            ->where('id_carrera', $idCarrera)
            ->first();

        if (!$mc) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($mc);
    }

    public function destroy($idMateria, $idCarrera)
    {
        $mc = MateriaCarrera::where('id_materia', $idMateria)
            ->where('id_carrera', $idCarrera)
            ->first();

        if (!$mc) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $mc->delete();
        return response()->json(['message' => 'Eliminado']);
    }
}
