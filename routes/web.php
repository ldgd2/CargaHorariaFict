<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{RolController,UsuarioController,DocenteController,EstudianteController,CarreraController};
use App\Http\Controllers\{AulaController, GrupoController, MateriaController, PeriodoAcademicoController};
use App\Http\Controllers\{MateriaCarreraController, BloqueoAulaController, DisponibilidadDocenteController, AsistenciaSesionController};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\{SesionDocenteTokenController, BitacoraController, ReaperturaHistorialController, ReporteCargaHorariaController};
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CoordinadorController;

Route::middleware(['web','auth'])->group(function () {
    Route::get('/periodos/stats', [PeriodoAcademicoController::class, 'stats'])->name('periodos.stats');

});
 
Route::get('/coordinador', [CoordinadorController::class, 'index'])
    ->name('coordinador.dashboard')
    ->middleware('auth');
Route::get('/login',  [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.post');
Route::post('/logout',[AuthController::class,'logout'])->name('logout');

Route::middleware(['web','auth'])
    ->prefix('admin/roles')
    ->name('roles.')
    ->group(function () {
        Route::get('/',            [RolController::class,'index'])->name('index');
        Route::post('/',           [RolController::class,'store'])->name('store');
        Route::put('/{rol}',       [RolController::class,'update'])->name('update');
        Route::patch('/{rol}/toggle', [RolController::class,'toggle'])->name('toggle');
        Route::delete('/{rol}',    [RolController::class,'destroy'])->name('destroy');

        Route::post('/asignar',    [RolController::class,'asignarRol'])->name('asignar');
        Route::delete('/revocar',  [RolController::class,'revocarRol'])->name('revocar');
    });

Route::middleware(['web','auth'])
  ->prefix('admin')
  ->as('admin.')
  ->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class,'dashboard'])->name('dashboard');

    // Usuarios (signup + import)
    Route::get('/usuarios/signup', [UsuarioController::class,'create'])->name('usuarios.signup');
    Route::post('/usuarios/signup', [UsuarioController::class,'storeSignup'])->name('usuarios.signup.post');
    Route::post('/usuarios/import', [UsuarioController::class,'import'])->name('usuarios.import');

    // Carreras (página y API)
    Route::get('/carreras/view', [CarreraController::class,'view'])->name('carreras.view');
    Route::get('/carreras',        [CarreraController::class,'index'])->name('carreras.index');
    Route::post('/carreras',       [CarreraController::class,'store'])->name('carreras.store');
    Route::put('/carreras/{carrera}',   [CarreraController::class,'update'])->name('carreras.update');
    Route::patch('/carreras/{carrera}/toggle', [CarreraController::class,'toggle'])->name('carreras.toggle');

    // Docentes (sólo API para listados)
    Route::get('/docentes',  [DocenteController::class,'index'])->name('docentes.index');

    // (Si ya tienes vistas para materias/aulas, mantenlas aquí)
    Route::get('/materias',  [MateriaController::class,'index'])->name('materias.index');
    Route::get('/aulas',     [AulaController::class,'index'])->name('aulas.index');

    // Periodos (dentro de admin)
    Route::get('/periodos',        [PeriodoAcademicoController::class,'index'])->name('periodos.index');
    Route::post('/periodos',       [PeriodoAcademicoController::class,'store'])->name('periodos.store');
    Route::put('/periodos/{periodo}', [PeriodoAcademicoController::class,'update'])->name('periodos.update');
    Route::patch('/periodos/{periodo}/estado', [PeriodoAcademicoController::class,'cambiarEstado'])->name('periodos.estado');
    Route::post('/periodos/{periodo}/reabrir', [PeriodoAcademicoController::class,'reabrir'])->name('periodos.reabrir');
});

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
Route::apiResource('aulas', AulaController::class);
Route::apiResource('grupos', GrupoController::class);
Route::apiResource('materias', MateriaController::class);
Route::apiResource('periodos', PeriodoAcademicoController::class);
Route::apiResource('estudiantes', EstudianteController::class);
Route::apiResource('roles', RolController::class);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('docentes', DocenteController::class);
Route::apiResource('estudiantes', EstudianteController::class);
Route::apiResource('carreras', CarreraController::class);

Route::get('/', function () {
    return view('auth.login');
});
