@extends('layouts.app')
@section('title','Dashboard Admin')

@section('content')
<div class="app-container">
  <h1 style="font-weight:700; margin-bottom: 16px;">Panel de Administración</h1>

  <div class="grid grid--3">
    {{-- USUARIOS --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Usuarios</h2>
      <p class="text-muted mb-3">Crear nuevas cuentas y asignar roles.</p>
      <a href="{{ route('admin.usuarios.signup') }}" class="btn btn--primary">Registrar usuario</a>
    </div>

    {{-- ROLES --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Roles y permisos</h2>
      <p class="text-muted mb-3">Administra los roles y la asignación a usuarios.</p>
      <a href="{{ route('roles.index') }}" class="btn btn--primary">Ir a Roles</a>
    </div>

    {{-- PERÍODOS --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Períodos</h2>
      <p class="text-muted mb-3">Define períodos académicos.</p>
      <a href="{{ route('periodos.index') }}" class="btn btn--primary">Gestionar períodos</a>
    </div>

    {{-- CARRERAS --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Carreras</h2>
      <p class="text-muted mb-3">Catálogo de carreras.</p>
      <a href="{{ route('admin.carreras.view') }}" class="btn btn--primary">Gestionar Carreras</a>
    </div>

    {{-- MATERIAS --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Materias</h2>
      <p class="text-muted mb-3">Catálogo de materias.</p>
     <a href="{{ route('admin.materias.view') }}" class="btn btn--primary">Abrir</a>
    </div>

    {{-- AULAS --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Aulas</h2>
      <p class="text-muted mb-3">Catálogo de aulas.</p>
      {{--<a href="{{ route('admin.aulas.view') }}" class="btn btn--tonal">Abrir</a> --}}
    </div>

    {{-- DOCENTES --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Docentes</h2>
      <p class="text-muted mb-3">Registro y gestión de docentes.</p>
      {{--<a href="{{ route('admin.docentes.view') }}" class="btn btn--tonal">Abrir</a> --}}
    </div>

    {{-- MATERIA ↔ CARRERA --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Materia ↔ Carrera</h2>
      <p class="text-muted mb-3">Vincular materias con carreras.</p>
     {{-- <a href="{{ route('admin.materia_carrera.view') }}" class="btn btn--tonal">Abrir</a> --}}
    </div>
 {{-- Espacio para que la siguiente fila inicie correctamente --}}
        <div class="card-placeholder"></div> 
        
    </div>
    
    {{-- Separador Visual para el Nuevo Módulo --}}
    <h2 style="font-weight:700; margin: 30px 0 16px 0; color: #a0aec0;">⚙️ MÓDULO DE PLANIFICACIÓN Y AUDITORÍA</h2>
    
    {{-- BLOQUE 2: Planificación y Auditoría (Casos CU9, CU10/CU11, CU12) --}}
    <div class="grid grid--3">
        
        {{-- CU9: REPORTES ESENCIALES --}}
        <div class="card">
            <h2 style="margin:0 0 6px 0;">📊 Reportes Esenciales</h2>
            <p class="text-muted mb-3">Generar reportes de carga y asistencia (PDF/XLSX).</p>
            <a href="{{ route('coordinador.reportes.index', ['tab' => 'reportes']) }}" class="btn btn--primary">Generar Reportes</a>
        </div>
        
        {{-- CU12: AUDITORÍA Y CONFLICTOS --}}
        <div class="card">
            <h2 style="margin:0 0 6px 0;">🚨 Auditoría y Conflictos</h2>
            <p class="text-muted mb-3">Listar conflictos, solapes y grupos incompletos.</p>
            {{-- Este botón lleva a la vista principal de Auditoría (CU12) --}}
            <a href="{{ route('coordinador.auditoria.index') }}" class="btn btn--primary">Listar Conflictos</a>
        </div>
        
        {{-- CU10/CU11: GESTIÓN DE PUBLICACIÓN --}}
        <div class="card">
            <h2 style="margin:0 0 6px 0;">🔓 Gestión de Publicación</h2>
            <p class="text-muted mb-3">Publicar / Reabrir horarios del ciclo actual.</p>
            {{-- Este botón lleva a la vista de alto impacto (CU10/CU11) --}}
             <a href="{{ route('coordinador.gestion_ciclo.index') }}" class="btn btn--primary">Gestionar Ciclo</a>
        </div>

    </div>
</div>
@endsection