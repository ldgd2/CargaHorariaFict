<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\PeriodoAcademico;
use App\Models\CargaHoraria;
use App\Models\DisponibilidadDocente;

class EditorSemanalController extends Controller
{
    /** Vista del editor (CU13) */
    public function editor(Request $r)
    {
        // Períodos “relevantes”: últimos 12 + activos
        $periodos = PeriodoAcademico::query()
            ->select('id_periodo','nombre','fecha_inicio','fecha_fin','estado_publicacion')
            ->orderByDesc('id_periodo')->limit(12)->get();

        // Opcional: listar docentes y aulas para filtros
        $docentes = DB::table('docente as d')
            ->leftJoin('usuario as u','u.id_usuario','=','d.id_docente')
            ->orderBy(DB::raw("COALESCE(u.nombre,'')"))
            ->get([
                'd.id_docente',
                DB::raw("(COALESCE(u.nombre,'')||' '||COALESCE(u.apellido,'')) as nombre_completo"),
            ]);

        $aulas = DB::table('aula')->orderBy('nombre_aula')
            ->get(['id_aula', DB::raw('aula.nombre_aula as codigo')]);

        // materias y carreras para filtros en la vista
    $carreras = DB::table('carrera')->orderBy('nombre')->get(['id_carrera','nombre']);
    // materia does not have id_carrera directly; map via materia_carrera
    $materias = DB::table('materia')->orderBy('nombre')->get(['id_materia','nombre']);
    $mc = DB::table('materia_carrera')->get(['id_materia','id_carrera']);
    $mcMap = [];
    foreach($mc as $r) { if (!isset($mcMap[$r->id_materia])) $mcMap[$r->id_materia] = $r->id_carrera; }
    // attach first carrera id (or null) to each materia for client-side filtering
    $materias = $materias->map(function($m) use ($mcMap){ $m->id_carrera = $mcMap[$m->id_materia] ?? null; return $m; });

        return view('carga.editor-semanal', compact('periodos','docentes','aulas','carreras','materias'));
    }

