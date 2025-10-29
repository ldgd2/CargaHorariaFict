<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Models\Bitacora;


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

    private function bitAudit(Request $r, string $accion, string $entidad, ?int $entidadId, array $meta = [], ?array $old = null, ?array $new = null): void
{
    try {
        if (!class_exists(\App\Models\Bitacora::class) || !Schema::hasTable('bitacora')) {
            Log::info('bitacora.skipped', ['accion'=>$accion, 'motivo'=>'sin modelo/tabla']);
            return;
        }

        $row = [];

        // Requeridos en tu controller
        if (Schema::hasColumn('bitacora','accion'))      $row['accion'] = $accion;
        if (Schema::hasColumn('bitacora','entidad'))     $row['entidad'] = $entidad;              // <- evita el "Falta entidad"
        if (Schema::hasColumn('bitacora','entidad_id'))  $row['entidad_id'] = $entidadId;

        // Usuario (según nombre de columna)
        $uid = Auth::id();
        if (Schema::hasColumn('bitacora','user_id'))     $row['user_id'] = $uid;
        if (Schema::hasColumn('bitacora','usuario_id'))  $row['usuario_id'] = $uid;

        // IP si existe
        if (Schema::hasColumn('bitacora','ip'))          $row['ip'] = $r->ip();

        // Fecha según columna disponible
        $now = now();
        if (Schema::hasColumn('bitacora','fecha_hora'))       $row['fecha_hora'] = $now;
        elseif (Schema::hasColumn('bitacora','fecha_creacion')) $row['fecha_creacion'] = $now;
        // (Si usas timestamps, Eloquent llenará created_at)

        // Texto sencillo
        if (Schema::hasColumn('bitacora','descripcion')) $row['descripcion'] = $meta['descripcion'] ?? null;

        // JSONs según tu esquema real
        if (Schema::hasColumn('bitacora','datos_anteriores')) $row['datos_anteriores'] = $old;
        if (Schema::hasColumn('bitacora','datos_nuevos'))     $row['datos_nuevos']     = $new;

        // Por compatibilidad con otros proyectos (si existiera 'detalle')
        if (Schema::hasColumn('bitacora','detalle'))     $row['detalle'] = json_encode($meta, JSON_UNESCAPED_UNICODE);

        \App\Models\Bitacora::create($row);
        Log::info('bitacora.ok', ['accion'=>$accion, 'entidad'=>$entidad, 'entidad_id'=>$entidadId]);
    } catch (\Throwable $e) {
        Log::warning('bitacora.fail', [
            'accion'=>$accion, 'entidad'=>$entidad, 'entidad_id'=>$entidadId,
            'msg'=>$e->getMessage()
        ]);
        // Nunca romper el flujo por bitácora
    }
}
}
