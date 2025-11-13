<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\CargaHoraria;
use App\Models\PeriodoAcademico;
use App\Models\Grupo;
use App\Models\Docente;
use App\Models\DisponibilidadDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class CargaHorariaController extends Controller
{
    /* ---------- VISTAS / APIS BÁSICAS ---------- */

    public function create(Request $r)
    {
        $periodos = PeriodoAcademico::query()
            ->select('id_periodo','nombre','fecha_inicio','fecha_fin','estado_publicacion')
            ->orderByDesc('id_periodo')->limit(25)->get();

        return view('carga.create', compact('periodos'));
    }

    public function apiPeriodos()
    {
        return PeriodoAcademico::query()
            ->select('id_periodo','nombre','fecha_inicio','fecha_fin','estado_publicacion')
            ->orderByDesc('id_periodo')->get();
    }

    public function apiGrupos(Request $r)
    {
        $pid = (int) $r->query('id_periodo');

        return Grupo::query()
            ->when($pid, fn($q) => $q->where('grupo.id_periodo', $pid))
            ->leftJoin('materia', 'materia.id_materia', '=', 'grupo.id_materia')
            ->leftJoin('carrera', 'carrera.id_carrera', '=', 'grupo.id_carrera')
            ->orderBy('grupo.nombre_grupo')
            ->get([
                'grupo.id_grupo',
                'grupo.nombre_grupo',
                'grupo.id_periodo',
                'grupo.id_materia',
                'grupo.id_carrera',
                DB::raw("COALESCE(materia.cod_materia,'')  AS cod_materia"),
                DB::raw("COALESCE(carrera.nombre,'')       AS nombre_carrera"),
            ]);
    }

    public function apiDocentes()
    {
        return Docente::query()
            ->leftJoin('usuario', 'usuario.id_usuario', '=', 'docente.id_docente')
            ->orderBy(DB::raw("COALESCE(usuario.nombre,'')"))
            ->get([
                'docente.id_docente',
                'docente.nro_documento',
                'docente.habilitado',
                'docente.tope_horas_semana',
                DB::raw("COALESCE(usuario.nombre,'')   AS nombre"),
                DB::raw("COALESCE(usuario.apellido,'') AS apellido"),
                DB::raw("(COALESCE(usuario.nombre,'') || ' ' || COALESCE(usuario.apellido,'')) AS nombre_completo"),
            ]);
    }

    public function apiAulas(Request $r)
    {
        $pid = (int) $r->query('id_periodo');
        $per = $pid ? PeriodoAcademico::select('fecha_inicio','fecha_fin')->find($pid) : null;

        $ini = $per ? Carbon::parse($per->fecha_inicio)->startOfDay()->toDateString() : null;
        $fin = $per ? Carbon::parse($per->fecha_fin)->endOfDay()->toDateString() : null;

        $cols = [
            'aula.id_aula',
            DB::raw('aula.nombre_aula AS codigo'),
            'aula.capacidad',
            DB::raw('aula.habilitado AS habilitada'),
        ];

        if ($per && Schema::hasTable('bloqueo_aula')) {
            $cols[] = DB::raw("
                EXISTS (
                    SELECT 1 FROM bloqueo_aula b
                     WHERE b.id_aula = aula.id_aula
                       AND b.fecha_fin    >= DATE '{$ini}'
                       AND b.fecha_inicio <= DATE '{$fin}'
                ) AS ocupada
            ");
        } else {
            $cols[] = DB::raw('false AS ocupada');
        }

        $aulas = DB::table('aula')->orderBy('nombre_aula')->get($cols);

        foreach ($aulas as $a) {
            if (!empty($a->ocupada)) {
                $a->codigo = $a->codigo.' (ocupada)';
            }
        }
        return $aulas;
    }

    public function apiDisponibilidadDocente(Request $r, int $docenteId)
    {
        $pid    = (int) $r->query('id_periodo');
        $aulaId = (int) $r->query('id_aula'); // opcional
        abort_if(!$pid, 422, 'id_periodo requerido');

        $estadoCH = Schema::hasColumn('carga_horaria','estado') ? 'estado' : null;

        $cols = [
            'd.id_disponibilidad',
            'd.dia_semana',
            DB::raw("to_char(d.hora_inicio,'HH24:MI') as hora_inicio"),
            DB::raw("to_char(d.hora_fin,'HH24:MI')    as hora_fin"),
            'd.prioridad',
            'd.observaciones',
            DB::raw("EXISTS(
                SELECT 1 FROM carga_horaria ch
                WHERE ch.id_docente = d.id_docente
                  AND ch.dia_semana = d.dia_semana
                  AND ch.hora_inicio < d.hora_fin
                  AND ch.hora_fin    > d.hora_inicio
                  ".($estadoCH ? "AND ch.$estadoCH IN ('Vigente','Activo')" : "")."
            ) as ocupado"),
        ];

        if ($aulaId) {
            $cols[] = DB::raw("EXISTS(
                SELECT 1 FROM carga_horaria ch2
                WHERE ch2.id_aula = {$aulaId}
                  AND ch2.dia_semana = d.dia_semana
                  AND ch2.hora_inicio < d.hora_fin
                  AND ch2.hora_fin    > d.hora_inicio
                  AND ch2.id_docente <> d.id_docente
                  ".($estadoCH ? "AND ch2.$estadoCH IN ('Vigente','Activo')" : "")."
            ) as ocupado_aula");

            $cols[] = DB::raw("(
                SELECT (COALESCE(u.nombre,'')||' '||COALESCE(u.apellido,'')) || ' — ' || COALESCE(g.nombre_grupo,'')
                FROM carga_horaria chx
                LEFT JOIN docente dd ON dd.id_docente = chx.id_docente
                LEFT JOIN usuario u  ON u.id_usuario = dd.id_docente
                LEFT JOIN grupo   g  ON g.id_grupo = chx.id_grupo
                WHERE chx.id_aula = {$aulaId}
                  AND chx.dia_semana = d.dia_semana
                  AND chx.hora_inicio < d.hora_fin
                  AND chx.hora_fin    > d.hora_inicio
                  AND chx.id_docente <> d.id_docente
                  ".($estadoCH ? "AND chx.$estadoCH IN ('Vigente','Activo')" : "")."
                ORDER BY chx.hora_inicio
                LIMIT 1
            ) as conflicto_con");
        } else {
            $cols[] = DB::raw('false as ocupado_aula');
            $cols[] = DB::raw('NULL::text as conflicto_con');
        }

        return DisponibilidadDocente::query()
            ->from('disponibilidad_docente as d')
            ->where('d.id_docente', $docenteId)
            ->where('d.id_periodo', $pid)
            ->orderBy('d.dia_semana')->orderBy('d.hora_inicio')
            ->get($cols);
    }

    public function apiAulaOcupaciones(Request $r)
    {
        $pid    = (int) $r->query('id_periodo');
        $aulaId = (int) $r->query('id_aula');
        abort_if(!$pid || !$aulaId, 422, 'id_periodo e id_aula requeridos');

        $estadoCH = Schema::hasColumn('carga_horaria','estado') ? 'estado' : null;

        return DB::table('carga_horaria as ch')
            ->join('grupo as g','g.id_grupo','=','ch.id_grupo')
            ->leftJoin('docente as d','d.id_docente','=','ch.id_docente')
            ->leftJoin('usuario as u','u.id_usuario','=','d.id_docente')
            ->where('ch.id_aula',$aulaId)
            ->where('g.id_periodo',$pid)
            ->when($estadoCH, fn($q)=>$q->whereIn("ch.$estadoCH",['Vigente','Activo']))
            ->orderBy('ch.dia_semana')->orderBy('ch.hora_inicio')
            ->get([
                'ch.dia_semana',
                DB::raw("to_char(ch.hora_inicio,'HH24:MI') as hora_inicio"),
                DB::raw("to_char(ch.hora_fin,'HH24:MI')    as hora_fin"),
                DB::raw("(COALESCE(u.nombre,'')||' '||COALESCE(u.apellido,'')) as docente"),
                'g.nombre_grupo as grupo',
            ]);
    }

    /* ---------- STORE (una carga) ---------- */

    public function store(Request $r)
    {
        $data = $r->validate([
            'id_periodo'   => ['required','integer','exists:periodo_academico,id_periodo'],
            'id_grupo'     => ['required','integer','exists:grupo,id_grupo'],
            'id_docente'   => ['required','integer','exists:docente,id_docente'],
            'id_aula'      => ['required','integer','exists:aula,id_aula'],
            'dia_semana'   => ['required','integer','between:1,7'],
            'hora_inicio'  => ['required','date_format:H:i'],
            'hora_fin'     => ['required','date_format:H:i','after:hora_inicio'],
            'observaciones'=> ['nullable','string','max:255'],
        ]);

        $periodo = (int)$data['id_periodo'];
        $aula    = (int)$data['id_aula'];

        $periodoRow = PeriodoAcademico::select('fecha_inicio','fecha_fin')->find($periodo);
        abort_if(!$periodoRow, 422, 'Período inválido.');
        $perIni = Carbon::parse($periodoRow->fecha_inicio)->startOfDay();
        $perFin = Carbon::parse($periodoRow->fecha_fin)->endOfDay();

        $estadoCol = Schema::hasColumn('periodo_academico','estado') ? 'estado'
                  : (Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);
        if ($estadoCol) {
            $ok = PeriodoAcademico::where('id_periodo',$periodo)
                ->whereIn($estadoCol, ['EnAsignacion','Reabierto','Activo','Publicado','publicado'])
                ->exists();
            abort_if(!$ok, 422, 'El período no permite asignación.');
        }

        $tieneDisp = DisponibilidadDocente::query()
            ->where('id_docente',$data['id_docente'])
            ->where('id_periodo',$periodo)
            ->where('dia_semana',$data['dia_semana'])
            ->where('hora_inicio','<=',$data['hora_inicio'])
            ->where('hora_fin','>=',$data['hora_fin'])
            ->exists();
        if (!$tieneDisp) {
            return back()->withErrors(['E3'=>'Docente sin disponibilidad en esa franja.'])->withInput();
        }

        $solapeDoc = CargaHoraria::query()
            ->where('id_docente',$data['id_docente'])
            ->where('dia_semana',$data['dia_semana'])
            ->where(function($q) use ($data){
                $q->where('hora_inicio','<',$data['hora_fin'])
                  ->where('hora_fin','>',$data['hora_inicio']);
            })->exists();
        if ($solapeDoc) {
            return back()->withErrors(['E1'=>'Solape de docente con otra carga.'])->withInput();
        }

        $solapeAula = CargaHoraria::query()
            ->where('id_aula',$aula)
            ->where('dia_semana',$data['dia_semana'])
            ->where(function($q) use ($data){
                $q->where('hora_inicio','<',$data['hora_fin'])
                  ->where('hora_fin','>',$data['hora_inicio']);
            })->exists();
        if ($solapeAula) {
            return back()->withErrors(['E2'=>'Aula ocupada en esa franja.'])->withInput();
        }

        if (Schema::hasTable('bloqueo_aula')) {
            $hayBloqueo = DB::table('bloqueo_aula')
                ->where('id_aula', $aula)
                ->whereDate('fecha_fin', '>=', $perIni->toDateString())
                ->whereDate('fecha_inicio', '<=', $perFin->toDateString())
                ->exists();
            if ($hayBloqueo) {
                return back()->withErrors(['E2' => 'Aula bloqueada en el período seleccionado.'])->withInput();
            }
        }

        $tope = (int) (Docente::where('id_docente',$data['id_docente'])->value('tope_horas_semana') ?? 0);
        if ($tope > 0) {
            $durNew = self::mins($data['hora_inicio'],$data['hora_fin'])/60.0;
            $durExist = (float) DB::table('carga_horaria')
                ->where('id_docente',$data['id_docente'])
                ->sum(DB::raw("EXTRACT(EPOCH FROM (hora_fin - hora_inicio))/3600.0"));
            if ($durExist + $durNew > $tope + 1e-6) {
                return back()->withErrors(['E4'=>"Excede tope semanal del docente ({$tope} h)."])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($data, $aula, $perIni, $perFin) {
                $c = new CargaHoraria();
                $c->fill([
                    'id_grupo'        => $data['id_grupo'],
                    'id_docente'      => $data['id_docente'],
                    'id_aula'         => $aula,
                    'dia_semana'      => $data['dia_semana'],
                    'hora_inicio'     => $data['hora_inicio'],
                    'hora_fin'        => $data['hora_fin'],
                    'fecha_asignacion'=> now(),
                    'estado'          => 'Vigente',
                ]);
                $c->save();
            });

            return redirect()->route('coordinador.dashboard')->with('ok','Carga asignada correctamente.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['E5'=>'Error de BD'])->withInput();
        }
    }

    /* ---------- STORE BATCH ---------- */

    public function storeBatch(Request $r)
    {
        $base = $r->validate([
            'id_periodo' => ['required','integer','exists:periodo_academico,id_periodo'],
            'id_grupo'   => ['required','integer','exists:grupo,id_grupo'],
            'id_docente' => ['required','integer','exists:docente,id_docente'],
            'id_aula'    => ['required','integer','exists:aula,id_aula'],
            'observaciones' => ['nullable','string','max:255'],
            'items'      => ['required','array','min:1'],
            'items.*.dia_semana'  => ['required','integer','between:1,7'],
            'items.*.hora_inicio' => ['required','date_format:H:i'],
            'items.*.hora_fin'    => ['required','date_format:H:i'],
        ]);

        $periodo = (int)$base['id_periodo'];
        $grupo   = (int)$base['id_grupo'];
        $docente = (int)$base['id_docente'];
        $aula    = (int)$base['id_aula'];

        $periodoRow = PeriodoAcademico::select('fecha_inicio','fecha_fin')->find($periodo);
        abort_if(!$periodoRow, 422, 'Período inválido.');
        $perIni = Carbon::parse($periodoRow->fecha_inicio)->startOfDay();
        $perFin = Carbon::parse($periodoRow->fecha_fin)->endOfDay();

        if (Schema::hasTable('bloqueo_aula')) {
            $hayBloqueo = DB::table('bloqueo_aula')
                ->where('id_aula', $aula)
                ->whereDate('fecha_fin', '>=', $perIni->toDateString())
                ->whereDate('fecha_inicio', '<=', $perFin->toDateString())
                ->exists();
            if ($hayBloqueo) {
                return back()->withErrors(['E2'=>'Aula bloqueada en el período seleccionado.'])->withInput();
            }
        }

        $errores = [];
        $creados = 0;

        DB::beginTransaction();
        try {
            foreach ($base['items'] as $i => $it) {
                $dia = (int)$it['dia_semana'];
                $ini = $it['hora_inicio'];
                $fin = $it['hora_fin'];

                if ($ini >= $fin) { $errores[]="Fila #$i: fin debe ser mayor que inicio"; continue; }

                $hayDisp = DisponibilidadDocente::query()
                    ->where('id_docente',$docente)->where('id_periodo',$periodo)
                    ->where('dia_semana',$dia)
                    ->where('hora_inicio','<=',$ini)->where('hora_fin','>=',$fin)
                    ->exists();
                if(!$hayDisp){ $errores[]="Fila #$i: docente sin disponibilidad (E3)"; continue; }

                $solapeDoc = DB::table('carga_horaria')
                    ->where('id_docente',$docente)->where('dia_semana',$dia)
                    ->where('hora_inicio','<',$fin)->where('hora_fin','>',$ini)
                    ->exists();
                if($solapeDoc){ $errores[]="Fila #$i: solape con otra carga del docente (E1)"; continue; }

                $solapeAula = DB::table('carga_horaria')
                    ->where('id_aula',$aula)->where('dia_semana',$dia)
                    ->where('hora_inicio','<',$fin)->where('hora_fin','>',$ini)
                    ->exists();
                if($solapeAula){ $errores[]="Fila #$i: aula ocupada / solape (E2)"; continue; }

                DB::table('carga_horaria')->insert([
                    'id_grupo'        => $grupo,
                    'id_docente'      => $docente,
                    'id_aula'         => $aula,
                    'dia_semana'      => $dia,
                    'hora_inicio'     => $ini,
                    'hora_fin'        => $fin,
                    'fecha_asignacion'=> now(),
                    'estado'          => 'Vigente',
                ]);
                $creados++;
            }

            if ($creados === 0) {
                DB::rollBack();
                return back()->withErrors($errores ?: ['No se pudo crear ninguna carga.'])->withInput();
            }

            DB::commit();
            return redirect()->route('coordinador.dashboard')
                ->with('ok', "Cargas creadas: $creados" . (count($errores) ? ' | Con advertencias: '.implode(' · ', $errores) : ''));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('storeBatch error', ['msg'=>$e->getMessage()]);
            return back()->withErrors(['E5' => $e->getMessage()])->withInput();
        }
    }

    /* ---------- HELPERS ---------- */

    /** Minutos entre dos horas HH:MM */
    private static function mins(string $h1, string $h2): int
    {
        [$h,$m]   = array_map('intval', explode(':',$h1));
        [$hB,$mB] = array_map('intval', explode(':',$h2));
        $a = $h*60 + $m;
        $b = $hB*60 + $mB;
        return max(0, $b - $a);
    }

    /** HH:MM -> minutos desde 00:00 */
    private static function timeToMinutes(string $hhmm): int
    {
        [$h,$m] = array_map('intval', explode(':',$hhmm));
        return $h*60 + $m;
    }

    private static function rangoContieneDiaSemana(Carbon $start, Carbon $end, int $isoDow): bool
    {
        if ($end->lt($start)) return false;
        $first  = $start->copy();
        $offset = ($isoDow - $first->isoWeekday() + 7) % 7;
        if ($offset) $first->addDays($offset);
        return $first->lte($end);
    }

    public function editor(Request $r)
    {
        $periodos = PeriodoAcademico::select('id_periodo','nombre','fecha_inicio','fecha_fin','estado_publicacion')
            ->orderByDesc('id_periodo')->limit(50)->get();

        // Estos endpoints ya existen en tu controller actual:
        $gridUrl   = route('cargas.grid');     // GET
        $checkUrl  = route('cargas.check');    // POST
        $dragUrl   = route('cargas.drag');     // PATCH
        $docUrl    = url('/api/docentes');     // ajusta si expusiste apiDocentes con otra ruta
        $aulaUrl   = url('/api/aulas');        // ajusta si expusiste apiAulas con otra ruta
        
        return view('carga.editor-semanal', compact('periodos','gridUrl','checkUrl','dragUrl','docUrl','aulaUrl'));
    }
    public function apiValidateSlot(Request $r)
{
    $data = $r->validate([
        'id_periodo'  => ['required','integer','exists:periodo_academico,id_periodo'],
        'id_carga'    => ['nullable','integer','exists:carga_horaria,id_carga_horaria'],
        'id_grupo'    => ['nullable','integer','exists:grupo,id_grupo'],
        'id_docente'  => ['required','integer','exists:docente,id_docente'],
        'id_aula'     => ['required','integer','exists:aula,id_aula'],
        'dia_semana'  => ['required','integer','between:1,7'],
        'hora_inicio' => ['required','date_format:H:i'],
        'hora_fin'    => ['required','date_format:H:i','after:hora_inicio'],
    ]);

    $resultado = $this->checkSlotConflicts($data, /*excludeId:*/ $data['id_carga'] ?? null);
    return response()->json($resultado, $resultado['ok'] ? 200 : 200); // siempre 200; el front pinta por ok/errores
}
public function dragUpdate(Request $r)
{
    $data = $r->validate([
        'id_carga'    => ['required','integer','exists:carga_horaria,id_carga_horaria'],
        'id_periodo'  => ['required','integer','exists:periodo_academico,id_periodo'],
        'id_grupo'    => ['required','integer','exists:grupo,id_grupo'],
        'id_docente'  => ['required','integer','exists:docente,id_docente'],
        'id_aula'     => ['required','integer','exists:aula,id_aula'],
        'dia_semana'  => ['required','integer','between:1,7'],
        'hora_inicio' => ['required','date_format:H:i'],
        'hora_fin'    => ['required','date_format:H:i','after:hora_inicio'],
    ]);

    // Estado del período
    $estadoCol = Schema::hasColumn('periodo_academico','estado') ? 'estado'
              : (Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);
    if ($estadoCol) {
        $ok = PeriodoAcademico::where('id_periodo',$data['id_periodo'])
            ->whereIn($estadoCol, ['EnAsignacion','Reabierto','Activo','Publicado','publicado'])
            ->exists();
        abort_if(!$ok, 422, 'El período no permite asignación.');
    }

    // Validaciones de conflicto
    $res = $this->checkSlotConflicts($data, $data['id_carga']);
    if (!$res['ok']) {
        return response()->json($res, 422);
    }

    // Guardar
    $affected = DB::table('carga_horaria')
        ->where('id_carga_horaria',$data['id_carga'])
        ->update([
            'id_grupo'    => $data['id_grupo'],
            'id_docente'  => $data['id_docente'],
            'id_aula'     => $data['id_aula'],
            'dia_semana'  => $data['dia_semana'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin'    => $data['hora_fin'],
        ]);

    return response()->json([
        'ok'      => true,
        'updated' => (bool)$affected,
        'msg'     => 'Actualizado',
    ]);
}

private function checkSlotConflicts(array $d, ?int $excludeId = null): array
{
    $errores    = [];
    $avisos     = [];
    $conflictos = ['docente'=>false,'aula'=>false,'disponibilidad'=>false,'bloqueo'=>false];

    // Período para filtrar bloqueos por rango (solo lectura)
    $perRow = PeriodoAcademico::select('fecha_inicio','fecha_fin')->find((int)$d['id_periodo']);
    $perIni = $perRow ? Carbon::parse($perRow->fecha_inicio)->startOfDay() : null;
    $perFin = $perRow ? Carbon::parse($perRow->fecha_fin)->endOfDay()   : null;

    // 1) Disponibilidad docente exacta
    $tieneDisp = DisponibilidadDocente::query()
        ->where('id_docente', (int)$d['id_docente'])
        ->where('id_periodo',(int)$d['id_periodo'])
        ->where('dia_semana',(int)$d['dia_semana'])
        ->where('hora_inicio','<=',$d['hora_inicio'])
        ->where('hora_fin','>=',$d['hora_fin'])
        ->exists();
    if (!$tieneDisp) {
        $conflictos['disponibilidad']=true;
        $avisos[] = 'Fuera de disponibilidad del docente (E3).';
    }

    // 2) Solape de docente (excluir self si aplica)
    $qDoc = DB::table('carga_horaria')
        ->where('id_docente',(int)$d['id_docente'])
        ->where('dia_semana',(int)$d['dia_semana'])
        ->where('hora_inicio','<',$d['hora_fin'])
        ->where('hora_fin','>',$d['hora_inicio']);
    if ($excludeId) $qDoc->where('id_carga_horaria','<>',$excludeId);
    if ($qDoc->exists()) {
        $conflictos['docente']=true;
        $errores[] = 'Solape de docente con otra carga (E1).';
    }

    // 3) Solape de aula (excluir self si aplica)
    $qAula = DB::table('carga_horaria')
        ->where('id_aula',(int)$d['id_aula'])
        ->where('dia_semana',(int)$d['dia_semana'])
        ->where('hora_inicio','<',$d['hora_fin'])
        ->where('hora_fin','>',$d['hora_inicio']);
    if ($excludeId) $qAula->where('id_carga_horaria','<>',$excludeId);
    if ($qAula->exists()) {
        $conflictos['aula']=true;
        $errores[] = 'Aula ocupada en esa franja (E2).';
    }

    // 4) Bloqueo de aula (solo lectura; si tienes la tabla y período)
    if ($perIni && $perFin && Schema::hasTable('bloqueo_aula')) {
        $hayBloqueo = DB::table('bloqueo_aula')
            ->where('id_aula',(int)$d['id_aula'])
            ->whereDate('fecha_fin','>=',$perIni->toDateString())
            ->whereDate('fecha_inicio','<=',$perFin->toDateString())
            ->exists();
        if ($hayBloqueo) {
            $conflictos['bloqueo']=true;
            $errores[] = 'Aula bloqueada en el período seleccionado.';
        }
    }

    // ok = no errores críticos; avisos (p.ej., disponibilidad) no bloquean si así lo decides.
    $ok = empty($errores);
    return compact('ok','errores','avisos','conflictos');
}




}