    /** Datos para el grid (NO requiere semana). Filtra por periodo (+docente/+aula opcional). */
    public function apiGridWeek(Request $r)
{
    $pid = (int) $r->query('id_periodo');
    abort_if(!$pid, 422, 'id_periodo requerido');

    $doc = (int) $r->query('id_docente');
    $aul = (int) $r->query('id_aula');

    // Cargas existentes (por día_semana y HH:MM)
    $cargas = DB::table('carga_horaria as ch')
        ->join('grupo as g','g.id_grupo','=','ch.id_grupo')
        ->leftJoin('aula  as a','a.id_aula','=','ch.id_aula')
        ->leftJoin('docente as d','d.id_docente','=','ch.id_docente')
        ->leftJoin('usuario as u','u.id_usuario','=','d.id_docente')
        ->where('g.id_periodo',$pid)
        ->when($doc, fn($q)=>$q->where('ch.id_docente',$doc))
        ->when($aul, fn($q)=>$q->where('ch.id_aula',$aul))
        ->orderBy('ch.dia_semana')->orderBy('ch.hora_inicio')
        ->get([
            DB::raw('ch.id_carga as id'),               // <-- CAMBIO
            'ch.dia_semana',
            DB::raw("to_char(ch.hora_inicio,'HH24:MI') as hora_inicio"),
            DB::raw("to_char(ch.hora_fin,'HH24:MI')    as hora_fin"),
            'ch.id_docente','ch.id_aula','g.id_grupo',
            DB::raw("COALESCE(u.nombre,'')||' '||COALESCE(u.apellido,'') as docente"),
            'g.nombre_grupo as grupo',
            DB::raw("COALESCE(a.nombre_aula,'') as aula"),
        ]);

    // Disponibilidad (para capa visual)
    $disps = DB::table('disponibilidad_docente as d')
        ->when($doc, fn($q)=>$q->where('d.id_docente',$doc))
        ->where('d.id_periodo',$pid)
        ->orderBy('d.dia_semana')->orderBy('d.hora_inicio')
        ->get([
          'd.dia_semana',
          DB::raw("to_char(d.hora_inicio,'HH24:MI') as hora_inicio"),
          DB::raw("to_char(d.hora_fin,'HH24:MI')    as hora_fin")
        ]);

    // Bloqueos de aula (si existe la tabla/columnas)
    $bloqueos = [];
    if (Schema::hasTable('bloqueo_aula')){
        $q = DB::table('bloqueo_aula');
        // try to select common columns (id_aula, fecha_inicio, fecha_fin, motivo)
        $cols = [];
        if (Schema::hasColumn('bloqueo_aula','id_aula')) $cols[] = 'id_aula';
        if (Schema::hasColumn('bloqueo_aula','fecha_inicio')) $cols[] = 'fecha_inicio';
        if (Schema::hasColumn('bloqueo_aula','fecha_fin')) $cols[] = 'fecha_fin';
        if (Schema::hasColumn('bloqueo_aula','motivo')) $cols[] = 'motivo';
        if (count($cols)) $bloqueos = $q->select($cols)->get();
    }

    // Pendientes: grupos sin cargas en el periodo
    $pendientes = DB::table('grupo as g')
        ->where('g.id_periodo',$pid)
        ->whereNotExists(function($q){
            $q->select(DB::raw(1))->from('carga_horaria as ch')->whereRaw('ch.id_grupo = g.id_grupo');
        })
        ->get(['g.id_grupo','g.nombre_grupo']);

    // Horas semanales por docente (minutos)
    $hours = DB::table('carga_horaria')
        ->select('id_docente', DB::raw("SUM(EXTRACT(EPOCH FROM (hora_fin-hora_inicio))/60)::integer as minutes"))
        ->whereExists(function($q) use ($pid){
            $q->select(DB::raw(1))->from('grupo as g')->whereRaw('g.id_grupo = carga_horaria.id_grupo')->where('g.id_periodo',$pid);
        })
        ->groupBy('id_docente')
        ->get();

    $weekly_hours = [];
    foreach($hours as $h) $weekly_hours[$h->id_docente] = (int)$h->minutes;

        return response()->json([
            'ok'=>true,
            'cargas'=>$cargas,
            'disponibilidades'=>$disps,
            'bloqueos'=>$bloqueos,
            'pendientes'=>$pendientes,
            'weekly_hours'=>$weekly_hours,
        ]);
}


