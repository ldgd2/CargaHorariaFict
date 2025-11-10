<?php

namespace App\Http\Controllers;

use App\Models\DisponibilidadDocente;
use App\Models\PeriodoAcademico;
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema; 

use Illuminate\Support\Facades\Log;
class DisponibilidadDocenteController extends Controller
{
    use LogsBitacora;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /** ----------------- helpers ----------------- */

    /** Id del docente a partir del usuario autenticado */
    private function logAction(string $accion, string $entidad, ?int $entidadId, array $detalle = []): void
{
    try {
        if (!class_exists(\App\Models\Bitacora::class)) return;

        \App\Models\Bitacora::create([
            'accion'         => $accion,
            'usuario_id'     => Auth::id(),         
            'ip'             => request()->ip(),
            'descripcion'    => json_encode($detalle, JSON_UNESCAPED_UNICODE),
            'entidad'        => $entidad,
            'entidad_id'     => $entidadId,
            'fecha_creacion' => now(),
        ]);
    } catch (\Throwable $e) {
        Log::warning('bitacora.fail', ['accion' => $accion, 'msg' => $e->getMessage()]);
    }
}


    private function currentDocenteId(): ?int
    {
        $userId = (int) Auth::id();
        if (Schema::hasColumn('docente', 'id_usuario')) {
            return \App\Models\Docente::where('id_usuario', $userId)
                ->value('id_docente');
        }
        $match = \App\Models\Docente::where('id_docente', $userId)->exists();
        return $match ? $userId : null;
    }
    /** Devuelve el último id_periodo válido*/
    private function pickPeriodoId(?int $fromQuery = null): ?int
    {
        if ($fromQuery) return $fromQuery;

        $q = PeriodoAcademico::query();

        $hasEstado    = Schema::hasColumn('periodo_academico','estado');
        $hasEstadoPub = Schema::hasColumn('periodo_academico','estado_publicacion');

        if ($hasEstado) {
            $q->whereIn('estado', ['EnAsignacion','Reabierto','Activo','Publicado','borrador','Borrador']);
        } elseif ($hasEstadoPub) {
            $q->whereIn('estado_publicacion', ['EnAsignacion','Reabierto','Activo','Publicado','borrador','Borrador']);
        }

        $p = $q->orderByDesc('id_periodo')->first();
        return $p?->id_periodo;
    }
    private function verificaPeriodo(int $periodoId): void
{
    $col = \Schema::hasColumn('periodo_academico','estado')
        ? 'estado'
        : (\Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);

    $p = \App\Models\PeriodoAcademico::query()
        ->selectRaw("id_periodo, ".($col ? "$col as estado" : "'Activo' as estado").", COALESCE(activo,false) as activo")
        ->where('id_periodo', $periodoId)
        ->firstOrFail();

    // Normaliza el estado
    $estado = strtolower((string) $p->estado);
    $permitidos = ['enasignacion', 'reabierto', 'activo', 'publicado', 'borrador'];
    if ($col && !in_array($estado, $permitidos, true)) {
        abort(422, 'El período no admite registrar disponibilidad.');
    }
}


    /** Regla de solape */
    private function existeSolape(
        int $docenteId, int $periodoId, int $dia, string $hIni, string $hFin, int $exceptId = null
    ): bool {
        return DisponibilidadDocente::query()
            ->where('id_docente', $docenteId)
            ->where('id_periodo', $periodoId)
            ->where('dia_semana', $dia)
            ->when($exceptId, fn($q) => $q->where('id_disponibilidad','<>',$exceptId))
            ->where(function ($q) use ($hIni, $hFin) {
                $q->where('hora_inicio', '<', $hFin)
                  ->where('hora_fin', '>', $hIni);
            })
            ->exists();
    }

    private function validaEntrada(Request $r, bool $updating=false): array
    {
        return $r->validate([
            'id_periodo'    => [$updating ? 'sometimes' : 'required','integer','exists:periodo_academico,id_periodo'],
            'dia_semana'    => [$updating ? 'sometimes' : 'required','integer','between:1,7'],
            'hora_inicio'   => [$updating ? 'sometimes' : 'required','date_format:H:i'],
            'hora_fin'      => [$updating ? 'sometimes' : 'required','date_format:H:i',
                function ($attr,$val,$fail) use ($r,$updating) {
                    $ini = $updating ? ($r->input('hora_inicio') ?? null) : $r->input('hora_inicio');
                    if ($ini && $val <= $ini) $fail('E2: hora_fin debe ser mayor que hora_inicio.');
                }
            ],
            'prioridad'     => [$updating ? 'sometimes' : 'nullable','integer','between:1,9'],
            'observaciones' => [$updating ? 'sometimes' : 'nullable','string','max:255'],
        ]);
    }

