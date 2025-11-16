<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Carrera;
use App\Models\Aula;
use App\Models\PeriodoAcademico;
use App\Models\Materia;
use App\Models\MateriaCarrera;
use App\Models\Grupo;
use App\Models\Docente;
use App\Models\CargaHoraria;

class ImportacionController extends Controller
{

    /* ---------- UI ---------- */
    public function form()
    {
        return view('usuarios.admin.importacion.importacion');
    }

    private function normalizarRol(?string $rolRaw): ?string
{
    if (!$rolRaw) return null;
    $r = mb_strtolower(trim($rolRaw));
    $map = [
        'administrador' => 'Administrador',
        'admin'         => 'Administrador',
        'coordinador'   => 'Coordinador',
        'coord'         => 'Coordinador',
        'docente'       => 'Docente',
        'profesor'      => 'Docente',
        'teacher'       => 'Docente',
        'estudiante'    => 'Estudiante',
        'alumno'        => 'Estudiante',
        'student'       => 'Estudiante',
    ];
    return $map[$r] ?? null;
}
private function rolRowPorCanon(string $canon): ?\App\Models\Rol
{
    $aliases = [
        'admin'       => ['admin', 'administrador'],
        'coordinador' => ['coordinador', 'coord'],
        'docente'     => ['docente', 'profesor', 'teacher'],
        'estudiante'  => ['estudiante', 'alumno', 'student'],
    ];

    $names = array_map('mb_strtolower', $aliases[$canon] ?? [$canon]);
    return \App\Models\Rol::whereIn(\DB::raw('LOWER(nombre_rol)'), $names)->first();
}


private function rolIdPorNombreEstricto(string $canon): ?int
{

    $rol = \App\Models\Rol::whereRaw('LOWER(nombre_rol)=?', [$canon])->first();
    return $rol?->id_rol;
}

/**
 * Busca un Rol por el texto provisto (insensible a mayúsculas) y lo crea si no existe.
 * Devuelve la instancia de Rol o null si $rolRaw es nulo/vacío.
 */
private function obtenerRolDesdeTexto(?string $rolRaw): ?\App\Models\Rol
{
    if (!$rolRaw) return null;
    $nombre = trim((string)$rolRaw);
    $lower = mb_strtolower($nombre);

    $rol = \App\Models\Rol::whereRaw('LOWER(nombre_rol)=?', [$lower])->first();
    if ($rol) return $rol;

    // Crear rol nuevo con nombre en Title Case y habilitado por defecto
    try {
        $title = mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');
        return \App\Models\Rol::create(['nombre_rol' => $title, 'habilitado' => true]);
    } catch (\Throwable $e) {
        Log::warning('import.rol_create_fail', ['rol'=>$rolRaw,'msg'=>$e->getMessage()]);
        return null;
    }
}

    /* ---------- RUN ---------- */
    public function import(Request $r)
    {
        $r->validate(['archivo'=>'required|file|mimes:xlsx,xls']);

        $path = $r->file('archivo')->getRealPath();
        $book = IOFactory::load($path);

        $resumen = [];
        $errores = [];

        $expect = [
            'USUARIOS'       => fn($ws)=>$this->impUsuarios($ws, $resumen, $errores, $r),
            'CARRERAS'       => fn($ws)=>$this->impCarreras($ws, $resumen, $errores, $r),
            'AULAS'          => fn($ws)=>$this->impAulas($ws, $resumen, $errores, $r),
            'PERIODOS'       => fn($ws)=>$this->impPeriodos($ws, $resumen, $errores, $r),
            'MATERIAS'       => fn($ws)=>$this->impMaterias($ws, $resumen, $errores, $r),
            'GRUPOS'         => fn($ws)=>$this->impGrupos($ws, $resumen, $errores, $r),
            'CARGA_HORARIA'  => fn($ws)=>$this->impCarga($ws, $resumen, $errores, $r),
            'DISPONIBILIDAD' => fn($ws)=>$this->impDisponibilidad($ws, $resumen, $errores, $r),
            'BLOQUEO_AULA'   => fn($ws)=>$this->impBloqueoAula($ws, $resumen, $errores, $r),
        ];

        foreach ($book->getWorksheetIterator() as $ws) {
            $raw = trim((string)$ws->getTitle());
            // Normalizar: reemplaza cualquier secuencia de no-alphanum por underscore y pasar a mayúsculas
            $norm = preg_replace('/[^A-Za-z0-9]+/u', '_', $raw);
            $title = strtoupper(trim($norm, '_'));

            if (isset($expect[$title])) {
                $expect[$title]($ws);
            } else {
                Log::info('import.skip_hoja', ['hoja' => $raw, 'norm' => $title]);
            }
        }

        // —— Bitácora
        $this->bit('importacion_masiva', 'importacion', null, [
            'resumen'=>$resumen, 'errores'=>$errores
        ], $r);
        $html = "<b>Resultado:</b><br>";

        if (!empty($resumen)) {
            $html .= "<ul>";
            foreach ($resumen as $sheet=>$msg) {
                $html .= "<li>✔️ <strong>".htmlspecialchars($sheet, ENT_QUOTES, 'UTF-8')."</strong>: ".htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8')."</li>";
            }
            $html .= "</ul>";
        } else {
            $html .= "<div>No se importó ninguna hoja o no hubo cambios.</div>";
        }
        if (!empty($errores)) {
            $groups = [];
            foreach ($errores as $errMsg) {
                $sheet = 'General';
                if (preg_match('/^([A-Z_Ñ]+)\b/i', trim($errMsg), $m)) {
                    $sheet = strtoupper($m[1]);
                }
                $groups[$sheet][] = $errMsg;
            }

            $html .= "<br><b>Detalles / Errores / Filas omitidas:</b><br>";
            foreach ($groups as $sheet=>$list) {
                $html .= "<h4>".htmlspecialchars($sheet, ENT_QUOTES, 'UTF-8')."</h4>\n<ul>";
                foreach ($list as $line) {
                    $html .= "<li>⚠️ ".htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."</li>";
                }
                $html .= "</ul>";
            }
        }
        session()->flash('imported_sheets', array_keys($resumen));
        session()->flash('import_errors', $errores);

        return back()->with('ok', $html);
    }

