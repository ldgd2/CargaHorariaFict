<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $q = Bitacora::query()
            ->when($request->user_id, fn($qq) => $qq->where('user_id', $request->user_id))
            ->when($request->entidad, fn($qq) => $qq->where('entidad', $request->entidad))
            ->when($request->accion, fn($qq) => $qq->where('accion', $request->accion))
            ->when($request->filled('desde'), fn($qq) => $qq->where('fecha_hora', '>=', $request->desde))
            ->when($request->filled('hasta'), fn($qq) => $qq->where('fecha_hora', '<=', $request->hasta));

        return $q->orderByDesc('fecha_hora')->paginate($request->integer('per_page', 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'          => ['nullable','integer','exists:usuario,id_usuario'],
            'fecha_hora'       => ['nullable','date'],
            'entidad'          => ['required','string','max:50'],
            'entidad_id'       => ['nullable','integer'],
            'accion'           => ['required','string','max:100'],
            'descripcion'      => ['nullable','string'],
            'datos_anteriores' => ['nullable','array'],
            'datos_nuevos'     => ['nullable','array'],
        ]);

        $row = Bitacora::create($data);
        return response()->json($row, 201);
    }

    public function show(Bitacora $bitacora)
    {
        return $bitacora;
    }

    public function update(Request $request, Bitacora $bitacora)
    {
        $data = $request->validate([
            'user_id'          => ['nullable','integer','exists:usuario,id_usuario'],
            'fecha_hora'       => ['nullable','date'],
            'entidad'          => ['sometimes','string','max:50'],
            'entidad_id'       => ['nullable','integer'],
            'accion'           => ['sometimes','string','max:100'],
            'descripcion'      => ['nullable','string'],
            'datos_anteriores' => ['nullable','array'],
            'datos_nuevos'     => ['nullable','array'],
        ]);

        $bitacora->update($data);
        return $bitacora;
    }

    public function destroy(Bitacora $bitacora)
    {
        $bitacora->delete();
        return response()->noContent();
    }
}
