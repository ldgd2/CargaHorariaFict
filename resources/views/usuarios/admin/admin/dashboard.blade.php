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
      <a href="{{ route('admin.carreras.view') }}" class="btn btn--primary">Abrir</a>
    </div>

    {{-- MATERIAS --}}
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Materias</h2>
      <p class="text-muted mb-3">Catálogo de materias.</p>
     {{-- <a href="{{ route('admin.materias.view') }}" class="btn btn--tonal">Abrir</a> --}}
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
  </div>
</div>
@endsection
