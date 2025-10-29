<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait LogsBitacora
{
    protected function logBitacora(
        string $accion,
        string $entidad,
        ?int $entidadId,
        array $payload,
        Request $r
    ): void {
        if (!class_exists(Bitacora::class) || !Schema::hasTable('bitacora')) {
            return; // no romper si no existe
        }

        $row = [
            'accion'     => $accion,
            'detalle'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'usuario_id' => Auth::id(),
            'ip'         => $r->ip(),
        ];

        if (Schema::hasColumn('bitacora','entidad'))       $row['entidad']    = $entidad;
        if (Schema::hasColumn('bitacora','entidad_id'))    $row['entidad_id'] = $entidadId;
        if (Schema::hasColumn('bitacora','fecha_creacion'))$row['fecha_creacion'] = now();

        try { Bitacora::create($row); } catch (\Throwable $e) { /* silencioso */ }
    }
}
