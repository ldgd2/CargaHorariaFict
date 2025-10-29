<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\Grupo;
use App\Models\CargaHoraria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;

class MateriaController extends Controller
{
    use LogsBitacora;

    public function index(Request $r)
    {
        $q = $r->get('q');
        $carrera = $r->get('id_carrera');
        return Materia::query()
            ->when($q, fn($qb)=> $qb->where('nombre','ilike',"%{$q}%")
                                     ->orWhere('codigo','ilike',"%{$q}%"))
            ->when($carrera, fn($qb)=> $qb->where('id_carrera',$carrera))
            ->orderBy('nombre')
            ->paginate($r->integer('per_page',20));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'id_carrera'    => ['required','integer','exists:carrera,id_carrera'],
            'codigo'        => ['required','string','max:20',
                Rule::unique('materia','codigo')->where(fn($q)=>$q->where('id_carrera',$r->id_carrera))
            ],
            'nombre'        => ['required','string','max:150'],
            'horas_semanales'=>['required','integer','min:1'],
            'programa'      => ['nullable','string'],
            'habilitado'    => ['sometimes','boolean'],
        ]);

        $mat = Materia::create($data + ['habilitado'=>$data['habilitado'] ?? true]);
        $this->logAction('materia_creada','materia',$mat->id_materia,$data);
        return response()->json($mat,201);
    }

    public function update(Request $r, Materia $materia)
    {
        $data = $r->validate([
            'id_carrera'    => ['sometimes','integer','exists:carrera,id_carrera'],
            'codigo'        => ['sometimes','string','max:20',
                Rule::unique('materia','codigo')
                    ->where(fn($q)=>$q->where('id_carrera',$r->input('id_carrera',$materia->id_carrera)))
                    ->ignore($materia->id_materia,'id_materia')
            ],
            'nombre'        => ['sometimes','string','max:150'],
            'horas_semanales'=>['sometimes','integer','min:1'],
            'programa'      => ['sometimes','nullable','string'],
        ]);
        $materia->update($data);
        $this->logAction('materia_editada','materia',$materia->id_materia,$data);
        return $materia;
    }

    public function toggle(Materia $materia)
    {
        if ($materia->habilitado) {
            $enGrupos = Grupo::where('id_materia',$materia->id_materia)->exists();
            $enCarga  = CargaHoraria::where('id_materia',$materia->id_materia)->exists();
            if ($enGrupos || $enCarga) {
                return response()->json(['ok'=>false,'error'=>'No se puede desactivar: tiene grupos/carga horaria.'],422);
            }
        }
        $materia->habilitado = !$materia->habilitado;
        $materia->save();
        $this->logAction($materia->habilitado?'materia_activada':'materia_desactivada','materia',$materia->id_materia,[]);
        return $materia;
    }
}
