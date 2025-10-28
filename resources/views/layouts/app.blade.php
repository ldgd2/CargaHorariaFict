<!DOCTYPE html>
<html lang="es" class="dark h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Sistema')</title>

  {{-- Vite: CSS + JS --}}
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="h-full theme-dark">
  {{-- AppBar --}}
  @include('partials.nav')

  {{-- Contenido --}}
  <main class="app-container">
    {{-- Flash (ok / error) --}}
    @if (session('ok'))
      <div class="snackbar snackbar--ok">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
      <div class="snackbar snackbar--error">
        <strong>Revisa los campos:</strong>
        <ul>
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @yield('content')
  </main>
</body>
</html>
