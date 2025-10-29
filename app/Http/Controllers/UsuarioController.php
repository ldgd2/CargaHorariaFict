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
    Log::info('signup.start', ['rid'=>$rid, 'by'=>Auth::id(), 'ip'=>$r->ip(), 'payload'=>$r->except(['password','password_confirmation'])]);

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

    $v = Validator::make($r->all(), $rules);
    if ($v->fails()) {
        Log::warning('signup.validation_failed', ['rid'=>$rid, 'errors'=>$v->errors()->toArray()]);
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

        Log::info('signup.before_create', ['rid'=>$rid, 'insert'=>$insert]);
        $user = Usuario::create($insert);
        Log::info('signup.user_created', ['rid'=>$rid, 'usuario_id'=>$user->id_usuario]);

        if (Schema::hasTable('usuario_rol')) {
            $attrs = [];
            if (Schema::hasColumn('usuario_rol','fecha_creacion')) {
                $attrs['fecha_creacion'] = now();
            }
            Log::info('signup.attach_role', ['rid'=>$rid, 'id_rol'=>$data['id_rol'], 'attrs'=>$attrs]);
            $user->roles()->attach($data['id_rol'], $attrs);
        }

        // ===== BITÁCORA segura (inline) =====
        try {
            if (class_exists(\App\Models\Bitacora::class) && Schema::hasTable('bitacora')) {
                $row = [
                    'accion'     => 'usuario_creado',
                    'detalle'    => json_encode([
                        'rid'    => $rid,
                        'email'  => $user->email,
                        'id_rol' => $data['id_rol'],
                        'origen' => 'admin.signup',
                    ], JSON_UNESCAPED_UNICODE),
                    'usuario_id' => Auth::id(),
                    'ip'         => $r->ip(),
                ];
                if (Schema::hasColumn('bitacora','entidad'))        $row['entidad']     = 'usuario';
                if (Schema::hasColumn('bitacora','entidad_id'))     $row['entidad_id']  = $user->id_usuario;
                if (Schema::hasColumn('bitacora','fecha_creacion')) $row['fecha_creacion'] = now();

                \App\Models\Bitacora::create($row);
                Log::info('signup.bitacora_ok', ['rid'=>$rid, 'row'=>$row]);
            } else {
                Log::info('signup.bitacora_skipped', ['rid'=>$rid, 'exists'=>class_exists(\App\Models\Bitacora::class), 'hasTable'=>Schema::hasTable('bitacora')]);
            }
        } catch (\Throwable $e) {
            // Si Bitacora falla por fillable/columnas, no rompemos el alta:
            Log::warning('signup.bitacora_fail', ['rid'=>$rid, 'msg'=>$e->getMessage()]);
        }
        // ===== fin bitácora =====

        DB::commit();
        Log::info('signup.ok', ['rid'=>$rid, 'usuario_id'=>$user->id_usuario]);

        if ($r->wantsJson()) {
            return response()->json([
                'ok' => true,
                'password_inicial' => $plain,
                'usuario' => $user->load('roles')
            ], 201);
        }

        return redirect()->route('usuarios.signup')
            ->with('ok', 'Usuario creado y rol asignado. Contraseña inicial: '.$plain);

    } catch (QueryException $e) {
        DB::rollBack();
        $sqlState = $e->errorInfo[0] ?? null;
        $detail   = $e->errorInfo[2] ?? $e->getMessage();

        Log::error('signup.query_exception', [
            'rid'=>$rid, 'sqlstate'=>$sqlState, 'detail'=>$detail
        ]);

        if ($sqlState === '23502') { // NOT NULL
            if ($detail && preg_match('/null value in column "([^"]+)"/i', $detail, $m)) {
                return $this->backOrJson($r, ['general' => "Falta el campo obligatorio: {$m[1]}"]);
            }
        }
        if ($sqlState === '23505') { // UNIQUE
            return $this->backOrJson($r, ['email' => 'El email ya está registrado.']);
        }
        if ($sqlState === '23503') { // FK
            return $this->backOrJson($r, ['id_rol' => 'El rol seleccionado no existe o está inhabilitado.']);
        }

        return $this->backOrJson($r, ['general' => 'No se pudo registrar el usuario (BD).']);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('signup.unexpected_exception', [
            'rid'=>$rid,
            'msg'=>$e->getMessage(),
            'file'=>$e->getFile(),
            'line'=>$e->getLine(),
            // comenta la traza si te ensucia mucho el log:
            // 'trace'=>$e->getTraceAsString(),
        ]);
        return $this->backOrJson($r, ['general' => 'Error inesperado al registrar el usuario.']);
    }
}


    private function backOrJson(Request $r, array $errors)
    {
        if ($r->wantsJson()) {
            // 422 si es de validación, 400/500 
            return response()->json(['ok' => false, 'errors' => $errors], 422);
        }
        return back()->withErrors($errors)->withInput();
    }
}
