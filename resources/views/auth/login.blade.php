@extends('layouts.app')
@section('title','Ingresar')

@section('content')
  <div style="display:grid; place-items:center; min-height: calc(100dvh - 72px);">
    <div class="card" style="width:min(520px, 100%);">
      <h1 style="margin:0 0 6px 0; font-weight:700;">Bienvenido</h1>
      <p class="text-muted" style="margin:0 0 18px 0;">
        Inicia sesión para acceder al panel de administración.
      </p>

      @if (session('status'))
        <div class="snackbar snackbar--ok">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" novalidate>
        @csrf

        {{-- Email --}}
        <div class="field">
          <label class="field__label" for="email">Correo electrónico</label>
          <div class="field__box">
            <input
              class="field__input"
              type="email"
              id="email"
              name="email"
              value="{{ old('email') }}"
              autocomplete="email"
              required
              autofocus
            >
          </div>
          @error('email')
            <small class="field__hint" style="color:#ffb4ab;">{{ $message }}</small>
          @enderror
        </div>

        {{-- Password --}}
        <div class="field">
          <label class="field__label" for="password">Contraseña</label>
          <div class="field__box">
            <input
              class="field__input"
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              required
            >
          </div>
          @error('password')
            <small class="field__hint" style="color:#ffb4ab;">{{ $message }}</small>
          @enderror
        </div>

        {{-- Remember me --}}
        <div class="mt-2" style="display:flex; align-items:center; gap:10px;">
          <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
          <label for="remember" class="text-muted">Mantener sesión iniciada</label>
        </div>

        {{-- Errores generales --}}
        @if ($errors->has('general'))
          <div class="snackbar snackbar--error mt-3">
            {{ $errors->first('general') }}
          </div>
        @endif

        <div class="mt-3" style="display:flex; gap:12px; align-items:center;">
          <button type="submit" class="btn btn--primary" style="width:100%;">Ingresar</button>
          {{-- <a href="{{ route('password.request') }}" class="btn btn--text">¿Olvidaste tu contraseña?</a> --}}
        </div>
      </form>
    </div>
  </div>
@endsection
