<?php

namespace App\Http\Controllers;

use App\Models\CargaHoraria;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CargaHorariaController extends Controller
{
    // GET /carga-horaria
    public function index(Request $request)
    {
        $q = CargaHoraria::query()
            ->with(['grupo', 'docente', 'aula']);

        if ($request->filled('id_periodo')) {
            $q->delPeriodo((int) $request->id_periodo);
        }
        if ($request->filled('id_docente')) {
            $q->delDocente((int) $request->id_docente);
        }
        if ($request->filled('id_aula')) {
            $q->delAula((int) $request->id_aula);
        }
        if ($request->filled('dia_semana')) {
            $q->delDia((int) $request->dia_semana);
        }

        return response()->json($q->orderBy('dia_semana')->orderBy('hora_inicio')->get());
    }

    // POST /carga-horaria
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Validar disponibilidad y solapes antes de insertar (mensaje amigable)
        $this->validateDisponibilidad($data);
        $this->validateSolapesAplicacion($data);

        try {
            $carga = CargaHoraria::create($data);
            return response()->json($carga->load(['grupo', 'docente', 'aula']), 201);
        } catch (QueryException $e) {
            // Capturamos violaciones de EXCLUDE (GIST)
            $msg = $this->traducirErrorExclusion($e);
            return response()->json(['message' => $msg], 422);
        }
    }

    // GET /carga-horaria/{id}
    public function show(int $id)
    {
        $carga = CargaHoraria::with(['grupo', 'docente', 'aula'])->findOrFail($id);
        return response()->json($carga);
    }

    // PUT/PATCH /carga-horaria/{id}
    public function update(Request $request, int $id)
    {
        $carga = CargaHoraria::findOrFail($id);

        $data = $this->validateData($request, updating: true);

        // Validar disponibilidad y solapes (ignorando el propio registro)
        $this->validateDisponibilidad($data);
        $this->validateSolapesAplicacion($data, $id);

        try {
            $carga->update($data);
            return response()->json($carga->refresh()->load(['grupo', 'docente', 'aula']));
        } catch (QueryException $e) {
            $msg = $this->traducirErrorExclusion($e);
            return response()->json(['message' => $msg], 422);
        }
    }

    // DELETE /carga-horaria/{id}
    public function destroy(int $id)
    {
        $carga = CargaHoraria::findOrFail($id);
        $carga->delete();
        return response()->json(['deleted' => true]);
    }

    // -------- Helpers --------

    private function validateData(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'id_grupo'    => ['required', 'integer', 'exists:grupo,id_grupo'],
            'id_docente'  => ['required', 'integer', 'exists:docente,id_docente'],
            'id_aula'     => ['required', 'integer', 'exists:aula,id_aula'],
            'dia_semana'  => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin'    => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'estado'      => ['nullable', Rule::in(['Vigente','Modificado','Anulado'])],
        ]);
    }

    /**
     * Verifica que el docente tenga al menos un bloque de disponibilidad
     * que cubra completamente el rango solicitado en el día del grupo/periodo.
     */
    private function validateDisponibilidad(array $data): void
    {
        // Recuperamos el periodo a partir del grupo
        $grupo = Grupo::findOrFail((int) $data['id_grupo']);

        $existe = DB::table('disponibilidad_docente')
            ->where('id_docente', $data['id_docente'])
            ->where('id_periodo', $grupo->id_periodo)
            ->where('dia_semana', $data['dia_semana'])
            ->where('hora_inicio', '<=', $data['hora_inicio'])
            ->where('hora_fin', '>=', $data['hora_fin'])
            ->exists();

        if (!$existe) {
            abort(response()->json([
                'message' => 'El docente no tiene disponibilidad registrada para ese día y franja en el periodo del grupo.'
            ], 422));
        }
    }

    /**
     * Validación a nivel de aplicación para dar mensajes antes de que
     * la BD bloquee por las EXCLUSION CONSTRAINT (solapes de aula/docente).
     */
    private function validateSolapesAplicacion(array $data, ?int $ignorarId = null): void
    {
        // Solapes DOCENTE misma franja/día
        $docSolapa = CargaHoraria::query()
            ->when($ignorarId, fn($q) => $q->where('id_carga', '!=', $ignorarId))
            ->where('id_docente', $data['id_docente'])
            ->where('dia_semana', $data['dia_semana'])
            ->where(function ($q) use ($data) {
                $q->where(function ($q2) use ($data) {
                    $q2->where('hora_inicio', '<', $data['hora_fin'])
                       ->where('hora_fin',   '>', $data['hora_inicio']);
                });
            })
            ->exists();

        if ($docSolapa) {
            abort(response()->json([
                'message' => 'Solape detectado: el docente ya tiene una asignación en ese día/franja.'
            ], 422));
        }

        // Solapes AULA misma franja/día
        $aulaSolapa = CargaHoraria::query()
            ->when($ignorarId, fn($q) => $q->where('id_carga', '!=', $ignorarId))
            ->where('id_aula', $data['id_aula'])
            ->where('dia_semana', $data['dia_semana'])
            ->where(function ($q) use ($data) {
                $q->where(function ($q2) use ($data) {
                    $q2->where('hora_inicio', '<', $data['hora_fin'])
                       ->where('hora_fin',   '>', $data['hora_inicio']);
                });
            })
            ->exists();

        if ($aulaSolapa) {
            abort(response()->json([
                'message' => 'Solape detectado: el aula ya está asignada en ese día/franja.'
            ], 422));
        }
    }

    private function traducirErrorExclusion(QueryException $e): string
    {
        $msg = $e->getMessage();


        if (str_contains($msg, 'ex_aula')) {
            return 'Conflicto de aula: esa franja horaria en ese día ya está ocupada.';
        }
        if (str_contains($msg, 'ex_docente')) {
            return 'Conflicto de docente: la franja se solapa con otra asignación del mismo docente.';
        }

        return 'No se pudo guardar la carga horaria (verifique conflictos y datos).';
      
    }

    
}