    /** ----------------- endpoints ----------------- */

    /**
     * GET /docente/mi-disponibilidad?id_periodo=#
     * Devuelve las franjas del docente autenticado en el período indicado
     * (si no se envía, toma el último válido).
     */
    public function index(Request $r)
{

    if (! $r->expectsJson()) {
        return redirect()->route('docente.disp.view');
    }

    $docenteId = $this->currentDocenteId(); 
    $periodoId = $r->query('id_periodo');

    if (!$periodoId) {
        $col = \Schema::hasColumn('periodo_academico','estado') ? 'estado' :
               (\Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);

        $periodo = \App\Models\PeriodoAcademico::query()
            ->when($col, fn($q) => $q->whereIn($col, ['EnAsignacion','Reabierto','Activo','Publicado']))
            ->orderByDesc('id_periodo')
            ->first();

        $periodoId = $periodo?->id_periodo;
    }
    if (!$periodoId) return response()->json([]);
    $r->merge(['id_periodo' => $periodoId]);
    $r->validate(['id_periodo' => ['required','integer','exists:periodo_academico,id_periodo']]);

    return \App\Models\DisponibilidadDocente::deDocentePeriodo($docenteId, (int)$periodoId)
        ->orderBy('dia_semana')->orderBy('hora_inicio')
        ->get();
}


public function storeBatch(Request $r)
{
    $docenteId = $this->currentDocenteId();
    abort_if(!$docenteId, 403, 'No eres un docente.');
    $data = $r->validate([
        'id_periodo'              => ['required','integer','exists:periodo_academico,id_periodo'],
        'items'                   => ['required','array','min:1'],
        'items.*.dia_semana'      => ['required','integer','between:1,7'],
        'items.*.hora_inicio'     => ['required','date_format:H:i'],
        'items.*.hora_fin'        => ['required','date_format:H:i'],
        'items.*.prioridad'       => ['nullable','integer','between:1,9'],
        'items.*.observaciones'   => ['nullable','string','max:255'],
    ]);

    // Reglas del período (borrador/activo/publicado/etc.)
    $this->verificaPeriodo((int)$data['id_periodo']);

    $vistasLocales = []; 
    foreach ($data['items'] as $idx => $it) {
        $ini = $it['hora_inicio'];
        $fin = $it['hora_fin'];
        if ($fin <= $ini) {
            return back()->withErrors(["items.$idx.hora_fin" => "E2: hora_fin debe ser mayor que hora_inicio."])->withInput();
        }

        $dia = (int)$it['dia_semana'];

        // solape interno (entre los nuevos)
        if (!isset($vistasLocales[$dia])) $vistasLocales[$dia] = [];
        foreach ($vistasLocales[$dia] as [$li,$lf]) {
            if ($ini < $lf && $li < $fin) {
                return back()->withErrors(["items.$idx.hora_inicio" => "E1: En el request, esta franja solapa con otra del mismo día."])->withInput();
            }
        }
        $vistasLocales[$dia][] = [$ini,$fin];

        // solape con BD
        if ($this->existeSolape($docenteId, (int)$data['id_periodo'], $dia, $ini, $fin)) {
            return back()->withErrors(["items.$idx.hora_inicio" => "E1: Solapa con una franja ya registrada para ese día."])->withInput();
        }
    }

    try {
        DB::transaction(function () use ($docenteId, $data) {
            foreach ($data['items'] as $it) {
                $nuevo = DisponibilidadDocente::create([
                    'id_docente'    => $docenteId,
                    'id_periodo'    => (int)$data['id_periodo'],
                    'dia_semana'    => (int)$it['dia_semana'],
                    'hora_inicio'   => $it['hora_inicio'],
                    'hora_fin'      => $it['hora_fin'],
                    'prioridad'     => $it['prioridad'] ?? 1,
                    'observaciones' => $it['observaciones'] ?? null,
                ]);
                $this->logAction('disp_creada_batch','disponibilidad_docente',$nuevo->id_disponibilidad,$nuevo->toArray());
            }
        });

        return back()->with('ok','Franjas guardadas correctamente.');
    } catch (\Throwable $e) {
        report($e);
        return back()->withErrors(['general'=>'E3: Error de base de datos al crear las disponibilidades.'])->withInput();
    }
}


