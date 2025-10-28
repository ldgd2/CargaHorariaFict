<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocenteController extends Controller
{
    public function index()
    {
        return Docente::with('usuario')->orderBy('id_docente','desc')->paginate(20);
    }

    public function show(Docente $docente)
    {
        return $docente->load('usuario');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_docente' => ['required','integer','exists:usuario,id_usuario','unique:docente,id_docente'],
            'nro_documento' => ['nullable','string','max:30'],
            'tipo_contrato' => ['nullable','string','max:20'],
            'carrera_principal' => ['nullable','string','max:100'],
            'tope_horas_semana' => ['nullable','numeric','min:0'],
            'habilitado' => ['boolean'],
        ]);

        $docente = Docente::create($data);
        return response()->json($docente->load('usuario'), 201);
    }

    public function update(Request $request, Docente $docente)
    {
        $data = $request->validate([
            'nro_documento' => ['nullable','string','max:30'],
            'tipo_contrato' => ['nullable','string','max:20'],
            'carrera_principal' => ['nullable','string','max:100'],
            'tope_horas_semana' => ['nullable','numeric','min:0'],
            'habilitado' => ['sometimes','boolean'],
        ]);

        $docente->update($data);
        return $docente->load('usuario');
    }

    public function destroy(Docente $docente)
    {
        $docente->delete();
        return response()->noContent();
    }
}