    /** Validación en vivo (preview): verifica solape Docente/Aula y disponibilidad. */
    public function apiValidateSlot(Request $r)
    {
        $data = $r->validate([
            'id_periodo' => ['required','integer'],
            'id_grupo'   => ['nullable','integer'],
            'id_carga'   => ['nullable','integer'],
            'id_docente' => ['nullable','integer'],
            'id_aula'    => ['nullable','integer'],
            'dia_semana' => ['required','integer','between:1,7'],
            'hora_inicio'=> ['required','date_format:H:i'],
            'hora_fin'   => ['required','date_format:H:i','after:hora_inicio'],
        ]);

        $pid = (int)$data['id_periodo'];

        // 1) Disponibilidad (only if docente provided)
        $fueraDisp = false;
        if (!empty($data['id_docente'])){
            $fueraDisp = !DB::table('disponibilidad_docente')
                ->where('id_docente',$data['id_docente'])
                ->where('id_periodo',$pid)
                ->where('dia_semana',$data['dia_semana'])
                ->where('hora_inicio','<=',$data['hora_inicio'])
                ->where('hora_fin','>=',$data['hora_fin'])
                ->exists();
        }

        // 2) Solape docente (excluye la misma carga si es movimiento)
        $solapeDoc = false;
        if (!empty($data['id_docente'])){
            $solapeDoc = DB::table('carga_horaria')
                ->when(!empty($data['id_carga']), fn($q)=>$q->where('id_carga','<>',$data['id_carga']))
                ->where('id_docente',$data['id_docente'])
                ->where('dia_semana',$data['dia_semana'])
                ->where('hora_inicio','<',$data['hora_fin'])
                ->where('hora_fin','>',$data['hora_inicio'])
                ->exists();
        }

        $solapeAula = false;
        if (!empty($data['id_aula'])){
            $solapeAula = DB::table('carga_horaria')
                ->when(!empty($data['id_carga']), fn($q)=>$q->where('id_carga','<>',$data['id_carga']))
                ->where('id_aula',$data['id_aula'])
                ->where('dia_semana',$data['dia_semana'])
                ->where('hora_inicio','<',$data['hora_fin'])
                ->where('hora_fin','>',$data['hora_inicio'])
                ->exists();
        }

        // 3) Bloqueo de aula (si tabla/columnas de bloqueo existen)
        $bloqueo = false;
        if (!empty($data['id_aula']) && Schema::hasTable('bloqueo_aula')){
            if (Schema::hasColumn('bloqueo_aula','hora_inicio') && Schema::hasColumn('bloqueo_aula','hora_fin')){
                $bloqueo = DB::table('bloqueo_aula')
                    ->where('id_aula',$data['id_aula'])
                    ->where('hora_inicio','<',$data['hora_fin'])
                    ->where('hora_fin','>',$data['hora_inicio'])
                    ->exists();
            }
        }

        return response()->json([
            'ok'=>true,
            'conflictos'=>[
                'docente'=>$solapeDoc,
                'aula'=>$solapeAula,
            ],
            'advertencias'=>[
                'fueraDisponibilidad'=>$fueraDisp,
                'bloqueo'=>$bloqueo
            ]
        ]);
    }

    /** Aplicar movimiento (drag & drop): mover existente o crear nueva. */
    public function dragUpdate(Request $r)
    {
        $data = $r->validate([
            'modo'       => ['required','in:mover,crear'],
            'id_periodo' => ['required','integer'],
            'id_carga'   => ['nullable','integer','exists:carga_horaria,id_carga'], // <-- CAMBIO
            'id_grupo'   => ['nullable','integer','exists:grupo,id_grupo'],
            'id_docente' => ['required','integer','exists:docente,id_docente'],
            'id_aula'    => ['required','integer','exists:aula,id_aula'],
            'dia_semana' => ['required','integer','between:1,7'],
            'hora_inicio'=> ['required','date_format:H:i'],
            'hora_fin'   => ['required','date_format:H:i','after:hora_inicio'],
        ]);

        // Reutiliza la validación de preview (server-side)
        $preview = new Request($data);
        $checkResp = $this->apiValidateSlot($preview);
        $check = $checkResp->getData(true);
        if (!$check || (isset($check['ok']) && $check['ok']===false)){
            return response()->json(['ok'=>false,'error'=>'validation','detalle'=>$check],422);
        }
        if (!empty($check['conflictos']['docente']) || !empty($check['conflictos']['aula']) || !empty($check['advertencias']['bloqueo'])){
            return response()->json(['ok'=>false,'error'=>'conflicto','detalle'=>$check],422);
        }

        try{
            if ($data['modo']==='mover') {
                $affected = DB::table('carga_horaria')
                    ->where('id_carga',$data['id_carga'])
                    ->update([
                        'id_docente'=>$data['id_docente'] ?? null,
                        'id_aula'=>$data['id_aula'] ?? null,
                        'dia_semana'=>$data['dia_semana'],
                        'hora_inicio'=>$data['hora_inicio'],
                        'hora_fin'=>$data['hora_fin'],
                    ]);
                $minutes = 0;
                if (!empty($data['id_docente'])){
                    $h = DB::table('carga_horaria')
                        ->select(DB::raw("SUM(EXTRACT(EPOCH FROM (hora_fin-hora_inicio))/60)::integer as minutes"))
                        ->where('id_docente',$data['id_docente'])
                        ->get()->first();
                    $minutes = $h->minutes ?? 0;
                }
                return response()->json(['ok'=>true,'accion'=>'mover','rows'=>$affected,'weekly_minutes'=>$minutes]);
            } else {
                $id = DB::table('carga_horaria')->insertGetId([
                    'id_grupo'=>$data['id_grupo'] ?? null,
                    'id_docente'=>$data['id_docente'] ?? null,
                    'id_aula'=>$data['id_aula'] ?? null,
                    'dia_semana'=>$data['dia_semana'],
                    'hora_inicio'=>$data['hora_inicio'],
                    'hora_fin'=>$data['hora_fin'],
                    'fecha_asignacion'=>now(),
                    'estado'=>'Vigente',
                ], 'id_carga');
                $minutes = 0;
                if (!empty($data['id_docente'])){
                    $h = DB::table('carga_horaria')
                        ->select(DB::raw("SUM(EXTRACT(EPOCH FROM (hora_fin-hora_inicio))/60)::integer as minutes"))
                        ->where('id_docente',$data['id_docente'])
                        ->get()->first();
                    $minutes = $h->minutes ?? 0;
                }
                return response()->json(['ok'=>true,'accion'=>'crear','id'=>$id,'weekly_minutes'=>$minutes]);
            }
        }catch(\Throwable $e){
            report($e);
            return response()->json(['ok'=>false,'error'=>'tx','msg'=>$e->getMessage()],500);
        }
    }

