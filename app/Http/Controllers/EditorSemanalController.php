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

        return view('carga.editor-semanal', compact('periodos','docentes','aulas'));
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

    return response()->json([
      'ok'=>true,
      'cargas'=>$cargas,
      'disponibilidades'=>$disps,
    ]);
}


    /** Validación en vivo (preview): verifica solape Docente/Aula y disponibilidad. */
    public function apiValidateSlot(Request $r)
    {
        $data = $r->validate([
            'id_periodo' => ['required','integer'],
            'id_grupo'   => ['nullable','integer'],  // para nuevas asignaciones
            'id_carga'   => ['nullable','integer'],  // para mover una existente
            'id_docente' => ['required','integer'],
            'id_aula'    => ['required','integer'],
            'dia_semana' => ['required','integer','between:1,7'],
            'hora_inicio'=> ['required','date_format:H:i'],
            'hora_fin'   => ['required','date_format:H:i','after:hora_inicio'],
        ]);

        $pid = (int)$data['id_periodo'];

        // 1) Disponibilidad
        $fueraDisp = !DB::table('disponibilidad_docente')
            ->where('id_docente',$data['id_docente'])
            ->where('id_periodo',$pid)
            ->where('dia_semana',$data['dia_semana'])
            ->where('hora_inicio','<=',$data['hora_inicio'])
            ->where('hora_fin','>=',$data['hora_fin'])
            ->exists();

        // 2) Solape docente (excluye la misma carga si es movimiento)
        $solapeDoc = DB::table('carga_horaria')
            ->when(!empty($data['id_carga']), fn($q)=>$q->where('id_carga','<>',$data['id_carga']))
            ->where('id_docente',$data['id_docente'])
            ->where('dia_semana',$data['dia_semana'])
            ->where('hora_inicio','<',$data['hora_fin'])
            ->where('hora_fin','>',$data['hora_inicio'])
            ->exists();

        $solapeAula = DB::table('carga_horaria')
            ->when(!empty($data['id_carga']), fn($q)=>$q->where('id_carga','<>',$data['id_carga']))
            ->where('id_aula',$data['id_aula'])
            ->where('dia_semana',$data['dia_semana'])
            ->where('hora_inicio','<',$data['hora_fin'])
            ->where('hora_fin','>',$data['hora_inicio'])
            ->exists();

        return response()->json([
            'ok'=>true,
            'conflictos'=>[
                'docente'=>$solapeDoc,
                'aula'=>$solapeAula,
            ],
            'advertencias'=>[
                'fueraDisponibilidad'=>$fueraDisp
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

        // Reutiliza la validación de preview:
        $preview = request()->duplicate($data)->replace($data);
        $check = app(self::class)->apiValidateSlot($preview)->getData(true);

        if ($check['conflictos']['docente'] || $check['conflictos']['aula']) {
            return response()->json([
                'ok'=>false,
                'error'=>'conflicto',
                'detalle'=>$check
            ], 422);
        }

        try{
            if ($data['modo']==='mover') {
                $affected = DB::table('carga_horaria')
                    ->where('id_carga',$data['id_carga']) // <-- CAMBIO
                    ->update([
                        'id_docente'=>$data['id_docente'],
                        'id_aula'=>$data['id_aula'],
                        'dia_semana'=>$data['dia_semana'],
                        'hora_inicio'=>$data['hora_inicio'],
                        'hora_fin'=>$data['hora_fin'],
                    ]);
                return response()->json(['ok'=>true,'accion'=>'mover','rows'=>$affected]);
            } else {
                $id = DB::table('carga_horaria')->insertGetId([
                    'id_grupo'=>$data['id_grupo'],
                    'id_docente'=>$data['id_docente'],
                    'id_aula'=>$data['id_aula'],
                    'dia_semana'=>$data['dia_semana'],
                    'hora_inicio'=>$data['hora_inicio'],
                    'hora_fin'=>$data['hora_fin'],
                    'fecha_asignacion'=>now(),
                    'estado'=>'Vigente',
                ], 'id_carga'); // <-- CAMBIO
                return response()->json(['ok'=>true,'accion'=>'crear','id'=>$id]);
            }
        }catch(\Throwable $e){
            report($e);
            return response()->json(['ok'=>false,'error'=>'tx','msg'=>$e->getMessage()],500);
        }
    }
}
