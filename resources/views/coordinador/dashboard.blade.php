@extends('layouts.app')

@section('title','Panel del Coordinador')

@section('content')
<style>
  /* Layout apilado y limpio para móvil */
  .coor-stack{display:grid;gap:16px}
  .coor-top{display:grid;gap:16px}
  .coor-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;padding:16px}
  .coor-kpi{margin:0}
  .coor-kpi h4{margin:0 0 4px 0;font-weight:700}
  .coor-kpi .num{font-size:28px;font-weight:800}

  /* Tabla responsive (solo desktop) */
  .coor-table-wrap{overflow:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--color-outline-variant);border-radius:var(--radius-s)}
  .coor-th,.coor-td{padding:8px 10px;text-align:left;vertical-align:middle}
  .coor-recent thead{background:rgba(255,255,255,.03)}

  /* Badges de estado */
  .badge{padding:4px 10px;border-radius:999px;font-weight:800;font-size:.86rem;display:inline-block}
  .badge--outline{border:1px solid var(--color-outline-variant);color:var(--color-on-surface-variant);background:transparent}
  .badge--tonal{background:color-mix(in srgb,var(--color-primary) 18%, var(--color-surface));color:var(--color-on-surface)}
  .badge--primary{background:var(--color-primary);color:var(--color-on-primary)}
  .badge--text{background:transparent;color:var(--color-on-surface-variant)}

  @media (min-width: 960px){
    .coor-top{grid-template-columns: 1fr 1fr}
  }
</style>

<div class="app-container coor-stack">

  {{-- Flash --}}
  @if (session('ok'))
    <div class="snackbar snackbar--ok">{{ session('ok') }}</div>
  @endif
  @if ($errors->any())
    <div class="snackbar snackbar--error">
      <strong>Revisa los campos:</strong>
      <ul class="mt-2" style="margin-left:18px;">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Bienvenida + formato de fecha --}}
  <div class="card" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
    <div>
      <h2 class="appbar__title" style="margin:0">¡Hola, {{ auth()->user()->nombre }}!</h2>
      <p class="text-muted" style="margin:.25rem 0 0 0;">
        Panel de <strong>Coordinador</strong> — crea y gestiona los períodos académicos.
      </p>
    </div>

    <div style="display:flex;align-items:center;gap:8px">
      <label for="js-date-format" class="text-muted" style="font-weight:600">Formato de fecha</label>
      <div class="field__box" style="padding:6px 10px">
        <select id="js-date-format" class="field__input" style="min-width:150px">
          <option value="d/m/Y">DD/MM/AAAA</option>
          <option value="Y-m-d">AAAA-MM-DD</option>
          <option value="m/d/Y">MM/DD/AAAA</option>
          <option value="d M Y">DD Mon AAAA</option>
        </select>
      </div>
    </div>
  </div>

  {{-- Acciones rápidas + Último período --}}
  <div class="coor-top">
    <div class="card">
      <p class="text-muted" style="margin:0 0 10px 0;">Acciones rápidas</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('periodos.index') }}" class="btn btn--primary">➕ Crear período</a>
        <a href="{{ route('periodos.index') }}" class="btn btn--tonal">📋 Ver/editar períodos</a>
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 8px 0;">Último período</h3>

      @isset($ultimo)
        @php
          $fiIso = !empty($ultimo->fecha_inicio) ? \Illuminate\Support\Carbon::parse($ultimo->fecha_inicio)->toDateString() : null;
          $ffIso = !empty($ultimo->fecha_fin)    ? \Illuminate\Support\Carbon::parse($ultimo->fecha_fin)->toDateString()    : null;
          $uState = strtolower($ultimo->estado_publicacion ?? 'borrador');
          if(($ultimo->activo ?? false) && $uState !== 'activo') $uState='activo';
          $uBadge = match($uState){
            'borrador'=>'badge--outline','activo'=>'badge--tonal',
            'publicado'=>'badge--primary','archivado'=>'badge--text', default=>'badge--outline'
          };
        @endphp

        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
          <p class="mb-2" style="margin:0"><strong>{{ $ultimo->nombre ?? '—' }}</strong></p>
          <span class="badge {{ $uBadge }}">{{ ucfirst($uState) }}</span>
        </div>

        <p class="text-muted mb-2" style="margin:.5rem 0 0 0">
          <time class="js-date" data-iso="{{ $fiIso }}">{{ $fiIso ?? '—' }}</time>
          –
          <time class="js-date" data-iso="{{ $ffIso }}">{{ $ffIso ?? '—' }}</time>
        </p>

        <div class="mt-3">
          <a href="{{ route('periodos.index') }}" class="btn btn--outline">Gestionar períodos</a>
        </div>
      @else
        <p class="text-muted" style="margin:0 0 .5rem 0;">Aún no hay períodos registrados.</p>
        <a href="{{ route('periodos.index') }}" class="btn btn--primary">Crear el primero</a>
      @endisset
    </div>
  </div>

  {{-- KPIs (con auto-actualización) --}}
  {{-- KPIs (con auto-actualización) --}}
{{-- KPIs (con auto-actualización) --}}
@php
  $statsUrl = \Illuminate\Support\Facades\Route::has('periodos.stats')
      ? route('periodos.stats')
      : url('/periodos/stats'); // fallback si aún no está cacheada
