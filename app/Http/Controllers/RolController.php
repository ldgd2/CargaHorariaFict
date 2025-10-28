<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    public function index()
    {
        return Rol::orderBy('nombre_rol')->get();
    }

    public function show(Rol $rol)
    {
        return $rol;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_rol' => ['required','string','max:50','unique:rol,nombre_rol'],
            'descripcion' => ['nullable','string'],
            'habilitado' => ['boolean'],
        ]);

        $rol = Rol::create($data);
        return response()->json($rol, 201);
    }

    public function update(Request $request, Rol $rol)
    {
        $data = $request->validate([
            'nombre_rol' => [
                'sometimes','string','max:50',
                Rule::unique('rol','nombre_rol')->ignore($rol->id_rol,'id_rol')
            ],
            'descripcion' => ['nullable','string'],
            'habilitado' => ['sometimes','boolean'],
        ]);

        $rol->update($data);
        return $rol;
    }

    public function destroy(Rol $rol)
    {
        $rol->delete();
        return response()->noContent();
    }
}