    /** Bulk create multiple occurrences (transactional). */
    public function bulkCreate(Request $r)
    {
        $data = $r->validate([
            'id_periodo'=>['required','integer'],
            'days'=>['required','array'],
            'days.*'=>['integer','between:1,7'],
            'start'=>['required','date_format:H:i'],
            'times'=>['required','integer','min:1'],
            'duration'=>['required','integer','min:30'],
            'gap'=>['nullable','integer','min:0'],
            'id_docente'=>['nullable','integer','exists:docente,id_docente'],
            'id_grupo'=>['nullable','integer','exists:grupo,id_grupo'],
        ]);

        $created = [];
        DB::beginTransaction();
        try{
            foreach($data['days'] as $d){
                $cur = explode(':',$data['start']); $curMin = intval($cur[0])*60 + intval($cur[1]);
                for($i=0;$i<$data['times'];$i++){
                    $sHM = sprintf('%02d:%02d', intdiv($curMin,60), $curMin%60);
                    $eMin = $curMin + $data['duration'];
                    $eHM = sprintf('%02d:%02d', intdiv($eMin,60), $eMin%60);
                    // validate using apiValidateSlot logic
                    $preview = new Request([ 'id_periodo'=>$data['id_periodo'],'id_docente'=>$data['id_docente']??null,'id_aula'=>null,'dia_semana'=>$d,'hora_inicio'=>$sHM,'hora_fin'=>$eHM ]);
                    $check = $this->apiValidateSlot($preview)->getData(true);
                    if (!empty($check['conflictos']['docente']) || !empty($check['conflictos']['aula']) || !empty($check['advertencias']['fueraDisponibilidad'])){
                        $curMin = $eMin + ($data['gap'] ?? 0); continue;
                    }
                    $id = DB::table('carga_horaria')->insertGetId([
                        'id_grupo'=>$data['id_grupo'] ?? null,
                        'id_docente'=>$data['id_docente'] ?? null,
                        'id_aula'=>null,
                        'dia_semana'=>$d,
                        'hora_inicio'=>$sHM,
                        'hora_fin'=>$eHM,
                        'fecha_asignacion'=>now(),
                        'estado'=>'Vigente',
                    ], 'id_carga');
                    $created[] = $id;
                    $curMin = $eMin + ($data['gap'] ?? 0);
                }
            }
            DB::commit();
            return response()->json(['ok'=>true,'created'=>$created]);
        }catch(\Throwable $e){ DB::rollBack(); report($e); return response()->json(['ok'=>false,'error'=>'tx','msg'=>$e->getMessage()],500); }
    }
}
