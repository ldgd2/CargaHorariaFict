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
use app\Models\Estudiante;
use App\Models\Docente;
use Maatwebsite\Excel\Facades\Excel;
use \PhpOffice\PhpSpreadsheet\IOFactory;
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
    
    /**
     * Busca un Rol por texto (insensible a mayúsculas) y lo crea si no existe.
     * Devuelve la instancia de Rol o null si falla.
     */
    private function obtenerOCrearRolPorTexto(?string $texto): ?Rol
    {
        if (!$texto) return null;
        $nombre = trim((string)$texto);
        $lower = mb_strtolower($nombre);

        $rol = Rol::whereRaw('LOWER(nombre_rol) = ?', [$lower])->first();
        if ($rol) return $rol;

        try {
            $title = mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');
            return Rol::create(['nombre_rol' => $title, 'habilitado' => true]);
        } catch (\Throwable $e) {
            Log::warning('import.rol_create_fail', ['texto' => $texto, 'msg' => $e->getMessage()]);
            return null;
        }
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

        return redirect()->route('admin.usuarios.signup')
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

    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls'
        ]);

        $path = $request->file('archivo')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            Log::error('import.excel_load_error', ['msg' => $e->getMessage()]);
            return back()->withErrors(['archivo' => 'No se pudo leer el Excel. Verifique formato/hojas.']);
        }

        // Detectar si el archivo contiene hojas del paquete multi-hojas (ej. CARRERAS, AULAS, PERIODOS...) 
        $multiSheets = ['USUARIOS','CARRERAS','AULAS','PERIODOS','MATERIAS','GRUPOS','CARGA_HORARIA','DISPONIBILIDAD'];
        foreach ($spreadsheet->getWorksheetIterator() as $ws) {
            $title = strtoupper(preg_replace('/[^A-Za-z0-9]+/u', '_', trim($ws->getTitle())));
            if (in_array($title, $multiSheets, true) && $title !== 'USUARIOS') {
                Log::warning('import.detected_multi_sheet_file', ['archivo'=>$request->file('archivo')->getClientOriginalName(),'sheet'=>$title]);
                return back()->with('warning', "El archivo parece contener hojas de importación multi-hojas (ej: {$title}). Por favor use la pantalla Importación (Admin → Importación) para subir este archivo.");
            }
        }

        // Detectar cómo se relaciona Estudiante en tu esquema:
        // - Si existe la columna id_usuario en estudiante: usamos id_usuario como FK
        // - Si no existe: usamos id_estudiante = id_usuario del usuario
        $estudianteUsaIdUsuario = Schema::hasColumn('estudiante', 'id_usuario');

        $hojas_omitidas = [];   // rol no existe
        $hojas_con_error = [];  // se abortó esa hoja
        $hojas_ok = [];         // importada completa
        $total_creados = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $tituloHoja = trim($worksheet->getTitle());
            $rolSlug = mb_strtolower($tituloHoja);

            // 1) Resolver o crear rol a partir del nombre de la hoja (insensible a mayúsculas)
            $rol = $this->obtenerOCrearRolPorTexto($tituloHoja);
            if (!$rol) {
                Log::warning('import.rol_no_existe', ['hoja' => $tituloHoja]);
                $hojas_omitidas[] = $tituloHoja;
                continue;
            }

            // 2) Leer filas + mapear por encabezados
            $rows = $worksheet->toArray(null, true, true, true);
            if (!$rows || count($rows) < 2) {
                Log::warning('import.hoja_sin_datos', ['hoja' => $tituloHoja]);
                $hojas_omitidas[] = $tituloHoja;
                continue;
            }

            // Mapear headers (fila 1)
            $headersRaw = $rows[1] ?? [];
            // Normalizador de encabezados (case-insensitive, quita acentos y espacios)
            $norm = function ($s) {
                $s = trim((string)$s);
                $s = mb_strtolower($s);
                $s = strtr($s, [
                    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
                    'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u'
                ]);
                $s = preg_replace('/\s+/', ' ', $s);
                return $s;
            };
            $headers = [];
            foreach ($headersRaw as $col => $label) {
                $headers[$col] = $norm($label);
            }

            // --- DETECTAR SI ESTA HOJA PARECE UNA LISTA DE USUARIOS ---
            $headersNormalized = array_map(fn($v)=>trim((string)$v), array_values($headers));
            $possibleEmailNames = array_map(fn($a)=>$norm($a), ['email', 'correo', 'correo electronico', 'e-mail']);
            $hasEmailHeader = false;
            foreach ($headersNormalized as $h) {
                if (in_array($h, $possibleEmailNames, true)) { $hasEmailHeader = true; break; }
            }
            if (!$hasEmailHeader) {
                Log::warning('import.hoja_no_es_usuarios', ['hoja'=>$tituloHoja, 'headers'=>$headers]);
                $hojas_omitidas[] = $tituloHoja;
                continue; // skip this sheet instead of trying to parse it as users
            }

            // Helper para obtener valor por nombre lógico de campo
            $get = function(array $row, array $headers, array $aliasPosibles) use ($norm) {
                // $aliasPosibles = ['nombre', 'name'] etc.
                foreach ($row as $col => $val) {
                    $head = $headers[$col] ?? '';
                    foreach ($aliasPosibles as $a) {
                        if ($norm($head) === $norm($a)) {
                            return is_string($val) ? trim($val) : $val;
                        }
                    }
                }
                return null;
            };

            // Campos típicos por alias
            $ALIAS_NOMBRE    = ['nombre', 'name', 'nombres'];
            $ALIAS_APELLIDO  = ['apellido', 'apellidos', 'lastname'];
            $ALIAS_EMAIL     = ['email', 'correo', 'correo electronico', 'e-mail'];
            $ALIAS_TEL       = ['telefono', 'tel', 'celular', 'phone'];
            $ALIAS_DIR       = ['direccion', 'address', 'domicilio'];
            $ALIAS_PASS      = ['contrasena', 'contraseña', 'password', 'clave'];

            // Estudiante específicos
            $ALIAS_CODIGO    = ['codigo universitario', 'codigo_universitario', 'codigo', 'ru', 'registro universitario'];
            $ALIAS_CARRERA   = ['carrera', 'carrera principal', 'programa'];
            $ALIAS_SEMESTRE  = ['semestre', 'nivel'];

            // Quitar encabezado
            unset($rows[1]);

            $error_en_hoja = false;

            foreach ($rows as $idx => $row) {
                $fila = $idx; 

                $nombre    = $get($row, $headers, $ALIAS_NOMBRE);
                $apellido  = $get($row, $headers, $ALIAS_APELLIDO);
                $email     = $get($row, $headers, $ALIAS_EMAIL);
                $telefono  = $get($row, $headers, $ALIAS_TEL) ?? null;
                $direccion = $get($row, $headers, $ALIAS_DIR) ?? null;
                $password  = $get($row, $headers, $ALIAS_PASS) ?: '12345678';

                // Validación mínima por fila
                $faltantes = [];
                if (!$nombre)   $faltantes[] = 'nombre';
                if (!$apellido) $faltantes[] = 'apellido';
                if (!$email)    $faltantes[] = 'email';

                if ($faltantes) {
                    Log::error('import.faltantes', [
                        'hoja' => $tituloHoja, 'fila' => $fila, 'faltantes' => $faltantes
                    ]);
                    $error_en_hoja = true;
                    break; // aborta SOLO esta hoja (regla que pediste)
                }

                try {
                    // Crear/obtener Usuario
                    $usuario = Usuario::firstOrCreate(
                        ['email' => $email],
                        [
                            'nombre'          => $nombre,
                            'apellido'        => $apellido,
                            'telefono'        => $telefono,
                            'direccion'       => $direccion,
                            'contrasena_hash' => Hash::make($password),
                            'activo'          => 1,
                            'id_rol'          => $rol->id_rol
                        ]
                    );

                    // Si hay tabla pivote usuario_rol, sincroniza también (si corresponde)
                    if (Schema::hasTable('usuario_rol')) {
                        $attrs = [];
                        if (Schema::hasColumn('usuario_rol','fecha_creacion')) {
                            $attrs['fecha_creacion'] = now();
                        }
                        $usuario->roles()->syncWithoutDetaching([$rol->id_rol => $attrs]);
                    }

                    // Específicos por hoja/rol
                    if ($rolSlug === 'docente') {
                        $nroDoc    = $row['G'] ?? null; // seguirá aceptando por letra si existe
                        $tipoCont  = $row['H'] ?? null;
                        $carreraP  = $row['I'] ?? null;
                        $topeHoras = $row['J'] ?? null;

                        Docente::firstOrCreate(
                            // doc: usualmente id_docente = id_usuario (o FK separada)
                            [ Schema::hasColumn('docente','id_usuario') ? 'id_usuario' : 'id_docente' => $usuario->id_usuario ],
                            [
                                'nro_documento'      => $nroDoc,
                                'tipo_contrato'      => $tipoCont,
                                'carrera_principal'  => $carreraP,
                                'tope_horas_semana'  => $topeHoras,
                                'habilitado'         => true
                            ]
                        );
                    }
                    elseif ($rolSlug === 'estudiante') {
    // --- LEE CAMPOS ESPECÍFICOS ---
    $codigo   = $get($row, $headers, $ALIAS_CODIGO);
    $carrera  = $get($row, $headers, $ALIAS_CARRERA);
    $semestre = $get($row, $headers, $ALIAS_SEMESTRE);

    // --- DETECCION DE ESQUEMA ---
    $tieneIdUsuario = \Illuminate\Support\Facades\Schema::hasColumn('estudiante','id_usuario');

    try {
        if ($tieneIdUsuario) {
            // Esquema recomendado: PK autoincrement (id_estudiante) + FK unica (id_usuario)
            \App\Models\Estudiante::firstOrCreate(
                ['id_usuario' => $usuario->id_usuario],
                [
                    'codigo_universitario' => $codigo,
                    'carrera'              => $carrera,
                    'semestre'             => is_null($semestre) ? null : (int)$semestre,
                ]
            );
        } else {

            \Log::error('import.estudiante_sin_id_usuario', [
                'hoja' => $tituloHoja,
                'fila' => $fila,
                'msg'  => 'La tabla "estudiante" no tiene columna id_usuario. Evitando asignar id_estudiante manualmente para no romper el autoincrement.'
            ]);
            $error_en_hoja = true;
            $hojas_con_error[] = $tituloHoja;
            // Mensaje visible en la UI:
            return back()->with('warning',
                "⚠️ Hoja <b>{$tituloHoja}</b> omitida: la tabla <code>estudiante</code> no tiene columna <code>id_usuario</code>. ".
                "Soluciones: <br>1) Agrega <code>id_usuario</code> (FK única a <code>usuario.id_usuario</code>) o <br>2) adapta tu esquema y dime cuál usas."
            );
        }

        \Log::info('import.estudiante_ok', [
            'hoja'       => $tituloHoja,
            'fila'       => $fila,
            'usuario_id' => $usuario->id_usuario,
            'codigo'     => $codigo,
            'carrera'    => $carrera,
            'semestre'   => $semestre
        ]);

    } catch (\Illuminate\Database\QueryException $qe) {
        $sqlState = $qe->errorInfo[0] ?? null;
        $detail   = $qe->errorInfo[2] ?? $qe->getMessage();
        \Log::error('import.estudiante_query_error', [
            'hoja' => $tituloHoja, 'fila' => $fila, 'email' => $email,
            'sqlstate' => $sqlState, 'detail' => $detail
        ]);
        $error_en_hoja = true;
        break;
    } catch (\Throwable $e) {
        \Log::error('import.estudiante_throwable', [
            'hoja' => $tituloHoja, 'fila' => $fila, 'email' => $email,
            'msg' => $e->getMessage()
        ]);
        $error_en_hoja = true;
        break;
    }
}

                    $total_creados++;

                } catch (\Throwable $e) {
                    Log::error('import.error_fila', [
                        'hoja' => $tituloHoja,
                        'fila' => $fila,
                        'email' => $email,
                        'msg' => $e->getMessage()
                    ]);
                    $error_en_hoja = true;
                    break; // aborta SOLO esta hoja
                }
            }

            if ($error_en_hoja) {
                $hojas_con_error[] = $tituloHoja;
            } else {
                $hojas_ok[] = $tituloHoja;
            }
        }

        Log::info('import.resumen', [
            'total_creados'   => $total_creados,
            'hojas_ok'        => $hojas_ok,
            'hojas_con_error' => $hojas_con_error,
            'hojas_omitidas'  => $hojas_omitidas
        ]);

        $resumen = "✅ Importación completada.<br>";
        $resumen .= "Usuarios creados: <b>{$total_creados}</b><br>";
        if ($hojas_ok)        $resumen .= "✔️ Hojas importadas: " . implode(', ', $hojas_ok) . "<br>";
        if ($hojas_con_error) $resumen .= "⚠️ Hojas con error: " . implode(', ', $hojas_con_error) . "<br>";
        if ($hojas_omitidas)  $resumen .= "🚫 Hojas omitidas (rol inexistente): " . implode(', ', $hojas_omitidas);

        return back()->with('ok', $resumen);
    }
private function rolPorHoja($titulo)
{
    return match ($titulo) {
        'docente' => 2,
        'coordinador' => 3,
        'usuario' => 4,
        'estudiante' => 5,
        default => 4,
    };
}
}
