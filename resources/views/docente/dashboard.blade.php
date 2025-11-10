@extends('layouts.app')
@section('title','Panel del Docente')

@section('content')
<div class="app-container">

  {{-- Flash --}}
  @if (session('ok'))    <div class="snackbar snackbar--ok">{{ session('ok') }}</div>@endif
  @if ($errors->any())
    <div class="snackbar snackbar--error">
      <strong>Revisa los campos:</strong>
      <ul class="mt-2" style="margin-left:18px;">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  {{-- Bienvenida --}}
  <div class="card" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
    <div>
      <h2 class="appbar__title" style="margin:0">¡Hola, {{ auth()->user()->nombre }}!</h2>
      <p class="text-muted" style="margin:.25rem 0 0 0;">
        Panel de <strong>Docente</strong> — consulta tus grupos, sesiones y registra tu disponibilidad.
      </p>
    </div>

    {{-- Acción principal: Registrar disponibilidad (CU7) --}}
    <div>
      @php
        $pid = $pid ?? null;
        $dispUrl = $pid
          ? route('docente.disp.index', ['id_periodo' => $pid])
          : route('docente.disp.index'); // mostrará validación en la vista si falta periodo
      @endphp
      <a href="{{ $dispUrl }}" class="btn btn--primary">🗓️ Registrar disponibilidad</a>
    </div>
  </div>

  {{-- Tarjeta de período activo --}}
  <div class="card" style="margin-top:16px;">
    <h3 style="margin:0 0 6px 0;">Período actual</h3>
    @if($periodoActivo)
      @php
        $fi = optional(\Illuminate\Support\Carbon::parse($periodoActivo->fecha_inicio ?? null))->toDateString();
        $ff = optional(\Illuminate\Support\Carbon::parse($periodoActivo->fecha_fin ?? null))->toDateString();
      @endphp
      <p class="mb-2"><strong>{{ $periodoActivo->nombre ?? ('ID '.$periodoActivo->id_periodo) }}</strong></p>
      <p class="text-muted" style="margin:.25rem 0 0 0;">
        <time class="js-date" data-iso="{{ $fi }}">{{ $fi ?? '—' }}</time> –
        <time class="js-date" data-iso="{{ $ff }}">{{ $ff ?? '—' }}</time>
        <span class="badge badge--tonal" style="margin-left:8px">{{ $periodoActivo->estado }}</span>
      </p>
    @else
      <p class="text-muted" style="margin:0">No hay período activo. Solicita al coordinador la publicación o reapertura.</p>
    @endif
  </div>

  {{-- KPIs Docente --}}
  <div class="card" style="margin-top:16px;padding:0">
    <div class="grid grid--4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;padding:16px">
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">Grupos asignados</h4><div style="font-size:28px;font-weight:800">{{ $kpis['grupos'] }}</div></div>
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">Horas/semana</h4><div style="font-size:28px;font-weight:800">{{ number_format($kpis['horas_semana'],1) }}</div></div>
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">Franjas de disponibilidad</h4><div style="font-size:28px;font-weight:800">{{ $kpis['disp_franjas'] }}</div></div>
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">% Asistencia OK</h4><div style="font-size:28px;font-weight:800">{{ $kpis['asistencia_ok'] }}%</div></div>
    </div>
  </div>

  {{-- Accesos rápidos --}}
  <div class="grid grid--2" style="margin-top:16px">
    <div class="card">
      <p class="text-muted mb-2" style="margin:0 0 6px 0;">Acciones rápidas</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ $dispUrl }}" class="btn btn--primary">🗓️ Registrar disponibilidad</a>
        <a href="{{ route('grupos.index') }}" class="btn btn--tonal">👥 Ver mis grupos</a>
        <a href="{{ route('asistencia-sesion.index') }}" class="btn btn--outline">✅ Pasar asistencia</a>
      </div>
    </div>

    {{-- Bloque informativo de CU7 --}}
    <div class="card" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
      <div>
        <h3 style="margin:0 0 6px 0;">Mi disponibilidad</h3>
        <p class="text-muted" style="margin:0">Declara tus franjas por día para que el sistema asigne carga sin conflictos.</p>
      </div>
      <div>
        <a href="{{ $dispUrl }}" class="btn btn--primary">Abrir “Mi disponibilidad”</a>
      </div>
    </div>
  </div>

  {{-- Próximas sesiones (opcional) --}}
  <div class="card" style="margin-top:16px">
    <h3 style="margin:0 0 8px 0;">Próximas sesiones</h3>
    <div class="coor-table-wrap">
      <table class="min-w-full coor-recent" style="width:100%">
        <thead>
          <tr>
            <th class="coor-th">Fecha</th>
            <th class="coor-th">Hora</th>
            <th class="coor-th">Materia/Grupo</th>
            <th class="coor-th">Aula</th>
            <th class="coor-th">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          @forelse($proximas as $s)
            @php
              $f = optional(\Illuminate\Support\Carbon::parse($s->fecha))->toDateString();
            @endphp
            <tr>
              <td class="coor-td"><time class="js-date" data-iso="{{ $f }}">{{ $f }}</time></td>
              <td class="coor-td">{{ $s->hora_inicio }}–{{ $s->hora_fin }}</td>
              <td class="coor-td">{{ $s->nombre_materia ?? '—' }} {{ $s->nombre_grupo ?? '' }}</td>
              <td class="coor-td">{{ $s->aula ?? '—' }}</td>
              <td class="coor-td"><a href="{{ route('asistencia-sesion.index') }}" class="btn btn--outline">Abrir</a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="coor-td text-center text-muted">No hay sesiones próximas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Formato de fechas (igual que en coordinador) --}}
<script>
(function(){
  const months=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  function pad(n){return n<10?'0'+n:''+n;}
  function fmtDate(iso){ if(!iso) return '—'; const d=new Date(iso+'T00:00:00'); if(isNaN(d)) return iso;
    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear();
  }
  document.querySelectorAll('.js-date').forEach(el=>{
    const iso=el.getAttribute('data-iso'); el.textContent=fmtDate(iso);
  });
})();
</script>
@endsection
