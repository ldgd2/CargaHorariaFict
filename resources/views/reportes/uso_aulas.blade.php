@extends('layouts.app')

@section('content')
<div class="app-container">

  {{-- Título + descripción breve --}}
  <div class="mb-4">
    <h1 class="text-2xl font-bold">Uso de aulas</h1>
    <p class="text-muted mt-2">
      Reporte de ocupación de aulas por período, rango de fechas, origen de ocupación y motivo de bloqueo.
    </p>
  </div>

  {{-- Mensaje de error --}}
  @if(!empty($error))
    <div class="snackbar snackbar--error">
      Ocurrió un error al calcular el reporte: {{ $error }}
    </div>
  @endif

  {{-- Filtros / barra superior --}}
  <div class="card mb-4">
    <form id="form-uso-aulas" method="GET">
      <div class="toolbar mb-3">
        <div class="text-muted">
          Filtros del reporte
        </div>
        <div class="savebar">
          <button type="submit" class="btn btn--primary">
            Buscar
          </button>
          <button type="button" onclick="exportar('pdf')" class="btn btn--tonal">
            PDF
          </button>
          <button type="button" onclick="exportar('xlsx')" class="btn btn--outline">
            Excel
          </button>
          <a href="{{ route('reportes.uso_aulas.view') }}" class="btn btn--text">
            Limpiar
          </a>
        </div>
      </div>

      <div class="filters">
        {{-- Período --}}
        <div class="field">
          <label class="field__label">Período *</label>
          <div class="field__box">
            <select name="id_periodo" required class="field__select">
              <option value="">Seleccione un período</option>
              @foreach($periodos as $p)
                <option value="{{ $p->id_periodo }}"
                  {{ (string)request('id_periodo') === (string)$p->id_periodo ? 'selected' : '' }}>
                  {{ $p->nombre }} ({{ $p->fecha_inicio }} – {{ $p->fecha_fin }})
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Desde --}}
        <div class="field">
          <label class="field__label">Desde *</label>
          <div class="field__box">
            <input type="date"
                   name="desde"
                   value="{{ request('desde') }}"
                   required
                   class="field__input">
          </div>
        </div>

        {{-- Hasta --}}
        <div class="field">
          <label class="field__label">Hasta *</label>
          <div class="field__box">
            <input type="date"
                   name="hasta"
                   value="{{ request('hasta') }}"
                   required
                   class="field__input">
          </div>
        </div>

        {{-- Tipo de aula --}}
        <div class="field">
          <label class="field__label">Tipo de aula</label>
          <div class="field__box">
            <select name="tipo_aula" class="field__select">
              <option value="">Todos</option>
              @foreach($tipos as $t)
                <option value="{{ $t }}" {{ request('tipo_aula')===$t ? 'selected' : '' }}>
                  {{ $t }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Aula --}}
        <div class="field">
          <label class="field__label">Aula</label>
          <div class="field__box">
            <select name="id_aula" class="field__select">
              <option value="">Todas</option>
              @foreach($aulasList as $a)
                <option value="{{ $a->id_aula }}" {{ request('id_aula')==$a->id_aula ? 'selected' : '' }}>
                  {{ $a->id_aula }} — {{ $a->nombre_aula }}
                  (cap: {{ $a->capacidad }}) [{{ $a->tipo_aula }}]
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Capacidad mínima --}}
        <div class="field">
          <label class="field__label">Capacidad mínima</label>
          <div class="field__box">
            <select name="cap_min" class="field__select">
              <option value="">Cualquiera</option>
              @foreach($capacidades as $cap)
                <option value="{{ $cap }}" {{ (string)request('cap_min') === (string)$cap ? 'selected' : '' }}>
                  ≥ {{ $cap }} estudiantes
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Origen de ocupación --}}
        <div class="field">
          <label class="field__label">Origen de ocupación</label>
          <div class="field__box">
            <select name="origen" class="field__select">
              <option value="todos" {{ request('origen','todos')=='todos' ? 'selected' : '' }}>
                Clases + bloqueos
              </option>
              <option value="clases" {{ request('origen')=='clases' ? 'selected' : '' }}>
                Solo clases (carga horaria)
              </option>
              <option value="bloqueos" {{ request('origen')=='bloqueos' ? 'selected' : '' }}>
                Solo bloqueos / mantenimiento
              </option>
            </select>
          </div>
        </div>

        {{-- Motivo de bloqueo --}}
        <div class="field">
          <label class="field__label">Motivo de bloqueo</label>
          <div class="field__box">
            <select name="motivo" class="field__select">
              <option value="">Todos</option>
              @foreach($motivos as $m)
                <option value="{{ $m }}" {{ request('motivo')===$m ? 'selected' : '' }}>
                  {{ $m }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Estado --}}
        <div class="field">
          <label class="field__label">Estado</label>
          <div class="field__box">
            <select name="estado" class="field__select">
              <option value="todas" {{ request('estado','todas')=='todas' ? 'selected' : '' }}>Todas</option>
              <option value="ocupadas" {{ request('estado')=='ocupadas' ? 'selected' : '' }}>Solo ocupadas</option>
              <option value="libres" {{ request('estado')=='libres' ? 'selected' : '' }}>Solo libres</option>
            </select>
          </div>
        </div>

      </div> {{-- filters --}}
    </form>
  </div>

  {{-- Resultado --}}
  @if(isset($reporte) && $reporte->count())

    {{-- Vista en grid de cards --}}
    <div class="grid grid--2 mb-4">
      @foreach($reporte as $r)
        @php
          $pct = (float) $r['ocupacion'];
          $claseBarra = $pct < 60 ? 'bg-emerald-400'
                       : ($pct < 100 ? 'bg-amber-400' : 'bg-red-500');
        @endphp
        <div class="card">
          <div class="flex justify-between items-start gap-3">
            <div>
              <div class="text-sm text-muted mb-1">
                Aula {{ $r['codigo'] }} · {{ $r['tipo'] ?? 'Sin tipo' }}
              </div>
              <div class="font-semibold text-base">
                {{ $r['nombre'] }}
              </div>
              <div class="text-sm text-muted mt-1">
                Capacidad: <span class="font-semibold">{{ $r['capacidad'] }}</span> estudiantes
              </div>
              <div class="text-xs text-muted mt-2">
                Asignadas: <strong>{{ number_format($r['horas_asignadas'], 2) }} h</strong> ·
                Bloqueadas: <strong>{{ number_format($r['horas_bloqueadas'], 2) }} h</strong> ·
                Libres: <strong>{{ number_format($r['horas_libres'], 2) }} h</strong>
              </div>
            </div>
            <div class="text-right" style="min-width: 120px;">
              <div class="text-xs text-muted">Ocupación</div>
              <div class="text-lg font-bold">
                {{ number_format($pct, 1) }}%
              </div>
              <div class="w-full h-2.5 rounded-full bg-slate-800 mt-1 overflow-hidden">
                <div class="h-full {{ $claseBarra }}"
                     style="width: {{ max(0, min($pct, 100)) }}%;"></div>
              </div>
              <div class="mt-1 text-xs {{ $r['estado']=='Ocupada' ? 'text-rose-400' : 'text-emerald-400' }}">
                {{ $r['estado'] }}
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Tabla resumen abajo (útil para revisar antes de exportar) --}}
    <div class="card">
      <div class="mb-2 font-semibold">Detalle tabular</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-slate-900/60">
              <th class="border border-slate-700 px-2 py-1 text-left">Código</th>
              <th class="border border-slate-700 px-2 py-1 text-left">Nombre</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Tipo</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Capacidad</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Horas asignadas</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Horas bloqueadas</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Horas libres</th>
              <th class="border border-slate-700 px-2 py-1 text-center">% ocupación</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
          @foreach($reporte as $r)
            <tr class="hover:bg-slate-900/40">
              <td class="border border-slate-800 px-2 py-1">{{ $r['codigo'] }}</td>
              <td class="border border-slate-800 px-2 py-1">{{ $r['nombre'] }}</td>
              <td class="border border-slate-800 px-2 py-1 text-center">{{ $r['tipo'] ?? '—' }}</td>
              <td class="border border-slate-800 px-2 py-1 text-center">{{ $r['capacidad'] }}</td>
              <td class="border border-slate-800 px-2 py-1 text-center">{{ number_format($r['horas_asignadas'], 2) }}</td>
              <td class="border border-slate-800 px-2 py-1 text-center">{{ number_format($r['horas_bloqueadas'], 2) }}</td>
              <td class="border border-slate-800 px-2 py-1 text-center">{{ number_format($r['horas_libres'], 2) }}</td>
              <td class="border border-slate-800 px-2 py-1 text-center">{{ number_format($r['ocupacion'], 2) }}%</td>
              <td class="border border-slate-800 px-2 py-1 text-center">
                {{ $r['estado'] }}
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>

  @elseif(request()->has('id_periodo'))
    <div class="card">
      <p class="text-muted">No se encontraron aulas con ese criterio de búsqueda.</p>
    </div>
  @else
    <div class="card">
      <p class="text-muted">Completa los filtros y presiona <strong>Buscar</strong>.</p>
    </div>
  @endif
</div>

<script>
function exportar(formato) {
  const form = document.getElementById('form-uso-aulas');
  const periodo = form.querySelector('[name="id_periodo"]')?.value;
  if (!periodo) {
    alert('Debe ingresar un período');
    return;
  }
  const data   = new FormData(form);
  const params = new URLSearchParams(data);
  params.set('formato', formato);
  window.open('{{ route('reportes.uso_aulas') }}' + '?' + params.toString(), '_blank');
}
</script>
@endsection
