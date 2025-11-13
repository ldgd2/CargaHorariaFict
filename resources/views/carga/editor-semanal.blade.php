@extends('layouts.app')
@section('title','Editor semanal (CU13)')

@section('content')
  <div class="app-container" style="display:grid;gap:16px">
    <div class="card">
      <h2 style="margin:0">Editor semanal (CU13)</h2>
      <div class="legend" style="margin-top:8px">
        <span class="pill"><span class="dot dot--disp"></span> Disponibilidad docente</span>
        <span class="pill"><span class="dot dot--ok"></span> OK</span>
        <span class="pill"><span class="dot dot--warn"></span> Advertencia</span>
        <span class="pill"><span class="dot dot--err"></span> Conflicto</span>
      </div>
    </div>

    <x-cu13.filters
      :periodos="$periodos"
      :docentes="$docentes"
      :aulas="$aulas"
      :grid-url="route('cargas.grid')"
      :check-url="route('cargas.check')"
      :drag-url="route('cargas.drag')"
    />

    <div class="card" style="display:grid;grid-template-columns:280px 1fr;gap:16px">
      <x-cu13.aula-palette :aulas="$aulas" />
      <div style="display:grid;gap:12px">
        <x-cu13.savebar />
        <x-cu13.week-grid />
      </div>
    </div>
  </div>

  @vite(['resources/css/app.css','resources/js/app.js'])
  <script>document.addEventListener('DOMContentLoaded',()=>{ window.CU13?.initCU13Editor?.() })</script>
@endsection
