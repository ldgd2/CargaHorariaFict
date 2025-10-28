<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Bitacora;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /* =======================
     *  API (igual que antes)
     * ======================= */
    public function index()
    {
        return Usuario::with(['roles','docente','estudiante'])
            ->orderBy('id_usuario','desc')
            ->paginate(20);
    }

    public function show(Usuario $usuario)
    {
        return $usuario->load(['roles','docente','estudiante']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'          => ['required','string','max:100'],
            'apellido'        => ['required','string','max:100'],
            'email'           => ['required','email','max:150','unique:usuario,email'],
            'contrasena_hash' => ['required','string'],
            'telefono'        => ['nullable','string','max:30'],
            'direccion'       => ['nullable','string'],
            'activo'          => ['boolean'],
            'roles'           => ['array'],
            'roles.*'         => ['integer','exists:rol,id_rol'],
        ]);

        return DB::transaction(function() use ($data) {
            $roles = $data['roles'] ?? [];
            unset($data['roles']);

            $usuario = Usuario::create($data);
            if (!empty($roles)) {
                $usuario->roles()->sync($roles);
            }
            return response()->json($usuario->load('roles'), 201);
        });
    }

    public function update(Request $request, Usuario $usuario)
    {
        $data = $request->validate([
            'nombre'          => ['sometimes','string','max:100'],
            'apellido'        => ['sometimes','string','max:100'],
            'email'           => [
                'sometimes','email','max:150',
                Rule::unique('usuario','email')->ignore($usuario->id_usuario,'id_usuario')
            ],
            'contrasena_hash' => ['sometimes','string'],
            'telefono'        => ['nullable','string','max:30'],
            'direccion'       => ['nullable','string'],
            'activo'          => ['sometimes','boolean'],
            'roles'           => ['sometimes','array'],
            'roles.*'         => ['integer','exists:rol,id_rol'],
        ]);

        return DB::transaction(function() use ($usuario, $data) {
            $roles = array_key_exists('roles',$data) ? $data['roles'] : null;
            unset($data['roles']);

            $usuario->update($data);
            if (!is_null($roles)) {
                $usuario->roles()->sync($roles);
            }
            return $usuario->load('roles');
        });
    }

    public function destroy(Usuario $usuario)
    {
        $usuario->delete();
        return response()->noContent();
    }

    /* =====================================
     *  Signup Admin (vista + post del form)
     * ===================================== */

    public function create()
    {
        $roles = Rol::query()
            ->where('habilitado', true)
            ->orderBy('nombre_rol')
            ->get(['id_rol', 'nombre_rol']);

        return view('usuarios.admin.signup', compact('roles'));
    }

    public function storeSignup(Request $r)
{
    $rid = (string) Str::uuid();

    // Reglas
    $rules = [
        'nombre'    => ['required','string','max:100'],
        'apellido'  => ['required','string','max:100'],
        'email'     => ['required','email','max:150','unique:usuario,email'],
        'id_rol'    => ['required','exists:rol,id_rol'],
        'activo'    => ['nullable','boolean'],
        'telefono'  => ['nullable','string','max:30'],
        'direccion' => ['nullable','string'],
        'password'  => ['nullable','confirmed','min:8'],
    ];
    if (Schema::hasColumn('usuario','entidad')) {
        $rules['entidad'] = ['required','string','max:120']; // ajusta max según tu schema
    }

    $v = Validator::make($r->all(), $rules);
    if ($v->fails()) {
        return $this->backOrJson($r, $v->errors()->toArray());
    }

    $data  = $v->validated();
    $plain = $data['password'] ?? Str::password(12);
    $hash  = Hash::make($plain);

    try {
        DB::beginTransaction();

        $insert = [
            'nombre'          => $data['nombre'],
            'apellido'        => $data['apellido'],
            'email'           => $data['email'],
            'contrasena_hash' => $hash,
            'activo'          => $data['activo'] ?? true,
            'telefono'        => $data['telefono'] ?? null,
            'direccion'       => $data['direccion'] ?? null,
        ];
        if (Schema::hasColumn('usuario','fecha_creacion')) {
            $insert['fecha_creacion'] = now();
        }
        if (Schema::hasColumn('usuario','id_rol')) {
            $insert['id_rol'] = $data['id_rol'];
        }
        if (Schema::hasColumn('usuario','entidad')) {
            $insert['entidad'] = $data['entidad']; // <-- ENVIAR
        }

        $user = Usuario::create($insert);

        if (Schema::hasTable('usuario_rol')) {
            $attrs = [];
            if (Schema::hasColumn('usuario_rol','fecha_creacion')) {
                $attrs['fecha_creacion'] = now();
            }
            $user->roles()->attach($data['id_rol'], $attrs);
        }

        DB::commit();

        if ($r->wantsJson()) {
            return response()->json([
                'ok' => true,
                'password_inicial' => $plain,
                'usuario' => $user->load('roles')
            ], 201);
        }

        return redirect()->route('usuarios.signup')
            ->with('ok', 'Usuario creado y rol asignado. Contraseña inicial: '.$plain);

    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack();
        // tu manejo de errores ya existente...
        return $this->backOrJson($r, ['general' => 'No se pudo registrar el usuario (BD).']);
    } catch (\Throwable $e) {
        DB::rollBack();
        return $this->backOrJson($r, ['general' => 'Error inesperado al registrar el usuario.']);
    }
}

    private function backOrJson(Request $r, array $errors)
    {
        if ($r->wantsJson()) {
            // 422 si es de validación, 400/500 si quisieras distinguir más
            return response()->json(['ok' => false, 'errors' => $errors], 422);
        }
        return back()->withErrors($errors)->withInput();
    }
}
