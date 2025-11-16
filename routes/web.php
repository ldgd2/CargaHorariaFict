<?php

use Illuminate\Support\Facades\Route;

// --------- Controladores ---------
use App\Http\Controllers\{
    AuthController, AdminController, CoordinadorController,
    RolController, UsuarioController, DocenteController, EstudianteController, CarreraController,
    AulaController, GrupoController, MateriaController, PeriodoAcademicoController,
    MateriaCarreraController, BloqueoAulaController, DisponibilidadDocenteController,
    AsistenciaSesionController, SesionDocenteTokenController, BitacoraController,
    ReaperturaHistorialController, ReporteCargaHorariaController, ReporteUsoAulaController, CargaHorariaController, ImportacionController, EditorSemanalController,
    ReporteAuditoriaController, // Controlador para CU9
    AuditoriaController, // Controlador para CU12
    HorarioCicloController // <--- ¡Controlador para CU10/CU11!
};

// ===============================
// PÚBLICO / AUTH
// ===============================
Route::get('/', fn () => view('auth.login'));
Route::get('/login', [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.post');
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

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
    // CU9: REPORTES ESENCIALES
    // ---------------------------
    Route::prefix('coordinador/reportes')->as('coordinador.reportes.')->group(function () {
        Route::get('/', [ReporteAuditoriaController::class, 'index'])->name('index'); 
        Route::get('/generar', [ReporteAuditoriaController::class, 'generar'])->name('generar');
        Route::get('/preview', [ReporteAuditoriaController::class, 'preview'])->name('preview');
        Route::get('/exportar-xlsx', [ReporteAuditoriaController::class, 'exportXLSX'])->name('exportXLSX');
    });

    // ---------------------------
    // CU12: AUDITORÍA Y CONFLICTOS
    // ---------------------------
    Route::prefix('coordinador/auditoria')->as('coordinador.auditoria.')->group(function () {
        Route::get('/', [AuditoriaController::class, 'index'])->name('index'); 
        Route::post('/refrescar', [AuditoriaController::class, 'refrescar'])->name('refrescar');
        Route::get('/listar', [AuditoriaController::class, 'listar'])->name('listar');
    });

    // ---------------------------
    // CU10/CU11: GESTIÓN DE PUBLICACIÓN DEL CICLO <--- ¡BLOQUE AGREGADO Y CORREGIDO!
    // Rutas resultantes: coordinador.gestion_ciclo.index, coordinador.gestion_ciclo.publicar, etc.
    // ---------------------------
    Route::prefix('coordinador/gestion-ciclo')->as('coordinador.gestion_ciclo.')->group(function () {
        
        // RUTA FALTANTE (index): Apunta al método 'index' en HorarioCicloController.
        Route::get('/', [HorarioCicloController::class, 'index'])->name('index'); 

        // RUTA AJAX para Publicar
        Route::post('/publicar', [HorarioCicloController::class, 'publicarHorarios'])->name('publicar');
        
        // RUTA AJAX para Reabrir
        Route::post('/reabrir', [HorarioCicloController::class, 'reabrirHorarios'])->name('reabrir');
    });

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
            Route::get('/', [RolController::class,'index'])->name('index');
            Route::post('/', [RolController::class,'store'])->name('store');
            Route::put('/{rol}', [RolController::class,'update'])->name('update');
            Route::patch('/{rol}/toggle', [RolController::class,'toggle'])->name('toggle');
            Route::delete('/{rol}', [RolController::class,'destroy'])->name('destroy');
            Route::post('/asignar', [RolController::class,'asignarRol'])->name('asignar');
            Route::delete('/revocar', [RolController::class,'revocarRol'])->name('revocar');
        });
    });

    // ---------------------------
    // Docente (panel + disponibilidad)
    // ---------------------------
    Route::prefix('docente')->as('docente.')->group(function () {
        Route::get('/', [DocenteController::class, 'dashboard'])->name('dashboard');
        Route::get('/disponibilidad', [DocenteController::class, 'disponibilidad'])->name('disp.view');
        Route::get('/mi-disponibilidad', [DisponibilidadDocenteController::class, 'index'])->name('disp.index'); 
        Route::post('/mi-disponibilidad', [DisponibilidadDocenteController::class, 'store'])->name('disp.store');
        Route::match(['put','patch'],'/mi-disponibilidad/{disponibilidad}', [DisponibilidadDocenteController::class, 'update'])->name('disp.update');
        Route::delete('/mi-disponibilidad/{disponibilidad}', [DisponibilidadDocenteController::class, 'destroy'])->name('disp.destroy');

        Route::post('/mi-disponibilidad/batch', [DisponibilidadDocenteController::class,'storeBatch'])
            ->name('disp.storeBatch');
    });

    // ---------------------------
    //  Asignación de carga (UI + APIs auxiliares)
    // ---------------------------
    Route::get('/carga/nueva', [CargaHorariaController::class,'create'])->name('carga.create');
    Route::post('/carga', [CargaHorariaController::class,'store'])->name('carga.store'); 
    Route::post('/carga/batch', [CargaHorariaController::class,'storeBatch'])->name('carga.storeBatch'); 

    // APIs de apoyo para selects/UX 
    Route::prefix('api')->group(function () {
        Route::get('/periodos', [CargaHorariaController::class,'apiPeriodos'])->name('api.periodos');
        Route::get('/grupos', [CargaHorariaController::class,'apiGrupos'])->name('api.grupos');
        Route::get('/docentes', [CargaHorariaController::class,'apiDocentes'])->name('api.docentes');
        Route::get('/aulas', [CargaHorariaController::class,'apiAulas'])->name('api.aulas');
        Route::get('/docentes/{docenteId}/disponibilidad',
            [\App\Http\Controllers\CargaHorariaController::class, 'apiDisponibilidadDocente']
        )->name('api.docente.disponibilidad');
    });


      /*
    |--------------------------------------
    | ASISTENCIA + QR
    |--------------------------------------
    */

    // Página del coordinador: selector / generador de QR
 // --- COORDINADOR ---
