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
        ];

        foreach ($book->getWorksheetIterator() as $ws) {
            $title = strtoupper(trim($ws->getTitle()));
            if (isset($expect[$title])) {
                $expect[$title]($ws); 
            } else {
                Log::info('import.skip_hoja', ['hoja'=>$title]);
            }
        }

        // —— Bitácora
        $this->bit('importacion_masiva', 'importacion', null, [
            'resumen'=>$resumen, 'errores'=>$errores
        ], $r);

        $html = "<b>Resultado:</b><br>";
        foreach ($resumen as $k=>$v) { $html .= "✔️ {$k}: {$v}<br>"; }
        if ($errores) {
            $html .= "<br><b>Errores:</b><br>";
            foreach ($errores as $e) { $html .= "⚠️ {$e}<br>"; }
        }

        return back()->with('ok', $html);
    }

    /* =========================
       Helpers genéricos
    ==========================*/
    private function rowsWithHeader($worksheet): array
    {
        $rows = $worksheet->toArray(null, true, true, true);
        if (!$rows || count($rows) < 2) return [];

        $norm = fn($s)=> preg_replace('/\s+/', ' ',
                    strtr(mb_strtolower(trim((string)$s)), [
                        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
                        'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u'
                    ]));

        $header = [];
        foreach ($rows[1] as $col=>$label) $header[$col] = $norm($label);
        unset($rows[1]);

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

        $created = 0; 
        $updated = 0;

        DB::transaction(function () use ($rows, &$created, &$updated, $r) {

            foreach ($rows as $idx => $row) {

                $email = $row['email'] ?? null;
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

                    throw new \RuntimeException("USUARIOS (fila " . ($idx + 2) . "): email inválido.");
                }
                $rolCanon = $this->normalizarRol($row['rol'] ?? null);
            if (!$rolCanon) {
                throw new \RuntimeException("USUARIOS (fila " . ($idx + 2) . "): rol inválido. Use admin, coordinador, docente o estudiante.");
            }

            $rRow = $this->rolRowPorCanon($rolCanon);
            if (!$rRow) {
                throw new \RuntimeException("USUARIOS (fila " . ($idx + 2) . "): el rol '{$rolCanon}' no existe en la BD (aceptados: " . implode(', ', [
                    ...(['admin','administrador']),
                    ...(['coordinador','coord']),
                    ...(['docente','profesor','teacher']),
                    ...(['estudiante','alumno','student']),
                ]) . ").");
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
            if ($rolCanon === 'docente') {
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
       Campos: codigo, nombre
    ==========================*/
    private function impCarreras($ws, array &$ok, array &$err, Request $r): void
{
    $rows = $this->rowsWithHeader($ws);
    if (!$rows) return;

    $c = 0; $u = 0;

    DB::transaction(function() use ($rows, &$c, &$u, &$err) {
        $parseBool = function($v): ?bool {
            if ($v === null || $v === '') return null;
            $s = mb_strtolower(trim((string)$v));
            if (in_array($s, ['1','true','t','si','sí','yes','y'])) return true;
            if (in_array($s, ['0','false','f','no','n'])) return false;
            return null;
        };
        $resolverDocenteId = function(?string $email): ?int {
            if (!$email) return null;
            $user = \App\Models\Usuario::where('email', trim($email))->first();
            if (!$user) return null;
            if (\Illuminate\Support\Facades\Schema::hasColumn('docente','id_usuario')) {
                $doc = \App\Models\Docente::where('id_usuario', $user->id_usuario)->first();
                return $doc?->id_docente;
            }
            $doc = \App\Models\Docente::find($user->id_usuario);
            return $doc?->id_docente;
        };

        foreach ($rows as $i => $row) {
            $nombre = $row['nombre'] ?? null;
            if (!$nombre) {
                throw new \RuntimeException("CARRERAS (fila {$i}): falta 'nombre'.");
            }

            $jefeEmail = $row['jefe_docente_email'] ?? $row['jefe_email'] ?? null;
            $jefeId    = $resolverDocenteId($jefeEmail);
            $habilitado = $parseBool($row['habilitado'] ?? null);
            $curr = \App\Models\Carrera::whereRaw('LOWER(nombre)=?', [mb_strtolower($nombre)])->first();

            $payload = ['nombre' => $nombre];
            if ($jefeId !== null)     { $payload['jefe_docente_id'] = $jefeId; }
            if ($habilitado !== null) { $payload['habilitado']      = $habilitado; }

            if ($curr) {
                $curr->update($payload);
                $u++;
            } else {
                if (!array_key_exists('habilitado', $payload)) $payload['habilitado'] = true;
                \App\Models\Carrera::create($payload);
                $c++;
            }

            if ($jefeEmail && $jefeId === null) {
                $err[] = "CARRERAS (fila {$i}): no se encontró Docente para '{$jefeEmail}'";
            }
        }
    });

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
                $payload['habilitado'] = isset($row['habilitado'])
                    ? filter_var($row['habilitado'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
                    : true;
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
        $c=0;$u=0;

        DB::transaction(function() use($rows,&$c,&$u){
            foreach ($rows as $i=>$row) {
                $nombre = $row['nombre'] ?? null;
                $fi = $row['fecha_inicio'] ?? null;
                $ff = $row['fecha_fin'] ?? null;
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

    $c=0;$u=0;$vinc=0;

    DB::transaction(function() use($rows,&$c,&$u,&$vinc){
        foreach ($rows as $i=>$row) {
            $cod   = $row['cod_materia']      ?? null;
            $nombre= $row['nombre']           ?? null;
            $hs    = (int)($row['horas_semanales'] ?? 2);
            $cred  = isset($row['creditos']) ? (int)$row['creditos'] : null;
            $car   = $row['carrera']          ?? null; // acepta id_carrera o nombre

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

    $c = 0; $u = 0;

    DB::transaction(function() use ($rows, &$c, &$u) {

        foreach ($rows as $i => $row) {
            $periodoNombre = trim($row['periodo_nombre'] ?? '');
            $codMateria    = trim($row['cod_materia']    ?? '');
            $nombreGrupo   = trim($row['nombre_grupo']   ?? '');
            $car           = trim((string)($row['carrera'] ?? '')); 
            $capacidad     = (int)($row['capacidad_estudiantes'] ?? 40);
            $estado        = $row['estado'] ?? 'En Asignacion';

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

    $c=0;$skip=0;
    DB::transaction(function() use($rows,&$c,&$skip){
        foreach ($rows as $i=>$row) {
            $pNom = $row['periodo_nombre'] ?? null;
            $gNom = $row['nombre_grupo']   ?? null;
            $mCod = $row['materia_codigo'] ?? ($row['cod_materia'] ?? null);
            $docE = $row['docente_email']  ?? null;
            // ahora esperamos 'aula_nombre' (pero aceptamos aula_codigo por compat.)
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

            CargaHoraria::create([
                'id_grupo'     => $grupo->id_grupo,
                'id_docente'   => $doc->id_docente,
                'id_aula'      => $aula->id_aula,
                'dia_semana'   => $dia,
                'hora_inicio'  => $ini,
                'hora_fin'     => $fin,
                'estado'       => 'Vigente',
            ]);
            $c++;
        }
    });

    $ok['CARGA_HORARIA']="insertadas {$c}, omitidas {$skip}";
    $this->bit('imp_carga','importacion',null,compact('c','skip'),$r);
}
}
