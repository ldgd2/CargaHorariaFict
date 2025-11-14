@extends('layouts.app')

@section('title', 'Roles y permisos')

@section('content')
<div class="app-container">

  {{-- Flash / errores --}}
  @if (session('ok'))
    <div class="snackbar snackbar--ok">{{ session('ok') }}</div>
  @endif

  @if ($errors->any())
    <div class="snackbar snackbar--error">
      <strong>Revisa los campos:</strong>
      <ul class="mt-2">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="grid grid--2">
    {{-- Columna izquierda: CRUD Roles --}}
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <h2 class="appbar__title" style="font-size:1.05rem">Catálogo de roles</h2>
      </div>

      {{-- Crear rol --}}
      <div class="card" style="margin-bottom:16px">
        <form method="POST" action="{{ route('roles.store') }}">
          @csrf

          <div class="grid grid--3">
            <div class="field">
              <label class="field__label">Nombre del rol</label>
              <div class="field__box">
                <input name="nombre_rol" value="{{ old('nombre_rol') }}" class="field__input" required>
              </div>
            </div>

            @if (\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion'))
              <div class="field" style="grid-column: span 2;">
                <label class="field__label">Descripción (opcional)</label>
                <div class="field__box">
                  <input name="descripcion" value="{{ old('descripcion') }}" class="field__input">
                </div>
              </div>
            @endif
          </div>

          <div style="display:flex;align-items:center;gap:12px;justify-content:flex-end">
            <label class="text-muted" style="display:inline-flex;gap:8px;align-items:center">
              <input type="checkbox" name="habilitado" value="1" checked>
              <span>Habilitado</span>
            </label>

            <button class="btn btn--primary" type="submit">Crear</button>
          </div>
        </form>
      </div>

      {{-- Tabla de roles --}}
      <div class="card" style="padding:0">
        <div style="overflow:auto">
          <table style="width:100%;border-collapse:collapse">
            <thead>
              <tr style="background:var(--color-surface-2);border-bottom:1px solid var(--color-outline-variant)">
                <th style="text-align:left;padding:10px 12px">#</th>
                <th style="text-align:left;padding:10px 12px">Rol</th>
                @if (\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion'))
                  <th style="text-align:left;padding:10px 12px">Descripción</th>
                @endif
                <th style="text-align:left;padding:10px 12px">Estado</th>
                <th style="width:1%;padding:10px 12px"></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($roles as $rol)
                <tr style="border-top:1px solid var(--color-outline-variant)">
                  <td style="padding:10px 12px">{{ $rol->id_rol }}</td>
                  <td style="padding:10px 12px;font-weight:700">{{ $rol->nombre_rol }}</td>
                  @if (\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion'))
                    <td style="padding:10px 12px">{{ $rol->descripcion }}</td>
                  @endif
                  <td style="padding:10px 12px">
                    @if ($rol->habilitado)
                      <span class="text-muted" style="padding:3px 8px;border-radius:10px;background:color-mix(in srgb, var(--color-primary) 18%, transparent)">Habilitado</span>
                    @else
                      <span class="text-muted" style="padding:3px 8px;border-radius:10px;background:#5a3c05">Inhabilitado</span>
                    @endif
                  </td>
                  <td style="padding:10px 12px;text-align:right;white-space:nowrap">
                    {{-- Editar (details/summary) --}}
                    <details style="display:inline-block;margin-right:6px">
                      <summary class="btn btn--text">Editar</summary>
                      <div class="card" style="margin-top:8px;width:320px">
                        <form method="POST" action="{{ route('roles.update', $rol) }}">
                          @csrf @method('PUT')

                          <div class="field">
                            <label class="field__label">Nombre</label>
                            <div class="field__box">
                              <input name="nombre_rol" value="{{ old('nombre_rol', $rol->nombre_rol) }}" class="field__input">
                            </div>
                          </div>

                          @if (\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion'))
                            <div class="field">
                              <label class="field__label">Descripción</label>
                              <div class="field__box">
                                <input name="descripcion" value="{{ old('descripcion', $rol->descripcion) }}" class="field__input">
                              </div>
                            </div>
                          @endif

                          <label class="text-muted" style="display:inline-flex;gap:8px;align-items:center">
                            <input type="checkbox" name="habilitado" value="1" {{ $rol->habilitado ? 'checked' : '' }}>
                            <span>Habilitado</span>
                          </label>

                          <div style="text-align:right;margin-top:10px">
                            <button class="btn btn--primary" type="submit">Guardar</button>
                          </div>
                        </form>
                      </div>
                    </details>

                    {{-- Toggle --}}
                    <form method="POST" action="{{ route('admin.roles.toggle', $rol) }}" style="display:inline-block">
                      @csrf @method('PATCH')
                      <button class="btn btn--tonal" type="submit">
                        {{ $rol->habilitado ? 'Inhabilitar' : 'Habilitar' }}
                      </button>
                    </form>

                    {{-- Eliminar --}}
                    <form method="POST" action="{{ route('roles.destroy', $rol) }}" style="display:inline-block"
                          onsubmit="return confirm('¿Eliminar este rol?');">
                      @csrf @method('DELETE')
                      <button class="btn btn--outline" type="submit" style="margin-left:6px;border-color:#ff6b6b;color:#ff6b6b">
                        Eliminar
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" style="padding:16px 12px;text-align:center" class="text-muted">No hay roles aún.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3" style="padding:10px 12px">
          {{ $roles->links() }}
        </div>
      </div>
    </div>

    {{-- Columna derecha: Asignación / Revocación --}}
    <div class="card">
      <h2 class="appbar__title" style="font-size:1.05rem;margin-bottom:12px">Asignación de roles a usuarios</h2>

      {{-- Asignar --}}
      <div class="card" style="margin-bottom:16px">
        <form method="POST" action="{{ route('admin.roles.asignar') }}">
          @csrf

          <div class="grid grid--2">
            <div class="field">
              <label class="field__label">Usuario (lista rápida)</label>
              <div class="field__box">
                <select name="usuario_id" class="field__select">
                  <option value="">— Selecciona —</option>
                  @foreach ($usuarios as $u)
                    <option value="{{ $u->id_usuario }}">
                      {{ $u->apellido }}, {{ $u->nombre }} — {{ $u->email }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="field__hint">También puedes buscar por email (campo derecho).</div>
            </div>

            <div class="field">
              <label class="field__label">Buscar por email (opcional)</label>
              <div class="field__box">
                <input name="email" placeholder="usuario@correo.com" class="field__input">
              </div>
              <div class="field__hint">Si completas email, no es necesario elegir en el combo.</div>
            </div>

            <div class="field" style="grid-column: 1 / -1">
              <label class="field__label">Rol a asignar</label>
              <div class="field__box">
                <select name="id_rol" class="field__select" required>
                  @foreach ($roles as $r)
                    @if ($r->habilitado)
                      <option value="{{ $r->id_rol }}">{{ $r->nombre_rol }}</option>
                    @endif
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div style="text-align:right">
            <button class="btn btn--primary" type="submit">Asignar rol</button>
          </div>
        </form>
      </div>

      {{-- Revocar --}}
      <div class="card">
        <form method="POST" action="{{ route('admin.roles.revocar') }}">
          @csrf @method('DELETE')

          <h3 style="margin:0 0 10px 0">Revocar rol</h3>

          <div class="grid grid--3">
            <div class="field">
              <label class="field__label">Usuario</label>
              <div class="field__box">
                <select name="usuario_id" class="field__select" required>
                  <option value="">— Selecciona —</option>
                  @foreach ($usuarios as $u)
                    <option value="{{ $u->id_usuario }}">
                      {{ $u->apellido }}, {{ $u->nombre }} — {{ $u->email }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="field">
              <label class="field__label">Rol</label>
              <div class="field__box">
                <select name="id_rol" class="field__select" required>
                  @foreach ($roles as $r)
                    <option value="{{ $r->id_rol }}">{{ $r->nombre_rol }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div style="align-self:end;text-align:right">
              <button class="btn btn--tonal" type="submit">Revocar</button>
            </div>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
@endsection
