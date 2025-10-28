@extends('layouts.app')
@section('title','Registrar usuario')

@section('content')
  <h1 style="font-weight:700; margin-bottom: 16px;">Registrar usuario</h1>

  <form method="POST" action="{{ route('usuarios.signup.post') }}" novalidate>
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

    <div class="mt-3">
      <label class="text-muted" style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
        Habilitado
      </label>
    </div>
    
    <div class="mb-4">
  <label class="block text-sm font-semibold mb-1" for="entidad">Entidad</label>
  <input
    id="entidad"
    name="entidad"
    type="text"
    value="{{ old('entidad') }}"
    required
    class="w-full px-4 py-3 rounded-xl bg-[#141920] border border-zinc-700 focus:border-emerald-400 outline-none text-sm"
    placeholder="Ej: UAGRM, Dirección Académica, etc."
  >
  @error('entidad')
    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
  @enderror
</div>


    <div class="mt-3" style="display:flex; gap:12px;">
      <button type="submit" class="btn btn--primary">Guardar</button>
      <a href="{{ route('admin.dashboard') }}" class="btn btn--text">Cancelar</a>
    </div>
  </form>
@endsection
