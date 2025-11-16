@extends('layouts.app')

@section('title', 'QR de asistencia — Coordinador')

@section('content')
<div class="app-container" style="max-width:960px">
  <div class="card" style="display:grid;gap:16px;grid-template-columns:1.4fr 1fr;align-items:flex-start">
    
    {{-- Información de la carga --}}
    <div>
      <h2 class="appbar__title" style="margin:0 0 8px 0">
        Código QR de asistencia
      </h2>
      <p class="text-muted" style="margin:0 0 12px 0">
        Escanea este código con la sesión iniciada como <strong>docente</strong>
        para marcar asistencia de la carga seleccionada.
      </p>

      <ul class="text-muted" style="margin:0 0 12px 18px">
        <li><strong>Docente:</strong> {{ optional($carga->docente)->nro_documento ?? '—' }}</li>
        <li><strong>Materia:</strong> {{ optional($carga->grupo->materia)->nombre ?? '—' }}</li>
        <li><strong>Grupo:</strong> {{ $carga->grupo->codigo ?? '—' }}</li>
        <li><strong>Aula:</strong> {{ optional($carga->aula)->nombre_aula ?? '—' }}</li>
        <li><strong>Fecha:</strong> {{ $hoy }}</li>
      </ul>

      <p class="text-muted" style="font-size:.9rem">
        URL codificada:<br>
        <code style="font-size:.8rem;word-break:break-all">{{ $qrUrl }}</code>
      </p>

      <a href="{{ route('coordinador.asistencia.qr.selector') }}" class="btn btn--outline mt-3">
        ⬅ Volver al selector de cargas
      </a>
    </div>

    {{-- QR en SVG --}}
    <div style="display:flex;flex-direction:column;align-items:center;gap:12px">
      <div style="background:#0b1118;border-radius:16px;padding:16px;border:1px solid var(--color-outline-variant)">
        {{-- IMPORTANTE: usar {!! !!} para inyectar el SVG --}}
        {!! $qrSvg !!}
      </div>
      <p class="text-muted" style="font-size:.85rem;text-align:center">
        Escanear este código abre la página de marcado de asistencia<br>
        para esta carga en la fecha <strong>{{ $hoy }}</strong>.
      </p>
    </div>
  </div>
</div>
@endsection
