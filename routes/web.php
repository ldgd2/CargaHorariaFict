<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{RolController,UsuarioController,DocenteController,EstudianteController,CarreraController};
use App\Http\Controllers\{AulaController, GrupoController, MateriaController, PeriodoAcademicoController};
use App\Http\Controllers\{MateriaCarreraController, BloqueoAulaController, DisponibilidadDocenteController, AsistenciaSesionController};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\{SesionDocenteTokenController, BitacoraController, ReaperturaHistorialController, ReporteCargaHorariaController};
use App\Http\Controllers\AdminController;
Route::get('/login',  [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.post');
Route::post('/logout',[AuthController::class,'logout'])->name('logout');

Route::middleware(['web','auth'])->group(function () {
    // Dashboard admin
    Route::get('/admin', [\App\Http\Controllers\AdminController::class, 'dashboard'])
        ->name('admin.dashboard');

    // CU1: Registrar usuario (vista + post)
    Route::get('/admin/usuarios/signup', [UsuarioController::class, 'create'])
        ->name('usuarios.signup'); // Gate::before ya te deja pasar por ser Admin

    Route::post('/admin/usuarios/signup', [UsuarioController::class, 'storeSignup'])
        ->name('usuarios.signup.post');
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
    return view('welcome');
});
