<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Models\CargaHoraria;
use App\Models\PeriodoAcademico;
use App\Models\Grupo;
use App\Models\Docente;
use App\Models\Aula;
use App\Models\DisponibilidadDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
class CargaHorariaController extends Controller
{
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



public function apiAulas()
{

    return \DB::table('aula')
        ->orderBy('nombre_aula')
        ->get([
            'id_aula',
            \DB::raw('nombre_aula AS codigo'),
            'capacidad',
            \DB::raw('habilitado AS habilitada')
        ]);
}


public function apiDisponibilidadDocente(Request $r, int $docenteId)
{
    $pid = (int) $r->query('id_periodo');
    abort_if(!$pid, 422, 'id_periodo requerido');

    $estadoCol = \Schema::hasColumn('carga_horaria', 'estado') ? 'estado' : null;

    return \App\Models\DisponibilidadDocente::query()
        ->from('disponibilidad_docente as d')
        ->where('d.id_docente', $docenteId)
        ->where('d.id_periodo', $pid)
        ->orderBy('d.dia_semana')->orderBy('d.hora_inicio')
        ->get([
            'd.id_disponibilidad',
            'd.dia_semana',
            \DB::raw("to_char(d.hora_inicio,'HH24:MI') as hora_inicio"),
            \DB::raw("to_char(d.hora_fin,'HH24:MI')    as hora_fin"),
            'd.prioridad',
            'd.observaciones',
            \DB::raw("EXISTS(
                SELECT 1
                  FROM carga_horaria ch
                 WHERE ch.id_docente = d.id_docente
                   AND ch.dia_semana = d.dia_semana
                   AND ch.hora_inicio < d.hora_fin
                   AND ch.hora_fin    > d.hora_inicio
                   " . ($estadoCol ? "AND ch.$estadoCol IN ('Vigente','Activo')" : "") . "
            ) as ocupado")
        ]);
}



    /** ------------- STORE--------------- */
    public function store(Request $r)
{
    // 1) Validación
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

    // 2) Rango del período (para cruzar con bloqueos por fecha)
    $periodoRow = PeriodoAcademico::select('fecha_inicio','fecha_fin')->find($periodo);
    abort_if(!$periodoRow, 422, 'Período inválido.');
    $perIni = Carbon::parse($periodoRow->fecha_inicio)->startOfDay();
    $perFin = Carbon::parse($periodoRow->fecha_fin)->endOfDay();

    // 3) Estado del período (si tu tabla lo maneja)
    $estadoCol = Schema::hasColumn('periodo_academico','estado') ? 'estado'
                : (Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);
    if ($estadoCol) {
        $ok = PeriodoAcademico::where('id_periodo',$periodo)
            ->whereIn($estadoCol, ['EnAsignacion','Reabierto','Activo','Publicado','publicado'])
            ->exists();
        abort_if(!$ok, 422, 'El período no permite asignación.');
    }

    // 4) Debe existir disponibilidad del docente para esa franja exacta
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

    // 5) Solapes existentes (docente y aula)
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

    // 6) Bloqueos de aula (por rango de fechas; día completo)
    if (Schema::hasTable('bloqueo_aula')) {
        $bloqueos = DB::table('bloqueo_aula')
            ->where('id_aula', $aula)
            ->whereDate('fecha_fin', '>=', $perIni->toDateString())
            ->whereDate('fecha_inicio', '<=', $perFin->toDateString())
            ->get(['fecha_inicio','fecha_fin']);

        $bloqueada = false;
        foreach ($bloqueos as $b) {
            $bIni = Carbon::parse($b->fecha_inicio)->startOfDay();
            $bFin = Carbon::parse($b->fecha_fin)->endOfDay();
            $ini = $perIni->max($bIni);
            $fin = $perFin->min($bFin);
            if (self::rangoContieneDiaSemana($ini, $fin, (int)$data['dia_semana'])) {
                $bloqueada = true; break;
            }
        }
        if ($bloqueada) {
            return back()->withErrors(['E2'=>'Aula bloqueada por rango de fechas (día completo).'])->withInput();
        }
    }

    // 7) Tope semanal
    $tope = (int) (Docente::where('id_docente',$data['id_docente'])->value('tope_horas_semana') ?? 0);
    if ($tope > 0) {
        $durNew = self::mins($data['hora_inicio'],$data['hora_fin'])/60.0;
        $durExist = (float) CargaHoraria::query()
            ->where('id_docente',$data['id_docente'])
            ->get()
            ->sum(fn($c)=> self::mins($c->hora_inicio,$c->hora_fin)/60.0);
        if ($durExist + $durNew > $tope + 1e-6) {
            return back()->withErrors(['E4'=>"Excede tope semanal del docente ({$tope} h)."])->withInput();
        }
    }

    // 8) Insert
    try {
        DB::transaction(function () use ($data) {
    $c = new CargaHoraria();
    $c->fill([
        'id_grupo'        => $data['id_grupo'],
        'id_docente'      => $data['id_docente'],
        'id_aula'         => $data['id_aula'],
        'dia_semana'      => $data['dia_semana'],
        'hora_inicio'     => $data['hora_inicio'],
        'hora_fin'        => $data['hora_fin'],
        // NO enviar start_min / end_min
        'fecha_asignacion'=> now(),
        'estado'          => 'Vigente',
    ]);
            $c->save();

            if (class_exists(\App\Models\Bitacora::class)) {
                \App\Models\Bitacora::create([
                    'accion'      => 'carga_creada',
                    'usuario_id'  => Auth::id(),
                    'ip'          => request()->ip(),
                    'descripcion' => json_encode($c->toArray(), JSON_UNESCAPED_UNICODE),
                    'entidad'     => 'carga_horaria',
                    'entidad_id'  => $c->id_carga,
                    'fecha_creacion' => now(),
                ]);
            }
        });

        return redirect()->route('coordinador.dashboard')->with('ok','Carga asignada correctamente.');
    } catch (\Throwable $e) {
        report($e);
        return back()->withErrors(['E5'=>'Error de BD'])->withInput();
    }
}


