<?php

use Illuminate\Support\Facades\Route;

// --------- Controladores ---------
use App\Http\Controllers\{
    AuthController, AdminController, CoordinadorController,
    RolController, UsuarioController, DocenteController, EstudianteController, CarreraController,
    AulaController, GrupoController, MateriaController, PeriodoAcademicoController,
    MateriaCarreraController, BloqueoAulaController, DisponibilidadDocenteController,
    AsistenciaSesionController, SesionDocenteTokenController, BitacoraController,
    ReaperturaHistorialController, ReporteCargaHorariaController, CargaHorariaController,ImportacionController
};

// ===============================
// PÚBLICO / AUTH
// ===============================
Route::get('/', fn () => view('auth.login'));
Route::get('/login',  [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.post');
Route::post('/logout',[AuthController::class,'logout'])->name('logout');

// ===============================
// RUTAS CON AUTENTICACIÓN
// ===============================
Route::middleware('auth')->group(function () {

    // ---------------------------
    // Coordinador (panel)
    // ---------------------------
    Route::get('/coordinador', [CoordinadorController::class, 'index'])
        ->name('coordinador.dashboard');

    // ---------------------------
    // Periodos: acciones extra
    // ---------------------------
    Route::patch('/periodos/{periodo}/estado', [PeriodoAcademicoController::class,'cambiarEstado'])
        ->name('periodos.estado');
    Route::post('/periodos/{periodo}/reabrir', [PeriodoAcademicoController::class,'reabrir'])
        ->name('periodos.reabrir');

    // ---------------------------
    // Admin
    // ---------------------------
    Route::prefix('admin')->as('admin.')->group(function () {
        Route::get('/', [AdminController::class,'dashboard'])->name('dashboard');

        // Usuarios (signup + import)
        Route::get('/usuarios/signup', [UsuarioController::class,'create'])->name('usuarios.signup');
        Route::post('/usuarios/signup', [UsuarioController::class,'storeSignup'])->name('usuarios.signup.post');
        Route::post('/usuarios/import', [UsuarioController::class,'import'])->name('usuarios.import');

        // Carreras (vista administrativa)
        Route::get('/carreras/view', [CarreraController::class,'view'])->name('carreras.view');

        // Roles (módulo)
        Route::prefix('roles')->as('roles.')->group(function () {
            Route::get('/',                 [RolController::class,'index'])->name('index');
            Route::post('/',                [RolController::class,'store'])->name('store');
            Route::put('/{rol}',            [RolController::class,'update'])->name('update');
            Route::patch('/{rol}/toggle',   [RolController::class,'toggle'])->name('toggle');
            Route::delete('/{rol}',         [RolController::class,'destroy'])->name('destroy');
            Route::post('/asignar',         [RolController::class,'asignarRol'])->name('asignar');
            Route::delete('/revocar',       [RolController::class,'revocarRol'])->name('revocar');
        });
    });

    // ---------------------------
    // Docente (panel + disponibilidad)
    // ---------------------------
    Route::prefix('docente')->as('docente.')->group(function () {
        Route::get('/', [DocenteController::class, 'dashboard'])->name('dashboard');
        Route::get('/disponibilidad', [DocenteController::class, 'disponibilidad'])->name('disp.view');
        Route::get('/mi-disponibilidad', [DisponibilidadDocenteController::class, 'index'])->name('disp.index');  // ?id_periodo=#
        Route::post('/mi-disponibilidad', [DisponibilidadDocenteController::class, 'store'])->name('disp.store');
        Route::match(['put','patch'],'/mi-disponibilidad/{disponibilidad}', [DisponibilidadDocenteController::class, 'update'])->name('disp.update');
        Route::delete('/mi-disponibilidad/{disponibilidad}', [DisponibilidadDocenteController::class, 'destroy'])->name('disp.destroy');

        Route::post('/mi-disponibilidad/batch', [DisponibilidadDocenteController::class,'storeBatch'])
            ->name('disp.storeBatch');
    });

    // ---------------------------
    //  Asignación de carga (UI + APIs auxiliares)
    // ---------------------------
    Route::get('/carga/nueva',  [CargaHorariaController::class,'create'])->name('carga.create');
Route::post('/carga',       [CargaHorariaController::class,'store'])->name('carga.store'); // si quieres mantener individual
Route::post('/carga/batch',[CargaHorariaController::class,'storeBatch'])->name('carga.storeBatch'); // <- NUEVA

    // APIs de apoyo para selects/UX 
    Route::prefix('api')->group(function () {
        Route::get('/periodos', [CargaHorariaController::class,'apiPeriodos'])->name('api.periodos');
        Route::get('/grupos',   [CargaHorariaController::class,'apiGrupos']);             // ?id_periodo=
        Route::get('/docentes', [CargaHorariaController::class,'apiDocentes']);
        Route::get('/aulas',    [CargaHorariaController::class,'apiAulas']);
        Route::get('/docentes/{id}/disponibilidad', [CargaHorariaController::class,'apiDisponibilidadDocente']);
    });

    // ---------------------------
    // Resources / APIs del sistema
    // ---------------------------
    Route::apiResource('sesiones-tokens', SesionDocenteTokenController::class);
    Route::apiResource('bitacora', BitacoraController::class)->only(['index','show','store','update','destroy']);
    Route::apiResource('reaperturas', ReaperturaHistorialController::class);
    Route::apiResource('reportes-carga', ReporteCargaHorariaController::class);

    Route::get('materia-carrera', [MateriaCarreraController::class, 'index']);
    Route::post('materia-carrera', [MateriaCarreraController::class, 'store']);
    Route::get('materia-carrera/{id_materia}/{id_carrera}', [MateriaCarreraController::class, 'show']);
    Route::delete('materia-carrera/{id_materia}/{id_carrera}', [MateriaCarreraController::class, 'destroy']);

    Route::apiResource('bloqueos-aula', BloqueoAulaController::class)->parameters([
        'bloqueos-aula' => 'id'
    ]);
    Route::apiResource('disponibilidad-docente', DisponibilidadDocenteController::class)->parameters([
        'disponibilidad-docente' => 'id'
    ]);
    Route::apiResource('asistencia-sesion', AsistenciaSesionController::class)->parameters([
        'asistencia-sesion' => 'id'
    ]);

    // APIs de apoyo para selects/UX (aisladas bajo /api)
Route::prefix('api')->group(function () {
    Route::get('/periodos', [CargaHorariaController::class,'apiPeriodos'])->name('api.periodos');
    Route::get('/grupos',   [CargaHorariaController::class,'apiGrupos'])->name('api.grupos');  // <- nombra también
    Route::get('/docentes', [CargaHorariaController::class,'apiDocentes'])->name('api.docentes'); // <- necesario
    Route::get('/aulas',    [CargaHorariaController::class,'apiAulas'])->name('api.aulas');       // <- necesario
    Route::get('/docentes/{id}/disponibilidad', [CargaHorariaController::class,'apiDisponibilidadDocente'])
        ->name('api.docente.disponibilidad');
});

Route::prefix('admin')->as('admin.')->middleware('auth')->group(function () {
    Route::get('/importacion',  [ImportacionController::class,'form'])->name('import.form');
    Route::post('/importacion', [ImportacionController::class,'import'])->name('import.run');
});

    // Entidades base
    Route::apiResource('aulas', AulaController::class);
    Route::apiResource('grupos', GrupoController::class);
    Route::apiResource('materias', MateriaController::class);
    Route::apiResource('periodos', PeriodoAcademicoController::class);
    Route::apiResource('estudiantes', EstudianteController::class);
    Route::apiResource('roles', RolController::class);
    Route::apiResource('usuarios', UsuarioController::class);
    Route::apiResource('docentes', DocenteController::class);
    Route::apiResource('carreras', CarreraController::class);
});
