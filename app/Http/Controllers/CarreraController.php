<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    public function index()
    {
        return Carrera::with('jefeDocente')->orderBy('nombre')->get();
    }

    public function show(Carrera $carrera)
    {
        return $carrera->load('jefeDocente');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:120','unique:carrera,nombre'],
            'jefe_docente_id' => ['nullable','integer','exists:docente,id_docente'],
            'habilitado' => ['boolean'],
        ]);

        $carrera = Carrera::create($data);
        return response()->json($carrera->load('jefeDocente'), 201);
    }

    public function update(Request $request, Carrera $carrera)
    {
        $data = $request->validate([
            'nombre' => ['sometimes','string','max:120', Rule::unique('carrera','nombre')->ignore($carrera->id_carrera,'id_carrera')],
            'jefe_docente_id' => ['nullable','integer','exists:docente,id_docente'],
            'habilitado' => ['sometimes','boolean'],
        ]);

        $carrera->update($data);
        return $carrera->load('jefeDocente');
    }

    public function destroy(Carrera $carrera)
    {
        $carrera->delete();
        return response()->noContent();
    }
}
