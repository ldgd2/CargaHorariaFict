<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\Grupo;
use App\Models\CargaHoraria;
use App\Models\Carrera; // Se mantiene por si se usa en otras partes del controlador, pero el uso principal es eliminado
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;

class MateriaController extends Controller
{
    use LogsBitacora;

    public function viewIndex()
    {
        // ⚠️ CORREGIDO: Ya no es necesario cargar $carreras
        // Simplemente devuelve la vista
        return view('usuarios.admin.admin.materias.index');
    }

    public function index(Request $r)
    {
        $q = $r->get('q');
        // ⚠️ Eliminada la cláusula ->when($carrera, fn($qb)=> $qb->where('id_carrera',$carrera))
        return Materia::query()
            ->when($q, fn($qb)=> $qb->where('nombre','ilike',"%{$q}%")
                                    ->orWhere('cod_materia','ilike',"%{$q}%"))
            ->orderBy('nombre')
            ->paginate($r->integer('per_page',20));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            // ⚠️ ELIMINADO: 'id_carrera'
            
            'cod_materia'   => ['required','string','max:20', 
                // La unicidad es a nivel de la tabla Materia
                Rule::unique('materia','cod_materia') 
            ],
            'nombre'        => ['required','string','max:150'],
            'horas_semanales'=>['required','integer','min:1'],
            'programa'      => ['nullable','string'],
            'habilitado'    => ['sometimes','boolean'],
        ]);
        
        // ⚠️ ELIMINADA la lógica de attach/sync de Carrera
        $mat = Materia::create($data + ['habilitado'=>$data['habilitado'] ?? true]);
        
        $this->logAction('materia_creada','materia',$mat->id_materia,$data);
        return response()->json($mat,201);
    }

    public function update(Request $r, Materia $materia)
    {
        $data = $r->validate([
            // ⚠️ ELIMINADO: 'id_carrera'
            
            'cod_materia'   => ['sometimes','string','max:20', 
                Rule::unique('materia','cod_materia') 
                    ->ignore($materia->id_materia,'id_materia')
            ],
            'nombre'        => ['sometimes','string','max:150'],
            'horas_semanales'=>['sometimes','integer','min:1'],
            'programa'      => ['sometimes','nullable','string'],
        ]);
        
        // ⚠️ ELIMINADA la lógica de attach/sync de Carrera
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