    /* =========================
       Helpers genéricos
    ==========================*/
    private function rowsWithHeader($worksheet): array
    {
        $rows = $worksheet->toArray(null, true, true, true);
        if (!$rows || count($rows) < 2) return [];
        $norm = function($s) {
            $s = mb_strtolower(trim((string)$s));
            // Reemplazar vocales acentuadas y la ñ por su forma ASCII
            $s = strtr($s, [
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
                'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u'
            ]);
            // Reemplazar cualquier caracter no ASCII alfanumérico por underscore
            $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
            // Colapsar underscores repetidos y recortar extremos
            $s = preg_replace('/_+/', '_', $s);
            return trim($s, '_');
        };

        $maxScan = min(10, count($rows));
        $headerRowIndex = null;
        $knownKeys = ['nombre_aula','fecha_inicio','fecha_fin','motivo','email','nombre','apellido','cod_materia','periodo_nombre','nombre_grupo'];
        for ($ri = 1; $ri <= $maxScan; $ri++) {
            $cand = $rows[$ri];
            $normed = [];
            foreach ($cand as $col=>$label) $normed[$col] = $norm($label);
            $nonEmpty = array_filter($normed, fn($v)=>$v !== '');
            if (count($nonEmpty) >= 2) { $headerRowIndex = $ri; break; }
            // si alguna de las columnas normalizadas coincide con una llave conocida, aceptar
            foreach ($nonEmpty as $val) {
                if (in_array($val, $knownKeys, true)) { $headerRowIndex = $ri; break 2; }
            }
        }
        if ($headerRowIndex === null) $headerRowIndex = 1;

        $header = [];
        foreach ($rows[$headerRowIndex] as $col=>$label) $header[$col] = $norm($label);
        unset($rows[$headerRowIndex]);

        $out = [];
        foreach ($rows as $i=>$row) {
            $assoc = [];
            foreach ($row as $col=>$val) {
                $key = $header[$col] ?? $col;
                $assoc[$key] = is_string($val) ? trim($val) : $val;
            }
            $out[] = $assoc;
        }
        return $out;
    }

    private function parseBoolFlexible($v): ?bool
    {
        if ($v === null || $v === '') return null;
        $s = mb_strtolower(trim((string)$v));
        $true = ['1','true','t','si','sí','yes','y','verdadero','v'];
        $false = ['0','false','f','no','n','falso'];
        if (in_array($s, $true, true)) return true;
        if (in_array($s, $false, true)) return false;
        return null;
    }

