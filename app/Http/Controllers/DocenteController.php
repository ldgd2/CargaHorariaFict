<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Grupo;
use App\Models\CargaHoraria;
use App\Models\AsistenciaSesion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;

class DocenteController extends Controller
{
    use LogsBitacora;

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
