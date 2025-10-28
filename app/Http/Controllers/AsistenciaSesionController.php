<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaSesion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class AsistenciaSesionController extends Controller
{
    public function index(Request $request)
    {
        $q = AsistenciaSesion::query();

        if ($request->filled('id_carga')) {
            $q->where('id_carga', $request->id_carga);
        }
        if ($request->filled('desde')) {
            $q->whereDate('fecha_sesion', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $q->whereDate('fecha_sesion', '<=', $request->hasta);
        }
        if ($request->filled('estado')) {
            $q->where('estado', $request->estado);
        }

        return response()->json($q->orderBy('fecha_sesion','desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_carga'       => ['required','integer'],
            'fecha_sesion'   => ['required','date'],
            'hora_registro'  => ['nullable','date_format:H:i'],
            'tipo_registro'  => ['required', Rule::in(['QR','Manual'])],
            'registrado_por' => ['nullable','integer'],
            'estado'         => ['nullable', Rule::in(['Presente','Manual Validado','Pendiente','Anulado'])],
            'motivo'         => ['nullable','string'],
        ]);

        try {
            $row = AsistenciaSesion::create($data);
            return response()->json($row, 201);
        } catch (QueryException $e) {
            // Unicidad (id_carga, fecha_sesion) o FK inválida
            return response()->json([
                'message' => 'No se pudo registrar la asistencia (posible duplicado o FK inválida).',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        $row = AsistenciaSesion::find($id);
        if (!$row) return response()->json(['message'=>'No encontrado'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = AsistenciaSesion::find($id);
        if (!$row) return response()->json(['message'=>'No encontrado'], 404);

        $data = $request->validate([
            'hora_registro'  => ['sometimes','date_format:H:i'],
            'tipo_registro'  => ['sometimes', Rule::in(['QR','Manual'])],
            'registrado_por' => ['nullable','integer'],
            'estado'         => ['sometimes', Rule::in(['Presente','Manual Validado','Pendiente','Anulado'])],
            'motivo'         => ['nullable','string'],
        ]);

        try {
            $row->update($data);
            return response()->json($row);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error al actualizar.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $row = AsistenciaSesion::find($id);
        if (!$row) return response()->json(['message'=>'No encontrado'], 404);

        $row->delete();
        return response()->json(['message'=>'Eliminado']);
    }
}
