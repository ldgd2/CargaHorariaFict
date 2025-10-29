<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $q       = $request->get('q');
        $carrera = $request->get('carrera');
        $perPage = $request->integer('per_page', 20);

        $estudiantes = Estudiante::query()
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($inner) use ($q) {
                    // Postgres: ILIKE; en otros motores usa like/lower
                    $inner->where('codigo_universitario', 'ilike', "%{$q}%")
                          ->orWhere('id_usuario', $q);
                });
            })
            ->when($carrera, fn ($qb) =>
                $qb->where('carrera', 'ilike', "%{$carrera}%")
            )
            ->orderBy('codigo_universitario')
            ->paginate($perPage);

        return response()->json($estudiantes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo_universitario' => ['required','string','max:20','unique:estudiante,codigo_universitario'],
            'id_usuario'           => ['required','integer','exists:usuario,id_usuario','unique:estudiante,id_usuario'],
            'carrera'              => ['required','string','max:100'],
            'semestre'             => ['nullable','integer','min:1'],
        ]);

        $estudiante = Estudiante::create($data);
        return response()->json($estudiante, 201);
    }

    public function show(Estudiante $estudiante)
    {
        // Route model binding por PK = codigo_universitario
        return response()->json($estudiante);
    }

    public function update(Request $request, Estudiante $estudiante)
    {
        $data = $request->validate([
            'codigo_universitario' => [
                'sometimes','string','max:20',
                Rule::unique('estudiante','codigo_universitario')
                    ->ignore($estudiante->getKey(), 'codigo_universitario'),
            ],
            'id_usuario' => [
                'sometimes','integer','exists:usuario,id_usuario',
                Rule::unique('estudiante','id_usuario')
                    ->ignore($estudiante->id_usuario, 'id_usuario'),
            ],
            'carrera'  => ['sometimes','string','max:100'],
            'semestre' => ['sometimes','nullable','integer','min:1'],
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
