<?php

namespace App\Http\Controllers;

use App\Models\PeriodoAcademico;
use App\Models\CargaHoraria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodoAcademicoController extends Controller
{
    public function index(Request $r)
    {
        $periodos = PeriodoAcademico::orderByDesc('fecha_inicio')->paginate(12);
        return view('periodos.index', compact('periodos'));
    }

    /**
     * Devuelve los detalles de un período específico. Necesario por Route::apiResource.
     * @param PeriodoAcademico $periodo
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PeriodoAcademico $periodo)
    {
        // Dado que se usa apiResource, se asume que esta ruta es para API/JSON
        return response()->json($periodo);
    }


    public function store(Request $r)
    {
        $data = $r->validate([
            'nombre'       => ['required','string','max:120'],
            'fecha_inicio' => ['required','date'],
            'fecha_fin'    => ['required','date','after:fecha_inicio'],
        ]);

        
        if (PeriodoAcademico::haySolapamiento($data['fecha_inicio'], $data['fecha_fin'], null)) {
            return back()->withErrors(['fecha_inicio' => 'Rango solapado con otro período vigente.'])->withInput();
        }

      
        if (PeriodoAcademico::nombreUsado($data['nombre'])) {
            return back()->withErrors(['nombre' => 'Ya existe un período con ese nombre.'])->withInput();
        }

        DB::transaction(function () use ($data, $r) {
            $row = PeriodoAcademico::create([
                'nombre'              => $data['nombre'],
                'fecha_inicio'        => $data['fecha_inicio'],
                'fecha_fin'           => $data['fecha_fin'],
                'estado_publicacion'  => 'borrador', 
                'activo'              => true,       
            ]);

            $this->bit('periodo_creado', 'periodo', $row->getKey(), [
                'nuevo' => $row->only(['nombre','fecha_inicio','fecha_fin','estado_publicacion','activo'])
            ], $r);
        });

        return back()->with('ok','Período creado (borrador) y ACTIVADO.');
    }


    public function update(Request $r, PeriodoAcademico $periodo)
    {
        if (in_array($periodo->estado_publicacion, ['publicado','archivado'])) {
            return back()->withErrors(['general'=>'No se puede editar un período publicado/archivado.'])->withInput();
        }

        $data = $r->validate([
            'nombre'       => ['required','string','max:120'],
            'fecha_inicio' => ['required','date'],
            'fecha_fin'    => ['required','date','after:fecha_inicio'],
        ]);

        if (PeriodoAcademico::nombreUsado($data['nombre'], $periodo->getKey())) {
            return back()->withErrors(['nombre' => 'Ya existe un período con ese nombre.'])->withInput();
        }

        if (PeriodoAcademico::haySolapamiento($data['fecha_inicio'], $data['fecha_fin'], $periodo->getKey())) {
            return back()->withErrors(['fecha_inicio' => 'Rango solapado con otro período vigente.'])->withInput();
        }

        DB::transaction(function () use ($periodo, $data, $r) {
            $antes = $periodo->only(['nombre','fecha_inicio','fecha_fin','estado_publicacion','activo']);

            $periodo->update([
                'nombre'       => $data['nombre'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin'    => $data['fecha_fin'],
            ]);

            $this->bit('periodo_actualizado', 'periodo', $periodo->getKey(), [
                'antes'   => $antes,
                'despues' => $periodo->only(['nombre','fecha_inicio','fecha_fin','estado_publicacion','activo'])
            ], $r);
        });

        return back()->with('ok','Período actualizado.');
    }

    /**
     * Cambios de estado:
     * - 'activo'     => estado_publicacion sigue 'borrador', activo=true (valida solape)
     * - 'borrador'   => sólo si estaba en borrador, activo=false (desactivar)
     * - 'publicado'  => estado_publicacion='publicado', activo=false (valida pendientes)
     * - 'archivado'  => estado_publicacion='archivado', activo=false (valida pendientes)
     */
    public function cambiarEstado(Request $r, PeriodoAcademico $periodo)
    {
        $data = $r->validate([
            'estado' => ['required','in:activo,borrador,publicado,archivado'],
        ]);

        $target = $data['estado'];

        if ($target === 'activo') {
            if ($periodo->estado_publicacion !== 'borrador') {
                return back()->withErrors(['estado'=>'Sólo se puede activar si está en borrador.']);
            }
            if ($periodo->activo) {
                return back()->withErrors(['estado'=>'El período ya está activo.']);
            }
            // Importante: toDateString() debe usarse si fecha_inicio/fecha_fin son Carbon objects
            if (PeriodoAcademico::haySolapamiento(
                $periodo->fecha_inicio->toDateString(),
                $periodo->fecha_fin->toDateString(),
                $periodo->getKey()
            )) {
                return back()->withErrors(['estado'=>'No puede activarse: rango solapado con otro vigente.']);
            }

            DB::transaction(function () use ($periodo, $r) {
                $periodo->activo = true;
                $periodo->save();
                $this->bit('periodo_activado', 'periodo', $periodo->getKey(), [], $r);
            });
            return back()->with('ok','Período ACTIVADO.');
        }

        // Desactivar (volver a borrador inactivo)
        if ($target === 'borrador') {
            if ($periodo->estado_publicacion !== 'borrador') {
                return back()->withErrors(['estado'=>'Sólo se puede desactivar si está en borrador.']);
            }
            if (!$periodo->activo) {
                return back()->withErrors(['estado'=>'El período ya está desactivado.']);
            }

            DB::transaction(function () use ($periodo, $r) {
                $periodo->activo = false;
                $periodo->save();
                $this->bit('periodo_desactivado', 'periodo', $periodo->getKey(), [], $r);
            });
            return back()->with('ok','Período DESACTIVADO.');
        }

        // Publicar
        if ($target === 'publicado') {
            if (CargaHoraria::tieneAsignacionesAbiertas($periodo->getKey())) {
                return back()->withErrors(['estado'=>'No puede publicarse: existen asignaciones pendientes.']);
            }
            DB::transaction(function () use ($periodo, $r) {
                $periodo->estado_publicacion = 'publicado';
                $periodo->activo = false;
                $periodo->save();
                $this->bit('periodo_publicado', 'periodo', $periodo->getKey(), [], $r);
            });
            return back()->with('ok','Período PUBLICADO.');
        }

        // Archivado (Si el target no fue 'publicado', asume 'archivado' al final)
        if (CargaHoraria::tieneAsignacionesAbiertas($periodo->getKey())) {
            return back()->withErrors(['estado'=>'No puede archivarse: existen procesos abiertos.']);
        }
        DB::transaction(function () use ($periodo, $r) {
            $periodo->estado_publicacion = 'archivado';
            $periodo->activo = false;
            $periodo->save();
            $this->bit('periodo_archivado', 'periodo', $periodo->getKey(), [], $r);
        });
        return back()->with('ok','Período ARCHIVADO.');
    }

    public function reabrir(Request $r, PeriodoAcademico $periodo)
    {
        if (!in_array($periodo->estado_publicacion, ['publicado','archivado'])) {
            return back()->withErrors(['estado'=>'Sólo se puede reabrir un período publicado/archivado.']);
        }

        if (PeriodoAcademico::haySolapamiento(
            $periodo->fecha_inicio->toDateString(),
            $periodo->fecha_fin->toDateString(),
            $periodo->getKey()
        )) {
            return back()->withErrors(['estado'=>'No puede reabrirse: rango solapado con otro vigente.']);
        }

        DB::transaction(function () use ($periodo, $r) {
            $periodo->estado_publicacion = 'borrador';
            $periodo->activo = true;
            $periodo->save();
            $this->bit('periodo_reabierto', 'periodo', $periodo->getKey(), [
                'motivo' => $r->input('motivo')
            ], $r);
        });

        return back()->with('ok','Período reabierto a BORRADOR y ACTIVADO.');
    }

    /* ---------- Bitácora ----------- */
    private function bit(string $accion, string $entidad, ?int $entidadId, array $detalle, Request $r): void
    {
        try {
            if (!class_exists(\App\Models\Bitacora::class)) return;

            \App\Models\Bitacora::create([
                'accion'          => $accion,
                'usuario_id'      => Auth::id(),
                'ip'              => $r->ip(),
                'descripcion'     => json_encode($detalle, JSON_UNESCAPED_UNICODE),
                'entidad'         => $entidad,
                'entidad_id'      => $entidadId,
                'fecha_creacion'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('bitacora.fail', ['accion'=>$accion,'msg'=>$e->getMessage()]);
        }
    }

    // ✅ CORRECCIÓN FINAL: Usar Eloquent en stats() para mayor portabilidad y limpieza.
    public function stats()
    {
        // Se inicializa el query builder.
        $q = PeriodoAcademico::query();

        return response()->json([
            'total'      => $q->count(), // Count total
            // Se usa clone para aplicar filtros a una copia, evitando que se acumulen
            'borrador'   => (clone $q)->where('estado_publicacion', 'borrador')->count(),
            'publicado'  => (clone $q)->where('estado_publicacion', 'publicado')->count(),
            'archivado'  => (clone $q)->where('estado_publicacion', 'archivado')->count(),
            // Se usa el campo booleano 'activo'
            'activo'     => (clone $q)->where('activo', true)->count(), 
        ]);
    }
}