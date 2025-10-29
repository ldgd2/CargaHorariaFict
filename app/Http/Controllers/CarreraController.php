<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;

class CarreraController extends Controller
{
    use LogsBitacora;


    public function view()
    {
        return view('usuarios.admin.admin.carreras.index');
    }


    public function index(Request $r)
{
    $q   = $r->get('q');
    $per = max(1, (int) $r->get('per_page', 20));

    return Carrera::query()
        ->when($q, fn($qb) => $qb->where('nombre','ilike',"%{$q}%"))
        ->with(['jefe.usuario:id_usuario,nombre']) 
        ->orderBy('nombre')
        ->paginate($per);
}

public function store(Request $r)
{
    $data = $r->validate([
        'nombre'          => ['required','string','max:120'],
        'habilitado'      => ['sometimes','boolean'],
        'jefe_docente_id' => ['nullable','integer','exists:docente,id_docente'],
    ]);

    $car = Carrera::create($data + ['habilitado' => $data['habilitado'] ?? true]);
    if (method_exists($this, 'logAction')) {
        $this->logAction('carrera_creada','carrera',$car->id_carrera,$data);
    }

    return response()->json($car->load('jefe.usuario'), 201);
}

    public function update(Request $r, Carrera $carrera)
{
    $data = $r->validate([
        'nombre'          => ['sometimes','string','max:120'],
        'jefe_docente_id' => ['nullable','integer','exists:docente,id_docente'],
        'habilitado'      => ['sometimes','boolean'],
    ]);

    $carrera->update($data);

    if (method_exists($this, 'logAction')) {
        $this->logAction('carrera_editada','carrera',$carrera->id_carrera,$data);
    }

    return $carrera->load('jefe.usuario');
}

    public function toggle(Carrera $carrera)
    {
        if ($carrera->habilitado) {
            $tieneMaterias = Materia::where('id_carrera',$carrera->id_carrera)->exists();
            $tieneGrupos   = Grupo::where('id_carrera',$carrera->id_carrera)->exists();
            if ($tieneMaterias || $tieneGrupos) {
                return response()->json(['ok'=>false,'error'=>'No se puede desactivar: tiene materias/grupos asociados.'],422);
            }
        }
        $carrera->habilitado = !$carrera->habilitado;
        $carrera->save();
        $this->logAction($carrera->habilitado?'carrera_activada':'carrera_desactivada','carrera',$carrera->id_carrera,[]);

        return $carrera->load('jefe:id_docente,nombre');
    }
}
