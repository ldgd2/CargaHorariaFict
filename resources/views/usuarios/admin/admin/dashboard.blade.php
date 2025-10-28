@extends('layouts.app')
@section('title','Dashboard Admin')

@section('content')
  <h1 style="font-weight:700; margin-bottom: 16px;">Panel de Administración</h1>

  <div class="grid grid--3">
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Usuarios</h2>
      <p class="text-muted mb-3">Crear nuevas cuentas y asignar roles.</p>
      <a href="{{ route('usuarios.signup') }}" class="btn btn--primary">Registrar usuario</a>
    </div>

    <div class="card">
      <h2 style="margin:0 0 6px 0;">Roles y permisos</h2>
      <p class="text-muted mb-3">Gestión de roles (próximamente).</p>
      <button class="btn btn--outline" disabled>En desarrollo</button>
    </div>

    <div class="card">
      <h2 style="margin:0 0 6px 0;">Períodos</h2>
      <p class="text-muted mb-3">Define períodos académicos.</p>
      <button class="btn btn--outline" disabled>En desarrollo</button>
    </div>
  </div>
@endsection
