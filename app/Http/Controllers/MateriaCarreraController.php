<?php

namespace App\Http\Controllers;

use App\Models\MateriaCarrera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\LogsBitacora;

class MateriaCarreraController extends Controller
{
    use LogsBitacora;

    public function index(Request $r)
    {
        return MateriaCarrera::with(['materia','carrera'])
            ->orderByDesc('id')
            ->paginate($r->integer('per_page',20));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'id_materia' => ['required','integer','exists:materia,id_materia'],
            'id_carrera' => ['required','integer','exists:carrera,id_carrera'],
        ]);

        $exists = MateriaCarrera::where($data)->exists();
        if ($exists) return response()->json(['ok'=>false,'error'=>'Ya existe el vínculo materia↔carrera.'],422);

        $mc = MateriaCarrera::create($data);
        $this->logAction('materia_carrera_creada','materia_carrera',$mc->id,$data);
        return response()->json($mc,201);
    }

    public function destroy(MateriaCarrera $materiaCarrera)
    {
        $id = $materiaCarrera->id;
        $materiaCarrera->delete();
        $this->logAction('materia_carrera_eliminada','materia_carrera',$id,[]);
        return response()->json(null,204);
    }
}
