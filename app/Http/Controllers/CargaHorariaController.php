<?php

namespace App\Http\Controllers;

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
            ->when($pid, fn($q)=>$q->where('id_periodo',$pid))
            ->select('id_grupo','nombre_grupo','id_periodo','id_materia','id_carrera')
            ->orderBy('nombre_grupo')->get();
    }

    public function apiDocentes()
    {
        return Docente::query()
            ->select('id_docente','nro_documento','habilitado','tope_horas_semana')
            ->orderBy('id_docente')->get();
    }

    public function apiAulas()
    {
        return Aula::query()
            ->select('id_aula','codigo','habilitada','capacidad')
            ->orderBy('codigo')->get();
    }

    public function apiDisponibilidadDocente(Request $r, int $docenteId)
    {
        $pid = (int) $r->query('id_periodo');
        abort_if(!$pid, 422, 'id_periodo requerido');
        return DisponibilidadDocente::query()
            ->where('id_docente',$docenteId)->where('id_periodo',$pid)
            ->orderBy('dia_semana')->orderBy('hora_inicio')
            ->get(['id_disponibilidad','dia_semana','hora_inicio','hora_fin','prioridad','observaciones']);
    }

    /** ------------- STORE--------------- */
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

        $estadoCol = Schema::hasColumn('periodo_academico','estado') ? 'estado' :
                     (Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);
        if ($estadoCol) {
            $ok = PeriodoAcademico::where('id_periodo',$data['id_periodo'])
                ->whereIn($estadoCol, ['EnAsignacion','Reabierto','Activo','Publicado','publicado'])
                ->exists();
            abort_if(!$ok, 422, 'El período no permite asignación.');
        }
        $tieneDisp = DisponibilidadDocente::query()
            ->where('id_docente',$data['id_docente'])
            ->where('id_periodo',$data['id_periodo'])
            ->where('dia_semana',$data['dia_semana'])
            ->where('hora_inicio','<=',$data['hora_inicio'])
            ->where('hora_fin','>=',$data['hora_fin'])
            ->exists();
        if (!$tieneDisp) {
            return back()->withErrors(['E3'=>'Docente sin disponibilidad en esa franja.'])->withInput();
        }

        // c) No solape del DOCENTE (misma franja/día en carga_horaria)
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
            ->where('id_aula',$data['id_aula'])
            ->where('dia_semana',$data['dia_semana'])
            ->where(function($q) use ($data){
                $q->where('hora_inicio','<',$data['hora_fin'])
                  ->where('hora_fin','>',$data['hora_inicio']);
            })->exists();
        if ($solapeAula) {
            return back()->withErrors(['E2'=>'Aula ocupada en esa franja.'])->withInput();
        }
        if (Schema::hasTable('bloqueo_aula')) {
            $bloq = DB::table('bloqueo_aula')
                ->where('id_aula',$data['id_aula'])
                ->where(function($q) use ($data){
                    $q->where('dia_semana',$data['dia_semana'])
                      ->orWhereNull('dia_semana');
                })
                ->where(function($q) use ($data){
                    $q->whereNull('hora_inicio')->whereNull('hora_fin')
                      ->orWhere(function($qq) use ($data){
                          $qq->where('hora_inicio','<',$data['hora_fin'])
                             ->where('hora_fin','>',$data['hora_inicio']);
                      });
                })
                ->where('activo', true)
                ->exists();
            if ($bloq) {
                return back()->withErrors(['E2'=>'Aula bloqueada en ese rango.'])->withInput();
            }
        }
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
        try {
            DB::transaction(function () use ($data) {
                $c = new CargaHoraria();
                $c->fill([
                    'id_grupo'    => $data['id_grupo'],
                    'id_docente'  => $data['id_docente'],
                    'id_aula'     => $data['id_aula'],
                    'dia_semana'  => $data['dia_semana'],
                    'hora_inicio' => $data['hora_inicio'],
                    'hora_fin'    => $data['hora_fin'],
                    'estado'      => 'Vigente',
                ]);
                $c->save();

                // Bitácora
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

    $errores = [];
    $creados = 0;

    \DB::beginTransaction();
    try {
        foreach ($base['items'] as $i => $it) {
            $dia = (int)$it['dia_semana'];
            $ini = $it['hora_inicio'];
            $fin = $it['hora_fin'];
            if ($ini >= $fin) { $errores[]="Fila #$i: fin debe ser mayor que inicio"; continue; }
            $hayDisp = \App\Models\DisponibilidadDocente::query()
                ->where('id_docente',$docente)
                ->where('id_periodo',$periodo)
                ->where('dia_semana',$dia)
                ->where('hora_inicio','<=',$ini)
                ->where('hora_fin','>=',$fin)
                ->exists();
            if(!$hayDisp){ $errores[]="Fila #$i: docente sin disponibilidad en esa franja (E3)"; continue; }
            $solapeDoc = \DB::table('carga_horaria')->where([
                    ['id_docente','=',$docente],
                    ['dia_semana','=',$dia],
                ])->where('hora_inicio','<',$fin)
                  ->where('hora_fin','>',$ini)
                  ->exists();
            if($solapeDoc){ $errores[]="Fila #$i: solape con otra carga del docente (E1)"; continue; }
            $solapeAula = \DB::table('carga_horaria')->where([
                    ['id_aula','=',$aula],
                    ['dia_semana','=',$dia],
                ])->where('hora_inicio','<',$fin)
                  ->where('hora_fin','>',$ini)
                  ->exists();
            if($solapeAula){ $errores[]="Fila #$i: aula ocupada / solape (E2)"; continue; }
            $bloq = \DB::table('bloqueo_aula')
                ->where('id_aula',$aula)
                ->where(function($q) use($dia,$ini,$fin){
                    $q->where('dia_semana',$dia)
                      ->where('hora_inicio','<',$fin)
                      ->where('hora_fin','>',$ini);
                })
                ->where('activo',true)
                ->exists();
            if($bloq){ $errores[]="Fila #$i: aula bloqueada (E2)"; continue; }
            $minutosNuevos = (\Carbon\Carbon::createFromFormat('H:i',$fin)->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i',$ini)));
            $minutosAcum = \DB::table('carga_horaria')
                ->where('id_docente',$docente)
                ->sum(\DB::raw("EXTRACT(EPOCH FROM (hora_fin - hora_inicio))/60"));
            $tope = \DB::table('docente')->where('id_docente',$docente)->value('tope_horas_semana'); // ajusta nombre
            if($tope && (($minutosAcum + $minutosNuevos)/60) > $tope){
                $errores[]="Fila #$i: excede tope semanal del docente (E4)"; continue;
            }

            // OK => crear
            \DB::table('carga_horaria')->insert([
                'id_grupo'      => $grupo,
                'id_docente'    => $docente,
                'id_aula'       => $aula,
                'dia_semana'    => $dia,
                'hora_inicio'   => $ini,
                'hora_fin'      => $fin,
                'estado'        => 'Vigente',
                'fecha_asignacion' => now(),
            ]);
            $creados++;
        }

        if ($creados === 0) {
            \DB::rollBack();
            return back()->withErrors($errores ?: ['No se pudo crear ninguna carga.'])->withInput();
        }

        \DB::commit();
        return redirect()->route('coordinador.dashboard')
            ->with('ok', "Cargas creadas: $creados" . (count($errores) ? ' | Con advertencias: '.implode(' · ', $errores) : ''));
    } catch (\Throwable $e) {
        \DB::rollBack();
        report($e);
        return back()->withErrors(['E5: Error de BD'])->withInput();
    }
}

    /** util */
    private static function mins($h1,$h2): int {
        [$h,$m]=array_map('intval',explode(':',$h1)); $a=$h*60+$m;
        [$h,$m]=array_map('intval',explode(':',$h2)); $b=$h*60+$m;
        return max(0,$b-$a);
    }
}
