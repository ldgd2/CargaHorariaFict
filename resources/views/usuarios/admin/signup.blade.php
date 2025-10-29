@extends('layouts.app')

@section('title','Registrar usuario')

@section('content')
<div class="app-container">

  {{-- ======================
       ALERTAS GLOBALES
  ======================= --}}
  @if(session('ok') || session('warning') || $errors->any())
    @if(session('ok'))
      @php
        $mensajeOk = strip_tags(session('ok'));
        $lineasOk = explode("\n", str_replace(["<br>", "<br/>", "<br />"], "\n", $mensajeOk));
      @endphp
      <div class="snackbar snackbar--ok mb-3">
        <b>Operación exitosa</b>
        <ul class="mt-2">
          @foreach($lineasOk as $l)
            @if(trim($l)!=='')
              <li class="mt-1">{{ trim($l) }}</li>
            @endif
          @endforeach
        </ul>
      </div>
    @endif

    @if(session('warning'))
      <div class="snackbar snackbar--error mb-3">
        {!! session('warning') !!}
      </div>
    @endif

    @if($errors->any())
      <div class="snackbar snackbar--error mb-3">
        <b>Revisa los campos</b>
        <ul class="mt-2 list-disc list-inside">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  @endif

  {{-- ===========================
       SECCIÓN: REGISTRAR USUARIO
  ============================ --}}
  <div class="card mb-4">
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-bold">Registrar usuario</h1>
    </div>

    <form method="POST" action="{{ route('admin.usuarios.signup.post') }}" novalidate>
      @csrf

      <div class="grid grid--2">
        <div class="field">
          <label class="field__label">Nombre</label>
          <div class="field__box">
            <input class="field__input" type="text" name="nombre" value="{{ old('nombre') }}" required>
          </div>
        </div>

        <div class="field">
          <label class="field__label">Apellido</label>
          <div class="field__box">
            <input class="field__input" type="text" name="apellido" value="{{ old('apellido') }}" required>
          </div>
        </div>

        <div class="field">
          <label class="field__label">Email</label>
          <div class="field__box">
            <input class="field__input" type="email" name="email" value="{{ old('email') }}" required>
          </div>
        </div>

        <div class="field">
          <label class="field__label">Teléfono (opcional)</label>
          <div class="field__box">
            <input class="field__input" type="text" name="telefono" value="{{ old('telefono') }}">
          </div>
        </div>

        <div class="field">
          <label class="field__label">Dirección (opcional)</label>
          <div class="field__box">
            <input class="field__input" type="text" name="direccion" value="{{ old('direccion') }}">
          </div>
        </div>

        <div class="field">
          <label class="field__label">Rol</label>
          <div class="field__box">
            <select class="field__select" name="id_rol" required>
              <option value="">— Selecciona —</option>
              @foreach($roles as $r)
                <option value="{{ $r->id_rol }}">{{ $r->nombre_rol }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="field">
          <label class="field__label">Contraseña inicial (opcional)</label>
          <div class="field__box">
            <input class="field__input" type="password" name="password" autocomplete="new-password">
          </div>
          <small class="field__hint">Si la dejas vacía, se generará una aleatoria segura.</small>
        </div>

        <div class="field">
          <label class="field__label">Confirmar contraseña</label>
          <div class="field__box">
            <input class="field__input" type="password" name="password_confirmation" autocomplete="new-password">
          </div>
        </div>
      </div>

      <div class="mt-3 flex items-center gap-2">
        <input type="checkbox" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
        <label class="text-muted">Habilitado</label>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <button type="submit" class="btn btn--primary w-full sm:w-auto">Guardar</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn--text w-full sm:w-auto text-center">Cancelar</a>
      </div>
    </form>
  </div>

  {{-- ===========================
       SECCIÓN: IMPORTACIÓN MASIVA
  ============================ --}}
  <div class="card">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-lg font-semibold">Carga masiva desde Excel</h2>
    </div>

    <form method="POST" action="{{ route('admin.usuarios.import') }}" enctype="multipart/form-data" novalidate>
      @csrf

      <div class="field">
        <label class="field__label">Archivo Excel (.xlsx o .xls)</label>
        <div class="field__box" style="padding: 18px;">
          <input id="archivo" type="file" name="archivo" accept=".xlsx,.xls" required class="w-full text-sm">
          <small class="field__hint">Arrastra y suelta o toca para seleccionar. Tamaño sugerido &lt; 10MB.</small>
        </div>
      </div>

      {{-- Panel de ayuda rápido --}}
      <div class="grid grid--2 mt-3">
        <div class="field">
          <label class="field__label">Formato esperado (hojas)</label>
          <div class="field__box">
            <div class="text-sm">
              <p class="mb-2"><b>Hojas válidas como roles:</b> <code>Docente</code>, <code>Estudiante</code>, <code>Coordinador</code>, <code>Usuario</code>.</p>
              <p class="mb-2">Si el nombre de la hoja no coincide con un rol, se omite y se reporta.</p>
              <p class="mb-2"><b>Encabezados base (flexibles):</b> Nombre, Apellido, Email, Teléfono, Dirección, Contraseña.</p>
              <p class="mb-1"><b>Estudiante</b> (PK del negocio): <code>Código Universitario</code> (obligatorio), Carrera, Semestre.</p>
            </div>
          </div>
        </div>

        <div class="field">
          <label class="field__label">Comportamiento de importación</label>
          <div class="field__box">
            <ul class="text-sm list-disc list-inside">
              <li>Si una hoja tiene error de estructura o campos vacíos, <b>se detiene esa hoja</b> y el proceso sigue con las demás.</li>
              <li>Si el rol de la hoja no existe, <b>se omite</b> y se muestra en el resumen.</li>
              <li>Usuarios se crean/actualizan por email y rol; Estudiantes por <code>código_universitario</code>.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <button type="submit" class="btn btn--tonal w-full sm:w-auto">Cargar Lista de Usuarios</button>
     
        {{-- <a href="{{ route('usuarios.plantilla') }}" class="btn btn--outline w-full sm:w-auto">Descargar plantilla</a> --}}
      </div>
    </form>


    @if(session('ok'))
      @php
        $mensaje = strip_tags(session('ok'));
        $lineas = explode("\n", str_replace(["<br>", "<br/>", "<br />"], "\n", $mensaje));
      @endphp
      <div class="card mt-4">
        <h3 class="text-lg font-bold mb-3">📋 Resumen de importación</h3>
        <ul class="text-sm leading-relaxed">
          @foreach($lineas as $linea)
            @php
              $trim = trim($linea);
              $color = str_starts_with($trim, '⚠️') ? 'text-yellow-400' :
                      (str_starts_with($trim, '🚫') ? 'text-red-400' :
                      (str_starts_with($trim, '✔️') ? 'text-green-400' :
                      (str_starts_with($trim, '✅') ? 'text-emerald-400' : 'text-on-surface')));
            @endphp
            @if($trim !== '')
              <li class="{{ $color }}">{{ $trim }}</li>
            @endif
          @endforeach
        </ul>
      </div>
    @endif
  </div>

</div>
@endsection
