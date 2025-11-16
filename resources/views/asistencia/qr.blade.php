@extends('layouts.app')

@section('content')
<div class="app-container">
  <div class="card">
    <h1 class="text-xl font-bold mb-1">Código QR de asistencia</h1>
    <p class="text-muted mb-4">
      Pide a los docentes / estudiantes que escaneen este código con su celular
      antes de iniciar la clase. El sistema registrará la asistencia de forma automática.
    </p>

    <div class="flex flex-col md:flex-row gap-6 items-center">
      <div class="p-4 bg-black rounded-xl">
        {{-- Mostrar el QR generado en formato SVG --}}
        {!! $qrSvg !!}
      </div>

      <div class="space-y-2 text-sm">
        <p><strong>Carga:</strong> #{{ $carga->id_carga }}</p>
        <p><strong>Aula:</strong> {{ $carga->id_aula }}</p>
        <p><strong>Horario:</strong> {{ $carga->hora_inicio }} – {{ $carga->hora_fin }}</p>
        <p><strong>Fecha de sesión:</strong> {{ $fechaSesion }}</p>
        <p class="text-xs text-muted">
          El código es válido solo para la fecha indicada y para esta carga horaria.
        </p>
      </div>
    </div>
  </div>
</div>
@endsection
