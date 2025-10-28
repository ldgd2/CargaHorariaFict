<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AulaController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $estado = $request->get('estado');
        $habilitado = $request->boolean('habilitado', null);

        $aulas = Aula::query()
            ->when($q, fn($qb) =>
                $qb->where('nombre_aula', 'ilike', "%{$q}%")
                   ->orWhere('ubicacion', 'ilike', "%{$q}%"))
            ->when($estado, fn($qb) => $qb->where('tipo_aula', $estado))
            ->when(!is_null($habilitado), fn($qb) => $qb->where('habilitado', $habilitado))
            ->orderBy('nombre_aula')
            ->paginate($request->integer('per_page', 20));

        return response()->json($aulas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_aula' => ['required','string','max:50','unique:aula,nombre_aula'],
            'capacidad'   => ['nullable','integer','min:1'],
            'tipo_aula'   => ['nullable','string','max:30'],
            'ubicacion'   => ['nullable','string','max:100'],
            'habilitado'  => ['boolean'],
        ]);

        $aula = Aula::create($data);
        return response()->json($aula, 201);
    }

    public function show(Aula $aula)
    {
        return response()->json($aula);
    }

    public function update(Request $request, Aula $aula)
    {
        $data = $request->validate([
            'nombre_aula' => [
                'sometimes','string','max:50',
                Rule::unique('aula','nombre_aula')->ignore($aula->id_aula, 'id_aula')
            ],
            'capacidad'   => ['sometimes','nullable','integer','min:1'],
            'tipo_aula'   => ['sometimes','nullable','string','max:30'],
            'ubicacion'   => ['sometimes','nullable','string','max:100'],
            'habilitado'  => ['sometimes','boolean'],
        ]);

        $aula->update($data);
        return response()->json($aula);
    }

    public function destroy(Aula $aula)
    {
        // Si prefieres borrado físico:
        $aula->delete();

        // Si prefieres “deshabilitar”:
        // $aula->update(['habilitado' => false]);

        return response()->json(null, 204);
    }
}