    public function storeBatch(Request $r)
{
    // 1) Validación
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

    // 2) Rango del período
    $periodoRow = PeriodoAcademico::select('fecha_inicio','fecha_fin')->find($periodo);
    abort_if(!$periodoRow, 422, 'Período inválido.');
    $perIni = Carbon::parse($periodoRow->fecha_inicio)->startOfDay();
    $perFin = Carbon::parse($periodoRow->fecha_fin)->endOfDay();

    // 3) Carga de bloqueos del aula (por fechas)
    $bloqueos = Schema::hasTable('bloqueo_aula')
        ? DB::table('bloqueo_aula')
            ->where('id_aula',$aula)
            ->whereDate('fecha_fin', '>=', $perIni->toDateString())
            ->whereDate('fecha_inicio', '<=', $perFin->toDateString())
            ->get(['fecha_inicio','fecha_fin'])
        : collect([]);

    $errores = [];
    $creados = 0;

    DB::beginTransaction();
    try {
        foreach ($base['items'] as $i => $it) {
            $dia = (int)$it['dia_semana'];
            $ini = $it['hora_inicio']; // "HH:MM"
            $fin = $it['hora_fin'];

            if ($ini >= $fin) { $errores[]="Fila #$i: fin debe ser mayor que inicio"; continue; }

            // 1) Debe existir disponibilidad
            $hayDisp = DisponibilidadDocente::query()
                ->where('id_docente', $docente)->where('id_periodo', $periodo)
                ->where('dia_semana', $dia)
                ->where('hora_inicio','<=',$ini)->where('hora_fin','>=',$fin)
                ->exists();
            if(!$hayDisp){ $errores[]="Fila #$i: docente sin disponibilidad (E3)"; continue; }

            // 2) Solapes (docente y aula)
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

            // 3) Bloqueos de aula (por fechas; día completo)
            $bloqueada = false;
            foreach ($bloqueos as $b) {
                $bIni = Carbon::parse($b->fecha_inicio)->startOfDay();
                $bFin = Carbon::parse($b->fecha_fin)->endOfDay();
                $i1 = $perIni->max($bIni);
                $i2 = $perFin->min($bFin);
                if (self::rangoContieneDiaSemana($i1, $i2, $dia)) {
                    $bloqueada = true; break;
                }
            }
            if($bloqueada){ $errores[]="Fila #$i: aula bloqueada por rango de fechas (E2)"; continue; }

            // 4) Tope semanal 
            $tope = (int) (Docente::where('id_docente',$docente)->value('tope_horas_semana') ?? 0);
            if ($tope > 0) {
                $minNuevos = self::mins($ini,$fin);
                $minAcum   = (int) DB::table('carga_horaria')
                    ->where('id_docente',$docente)
                    ->sum(DB::raw("EXTRACT(EPOCH FROM (hora_fin - hora_inicio))/60"));
                if ( ($minAcum + $minNuevos) > $tope*60 ) {
                    $errores[]="Fila #$i: excede tope semanal del docente (E4)"; continue;
                }
            }


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

        if ($e instanceof QueryException) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driver   = $e->errorInfo[1] ?? null;
            $message  = $e->errorInfo[2] ?? $e->getMessage();

            \Log::error('storeBatch error', [
                'sqlstate' => $sqlState,
                'driver'   => $driver,
                'message'  => $message,
                'sql'      => method_exists($e, 'getSql') ? $e->getSql() : null,
                'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : null,
            ]);

            return back()->withErrors([
                'E5' => "DB [$sqlState/$driver]: $message",
            ])->withInput();
        }

        \Log::error('storeBatch error', ['msg' => $e->getMessage()]);
        return back()->withErrors(['E5' => $e->getMessage()])->withInput();
    }
}




/** util: minutos entre dos horas HH:MM */
private static function mins($h1,$h2): int {
    [$h,$m]=array_map('intval',explode(':',$h1)); $a=$h*60+$m;
    [$h,$m]=array_map('intval',explode(':',$h2)); $b=$h*60+$m;
    return max(0,$b-$a);
}
/** util: HH:MM -> minutos desde 00:00 */
private static function timeToMinutes(string $hhmm): int {
    [$h,$m] = array_map('intval', explode(':',$hhmm));
    return $h*60 + $m;
}

private static function rangoContieneDiaSemana(Carbon $start, Carbon $end, int $isoDow): bool
{
    if ($end->lt($start)) return false;
    $first = $start->copy();
    $offset = ($isoDow - $first->isoWeekday() + 7) % 7; // 0..6
    if ($offset) $first->addDays($offset);
    return $first->lte($end);
}


}
