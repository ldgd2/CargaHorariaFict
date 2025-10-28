<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $materias = Materia::query()
            ->when($q, fn($qb) =>
                $qb->where('cod_materia','ilike',"%{$q}%")
                   ->orWhere('nombre','ilike',"%{$q}%"))
            ->orderBy('cod_materia')
            ->paginate($request->integer('per_page', 20));

        return response()->json($materias);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cod_materia'     => ['required','string','max:20','unique:materia,cod_materia'],
            'nombre'          => ['required','string','max:100','unique:materia,nombre'],
            'creditos'        => ['required','integer','min:0'],
            'horas_semanales' => ['nullable','integer','min:0'],
            'programa'        => ['nullable','string'],
        ]);

        $materia = Materia::create($data);
        return response()->json($materia, 201);
    }

    public function show(Materia $materia)
    {
        return response()->json($materia);
    }

    public function update(Request $request, Materia $materia)
    {
        $data = $request->validate([
            'cod_materia'     => ['sometimes','string','max:20', Rule::unique('materia','cod_materia')->ignore($materia->id_materia,'id_materia')],
            'nombre'          => ['sometimes','string','max:100', Rule::unique('materia','nombre')->ignore($materia->id_materia,'id_materia')],
            'creditos'        => ['sometimes','integer','min:0'],
            'horas_semanales' => ['sometimes','integer','min:0'],
            'programa'        => ['sometimes','nullable','string'],
        ]);

        $materia->update($data);
        return response()->json($materia);
    }

    public function destroy(Materia $materia)
    {
        $materia->delete();
        return response()->json(null, 204);
    }
}
