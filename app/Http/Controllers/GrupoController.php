<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $idPeriodo = $request->get('id_periodo');
        $idCarrera = $request->get('id_carrera');
        $idMateria = $request->get('id_materia');
        $estado = $request->get('estado');

        $grupos = Grupo::query()
            ->with(['periodo','carrera','materia']) // Asegúrate de tener estas relaciones en el Model
            ->when($idPeriodo, fn($qb) => $qb->where('id_periodo', $idPeriodo))
            ->when($idCarrera, fn($qb) => $qb->where('id_carrera', $idCarrera))
            ->when($idMateria, fn($qb) => $qb->where('id_materia', $idMateria))
            ->when($estado, fn($qb) => $qb->where('estado', $estado))
            ->when($q, fn($qb) => $qb->where('nombre_grupo','ilike',"%{$q}%"))
            ->orderBy('id_periodo')
            ->orderBy('id_carrera')
            ->orderBy('id_materia')
            ->orderBy('nombre_grupo')
            ->paginate($request->integer('per_page', 20));

        return response()->json($grupos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_periodo'            => ['required','integer','exists:periodo_academico,id_periodo'],
            'id_materia'            => ['required','integer','exists:materia,id_materia'],
            'id_carrera'            => ['required','integer','exists:carrera,id_carrera'],
            'nombre_grupo'          => ['required','string','max:50'],
            'capacidad_estudiantes' => ['required','integer','min:1'],
            'estado'                => ['nullable', Rule::in(['En Asignacion','Activo','Cerrado','Incompleto'])],
        ]);

        // Validar unicidad compuesta manualmente (además del UNIQUE en BD)
        $exists = Grupo::where('id_periodo',$data['id_periodo'])
            ->where('id_materia',$data['id_materia'])
            ->where('id_carrera',$data['id_carrera'])
            ->where('nombre_grupo',$data['nombre_grupo'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un grupo con el mismo Periodo/Materia/Carrera/Nombre.'
            ], 422);
        }

        $grupo = Grupo::create($data);
        return response()->json($grupo, 201);
    }

    public function show(Grupo $grupo)
    {
        $grupo->load(['periodo','carrera','materia']);
        return response()->json($grupo);
    }

    public function update(Request $request, Grupo $grupo)
    {
        $data = $request->validate([
            'id_periodo'            => ['sometimes','integer','exists:periodo_academico,id_periodo'],
            'id_materia'            => ['sometimes','integer','exists:materia,id_materia'],
            'id_carrera'            => ['sometimes','integer','exists:carrera,id_carrera'],
            'nombre_grupo'          => ['sometimes','string','max:50'],
            'capacidad_estudiantes' => ['sometimes','integer','min:1'],
            'estado'                => ['sometimes', Rule::in(['En Asignacion','Activo','Cerrado','Incompleto'])],
        ]);

        if (isset($data['id_periodo'],$data['id_materia'],$data['id_carrera'],$data['nombre_grupo'])) {
            $exists = Grupo::where('id_periodo',$data['id_periodo'])
                ->where('id_materia',$data['id_materia'])
                ->where('id_carrera',$data['id_carrera'])
                ->where('nombre_grupo',$data['nombre_grupo'])
                ->where('id_grupo','!=',$grupo->id_grupo)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ya existe un grupo con el mismo Periodo/Materia/Carrera/Nombre.'
                ], 422);
            }
        }

        $grupo->update($data);
        return response()->json($grupo);
    }

    public function destroy(Grupo $grupo)
    {
        $grupo->delete();
        return response()->json(null, 204);
    }
}
