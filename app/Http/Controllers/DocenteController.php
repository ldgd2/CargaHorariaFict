<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Grupo;
use App\Models\CargaHoraria;
use App\Models\AsistenciaSesion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;
use App\Models\DisponibilidadDocente;
use App\Models\PeriodoAcademico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DocenteController extends Controller
{
    use LogsBitacora;


        private function currentDocenteId(): ?int
        {
            $userId = (int) Auth::id();


            if (Schema::hasColumn('docente', 'id_usuario')) {
                return Docente::where('id_usuario', $userId)->value('id_docente');
            }

            return Docente::where('id_docente', $userId)->value('id_docente');
        }
    public function dashboard()
{
    $docenteId = (int) Auth::id();
    $hasEstado   = Schema::hasColumn('periodo_academico', 'estado');
    $hasEstadoPub= Schema::hasColumn('periodo_academico', 'estado_publicacion');

    $q = PeriodoAcademico::query();

    if ($hasEstado) {
        $q->whereIn('estado', ['EnAsignacion','Activo','Reabierto','Publicado']);
    } elseif ($hasEstadoPub) {
        $q->whereIn('estado_publicacion', ['EnAsignacion','Activo','Reabierto','Publicado']);
    }
    $periodo = $q->orderByDesc('id_periodo')->first();
    $pid = $periodo->id_periodo ?? null;
     $desde = now()->startOfMonth()->toDateString();

$res = AsistenciaSesion::query()
    ->join('carga_horaria as ch', 'ch.id_carga', '=', 'asistencia_sesion.id_carga')
    ->where('ch.id_docente', $docenteId)
    ->when($pid, fn($q) => $q->where('ch.id_periodo', $pid))
    ->whereDate('asistencia_sesion.fecha_sesion', '>=', $desde)
    ->select('asistencia_sesion.estado')                
    ->selectRaw('COUNT(*) AS c')
    ->groupBy('asistencia_sesion.estado')             
    ->pluck('c','estado');                        
   $kpis = [
        'grupos'        => $pid ? Grupo::where('id_docente',$docenteId)->where('id_periodo',$pid)->count() : 0,
        'horas_semana'  => $pid ? (float) Grupo::where('id_docente',$docenteId)->where('id_periodo',$pid)->sum('horas_semanales') : 0,
        'franjas'       => $pid ? DisponibilidadDocente::where('id_docente',$docenteId)->where('id_periodo',$pid)->count() : 0,
        'disp_franjas'  => $pid ? DisponibilidadDocente::where('id_docente',$docenteId)->where('id_periodo',$pid)->count() : 0, 
    ];
    $kpis['asistencia_ok']    = (int)($res['Presente'] ?? 0) + (int)($res['Manual Validado'] ?? 0);
    $kpis['asistencia_pend']  = (int)($res['Pendiente'] ?? 0);
    $kpis['asistencia_anul']  = (int)($res['Anulado'] ?? 0);


    $proximas = AsistenciaSesion::query()
    ->join('carga_horaria as ch', 'ch.id_carga', '=', 'asistencia_sesion.id_carga')
    ->where('ch.id_docente', $docenteId)
    ->when($pid, fn($q) => $q->where('ch.id_periodo', $pid))
    ->whereDate('asistencia_sesion.fecha_sesion', '>=', now()->toDateString())
    ->orderBy('asistencia_sesion.fecha_sesion')
    ->orderBy('asistencia_sesion.hora_registro')
    ->limit(5)
    ->get([
        'asistencia_sesion.*',

    ]);

$periodoActivo = $periodo;

return view('docente.dashboard', compact('periodo','periodoActivo','pid','kpis','proximas'));
}

    public function disponibilidad(Request $r)
    {
        $col = \Schema::hasColumn('periodo_academico','estado')
            ? 'estado'
            : (\Schema::hasColumn('periodo_academico','estado_publicacion') ? 'estado_publicacion' : null);

        $validos = ['EnAsignacion','Reabierto','Activo','Publicado','publicado','borrador','Borrador'];
        $periodos = \App\Models\PeriodoAcademico::query()
            ->when($col, fn($q) => $q->whereIn($col, $validos))
            ->orderByDesc('fecha_inicio')
            ->get(['id_periodo','nombre','fecha_inicio','fecha_fin']);

        // Período seleccionado
        $idPeriodo = (int) ($r->query('id_periodo')
            ?? optional(
                \App\Models\PeriodoAcademico::query()
                    ->when($col, fn($q) => $q->whereIn($col, $validos))
                    ->orderByDesc('id_periodo')
                    ->first()
            )->id_periodo ?? 0);


        $docenteId = $this->currentDocenteId();


        $disponibilidades = collect();
        if ($idPeriodo && $docenteId) {
            $disponibilidades = \App\Models\DisponibilidadDocente::query()
                ->where('id_docente', $docenteId)
                ->where('id_periodo', $idPeriodo)
                ->orderBy('dia_semana')->orderBy('hora_inicio')
                ->get();
        }

        return view('docente.disponibilidad', compact('periodos','idPeriodo','disponibilidades'));
    }


    public function index(Request $r)
{
    $q   = $r->get('q');
    $per = max(1, (int) $r->get('per_page', 20));

    return Docente::query()
        ->with(['usuario:id_usuario,nombre']) 
        ->when($q, function($qb) use ($q) {
            $qb->where(function($qq) use ($q) {
                // buscar por nro_documento o por usuario.nombre
                $qq->where('nro_documento', 'ilike', "%{$q}%")
                   ->orWhereHas('usuario', function($uq) use ($q) {
                       $uq->where('nombre', 'ilike', "%{$q}%");
                   });
            });
        })
        ->orderBy('id_docente') 
        ->paginate($per);
}

    public function store(Request $r)
    {
        $data = $r->validate([
            'nombre'            => ['required','string','max:120'],
            'nro_documento'     => ['required','string','max:40','unique:docente,nro_documento'],
            'tipo_contrato'     => ['required','string','max:40'],
            'carrera_principal' => ['required','string','max:120'],
            'tope_horas_semana' => ['required','integer','min:1'],
            'email'             => ['nullable','email','max:150'],
            'telefono'          => ['nullable','string','max:30'],
            'habilitado'        => ['sometimes','boolean'],
        ]);

        $doc = Docente::create($data + ['habilitado'=>$data['habilitado'] ?? true]);
        $this->logAction('docente_creado','docente',$doc->id_docente,$data);
        return response()->json($doc,201);
    }

    public function update(Request $r, Docente $docente)
    {
        $data = $r->validate([
            'nombre'            => ['sometimes','string','max:120'],
            'nro_documento'     => ['sometimes','string','max:40',
                Rule::unique('docente','nro_documento')->ignore($docente->id_docente,'id_docente')],
            'tipo_contrato'     => ['sometimes','string','max:40'],
            'carrera_principal' => ['sometimes','string','max:120'],
            'tope_horas_semana' => ['sometimes','integer','min:1'],
            'email'             => ['sometimes','nullable','email','max:150'],
            'telefono'          => ['sometimes','nullable','string','max:30'],
        ]);
        $docente->update($data);
        $this->logAction('docente_editado','docente',$docente->id_docente,$data);
        return $docente;
    }

    public function toggle(Docente $docente)
    {
        if ($docente->habilitado) {
            $tieneGrupos = Grupo::where('id_docente',$docente->id_docente)->exists();
            $enCarga     = CargaHoraria::where('id_docente',$docente->id_docente)->exists();
            $asistencias = AsistenciaSesion::where('id_docente',$docente->id_docente)->exists();
            if ($tieneGrupos || $enCarga || $asistencias) {
                return response()->json(['ok'=>false,'error'=>'No se puede desactivar: grupos/carga horaria/asistencias asociadas.'],422);
            }
        }
        $docente->habilitado = !$docente->habilitado;
        $docente->save();
        $this->logAction($docente->habilitado?'docente_activado':'docente_desactivado','docente',$docente->id_docente,[]);
        return $docente;
    }
}
