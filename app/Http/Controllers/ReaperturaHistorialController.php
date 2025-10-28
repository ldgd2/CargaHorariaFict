<?php

namespace App\Http\Controllers;

use App\Models\ReaperturaHistorial;
use Illuminate\Http\Request;

class ReaperturaHistorialController extends Controller
{
    public function index(Request $request)
    {
        $q = ReaperturaHistorial::query()
            ->when($request->id_periodo, fn($qq) => $qq->where('id_periodo', $request->id_periodo));

        return $q->orderByDesc('fecha_hora')->paginate($request->integer('per_page', 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_periodo'    => ['required','integer','exists:periodo_academico,id_periodo'],
            'fecha_hora'    => ['nullable','date'],
            'motivo'        => ['required','string'],
            'autorizado_por'=> ['nullable','integer','exists:usuario,id_usuario'],
        ]);

        $row = ReaperturaHistorial::create($data);
        return response()->json($row, 201);
    }

    public function show(ReaperturaHistorial $reaperturaHistorial)
    {
        return $reaperturaHistorial;
    }

    public function update(Request $request, ReaperturaHistorial $reaperturaHistorial)
    {
        $data = $request->validate([
            'id_periodo'    => ['sometimes','integer','exists:periodo_academico,id_periodo'],
            'fecha_hora'    => ['nullable','date'],
            'motivo'        => ['sometimes','string'],
            'autorizado_por'=> ['nullable','integer','exists:usuario,id_usuario'],
        ]);

        $reaperturaHistorial->update($data);
        return $reaperturaHistorial;
    }

    public function destroy(ReaperturaHistorial $reaperturaHistorial)
    {
        $reaperturaHistorial->delete();
        return response()->noContent();
    }
}
