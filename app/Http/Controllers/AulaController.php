<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\CargaHoraria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;

class AulaController extends Controller
{
    use LogsBitacora;

    public function index(Request $r)
    {
        $q = $r->get('q');
        return Aula::query()
            ->when($q, fn($qb)=> $qb->where('nombre','ilike',"%{$q}%")
                                     ->orWhere('codigo','ilike',"%{$q}%")
                                     ->orWhere('ubicacion','ilike',"%{$q}%"))
            ->orderBy('codigo')
            ->paginate($r->integer('per_page',20));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'codigo'    => ['required','string','max:20','unique:aula,codigo'],
            'nombre'    => ['required','string','max:120'],
            'capacidad' => ['required','integer','min:0'],
            'tipo'      => ['required','string','max:30'], // teoria/lab/etc
            'ubicacion' => ['nullable','string','max:120'],
            'habilitado'=> ['sometimes','boolean'],
        ]);

        $aula = Aula::create($data + ['habilitado'=>$data['habilitado'] ?? true]);
        $this->logAction('aula_creada','aula',$aula->id_aula,$data);
        return response()->json($aula,201);
    }

    public function update(Request $r, Aula $aula)
    {
        $data = $r->validate([
            'codigo'    => ['sometimes','string','max:20', Rule::unique('aula','codigo')->ignore($aula->id_aula,'id_aula')],
            'nombre'    => ['sometimes','string','max:120'],
            'capacidad' => ['sometimes','integer','min:0'],
            'tipo'      => ['sometimes','string','max:30'],
            'ubicacion' => ['sometimes','nullable','string','max:120'],
        ]);
        $aula->update($data);
        $this->logAction('aula_editada','aula',$aula->id_aula,$data);
        return $aula;
    }

    public function toggle(Aula $aula)
    {
        if ($aula->habilitado) {
            $enUso = CargaHoraria::where('id_aula',$aula->id_aula)->exists();
            if ($enUso) {
                return response()->json(['ok'=>false,'error'=>'No se puede desactivar: hay carga horaria asignada a esta aula.'],422);
            }
        }
        $aula->habilitado = !$aula->habilitado;
        $aula->save();
        $this->logAction($aula->habilitado?'aula_activada':'aula_desactivada','aula',$aula->id_aula,[]);
        return $aula;
    }
}
