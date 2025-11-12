<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{RolController,UsuarioController,DocenteController,EstudianteController,CarreraController};
use App\Http\Controllers\{AulaController, GrupoController, MateriaController, PeriodoAcademicoController};
use App\Http\Controllers\{MateriaCarreraController, BloqueoAulaController, DisponibilidadDocenteController, AsistenciaSesionController};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\{SesionDocenteTokenController, BitacoraController, ReaperturaHistorialController, ReporteCargaHorariaController};
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CoordinadorController;
use App\Http\Controllers\ReporteAuditoriaController;
use App\Http\Controllers\AuditoriaController; 
// Importamos el nuevo controlador que manejará CU10 y CU11
use App\Http\Controllers\HorarioCicloController; // <-- NUEVA IMPORTACIÓN (Necesitas crear este controlador)

Route::middleware(['web','auth'])->group(function () {
    Route::get('/periodos/stats', [PeriodoAcademicoController::class, 'stats'])->name('periodos.stats');
});
 
// 1. MODIFICACIÓN DEL DASHBOARD: Redirige a la vista de gestión del ciclo
// Asumimos que la acción 'gestionCicloIndex' en el controlador es la que retorna la vista Blade.
Route::get('/coordinador', [HorarioCicloController::class, 'gestionCicloIndex']) // <-- CAMBIO AQUÍ
    ->name('coordinador.dashboard')
    ->middleware('auth');
    