    /** POST /docente/mi-disponibilidad */
    public function store(Request $r)
    {
        $docenteId = $this->currentDocenteId();
        abort_if(!$docenteId, 403, 'No eres un docente.');

        $data = $this->validaEntrada($r);
        $this->verificaPeriodo($data['id_periodo']);

        if ($this->existeSolape($docenteId, $data['id_periodo'], $data['dia_semana'], $data['hora_inicio'], $data['hora_fin'])) {
            return response()->json(['ok'=>false,'error'=>'E1: Solapa con otra franja registrada para ese día.'], 422);
        }

        try {
            $nuevo = null;
            DB::transaction(function () use ($docenteId, $data, &$nuevo) {
                $nuevo = DisponibilidadDocente::create([
                    'id_docente'    => $docenteId,
                    'id_periodo'    => $data['id_periodo'],
                    'dia_semana'    => $data['dia_semana'],
                    'hora_inicio'   => $data['hora_inicio'],
                    'hora_fin'      => $data['hora_fin'],
                    'prioridad'     => $data['prioridad'] ?? 1,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);
                $this->logAction('disp_creada', 'disponibilidad_docente', $nuevo->id_disponibilidad, $nuevo->toArray());
            });

            return response()->json($nuevo, 201);
        } catch (\Throwable $e) {
    \Log::error('disp.store', ['msg'=>$e->getMessage()]);
    return response()->json(['ok'=>false,'error'=>'E3: '.$e->getMessage()], 500);
}

    }

    /** PUT/PATCH /docente/mi-disponibilidad/{disponibilidad} */
    public function update(Request $r, DisponibilidadDocente $disponibilidad)
    {
        $docenteId = $this->currentDocenteId();
        abort_if(!$docenteId, 403, 'No eres un docente.');
        abort_if($disponibilidad->id_docente !== $docenteId, 403, 'No puedes modificar disponibilidades de otro docente.');

        $data = $this->validaEntrada($r, true);
        $periodo = $data['id_periodo'] ?? $disponibilidad->id_periodo;
        $dia     = $data['dia_semana'] ?? $disponibilidad->dia_semana;
        $hIni    = $data['hora_inicio'] ?? $disponibilidad->hora_inicio;
        $hFin    = $data['hora_fin'] ?? $disponibilidad->hora_fin;

        $this->verificaPeriodo((int)$periodo);

        if ($this->existeSolape($docenteId, (int)$periodo, (int)$dia, $hIni, $hFin, (int)$disponibilidad->id_disponibilidad)) {
            return response()->json(['ok'=>false,'error'=>'E1: Solapa con otra franja registrada para ese día.'], 422);
        }

        try {
            DB::transaction(function () use ($disponibilidad, $data) {
                $disponibilidad->fill($data);
                $disponibilidad->save();
                $this->logAction('disp_editada', 'disponibilidad_docente', $disponibilidad->id_disponibilidad, $data);
            });

            return $disponibilidad->fresh();
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok'=>false,'error'=>'E3: Error de base de datos al actualizar la disponibilidad.'], 500);
        }
    }

    /** DELETE /docente/mi-disponibilidad/{disponibilidad} */
    public function destroy(DisponibilidadDocente $disponibilidad)
    {
        $docenteId = $this->currentDocenteId();
        abort_if(!$docenteId, 403, 'No eres un docente.');
        abort_if($disponibilidad->id_docente !== $docenteId, 403, 'No puedes eliminar disponibilidades de otro docente.');

        try {
            DB::transaction(function () use ($disponibilidad) {
                $payload = $disponibilidad->toArray();
                $disponibilidad->delete();
                $this->logAction('disp_eliminada', 'disponibilidad_docente', $payload['id_disponibilidad'], $payload);
            });

            return response()->json(['ok'=>true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok'=>false,'error'=>'E3: Error de base de datos al eliminar la disponibilidad.'], 500);
        }
    }
}
