<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class RolController extends Controller
{
    public function index(Request $r)
    {
        $roles = Rol::query()
            ->orderBy('nombre_rol')
            ->paginate(12);

        $usuarios = Usuario::query()
            ->select('id_usuario','nombre','apellido','email')
            ->orderBy('apellido')
            ->limit(100)
            ->get();

        return view('usuarios.admin.roles.index', compact('roles','usuarios'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nombre_rol'  => ['required','string','max:100','unique:rol,nombre_rol'],
            'descripcion' => ['nullable','string','max:255'],
            'habilitado'  => ['nullable','boolean'],
        ]);

        return DB::transaction(function() use ($data, $r) {
            $payload = [
                'nombre_rol' => $data['nombre_rol'],
                'habilitado' => $data['habilitado'] ?? true,
            ];
            if (Schema::hasColumn('rol','descripcion')) {
                $payload['descripcion'] = $data['descripcion'] ?? null;
            }

            $rol = Rol::create($payload);

            // Bitácora
            $this->bit('rol_creado', 'rol', $rol->id_rol, [
                'nuevo' => $payload
            ], $r);

            return back()->with('ok','Rol creado.');
        });
    }

    public function update(Request $r, Rol $rol)
    {
        $data = $r->validate([
            'nombre_rol'  => [
                'required','string','max:100',
                Rule::unique('rol','nombre_rol')->ignore($rol->id_rol,'id_rol')
            ],
            'descripcion' => ['nullable','string','max:255'],
            'habilitado'  => ['nullable','boolean'],
        ]);

        return DB::transaction(function() use ($rol,$data,$r) {
            $before = $rol->only(['nombre_rol','descripcion','habilitado']);

            $payload = [
                'nombre_rol' => $data['nombre_rol'],
                'habilitado' => $data['habilitado'] ?? $rol->habilitado,
            ];
            if (Schema::hasColumn('rol','descripcion')) {
                $payload['descripcion'] = $data['descripcion'] ?? null;
            }

            $rol->update($payload);

            // Bitácora
            $this->bit('rol_actualizado', 'rol', $rol->id_rol, [
                'antes'   => $before,
                'despues' => $payload
            ], $r);

            return back()->with('ok','Rol actualizado.');
        });
    }

    public function toggle(Rol $rol, Request $r)
    {
        return DB::transaction(function() use ($rol,$r) {
            $before = ['habilitado' => $rol->habilitado];

            $rol->habilitado = !$rol->habilitado;
            $rol->save();

            // Bitácora
            $this->bit('rol_toggle', 'rol', $rol->id_rol, [
                'antes'   => $before,
                'despues' => ['habilitado' => $rol->habilitado]
            ], $r);

            return back()->with('ok', $rol->habilitado ? 'Rol habilitado.' : 'Rol inhabilitado.');
        });
    }

    public function destroy(Rol $rol, Request $r)
    {
        return DB::transaction(function() use ($rol,$r) {
            $id = $rol->id_rol;
            $before = $rol->toArray();

            $rol->delete();

            // Bitácora
            $this->bit('rol_eliminado', 'rol', $id, [
                'antes' => $before
            ], $r);

            return back()->with('ok','Rol eliminado.');
        });
    }

    public function asignarRol(Request $r)
    {
        $data = $r->validate([
            'usuario_id' => ['nullable','integer','exists:usuario,id_usuario'],
            'email'      => ['nullable','email'],
            'id_rol'     => ['required','integer','exists:rol,id_rol'],
        ]);

        $usuario = null;
        if (!empty($data['usuario_id'])) {
            $usuario = Usuario::find($data['usuario_id']);
        } elseif (!empty($data['email'])) {
            $usuario = Usuario::where('email',$data['email'])->first();
        }

        if (!$usuario) {
            return back()->withErrors(['usuario'=>'No se encontró el usuario.'])->withInput();
        }

        if ($usuario->roles()->where('rol.id_rol',$data['id_rol'])->exists()) {
            return back()->withErrors(['usuario'=>'El usuario ya tiene ese rol.'])->withInput();
        }

        return DB::transaction(function() use ($usuario,$data,$r) {
            $attrs = [];
            if (Schema::hasColumn('usuario_rol','fecha_creacion')) {
                $attrs['fecha_creacion'] = now();
            }

            $usuario->roles()->attach($data['id_rol'], $attrs);

            // Bitácora (puedes usar entidad 'usuario_rol' si prefieres)
            $this->bit('rol_asignado', 'rol', $data['id_rol'], [
                'usuario_id' => $usuario->id_usuario,
                'id_rol'     => $data['id_rol']
            ], $r);

            return back()->with('ok','Rol asignado al usuario.');
        });
    }

    public function revocarRol(Request $r)
    {
        $data = $r->validate([
            'usuario_id' => ['required','integer','exists:usuario,id_usuario'],
            'id_rol'     => ['required','integer','exists:rol,id_rol'],
        ]);

        return DB::transaction(function() use ($data,$r) {
            $usuario = Usuario::findOrFail($data['usuario_id']);
            $usuario->roles()->detach($data['id_rol']);

            // Bitácora
            $this->bit('rol_revocado', 'rol', $data['id_rol'], [
                'usuario_id' => $usuario->id_usuario,
                'id_rol'     => $data['id_rol']
            ], $r);

            return back()->with('ok','Rol revocado del usuario.');
        });
    }

    /**
     * Helper de bitácora tolerante al esquema.
     * - Escribe 'entidad' y 'entidad_id' si existen.
     * - Usa 'fecha_hora' si existe, de lo contrario 'fecha_creacion'.
     * - Nunca rompe el flujo por errores de bitácora.
     */
    private function bit(string $accion, string $entidad, ?int $entidadId, array $detalle, Request $r): void
    {
        try {
            if (!class_exists(\App\Models\Bitacora::class) || !Schema::hasTable('bitacora')) {
                Log::info('bitacora.skipped', ['accion'=>$accion, 'reason'=>'no model/table']);
                return;
            }

            $row = [
                'accion'     => $accion,
                'detalle'    => json_encode($detalle, JSON_UNESCAPED_UNICODE),
            ];

            // Usuario (según nombre de columna disponible)
            $uid = Auth::id();
            if (Schema::hasColumn('bitacora','user_id'))     $row['user_id'] = $uid;
            if (Schema::hasColumn('bitacora','usuario_id'))  $row['usuario_id'] = $uid;

            if (Schema::hasColumn('bitacora','ip'))          $row['ip'] = $r->ip();

            // Entidad + ID si existen en tu tabla
            if (Schema::hasColumn('bitacora','entidad'))     $row['entidad']    = $entidad;
            if (Schema::hasColumn('bitacora','entidad_id'))  $row['entidad_id'] = $entidadId;

            // Fecha adaptable
            if (Schema::hasColumn('bitacora','fecha_hora')) {
                $row['fecha_hora'] = now();
            } elseif (Schema::hasColumn('bitacora','fecha_creacion')) {
                $row['fecha_creacion'] = now();
            }

            \App\Models\Bitacora::create($row);
            Log::info('bitacora.ok', ['accion'=>$accion, 'entidad'=>$entidad, 'entidad_id'=>$entidadId]);
        } catch (\Throwable $e) {
            Log::warning('bitacora.fail', [
                'accion'=>$accion, 'entidad'=>$entidad, 'entidad_id'=>$entidadId,
                'msg'=>$e->getMessage()
            ]);
        }
    }
}