Route::get('/login',  [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.post');
Route::post('/logout',[AuthController::class,'logout'])->name('logout');

// --------------------------------------------------------------------------
// RUTAS DEL COORDINADOR (PLANIFICACIÓN Y AUDITORÍA - CU9, CU10, CU11, CU12)
// --------------------------------------------------------------------------

Route::middleware(['web','auth'])
    ->prefix('coordinador')
    ->as('coordinador.')
    ->group(function () {
        
        // --------------------------------------------------------------------
        // CU9: Reportes de Carga Horaria (ReporteAuditoriaController)
        // --------------------------------------------------------------------
        // Vista principal de reportes (si usa pestañas)
        Route::get('/reportes-auditoria', [ReporteAuditoriaController::class, 'index'])
            ->name('reportes.index');
        
        // Endpoint para generar el reporte PDF
        Route::get('/reportes/generar', [ReporteAuditoriaController::class, 'generarReporte'])
            ->name('reportes.generar');

        // Endpoint para generar la vista previa (AJAX)
        Route::get('/reportes/preview', [ReporteAuditoriaController::class, 'preview']) 
            ->name('reportes.preview');
            
        // Endpoint para generar el reporte XLSX
        Route::get('/reportes/exportar/xlsx', [ReporteAuditoriaController::class, 'exportarReporteXLSX'])
            ->name('reportes.export.xlsx'); 
            
        // --------------------------------------------------------------------
        // CU12: Auditoría y Conflictos (AuditoriaController)
        // --------------------------------------------------------------------
        
        // 1. Ruta de la vista principal dedicada (Dirige a auditoria/index.blade.php)
        Route::get('/auditoria-conflictos', [AuditoriaController::class, 'index'])
            ->name('auditoria.index');

        // 2. Endpoint para listar datos de la tabla de auditoría (AJAX)
        Route::get('/auditoria/listar', [AuditoriaController::class, 'listarAuditoria'])
            ->name('auditoria.listar');
        
        // 3. Endpoint para refrescar el análisis de auditoría
        Route::post('/auditoria/refrescar', [AuditoriaController::class, 'refrescarAuditoria'])
            ->name('auditoria.refrescar');

        // --------------------------------------------------------------------
        // CU10 y CU11: Gestión de Ciclo (Publicar / Reabrir) - USANDO HorarioCicloController
        // --------------------------------------------------------------------
        
        // 2. RUTA DE GESTIÓN DEL CICLO: Muestra la vista publicacion_reapertura.blade.php
        Route::get('/gestion-ciclo', [HorarioCicloController::class, 'gestionCicloIndex']) // <-- CORRECTO
            ->name('gestion_ciclo.index');
        
        // CU10: Endpoint para Publicar Horarios
        Route::post('/gestion-ciclo/publicar', [HorarioCicloController::class, 'publicarHorarios']) // <-- CORRECTO
            ->name('gestion_ciclo.publicar');
            
        // CU11: Endpoint para Reabrir Horarios
        Route::post('/gestion-ciclo/reabrir', [HorarioCicloController::class, 'reabrirHorarios']) // <-- CORRECTO
            ->name('gestion_ciclo.reabrir');
            
    });

// --------------------------------------------------------------------------
// RUTAS DE ADMINISTRACIÓN EXISTENTES
// --------------------------------------------------------------------------

Route::middleware(['web','auth'])
    ->prefix('admin/roles')
    ->name('roles.')
    ->group(function () {
        Route::get('/',              [RolController::class,'index'])->name('index');
        Route::post('/',             [RolController::class,'store'])->name('store');
        Route::put('/{rol}',         [RolController::class,'update'])->name('update');
        Route::patch('/{rol}/toggle', [RolController::class,'toggle'])->name('toggle');
        Route::delete('/{rol}',      [RolController::class,'destroy'])->name('destroy');

        Route::post('/asignar',      [RolController::class,'asignarRol'])->name('asignar');
        Route::delete('/revocar',    [RolController::class,'revocarRol'])->name('revocar');
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
        Route::get('/carreras',              [CarreraController::class,'index'])->name('carreras.index');
        Route::post('/carreras',             [CarreraController::class,'store'])->name('carreras.store');
        Route::put('/carreras/{carrera}',  [CarreraController::class,'update'])->name('carreras.update');
        Route::patch('/carreras/{carrera}/toggle', [CarreraController::class,'toggle'])->name('carreras.toggle');

        // Docentes (sólo API para listados)
        Route::get('/docentes',  [DocenteController::class,'index'])->name('docentes.index');

        // 🟢 CORRECCIÓN: Definición explícita de la ruta que necesita el dashboard (admin.materias.view)
        Route::get('/materias/view', [MateriaController::class, 'viewIndex'])->name('materias.view'); // <-- Esto define 'admin.materias.view'

        // Materias (API CRUD bajo un prefijo distinto para evitar conflicto con /materias/view)
        // Usamos Route::resource con un prefijo URI diferente (materias-api) pero mantenemos 
        // los nombres de ruta esperados (admin.materias.index, .store, .update, etc.)
        Route::resource('materias-api', MateriaController::class)
            ->except(['create','edit'])
            ->parameters(['materias-api' => 'materia']) 
            ->names('materias'); 

        // Ruta de toggle que apunta a la nueva URI y usa el nombre esperado
        Route::patch('/materias-api/{materia}/toggle', [MateriaController::class,'toggle'])->name('materias.toggle');
        
        // Aulas (Vista)
        Route::get('/aulas',     [AulaController::class,'index'])->name('aulas.index');

        // Periodos (dentro de admin)
        Route::get('/periodos',              [PeriodoAcademicoController::class,'index'])->name('periodos.index');
        Route::post('/periodos',             [PeriodoAcademicoController::class,'store'])->name('periodos.store');
        Route::put('/periodos/{periodo}', [PeriodoAcademicoController::class,'update'])->name('periodos.update');
        Route::patch('/periodos/{periodo}/estado', [PeriodoAcademicoController::class,'cambiarEstado'])->name('periodos.estado');
        // RUTA ELIMINADA: Route::post('/periodos/{periodo}/reabrir', [PeriodoAcademicoController::class,'reabrir'])->name('periodos.reabrir'); 
    });

// Rutas API existentes
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
Route::apiResource('periodos', PeriodoAcademicoController::class);
Route::apiResource('estudiantes', EstudianteController::class);
Route::apiResource('roles', RolController::class);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('docentes', DocenteController::class);
Route::apiResource('estudiantes', EstudianteController::class);
Route::apiResource('carreras', CarreraController::class);

// RUTA ELIMINADA DEL CÓDIGO ANTERIOR: Route::apiResource('materias', MateriaController::class); 

Route::get('/', function () {
    return view('auth.login');
});