Route::prefix('coordinador')->as('coordinador.')->middleware(['auth'])->group(function () {
    Route::get('/asistencia/qr/selector', [AsistenciaSesionController::class, 'selectorQr'])->name('asistencia.qr.selector');
    Route::get('/materias/{idCarrera}/materias', [AsistenciaSesionController::class, 'obtenerMateriasPorCarrera']);
    Route::get('/materias/{idMateria}/docentes', [AsistenciaSesionController::class, 'obtenerDocentesPorMateria']);
    Route::get('/generar-qr/{cargaId}', [AsistenciaSesionController::class, 'generarQr']);
});

    Route::get('/coordinador/kpi', [CoordinadorController::class, 'kpiGeneral'])
    ->name('coordinador.kpi.general');
    Route::get('/coordinador/docentes/{docente}/kpi', [CoordinadorController::class, 'docenteKpi'])
        ->name('coordinador.docente.kpi');

    Route::prefix('docente')->as('docente.')->group(function () {
        Route::get('/asistencia/qr/marcar/{carga}/{fecha}', [AsistenciaSesionController::class, 'marcarDesdeQr'])->name('asistencia.marcar');
    });
    // API REST de asistencia
    Route::apiResource('asistencia-sesion', AsistenciaSesionController::class)->parameters([
        'asistencia-sesion' => 'id',
    ]);

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
        // CU13: Editor Semanal (APIs)
        Route::get('/cargas/editor', [EditorSemanalController::class, 'editor'])->name('cargas.editor'); 
        Route::get('/cargas/grid', [EditorSemanalController::class, 'apiGridWeek'])->name('cargas.grid'); 
        Route::post('/cargas/check', [EditorSemanalController::class, 'apiValidateSlot'])->name('cargas.check'); 
        Route::patch('/cargas/drag', [EditorSemanalController::class, 'dragUpdate'])->name('cargas.drag'); 
    });

    Route::prefix('admin')->as('admin.')->middleware('auth')->group(function () {
        Route::get('/importacion', [ImportacionController::class,'form'])->name('import.form');
        Route::post('/importacion', [ImportacionController::class,'import'])->name('import.run');
    });

    Route::get('/reportes/uso-aulas/view', [ReporteUsoAulaController::class, 'view'])
        ->name('reportes.uso_aulas.view');

    // API/descarga (json, pdf, xlsx)
    Route::get('/api/reporte-uso-aulas', [ReporteUsoAulaController::class, 'index'])
        ->name('reportes.uso_aulas');
        
    // Resources API
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