@endphp

<div class="card" style="padding:0;" data-stats-url="{{ $statsUrl }}">


<div class="card" style="padding:0" data-stats-url="{{ $statsUrl }}">
  <div class="coor-kpis">
    <div class="card coor-kpi"><h4>Total</h4><div id="kpi-total" class="num">{{ $stats['total'] ?? 0 }}</div></div>
    <div class="card coor-kpi"><h4>Borrador</h4><div id="kpi-borrador" class="num">{{ $stats['borrador'] ?? 0 }}</div></div>
    <div class="card coor-kpi"><h4>Activos</h4><div id="kpi-activo" class="num">{{ $stats['activo'] ?? 0 }}</div></div>
    <div class="card coor-kpi"><h4>Publicados</h4><div id="kpi-publicado" class="num">{{ $stats['publicado'] ?? 0 }}</div></div>
    @if(isset($stats['archivado']))
      <div class="card coor-kpi"><h4>Archivados</h4><div id="kpi-archivado" class="num">{{ $stats['archivado'] ?? 0 }}</div></div>
    @endif
  </div>
</div>


  {{-- Recientes (opcional) --}}
  @isset($recientes)
    <div class="card">
      <h3 style="margin:0 0 10px 0;">Recientes</h3>
      <div class="coor-table-wrap">
        <table class="min-w-full coor-recent" style="width:100%">
          <thead>
            <tr>
              <th class="coor-th">Nombre</th>
              <th class="coor-th">Inicio</th>
              <th class="coor-th">Fin</th>
              <th class="coor-th">Estado</th>
              <th class="coor-th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            @forelse($recientes as $p)
              @php
                $pfiIso = !empty($p->fecha_inicio) ? \Illuminate\Support\Carbon::parse($p->fecha_inicio)->toDateString() : null;
                $pffIso = !empty($p->fecha_fin)    ? \Illuminate\Support\Carbon::parse($p->fecha_fin)->toDateString()    : null;
                $st = strtolower($p->estado_publicacion ?? 'borrador');
                if(($p->activo ?? false) && $st!=='activo') $st='activo';
                $b = match($st){
                  'borrador'=>'badge--outline','activo'=>'badge--tonal',
                  'publicado'=>'badge--primary','archivado'=>'badge--text', default=>'badge--outline'
                };
              @endphp
              <tr>
                <td class="coor-td">{{ $p->nombre ?? '—' }}</td>
                <td class="coor-td"><time class="js-date" data-iso="{{ $pfiIso }}">{{ $pfiIso ?? '—' }}</time></td>
                <td class="coor-td"><time class="js-date" data-iso="{{ $pffIso }}">{{ $pffIso ?? '—' }}</time></td>
                <td class="coor-td"><span class="badge {{ $b }}">{{ ucfirst($st) }}</span></td>
                <td class="coor-td"><a class="btn btn--outline" href="{{ route('periodos.index') }}">Abrir</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="coor-td text-center text-muted">Sin registros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endisset

</div>

{{-- JS: formato de fecha + actualización de KPIs --}}
<script>
(function(){
  const key = 'date_fmt';
  const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const select = document.getElementById('js-date-format');
  function pad(n){ return n<10 ? '0'+n : ''+n; }
  function fmtDate(iso, fmt){
    if(!iso) return '—';
    const d = new Date(iso + 'T00:00:00');
    if(isNaN(d)) return iso;
    const DD = pad(d.getDate()), MM = pad(d.getMonth()+1), Mon = months[d.getMonth()], YYYY = d.getFullYear();
    switch(fmt){
      case 'Y-m-d': return `${YYYY}-${MM}-${DD}`;
      case 'm/d/Y': return `${MM}/${DD}/${YYYY}`;
      case 'd M Y': return `${DD} ${Mon} ${YYYY}`;
      case 'd/m/Y':
      default: return `${DD}/${MM}/${YYYY}`;
    }
  }
  function applyFormat(fmt){
    document.querySelectorAll('.js-date').forEach(el=>{
      const iso = el.getAttribute('data-iso');
      el.textContent = fmtDate(iso, fmt);
    });
  }

  // Init formato
  const saved = localStorage.getItem(key) || 'd/m/Y';
  select.value = saved; applyFormat(saved);
  select.addEventListener('change', ()=>{
    const fmt = select.value; localStorage.setItem(key, fmt); applyFormat(fmt);
  });

  // KPIs en vivo
  const statsBox = document.querySelector('[data-stats-url]');
  const url = statsBox?.getAttribute('data-stats-url');
  async function refreshKPIs(){
    if(!url) return;
    try{
      const r = await fetch(url, {headers:{'Accept':'application/json'}});
      if(!r.ok) return;
      const s = await r.json();
      const set = (id, val)=>{ const el = document.getElementById(id); if(el) el.textContent = (val ?? 0); };
      set('kpi-total', s.total);
      set('kpi-borrador', s.borrador);
      set('kpi-activo', s.activo);
      set('kpi-publicado', s.publicado);
      set('kpi-archivado', s.archivado);
    }catch(_){}
  }
  refreshKPIs();
  document.addEventListener('visibilitychange', ()=>{ if(!document.hidden) refreshKPIs(); });
  setInterval(refreshKPIs, 12000); // cada 12s
})();
</script>
@endsection
