<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $carrera = $request->get('carrera');

        $estudiantes = Estudiante::query()
            ->when($q, fn($qb) =>
                $qb->where('codigo_universitario','ilike',"%{$q}%")
                   ->orWhere('id_estudiante', $q))
            ->when($carrera, fn($qb) => $qb->where('carrera','ilike',"%{$carrera}%"))
            ->orderBy('codigo_universitario')
            ->paginate($request->integer('per_page', 20));

        return response()->json($estudiantes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_estudiante'        => ['required','integer','exists:usuario,id_usuario','unique:estudiante,id_estudiante'],
            'codigo_universitario' => ['required','string','max:20','unique:estudiante,codigo_universitario'],
            'carrera'              => ['required','string','max:100'],
            'semestre'             => ['nullable','integer','min:1'],
        ]);

        $estudiante = Estudiante::create($data);
        return response()->json($estudiante, 201);
    }

    public function show(Estudiante $estudiante)
    {
        return response()->json($estudiante);
    }

    public function update(Request $request, Estudiante $estudiante)
    {
        $data = $request->validate([
            'codigo_universitario' => ['sometimes','string','max:20', Rule::unique('estudiante','codigo_universitario')->ignore($estudiante->id_estudiante,'id_estudiante')],
            'carrera'              => ['sometimes','string','max:100'],
            'semestre'             => ['sometimes','nullable','integer','min:1'],
        ]);

        $estudiante->update($data);
        return response()->json($estudiante);
    }

    public function destroy(Estudiante $estudiante)
    {
        $estudiante->delete();
        return response()->json(null, 204);
    }
}
