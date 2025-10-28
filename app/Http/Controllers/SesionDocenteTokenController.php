<?php

namespace App\Http\Controllers;

use App\Models\SesionDocenteToken;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SesionDocenteTokenController extends Controller
{
    public function index(Request $request)
    {
        $q = SesionDocenteToken::query()
            ->when($request->id_carga, fn($qq) => $qq->where('id_carga', $request->id_carga))
            ->when($request->fecha_sesion, fn($qq) => $qq->whereDate('fecha_sesion', $request->fecha_sesion));

        return $q->orderByDesc('id_token')->paginate($request->integer('per_page', 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_carga'      => ['required','integer','exists:carga_horaria,id_carga'],
            'fecha_sesion'  => ['required','date'],
            'token'         => ['required','string','max:64','unique:sesion_docente_token,token'],
            'vence_en'      => ['required','date'],
            'creado_en'     => ['nullable','date'],
        ]);

        $row = SesionDocenteToken::create($data);
        return response()->json($row, 201);
    }

    public function show(SesionDocenteToken $sesionDocenteToken)
    {
        return $sesionDocenteToken;
    }

    public function update(Request $request, SesionDocenteToken $sesionDocenteToken)
    {
        $data = $request->validate([
            'id_carga'      => ['sometimes','integer','exists:carga_horaria,id_carga'],
            'fecha_sesion'  => ['sometimes','date'],
            'token'         => ['sometimes','string','max:64', Rule::unique('sesion_docente_token','token')->ignore($sesionDocenteToken->id_token, 'id_token')],
            'vence_en'      => ['sometimes','date'],
            'creado_en'     => ['nullable','date'],
        ]);

        $sesionDocenteToken->update($data);
        return $sesionDocenteToken;
    }

    public function destroy(SesionDocenteToken $sesionDocenteToken)
    {
        $sesionDocenteToken->delete();
        return response()->noContent();
    }
}