    /**
     * Normalizar fecha a YYYY-MM-DD (acepta datetime con hora)
     */
    private function normalizarFechaSoloDia(?string $v): ?string
    {
        if (!$v) return null;
        // Intenta extraer la parte date (YYYY-MM-DD)
        try {
            $d = new \DateTime($v);
            return $d->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normaliza el valor de estado para la tabla `grupo`.
     * Acepta variantes con tildes y diferentes capitalizaciones y
     * devuelve uno de los valores permitidos por la constraint:
     * 'En Asignacion','Activo','Cerrado','Incompleto'.
     */
    private function normalizeGrupoEstado(?string $v): string
    {
        if (!$v) return 'En Asignacion';
        $s = trim((string)$v);
        $norm = preg_replace('/\s+/', ' ', strtr(mb_strtolower($s), [
            'á' => 'a','é' => 'e','í' => 'i','ó' => 'o','ú' => 'u','ñ' => 'n',
            'ä' => 'a','ë' => 'e','ï' => 'i','ö' => 'o','ü' => 'u'
        ]));

        $map = [
            'en asignacion' => 'En Asignacion',
            'en asignacion '=> 'En Asignacion',
            'activo'        => 'Activo',
            'cerrado'       => 'Cerrado',
            'incompleto'    => 'Incompleto',
        ];

        return $map[$norm] ?? 'En Asignacion';
    }
    private function writeCarreraErrorLog(string $message, array $context = []): void
    {
        try {
            $ts = now()->toDateTimeString();
            $prefix = "HOJA CARRERA -";
            $line = "[{$ts}] {$prefix} {$message} " . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            $rootPath = base_path('Import_error_log');
            @file_put_contents($rootPath, $line, FILE_APPEND | LOCK_EX);
            $storagePath = storage_path('logs' . DIRECTORY_SEPARATOR . 'Import_error_log');
            @file_put_contents($storagePath, $line, FILE_APPEND | LOCK_EX);
            Log::info('import.carreras_written', ['ruta_root'=>$rootPath,'ruta_storage'=>$storagePath,'msg'=>$message]);
        } catch (\Throwable $e) {
            Log::warning('import.carreras_write_log_fail', ['msg'=>$e->getMessage()]);
        }
    }

    private function bit(string $accion, string $entidad, ?int $entidadId, array $detalle, Request $r): void
    {
        try {
            if (!class_exists(\App\Models\Bitacora::class) || !Schema::hasTable('bitacora')) return;
            \App\Models\Bitacora::create([
                'accion'        => $accion,
                'usuario_id'    => optional($r->user())->id_usuario ?? null,
                'ip'            => $r->ip(),
                'descripcion'   => json_encode($detalle, JSON_UNESCAPED_UNICODE),
                'entidad'       => $entidad,
                'entidad_id'    => $entidadId,
                'fecha_creacion'=> now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('bitacora.fail', ['accion'=>$accion,'msg'=>$e->getMessage()]);
        }
    }

    /* =========================
       HOJA: USUARIOS
       Campos: nombre, apellido, email, password?, telefono?, direccion?, rol
       Si rol = docente → crea/actualiza registro Docente.
    ==========================*/
    private function impUsuarios($ws, array &$ok, array &$err, Request $r): void
    {
        $rows = $this->rowsWithHeader($ws);
        if (!$rows) return;
        $anyNew = false;
        foreach ($rows as $row) {
            $email = $row['email'] ?? null;
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $exists = \App\Models\Usuario::where('email', $email)->exists();
                if (!$exists) { $anyNew = true; break; }
            } else {
                $anyNew = true; break;
            }
        }
        if (!$anyNew) { $ok['USUARIOS'] = 'sin cambios - todos los usuarios ya existen'; return; }

        $created = 0; 
        $updated = 0;

        DB::transaction(function () use ($rows, &$created, &$updated, $r) {

            foreach ($rows as $idx => $row) {

                $email = $row['email'] ?? null;
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

                    throw new \RuntimeException("USUARIOS (fila " . ($idx + 2) . "): email inválido.");
                }
                $rolRaw = $row['rol'] ?? null;
                if (!$rolRaw) {
                    throw new \RuntimeException("USUARIOS (fila " . ($idx + 2) . "): rol inválido.");
                }

                $rRow = $this->obtenerRolDesdeTexto($rolRaw);
                if (!$rRow) {
                    throw new \RuntimeException("USUARIOS (fila " . ($idx + 2) . "): no se pudo resolver o crear el rol '{$rolRaw}'.");
                }

            $payload = [
                'nombre'    => $row['nombre']   ?? '',
                'apellido'  => $row['apellido'] ?? '',
                'telefono'  => $row['telefono'] ?? null,
                'direccion' => $row['direccion']?? null,
            ];
            if (!empty($row['password'])) {
                $payload['contrasena_hash'] = \Illuminate\Support\Facades\Hash::make($row['password']);
            }
            $user = \App\Models\Usuario::where('email', $email)->first();
            if ($user) {
                $user->update($payload);
                $updated++;
            } else {
                $payload['email'] = $email;
                $payload['contrasena_hash'] = $payload['contrasena_hash'] ?? \Illuminate\Support\Facades\Hash::make('12345678');
                $user = \App\Models\Usuario::create($payload);
                $created++;
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('usuario_rol')) {
                $attrs = [];
                if (\Illuminate\Support\Facades\Schema::hasColumn('usuario_rol','fecha_creacion')) {
                    $attrs['fecha_creacion'] = now();
                }
                $user->roles()->syncWithoutDetaching([$rRow->id_rol => $attrs]);
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('usuario', 'id_rol')) {
                if ($user->id_rol !== $rRow->id_rol) {
                    $user->id_rol = $rRow->id_rol;
                    $user->save();
                }
            }
            // Si el rol resultante es un docente (según el nombre del rol), crear/actualizar registro en docente.
            $rolNombreNorm = mb_strtolower($rRow->nombre_rol);
            if (in_array($rolNombreNorm, ['docente','profesor','teacher'])) {
                \App\Models\Docente::updateOrCreate(
                    [ \Illuminate\Support\Facades\Schema::hasColumn('docente','id_usuario') ? 'id_usuario' : 'id_docente' => $user->id_usuario ],
                    [
                        'nro_documento'     => $row['nro_documento']     ?? null,
                        'tipo_contrato'     => $row['tipo_contrato']     ?? null,
                        'carrera_principal' => $row['carrera_principal'] ?? null,
                        'tope_horas_semana' => $row['tope_horas_semana'] ?? null,
                        'habilitado'        => true
                    ]
                );
            }
        }
    });

    $ok['USUARIOS'] = "creados {$created}, actualizados {$updated}";
    $this->bit('imp_usuarios', 'importacion', null, compact('created','updated'), $r);
}


        /* =========================
             HOJA: CARRERAS
             Campos esperados en el Excel (encabezados):
                 - nombre                       (obligatorio)
                 - jefe_docente_documento       (recomendado; busca por docente.nro_documento)
                     // alternativo: jefe_docente_email o jefe_email (buscará por usuario.email)
                 - sigla                        (opcional; si no se provee se generará automáticamente)
                 - habilitado                   (opcional; se fuerza habilitado=true al crear desde import)
        ==========================*/
    private function impCarreras($ws, array &$ok, array &$err, Request $r): void
{
    $rows = $this->rowsWithHeader($ws);
    if (!$rows) return;
    $anyNew = false;
    foreach ($rows as $row) {
        $nombre = $row['nombre'] ?? null;
        if (!$nombre) { $anyNew = true; break; }
        $exists = \App\Models\Carrera::whereRaw('LOWER(nombre)=?', [mb_strtolower($nombre)])->exists();
        if (!$exists) { $anyNew = true; break; }
    }
    if (!$anyNew) { $ok['CARRERAS'] = 'sin cambios - todas las carreras ya existen'; $this->bit('imp_carreras','importacion',null,['c'=>0,'u'=>0],$r); return; }
    try {
        $detectedHeaders = [];
        if (count($rows) > 0) {
            $detectedHeaders = array_keys($rows[0]);
        }
        $sample = array_slice($rows, 0, 5);
        $this->writeCarreraErrorLog('Detected headers and sample rows', ['headers'=>$detectedHeaders,'sample'=>$sample]);
        Log::info('import.carreras_detected_headers', ['headers'=>$detectedHeaders,'sample_count'=>count($sample)]);
    } catch (\Throwable $x) {
        Log::warning('import.carreras_diag_fail', ['msg'=>$x->getMessage()]);
    }

    $c = 0; $u = 0;

    DB::transaction(function() use ($rows, &$c, &$u, &$err, $r) {
        $parseBool = fn($v) => $this->parseBoolFlexible($v);

        foreach ($rows as $i => $row) {
            try {
                $nombre = $row['nombre'] ?? null;
                if (!$nombre) {
                    $missing = ['nombre'];
                    Log::warning('import.carreras_row_missing', ['fila'=>$i,'missing'=>$missing,'row'=>$row]);
                    $msg = "CARRERAS (fila {$i}): faltan campos: " . implode(', ', $missing) . ".";
                    $err[] = $msg . " Datos: " . json_encode($row, JSON_UNESCAPED_UNICODE);
                    $this->writeCarreraErrorLog($msg, ['fila'=>$i,'missing'=>$missing,'row'=>$row]);
                    continue;
                }

                // Preferimos jefe_docente_documento, si no existe se acepta jefe_docente_email
                $jefeIdent = $row['jefe_docente_documento'] ?? $row['jefe_docente_email'] ?? $row['jefe_email'] ?? null;
                // Resolver jefe usando helper del modelo Carrera (busca por nro_documento o email)
                $jefeId    = \App\Models\Carrera::resolverJefeId($jefeIdent);
                // Habilitar automáticamente al crear/actualizar desde importación
                $habilitado = true;
                $curr = \App\Models\Carrera::whereRaw('LOWER(nombre)=?', [mb_strtolower($nombre)])->first();

                $payload = ['nombre' => $nombre];
                if ($jefeId !== null)     { $payload['jefe_docente_id'] = $jefeId; }
                if ($habilitado !== null) { $payload['habilitado']      = $habilitado; }

                if ($curr) {
                    $curr->update($payload);
                    $u++;
                } else {
                    //  true al crear
                    $payload['habilitado'] = true;
                    \App\Models\Carrera::create($payload);
                    $c++;
                }

                if ($jefeIdent && $jefeId === null) {
                    $msg = "CARRERAS (fila {$i}): no se encontró Docente para '{$jefeIdent}'";
                    $err[] = $msg;
                    Log::warning('import.carreras_jefe_no_encontrado', ['fila'=>$i,'jefe'=>$jefeIdent,'row'=>$row]);
                    $this->writeCarreraErrorLog($msg, ['fila'=>$i,'jefe'=>$jefeIdent,'row'=>$row]);
                }
            } catch (\Throwable $e) {
                $msg = "CARRERAS (fila {$i}): error al procesar (ver logs).";
                Log::error('import.carreras_row_error', ['fila'=>$i,'msg'=>$e->getMessage(),'row'=>$row]);
                $err[] = $msg;
                $this->writeCarreraErrorLog($msg, ['fila'=>$i,'exception'=>$e->getMessage(),'row'=>$row]);
                continue;
            }
        }
    });
    foreach ($err as $e) {
        if (stripos((string)$e, 'CARRERAS') !== false) {

            $this->writeCarreraErrorLog((string)$e);
        }
    }

    $ok['CARRERAS'] = "creadas {$c}, actualizadas {$u}";
    $this->bit('imp_carreras','importacion',null,compact('c','u'),$r);
}

private function buscarCarreraFlexible(null|string $val): ?\App\Models\Carrera
{
    if ($val === null || $val === '') return null;

    $q = \App\Models\Carrera::query();

    if (is_numeric($val) && \Illuminate\Support\Facades\Schema::hasColumn('carrera','id_carrera')) {
        $c = (clone $q)->where('id_carrera', (int)$val)->first();
        if ($c) return $c;
    }

    if (\Illuminate\Support\Facades\Schema::hasColumn('carrera','nombre')) {
        $c = (clone $q)->whereRaw('LOWER(nombre)=?', [mb_strtolower((string)$val)])->first();
        if ($c) return $c;
    }

    return null;
}


    /* =========================
       HOJA: AULAS
       Campos: codigo, tipo?, capacidad?
    ==========================*/
    private function impAulas($ws, array &$ok, array &$err, Request $r): void
{
    $rows = $this->rowsWithHeader($ws);
    if (!$rows) return;
    $anyNew = false;
    foreach ($rows as $row) {
        $nombre = $row['nombre_aula'] ?? null;
        if (!$nombre) { $anyNew = true; break; }
        if (!Aula::where('nombre_aula', $nombre)->exists()) { $anyNew = true; break; }
    }
    if (!$anyNew) { $ok['AULAS'] = 'sin cambios - todas las aulas ya existen'; return; }

    $c=0;$u=0;
    DB::transaction(function() use($rows,&$c,&$u){
        foreach ($rows as $i=>$row) {
            $nombre = $row['nombre_aula'] ?? null;
            if (!$nombre) throw new \RuntimeException("AULAS (fila {$i}): falta nombre_aula.");

            $payload = [
                'capacidad'  => $row['capacidad']   ?? null,
                'tipo_aula'  => $row['tipo_aula']   ?? null,
                'ubicacion'  => $row['ubicacion']   ?? null,
            ];
            if (Schema::hasColumn('aula','habilitado')) {
                $pb = $this->parseBoolFlexible($row['habilitado'] ?? null);
                $payload['habilitado'] = $pb ?? true;
            }

            $aula = Aula::where('nombre_aula',$nombre)->first();
            if ($aula){ $aula->update($payload); $u++; }
            else { Aula::create(['nombre_aula'=>$nombre]+$payload); $c++; }
        }
    });

    $ok['AULAS']="creadas {$c}, actualizadas {$u}";
    $this->bit('imp_aulas','importacion',null,compact('c','u'),$r);
}


    /* =========================
       HOJA: PERIODOS
       Campos: nombre, fecha_inicio(YYYY-MM-DD), fecha_fin
    ==========================*/
    private function impPeriodos($ws, array &$ok, array &$err, Request $r): void
    {
        $rows = $this->rowsWithHeader($ws);
        if (!$rows) return;
        $anyNew = false;
        foreach ($rows as $row) {
            $nombre = $row['nombre'] ?? null;
            if (!$nombre) { $anyNew = true; break; }
            if (!PeriodoAcademico::where('nombre', $nombre)->exists()) { $anyNew = true; break; }
        }
        if (!$anyNew) { $ok['PERIODOS'] = 'sin cambios - todos los periodos ya existen'; return; }
        $c=0;$u=0;

        DB::transaction(function() use($rows,&$c,&$u){
            foreach ($rows as $i=>$row) {
                $nombre = $row['nombre'] ?? null;
                $fi = $this->normalizarFechaSoloDia($row['fecha_inicio'] ?? null);
                $ff = $this->normalizarFechaSoloDia($row['fecha_fin'] ?? null);
                if (!$nombre || !$fi || !$ff) throw new \RuntimeException("PERIODOS (fila {$i}): datos incompletos.");
                $payload = ['fecha_inicio'=>$fi,'fecha_fin'=>$ff];
                $p = PeriodoAcademico::where('nombre',$nombre)->first();
                if ($p){ $p->update($payload); $u++; }
                else { PeriodoAcademico::create(['nombre'=>$nombre]+$payload+['estado_publicacion'=>'borrador','activo'=>true]); $c++; }
            }
        });
        $ok['PERIODOS']="creados {$c}, actualizados {$u}";
        $this->bit('imp_periodos','importacion',null,compact('c','u'),$r);
    }

    /* =========================
       HOJA: MATERIAS
       Campos: codigo, nombre, horas_semanales, carrera_codigo
       → crea Materia y vínculo materia_carrera
    ==========================*/
    private function impMaterias($ws, array &$ok, array &$err, Request $r): void
{
    $rows = $this->rowsWithHeader($ws);
    if (!$rows) return;
    $anyNew = false;
    foreach ($rows as $row) {
        $cod = $row['cod_materia'] ?? null;
        $car = $row['carrera'] ?? null;
        if (!$cod || !$car) { $anyNew = true; break; }
        $m = \App\Models\Materia::where('cod_materia', $cod)->first();
        if (!$m) { $anyNew = true; break; }
        if (Schema::hasTable('materia_carrera')) {
            $carrera = $this->buscarCarreraFlexible((string)$car);
            if (!$carrera) { $anyNew = true; break; }
            $exists = \App\Models\MateriaCarrera::where('id_materia', $m->id_materia)->where('id_carrera', $carrera->id_carrera)->exists();
            if (!$exists) { $anyNew = true; break; }
        }
    }
    if (!$anyNew) { $ok['MATERIAS'] = 'sin cambios - todas las materias y vínculos ya existen'; return; }

    $c=0;$u=0;$vinc=0;

    DB::transaction(function() use($rows,&$c,&$u,&$vinc){
        foreach ($rows as $i=>$row) {
            $cod   = $row['cod_materia']      ?? null;
            $nombre= $row['nombre']           ?? null;
            $hs    = (int)($row['horas_semanales'] ?? 2);
            $cred  = isset($row['creditos']) ? (int)$row['creditos'] : null;
            $car   = $row['carrera']          ?? null; 

            if (!$cod || !$nombre || !$car) {
                throw new \RuntimeException("MATERIAS (fila ".($i+2)."): falta cod_materia/nombre/carrera.");
            }

            $carrera = $this->buscarCarreraFlexible((string)$car);
            if (!$carrera) {
                throw new \RuntimeException("MATERIAS (fila ".($i+2)."): carrera '{$car}' no existe (envía id_carrera o nombre).");
            }

            $m = \App\Models\Materia::where('cod_materia',$cod)->first();

            $payload = [
                'cod_materia'     => $cod,
                'nombre'          => $nombre,
                'horas_semanales' => $hs,
                'id_carrera'      => $carrera->id_carrera,
            ];
            if ($cred !== null && \Illuminate\Support\Facades\Schema::hasColumn('materia','creditos')) {
                $payload['creditos'] = $cred;
            }

            if ($m) { $m->update($payload); $u++; }
            else    { $m = \App\Models\Materia::create($payload); $c++; }


            if (\Illuminate\Support\Facades\Schema::hasTable('materia_carrera')) {
                $exists = \App\Models\MateriaCarrera::where([
                    'id_materia'=>$m->id_materia,
                    'id_carrera'=>$carrera->id_carrera
                ])->exists();
                if (!$exists) {
                    \App\Models\MateriaCarrera::create([
                        'id_materia'=>$m->id_materia,
                        'id_carrera'=>$carrera->id_carrera
                    ]);
                    $vinc++;
                }
            }
        }
    });

    $ok['MATERIAS']="creadas {$c}, actualizadas {$u}, vínculos {$vinc}";
    $this->bit('imp_materias','importacion',null,compact('c','u','vinc'),$r);
}



    /* =========================
       HOJA: GRUPOS
       Campos: nombre_grupo, periodo_nombre, materia_codigo
    ==========================*/
private function impGrupos($ws, array &$ok, array &$err, Request $r): void
{
    $rows = $this->rowsWithHeader($ws);
    if (!$rows) return;
    $anyNew = false;
    foreach ($rows as $row) {
        $periodoNombre = trim($row['periodo_nombre'] ?? '');
        $codMateria    = trim($row['cod_materia']    ?? '');
        $nombreGrupo   = trim($row['nombre_grupo']   ?? '');
        $car           = trim((string)($row['carrera'] ?? ''));
        if (!$periodoNombre || !$codMateria || !$nombreGrupo || !$car) { $anyNew = true; break; }
        $periodo = \App\Models\PeriodoAcademico::where('nombre', $periodoNombre)->first();
        $materia = \App\Models\Materia::where('cod_materia', $codMateria)->first();
        $carrera = $this->buscarCarreraFlexible($car);
        if (!$periodo || !$materia || !$carrera) { $anyNew = true; break; }
        $exists = \App\Models\Grupo::where('id_periodo', $periodo->id_periodo)
            ->where('id_materia', $materia->id_materia)
            ->where('id_carrera', $carrera->id_carrera)
            ->where('nombre_grupo', $nombreGrupo)
            ->exists();
        if (!$exists) { $anyNew = true; break; }
    }
    if (!$anyNew) { $ok['GRUPOS'] = 'sin cambios - todos los grupos ya existen'; $this->bit('imp_grupos','importacion',null,['c'=>0,'u'=>0],$r); return; }

    $c = 0; $u = 0;

    DB::transaction(function() use ($rows, &$c, &$u) {

        foreach ($rows as $i => $row) {
            $periodoNombre = trim($row['periodo_nombre'] ?? '');
            $codMateria    = trim($row['cod_materia']    ?? '');
            $nombreGrupo   = trim($row['nombre_grupo']   ?? '');
            $car           = trim((string)($row['carrera'] ?? '')); 
            $capacidad     = (int)($row['capacidad_estudiantes'] ?? 40);
            $estado        = $this->normalizeGrupoEstado($row['estado'] ?? null);

            if (!$periodoNombre || !$codMateria || !$nombreGrupo || !$car) {
                throw new \RuntimeException("GRUPOS (fila ".($i+2)."): falta periodo_nombre/cod_materia/nombre_grupo/carrera.");
            }

            $periodo = \App\Models\PeriodoAcademico::where('nombre', $periodoNombre)->first();
            $materia = \App\Models\Materia::where('cod_materia', $codMateria)->first();
            $carrera = $this->buscarCarreraFlexible($car);

            if (!$periodo || !$materia || !$carrera) {
                throw new \RuntimeException("GRUPOS (fila ".($i+2)."): período, materia o carrera inexistentes.");
            }

            $where = [
                'id_periodo'   => $periodo->id_periodo,
                'id_materia'   => $materia->id_materia,
                'id_carrera'   => $carrera->id_carrera,
                'nombre_grupo' => $nombreGrupo,
            ];

            $values = [
                'capacidad_estudiantes' => $capacidad,
                'estado'                => $estado,
            ];

            $g = \App\Models\Grupo::updateOrCreate($where, $values);

            // Contadores
            $g->wasRecentlyCreated ? $c++ : $u++;
        }
    });

    $ok['GRUPOS'] = "creados {$c}, actualizados {$u}";
    $this->bit('imp_grupos','importacion',null,compact('c','u'),$r);
}



    /* =========================
       HOJA: CARGA_HORARIA
       Campos: periodo_nombre, nombre_grupo, materia_codigo, docente_email, aula_codigo, dia_semana(1..7), hora_inicio(HH:MM), hora_fin(HH:MM)
       (usa tu migración con checks)
    ==========================*/
    private function impCarga($ws, array &$ok, array &$err, Request $r): void
{
    $rows = $this->rowsWithHeader($ws);
    if (!$rows) return;
    $anyNew = false;
    foreach ($rows as $i=>$row) {
        $pNom = $row['periodo_nombre'] ?? null;
        $gNom = $row['nombre_grupo']   ?? null;
        $mCod = $row['materia_codigo'] ?? ($row['cod_materia'] ?? null);
        $docE = $row['docente_email']  ?? null;
        $aNom = $row['aula_nombre'] ?? null;
        $dia  = (int)($row['dia_semana'] ?? 0);
        $ini  = $row['hora_inicio'] ?? null;
        $fin  = $row['hora_fin']    ?? null;
        if (!$pNom||!$gNom||!$mCod||!$docE||!$aNom||!$dia||!$ini||!$fin) { $anyNew = true; break; }

        $periodo = PeriodoAcademico::where('nombre',$pNom)->first();
        $materia = Materia::where('cod_materia',$mCod)->first();
        $grupo   = ($periodo && $materia)
            ? Grupo::where('id_periodo',$periodo->id_periodo)
                   ->where('id_materia',$materia->id_materia)
                   ->where('nombre_grupo',$gNom)->first()
            : null;
        $docenteUser = Usuario::where('email',$docE)->first();
        $doc         = $docenteUser
            ? (Schema::hasColumn('docente','id_usuario')
                ? Docente::where('id_usuario',$docenteUser->id_usuario)->first()
                : Docente::find($docenteUser->id_usuario))
            : null;
        $aula = Aula::where('nombre_aula',$aNom)->first();
        if (!$periodo || !$materia || !$grupo || !$doc || !$aula) { $anyNew = true; break; }

        $startMin = $this->timeToMinutes($ini);
        $endMin   = $this->timeToMinutes($fin);
        if ($startMin === null || $endMin === null || $endMin <= $startMin) { $anyNew = true; break; }
        $identical = \App\Models\CargaHoraria::where('id_grupo', $grupo->id_grupo)
            ->where('id_docente', $doc->id_docente)
            ->where('id_aula', $aula->id_aula)
            ->where('dia_semana', $dia)
            ->where('hora_inicio', $ini)
            ->where('hora_fin', $fin)
            ->exists();
        if (!$identical) {
            $conflict = \App\Models\CargaHoraria::where('id_aula', $aula->id_aula)
                ->where('dia_semana', $dia)
                ->where(function($q) use ($startMin, $endMin) {
                    $q->where('start_min', '<', $endMin)
                      ->where('end_min', '>', $startMin);
                })->exists();
            if (!$conflict) { $anyNew = true; break; }
        }
    }
    if (!$anyNew) { $ok['CARGA_HORARIA'] = 'sin cambios - todas las cargas ya existen o confligen'; return; }

    $c=0;$skip=0;
    DB::transaction(function() use($rows,&$c,&$skip){
        foreach ($rows as $i=>$row) {
            $pNom = $row['periodo_nombre'] ?? null;
            $gNom = $row['nombre_grupo']   ?? null;
            $mCod = $row['materia_codigo'] ?? ($row['cod_materia'] ?? null);
            $docE = $row['docente_email']  ?? null;
            $aNom = $row['aula_nombre'] ?? null;
            $dia  = (int)($row['dia_semana'] ?? 0);
            $ini  = $row['hora_inicio'] ?? null;
            $fin  = $row['hora_fin']    ?? null;

            if (!$pNom||!$gNom||!$mCod||!$docE||!$aNom||!$dia||!$ini||!$fin)
                throw new \RuntimeException("CARGA_HORARIA (fila {$i}): datos incompletos.");

            $periodo = PeriodoAcademico::where('nombre',$pNom)->first();
            $materia = Materia::where('cod_materia',$mCod)->first();
            $grupo   = ($periodo && $materia)
                ? Grupo::where('id_periodo',$periodo->id_periodo)
                       ->where('id_materia',$materia->id_materia)
                       ->where('nombre_grupo',$gNom)->first()
                : null;

            $docenteUser = Usuario::where('email',$docE)->first();
            $doc         = $docenteUser
                ? (Schema::hasColumn('docente','id_usuario')
                    ? Docente::where('id_usuario',$docenteUser->id_usuario)->first()
                    : Docente::find($docenteUser->id_usuario))
                : null;

            // Aula por nombre_aula
            $aula = Aula::where('nombre_aula',$aNom)->first();

            if (!$periodo || !$materia || !$grupo || !$doc || !$aula) {
                $skip++;
                continue;
            }
            $startMin = $this->timeToMinutes($ini);
            $endMin   = $this->timeToMinutes($fin);
            if ($startMin === null || $endMin === null || $endMin <= $startMin) {
                $skip++;
                Log::warning('import.carga_time_invalid', ['fila'=>$i,'hora_inicio'=>$ini,'hora_fin'=>$fin]);
                $err[] = "CARGA_HORARIA (fila {$i}): rango horario inválido ({$ini} - {$fin}).";
                continue;
            }
            $identicalQuery = \App\Models\CargaHoraria::where('id_grupo', $grupo->id_grupo)
                ->where('id_docente', $doc->id_docente)
                ->where('id_aula', $aula->id_aula)
                ->where('dia_semana', $dia)
                ->where('hora_inicio', $ini)
                ->where('hora_fin', $fin);

            if ($identicalQuery->exists()) {
                $skip++;
                $msg = "CARGA_HORARIA (fila {$i}): registro ya existe (omitido).";
                $err[] = $msg;
                Log::info('import.carga_duplicate', ['fila'=>$i,'grupo'=>$grupo->id_grupo,'aula'=>$aula->id_aula,'dia'=>$dia,'inicio'=>$ini,'fin'=>$fin]);
                continue;
            }
            $conflictQuery = \App\Models\CargaHoraria::where('id_aula', $aula->id_aula)
                ->where('dia_semana', $dia)
                ->where(function($q) use ($startMin, $endMin) {
                    $q->where('start_min', '<', $endMin)
                      ->where('end_min', '>', $startMin);
                });

            if ($conflictQuery->exists()) {
                $skip++;
                Log::warning('import.carga_conflict', ['fila'=>$i,'aula'=>$aula->id_aula,'dia'=>$dia,'start_min'=>$startMin,'end_min'=>$endMin]);
                $err[] = "CARGA_HORARIA (fila {$i}): conflicto de aula/horario para aula_id={$aula->id_aula} dia={$dia} ({$ini}-{$fin}).";
                continue;
            }

            $payload = [
                'id_grupo'     => $grupo->id_grupo,
                'id_docente'   => $doc->id_docente,
                'id_aula'      => $aula->id_aula,
                'dia_semana'   => $dia,
                'hora_inicio'  => $ini,
                'hora_fin'     => $fin,
                'estado'       => 'Vigente',
            ];

            if (Schema::hasColumn('carga_horaria','start_min') && Schema::hasColumn('carga_horaria','end_min')) {
                $payload['start_min'] = $startMin;
                $payload['end_min']   = $endMin;
            }

            \App\Models\CargaHoraria::create($payload);
            $c++;
        }
    });

    $ok['CARGA_HORARIA']="insertadas {$c}, omitidas {$skip}";
    $this->bit('imp_carga','importacion',null,compact('c','skip'),$r);
}

    /**
     * HOJA: DISPONIBILIDAD
     * Campos esperados: periodo_nombre, docente_email, dia_semana, hora_inicio, hora_fin, prioridad?, observaciones?
     */
    private function impDisponibilidad($ws, array &$ok, array &$err, Request $r): void
    {
        $rows = $this->rowsWithHeader($ws);
        if (!$rows) return;
        $anyNew = false;
        foreach ($rows as $row) {
            $pNom = $row['periodo_nombre'] ?? null;
            $docE = $row['docente_email'] ?? null;
            $dia  = isset($row['dia_semana']) ? (int)$row['dia_semana'] : 0;
            $ini  = $row['hora_inicio'] ?? null;
            $fin  = $row['hora_fin'] ?? null;
            if (!$pNom || !$docE || !$dia || !$ini || !$fin) { $anyNew = true; break; }
            $periodo = PeriodoAcademico::where('nombre', $pNom)->first();
            $user = Usuario::where('email', trim($docE))->first();
            $doc = $user ? (Schema::hasColumn('docente','id_usuario') ? Docente::where('id_usuario',$user->id_usuario)->first() : Docente::find($user->id_usuario)) : null;
            if (!$periodo || !$doc) { $anyNew = true; break; }
            $exists = \App\Models\DisponibilidadDocente::where('id_docente', $doc->id_docente)
                ->where('id_periodo', $periodo->id_periodo)
                ->where('dia_semana', $dia)
                ->where('hora_inicio', $ini)
                ->where('hora_fin', $fin)
                ->exists();
            if (!$exists) { $anyNew = true; break; }
        }
        if (!$anyNew) { $ok['DISPONIBILIDAD'] = 'sin cambios - todas las disponibilidades ya existen'; return; }

        $c = 0; $skipped = 0;
        DB::transaction(function() use($rows, &$c, &$skipped, &$err) {
            foreach ($rows as $i => $row) {
                $pNom = $row['periodo_nombre'] ?? null;
                $docE = $row['docente_email'] ?? null;
                $dia  = isset($row['dia_semana']) ? (int)$row['dia_semana'] : 0;
                $ini  = $row['hora_inicio'] ?? null;
                $fin  = $row['hora_fin'] ?? null;
                $prio = isset($row['prioridad']) ? (int)$row['prioridad'] : 1;
                $obs  = $row['observaciones'] ?? null;

                if (!$pNom || !$docE || !$dia || !$ini || !$fin) {
                    $err[] = "DISPONIBILIDAD (fila {$i}): datos incompletos.";
                    $skipped++;
                    continue;
                }

                $periodo = PeriodoAcademico::where('nombre', $pNom)->first();
                $user = Usuario::where('email', trim($docE))->first();
                $doc = $user ? (Schema::hasColumn('docente','id_usuario') ? Docente::where('id_usuario',$user->id_usuario)->first() : Docente::find($user->id_usuario)) : null;

                if (!$periodo || !$doc) { $skipped++; continue; }

                try {
                    $exists = \App\Models\DisponibilidadDocente::where('id_docente', $doc->id_docente)
                        ->where('id_periodo', $periodo->id_periodo)
                        ->where('dia_semana', $dia)
                        ->where('hora_inicio', $ini)
                        ->where('hora_fin', $fin)
                        ->exists();
                    if ($exists) {
                        $skipped++;
                        $err[] = "DISPONIBILIDAD (fila {$i}): ya existe registro idéntico (omitido).";
                        Log::info('import.disponibilidad_duplicate', ['fila'=>$i,'docente'=>$doc->id_docente,'periodo'=>$periodo->id_periodo,'dia'=>$dia,'inicio'=>$ini,'fin'=>$fin]);
                        continue;
                    }

                    \App\Models\DisponibilidadDocente::create([
                        'id_docente' => $doc->id_docente,
                        'id_periodo' => $periodo->id_periodo,
                        'dia_semana' => $dia,
                        'hora_inicio'=> $ini,
                        'hora_fin'   => $fin,
                        'observaciones' => $obs,
                        'prioridad'  => $prio,
                    ]);
                    $c++;
                } catch (\Throwable $e) {
                    $skipped++;
                    \Log::warning('import.disponibilidad_row_fail', ['fila'=>$i,'msg'=>$e->getMessage()]);
                }
            }
        });

        $ok['DISPONIBILIDAD'] = "insertadas {$c}, omitidas {$skipped}";
        $this->bit('imp_disponibilidad','importacion',null,compact('c','skipped'),$r);
    }


    /* =========================
       HOJA: BLOQUEO_AULA
       Campos: id_aula OR nombre_aula, fecha_inicio (YYYY-MM-DD), fecha_fin (YYYY-MM-DD), motivo
       El campo registrado_por será tomado del usuario autenticado (id_usuario)
    ==========================*/
    private function impBloqueoAula($ws, array &$ok, array &$err, Request $r): void
    {
        $rows = $this->rowsWithHeader($ws);
        if (!$rows) return;
        $anyNew = false;
        $anyNonEmpty = false;
        foreach ($rows as $row) {
            // Use normalized keys from rowsWithHeader
            $nombre = trim((string)($row['nombre_aula'] ?? ''));
            $fiRaw  = $row['fecha_inicio'] ?? null;
            $ffRaw  = $row['fecha_fin'] ?? null;
            $motivoRaw = trim((string)($row['motivo'] ?? ''));
            if ($nombre === '' && empty($fiRaw) && empty($ffRaw) && $motivoRaw === '') {
                continue;
            }
            $anyNonEmpty = true;
            if ($nombre === '' || empty($fiRaw) || empty($ffRaw)) { $anyNew = true; break; }

            $fi = $this->normalizarFechaSoloDia($fiRaw);
            $ff = $this->normalizarFechaSoloDia($ffRaw);
            if (!$fi || !$ff) { $anyNew = true; break; }
            $a = Aula::where('nombre_aula', $nombre)->first();
            if (!$a) { $anyNew = true; break; }
            $motivo = $motivoRaw ?: null;
            $exists = \App\Models\BloqueoAula::where('id_aula', $a->id_aula)
                ->where('fecha_inicio', $fi)
                ->where('fecha_fin', $ff)
                ->where(function($q) use ($motivo) {
                    if ($motivo === null || $motivo === '') {
                        $q->whereNull('motivo')->orWhere('motivo', '');
                    } else {
                        $q->where('motivo', $motivo);
                    }
                })->exists();

            if (!$exists) { $anyNew = true; break; }
        }

        if (!$anyNonEmpty) { $ok['BLOQUEO_AULA'] = 'sin cambios - hoja sin filas con datos'; return; }
        if (!$anyNew) { $ok['BLOQUEO_AULA'] = 'sin cambios - todos los bloqueos ya existen'; return; }

        if (!Schema::hasTable('bloqueo_aula')) {
            Log::warning('import.bloqueo_table_missing', []);
            return;
        }
        $detectedHeaders = array_keys($rows[0] ?? []);
        Log::info('import.bloqueo_detected_headers', ['headers'=>$detectedHeaders, 'sample'=>array_slice($rows,0,5)]);
        $aliases = [
            'id_aula' => ['id_aula','idaula','aula','id'],
            'nombre_aula' => ['nombre_aula','aula_nombre','nombre','nombreaula','aula'],
            'fecha_inicio' => ['fecha_inicio','inicio','start','start_date','fecha_inicio_hora'],
            'fecha_fin' => ['fecha_fin','fin','end','end_date','fecha_fin_hora'],
            'motivo' => ['motivo','razon','reason','descripcion','descripcion_motivo']
        ];

        $findKey = function(array $keys, array $candidates) {
            foreach ($candidates as $cand) {
                foreach ($keys as $k) {
                    if ($k === null) continue;
                    if (mb_strtolower(trim((string)$k)) === mb_strtolower(trim((string)$cand))) return $k;
                }
            }
            return null;
        };

        $h_idAula = $findKey($detectedHeaders, $aliases['id_aula']);
        $h_nombreAula = $findKey($detectedHeaders, $aliases['nombre_aula']);
        $h_fi = $findKey($detectedHeaders, $aliases['fecha_inicio']);
        $h_ff = $findKey($detectedHeaders, $aliases['fecha_fin']);
        $h_motivo = $findKey($detectedHeaders, $aliases['motivo']);
    $missingHeaders = [];
    if (!$h_nombreAula) $missingHeaders[] = 'nombre_aula';
    if (!$h_fi) $missingHeaders[] = 'fecha_inicio';
    if (!$h_ff) $missingHeaders[] = 'fecha_fin';
        if (!empty($missingHeaders)) {
            $msg = 'BLOQUEO_AULA: Encabezados faltantes: ' . implode(', ', $missingHeaders) . '. Encabezados detectados: ' . json_encode($detectedHeaders, JSON_UNESCAPED_UNICODE);
            Log::warning('import.bloqueo_headers_missing', ['msg'=>$msg]);
            $err[] = $msg;
        }

        $c = 0; $u = 0; $skipped = 0;
    DB::transaction(function() use($rows, &$c, &$u, &$skipped, &$err, $r, $h_idAula, $h_nombreAula, $h_fi, $h_ff, $h_motivo) {
            foreach ($rows as $i => $row) {
                $idAula = null;
                if ($h_idAula && isset($row[$h_idAula]) && is_numeric($row[$h_idAula])) {
                    $idAula = (int)$row[$h_idAula];
                } elseif ($h_nombreAula && !empty($row[$h_nombreAula])) {
                    $a = Aula::where('nombre_aula', trim($row[$h_nombreAula]))->first();
                    if ($a) $idAula = $a->id_aula;
                }

                $fiRaw = ($h_fi ? ($row[$h_fi] ?? null) : ($row['fecha_inicio'] ?? null));
                $ffRaw = ($h_ff ? ($row[$h_ff] ?? null) : ($row['fecha_fin'] ?? null));
                $fi = $this->normalizarFechaSoloDia($fiRaw);
                $ff = $this->normalizarFechaSoloDia($ffRaw);
                $motivo = trim((string)($h_motivo ? ($row[$h_motivo] ?? '') : ($row['motivo'] ?? ''))) ?: null;
                    $isAllEmpty = true;
                    foreach ([$h_nombreAula,$h_fi,$h_ff,$h_motivo] as $hk) {
                        if ($hk && isset($row[$hk]) && trim((string)$row[$hk]) !== '') { $isAllEmpty = false; break; }
                    }
                    if ($isAllEmpty) {
                        continue;
                    }

                    if (!$idAula || !$fi || !$ff) {
                        $skipped++;
                        $msg = "BLOQUEO_AULA (fila {$i}): datos incompletos (nombre_aula, fecha_inicio, fecha_fin).";
                        $err[] = $msg . ' Row: ' . json_encode($row, JSON_UNESCAPED_UNICODE);
                        Log::warning('import.bloqueo_row_invalid', ['fila'=>$i,'row'=>$row]);
                        continue;
                    }

                try {
                    $payload = [
                        'id_aula' => $idAula,
                        'fecha_inicio' => $fi,
                        'fecha_fin'   => $ff,
                        'motivo'      => $motivo,
                    ];
                    $registradoPor = optional($r->user())->id_usuario ?? null;
                    if (Schema::hasColumn('bloqueo_aula','registrado_por')) {
                        $payload['registrado_por'] = $registradoPor;
                    }
                    $exists = \App\Models\BloqueoAula::where('id_aula', $idAula)
                        ->where('fecha_inicio', $fi)
                        ->where('fecha_fin', $ff)
                        ->where(function($q) use ($motivo) {
                            if ($motivo === null || $motivo === '') {
                                $q->whereNull('motivo')->orWhere('motivo', '');
                            } else {
                                $q->where('motivo', $motivo);
                            }
                        })->exists();

                    if ($exists) {
                        $skipped++;
                        $err[] = "BLOQUEO_AULA (fila {$i}): ya existe bloqueo idéntico (omitido).";
                        Log::info('import.bloqueo_duplicate', ['fila'=>$i,'id_aula'=>$idAula,'fi'=>$fi,'ff'=>$ff,'motivo'=>$motivo]);
                        continue;
                    }

                    \App\Models\BloqueoAula::create($payload);
                    $c++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $err[] = "BLOQUEO_AULA (fila {$i}): error al insertar ({$e->getMessage()}).";
                    Log::error('import.bloqueo_insert_fail', ['fila'=>$i,'msg'=>$e->getMessage(),'row'=>$row]);
                    continue;
                }
            }
        });

        $ok['BLOQUEO_AULA'] = "creados {$c}, omitidos {$skipped}";
        $this->bit('imp_bloqueo_aula','importacion',null,compact('c','skipped'),$r);
    }

    /**
     * Convierte una cadena de tiempo HH:MM o HH:MM:SS a minutos desde medianoche.
     * Devuelve null si el formato no es válido.
     */
    private function timeToMinutes(?string $t): ?int
    {
        if (!$t) return null;
        $s = trim((string)$t);
        // Aceptar formatos H:M, HH:MM, HH:MM:SS
        $parts = explode(':', $s);
        if (count($parts) < 2) return null;
        $h = (int)$parts[0];
        $m = (int)$parts[1];
        if ($h < 0 || $h > 23 || $m < 0 || $m > 59) return null;
        return $h * 60 + $m;
    }
}
