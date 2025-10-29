@extends('layouts.app')
@section('title','Períodos académicos')

@section('content')
<style>
  /* ---- Vista apilada + listado responsivo ---- */
  .pa-stack{display:grid;gap:16px;}
  .pa-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
  .pa-badge{padding:4px 10px;font-size:.86rem;font-weight:800;border-radius:999px}
  .pa-badge--outline{border:1px solid var(--color-outline-variant);color:var(--color-on-surface-variant);background:transparent}
  .pa-badge--tonal{background:color-mix(in srgb,var(--color-primary) 18%, var(--color-surface));color:var(--color-on-surface)}
  .pa-badge--primary{background:var(--color-primary);color:var(--color-on-primary)}
  .pa-badge--text{background:transparent;color:var(--color-on-surface-variant)}

  /* Cards en móvil */
  .pa-list{display:grid;gap:10px}
  .pa-item{border:1px solid var(--color-outline-variant);border-radius:var(--radius-m);background:var(--color-surface);padding:12px}
  .pa-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
  .pa-meta{display:grid;gap:6px;margin-top:8px;font-size:.95rem}
  .pa-meta small{color:var(--color-on-surface-variant)}
  .pa-edit details>summary{list-style:none;cursor:pointer}
  .pa-edit details>summary::-webkit-details-marker{display:none}

  /* Tabla en escritorio */
  .pa-table-wrap{display:none;overflow:auto;-webkit-overflow-scrolling:touch}
  .pa-table{width:100%;border-collapse:separate;border-spacing:0 8px}
  .pa-th,.pa-td{padding:8px 10px;text-align:left;vertical-align:middle}

  @media (min-width: 960px){
    .pa-list{display:none}
    .pa-table-wrap{display:block}
  }
</style>

<div class="app-container">
  {{-- Flash / errores --}}
  @if (session('ok'))
    <div class="snackbar snackbar--ok">{{ session('ok') }}</div>
  @endif
  @if ($errors->any())
    <div class="snackbar snackbar--error">
      <strong>Revisa los campos:</strong>
      <ul class="mt-2" style="margin:.5rem 1rem;">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="pa-stack">
    {{-- Sección: Nuevo período (arriba siempre) --}}
    <div class="card">
      <h2 class="mb-3" style="margin:0;">Nuevo período</h2>

      <form method="POST" action="{{ route('periodos.store') }}">
        @csrf
        <div class="grid grid--2">
          <div class="field" style="grid-column:1 / -1;">
            <label class="field__label">Nombre</label>
            <div class="field__box">
              <input class="field__input" name="nombre" value="{{ old('nombre') }}" placeholder="Ej: Semestre 2/2026" required>
            </div>
          </div>

          <div class="field">
            <label class="field__label">Fecha inicio</label>
            <div class="field__box" style="display:flex;align-items:center;gap:8px;">
              <input type="date" class="field__input" name="fecha_inicio" value="{{ old('fecha_inicio') }}" data-picker="date" readonly required>
              <button type="button" class="btn btn--text open-picker" title="Seleccionar fecha">📅</button>
            </div>
            <div class="field__hint">YYYY-MM-DD</div>
          </div>

          <div class="field">
            <label class="field__label">Fecha fin</label>
            <div class="field__box" style="display:flex;align-items:center;gap:8px;">
              <input type="date" class="field__input" name="fecha_fin" value="{{ old('fecha_fin') }}" data-picker="date" readonly required>
              <button type="button" class="btn btn--text open-picker" title="Seleccionar fecha">📅</button>
            </div>
            <div class="field__hint">YYYY-MM-DD</div>
          </div>
        </div>

        <div class="mt-3" style="text-align:right;">
          <button class="btn btn--primary">Guardar</button>
        </div>
      </form>
    </div>

    {{-- Sección: Períodos (abajo) --}}
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
        <h2 class="mb-3" style="margin:0;">Períodos</h2>
        <div class="text-muted" style="font-size:.9rem;">{{ $periodos->total() }} total</div>
      </div>

      {{-- LISTA (móvil / tablet) --}}
      <div class="pa-list">
        @forelse ($periodos as $p)
          @php
            $state = strtolower($p->estado_publicacion ?? 'borrador');
            if ($p->activo && $state !== 'activo') { $state = 'activo'; } // coherencia visual
            $badge = match($state){
              'borrador'=>'pa-badge--outline',
              'activo'=>'pa-badge--tonal',
              'publicado'=>'pa-badge--primary',
              'archivado'=>'pa-badge--text',
              default=>'pa-badge--outline'
            };
            $canEdit = !in_array(($p->estado_publicacion ?? 'borrador'), ['publicado','archivado']);
          @endphp

          <div class="pa-item">
            <div class="pa-head">
              <div style="display:flex;flex-direction:column;gap:4px;min-width:0;">
                <div style="font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->nombre }}</div>
                <div>
                  <span class="pa-badge {{ $badge }}">{{ ucfirst($state) }}</span>
                </div>
              </div>
              <div class="pa-actions">
                @if($state==='borrador')
                  <form method="POST" action="{{ route('periodos.estado',$p) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="estado" value="activo">
                    <button class="btn btn--tonal">Activar</button>
                  </form>
                @endif

                @if(in_array($state,['borrador','activo']))
                  <form method="POST" action="{{ route('periodos.estado',$p) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="estado" value="publicado">
                    <button class="btn btn--primary">Publicar</button>
                  </form>
                @endif

                @if(in_array($state,['borrador','activo','publicado']))
                  <form method="POST" action="{{ route('periodos.estado',$p) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="estado" value="archivado">
                    <button class="btn btn--outline">Archivar</button>
                  </form>
                @endif

                @if(in_array($state,['publicado','archivado']))
                  <details>
                    <summary class="btn btn--text">Reabrir</summary>
                    <div class="card" style="margin-top:8px;">
                      <form method="POST" action="{{ route('periodos.reabrir',$p) }}">
                        @csrf
                        <div class="field">
                          <label class="field__label">Motivo (opcional)</label>
                          <div class="field__box"><input class="field__input" name="motivo" placeholder="Justificación"></div>
                        </div>
                        <div style="text-align:right;">
                          <button class="btn btn--tonal">Confirmar reapertura</button>
                        </div>
                      </form>
                    </div>
                  </details>
                @endif

                @if($state==='activo')
                  <form method="POST" action="{{ route('periodos.estado',$p) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="estado" value="borrador">
                    <button class="btn btn--text">Desactivar</button>
                  </form>
                @endif
              </div>
            </div>

            <div class="pa-meta">
              <div><small>Inicio</small><div>{{ \Illuminate\Support\Carbon::parse($p->fecha_inicio)->toDateString() }}</div></div>
              <div><small>Fin</small><div>{{ \Illuminate\Support\Carbon::parse($p->fecha_fin)->toDateString() }}</div></div>
            </div>

            {{-- Editar: solo nombre y fechas --}}
            <div class="pa-edit" style="margin-top:10px;">
              <details>
                <summary class="btn btn--outline" style="padding:8px 10px;">Editar</summary>
                <div class="card" style="margin-top:8px;">
                  <form method="POST" action="{{ route('periodos.update', $p) }}">
                    @csrf @method('PUT')
                    <div class="grid grid--2">
                      <div class="field" style="grid-column:1 / -1;">
                        <label class="field__label">Nombre</label>
                        <div class="field__box">
                          <input class="field__input" name="nombre" value="{{ old('nombre',$p->nombre) }}" {{ $canEdit ? '' : 'disabled' }} required>
                        </div>
                      </div>

                      <div class="field">
                        <label class="field__label">Inicio</label>
                        <div class="field__box" style="display:flex;gap:8px;align-items:center;">
                          <input type="date" class="field__input" name="fecha_inicio"
                                 value="{{ old('fecha_inicio', \Illuminate\Support\Carbon::parse($p->fecha_inicio)->toDateString()) }}"
                                 data-picker="date" readonly {{ $canEdit ? '' : 'disabled' }} required>
                          <button type="button" class="btn btn--text open-picker" {{ $canEdit ? '' : 'disabled' }}>📅</button>
                        </div>
                        <div class="field__hint">YYYY-MM-DD</div>
                      </div>

                      <div class="field">
                        <label class="field__label">Fin</label>
                        <div class="field__box" style="display:flex;gap:8px;align-items:center;">
                          <input type="date" class="field__input" name="fecha_fin"
                                 value="{{ old('fecha_fin', \Illuminate\Support\Carbon::parse($p->fecha_fin)->toDateString()) }}"
                                 data-picker="date" readonly {{ $canEdit ? '' : 'disabled' }} required>
                          <button type="button" class="btn btn--text open-picker" {{ $canEdit ? '' : 'disabled' }}>📅</button>
                        </div>
                        <div class="field__hint">YYYY-MM-DD</div>
                      </div>
                    </div>
                    <div style="text-align:right;">
                      <button class="btn btn--primary" {{ $canEdit ? '' : 'disabled' }}>Guardar cambios</button>
                    </div>
                  </form>
                </div>
              </details>
            </div>
          </div>
        @empty
          <div class="text-muted">Sin períodos aún.</div>
        @endforelse
      </div>

      {{-- TABLA (escritorio) --}}
      <div class="pa-table-wrap">
        <table class="pa-table">
          <thead>
            <tr class="text-muted">
              <th class="pa-th">Nombre</th>
              <th class="pa-th">Inicio</th>
              <th class="pa-th">Fin</th>
              <th class="pa-th">Estado</th>
              <th class="pa-th" style="text-align:right;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($periodos as $p)
              @php
                $state = strtolower($p->estado_publicacion ?? 'borrador');
                if ($p->activo && $state !== 'activo') { $state = 'activo'; }
                $badge = match($state){
                  'borrador'=>'pa-badge--outline','activo'=>'pa-badge--tonal',
                  'publicado'=>'pa-badge--primary','archivado'=>'pa-badge--text', default=>'pa-badge--outline'
                };
                $canEdit = !in_array(($p->estado_publicacion ?? 'borrador'), ['publicado','archivado']);
              @endphp
              <tr>
                <td class="pa-td" style="font-weight:700;">{{ $p->nombre }}</td>
                <td class="pa-td">{{ \Illuminate\Support\Carbon::parse($p->fecha_inicio)->toDateString() }}</td>
                <td class="pa-td">{{ \Illuminate\Support\Carbon::parse($p->fecha_fin)->toDateString() }}</td>
                <td class="pa-td"><span class="pa-badge {{ $badge }}">{{ ucfirst($state) }}</span></td>
                <td class="pa-td" style="text-align:right;">
                  <div class="pa-actions">
                    {{-- Acciones junto al período --}}
                    @if($state==='borrador')
                      <form method="POST" action="{{ route('periodos.estado',$p) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="activo">
                        <button class="btn btn--tonal">Activar</button>
                      </form>
                    @endif

                    @if(in_array($state,['borrador','activo']))
                      <form method="POST" action="{{ route('periodos.estado',$p) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="publicado">
                        <button class="btn btn--primary">Publicar</button>
                      </form>
                    @endif

                    @if(in_array($state,['borrador','activo','publicado']))
                      <form method="POST" action="{{ route('periodos.estado',$p) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="archivado">
                        <button class="btn btn--outline">Archivar</button>
                      </form>
                    @endif

                    @if(in_array($state,['publicado','archivado']))
                      <details>
                        <summary class="btn btn--text">Reabrir</summary>
                        <div class="card" style="margin-top:8px;max-width:420px;">
                          <form method="POST" action="{{ route('periodos.reabrir',$p) }}">
                            @csrf
                            <div class="field">
                              <label class="field__label">Motivo (opcional)</label>
                              <div class="field__box"><input class="field__input" name="motivo" placeholder="Justificación"></div>
                            </div>
                            <div style="text-align:right;">
                              <button class="btn btn--tonal">Confirmar reapertura</button>
                            </div>
                          </form>
                        </div>
                      </details>
                    @endif

                    @if($state==='activo')
                      <form method="POST" action="{{ route('periodos.estado',$p) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="borrador">
                        <button class="btn btn--text">Desactivar</button>
                      </form>
                    @endif

                    {{-- Editar (solo nombre y fechas) --}}
                    <details>
                      <summary class="btn btn--outline">Editar</summary>
                      <div class="card" style="margin-top:8px;width:min(560px,50vw);">
                        <form method="POST" action="{{ route('periodos.update', $p) }}">
                          @csrf @method('PUT')
                          <div class="grid grid--2">
                            <div class="field" style="grid-column:1 / -1;">
                              <label class="field__label">Nombre</label>
                              <div class="field__box">
                                <input class="field__input" name="nombre" value="{{ old('nombre',$p->nombre) }}" {{ $canEdit ? '' : 'disabled' }} required>
                              </div>
                            </div>
                            <div class="field">
                              <label class="field__label">Inicio</label>
                              <div class="field__box" style="display:flex;gap:8px;align-items:center;">
                                <input type="date" class="field__input" name="fecha_inicio"
                                       value="{{ old('fecha_inicio', \Illuminate\Support\Carbon::parse($p->fecha_inicio)->toDateString()) }}"
                                       data-picker="date" readonly {{ $canEdit ? '' : 'disabled' }} required>
                                <button type="button" class="btn btn--text open-picker" {{ $canEdit ? '' : 'disabled' }}>📅</button>
                              </div>
                              <div class="field__hint">YYYY-MM-DD</div>
                            </div>
                            <div class="field">
                              <label class="field__label">Fin</label>
                              <div class="field__box" style="display:flex;gap:8px;align-items:center;">
                                <input type="date" class="field__input" name="fecha_fin"
                                       value="{{ old('fecha_fin', \Illuminate\Support\Carbon::parse($p->fecha_fin)->toDateString()) }}"
                                       data-picker="date" readonly {{ $canEdit ? '' : 'disabled' }} required>
                                <button type="button" class="btn btn--text open-picker" {{ $canEdit ? '' : 'disabled' }}>📅</button>
                              </div>
                              <div class="field__hint">YYYY-MM-DD</div>
                            </div>
                          </div>
                          <div style="text-align:right;">
                            <button class="btn btn--primary" {{ $canEdit ? '' : 'disabled' }}>Guardar cambios</button>
                          </div>
                        </form>
                      </div>
                    </details>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="pa-td text-muted">Sin períodos aún.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3" style="display:flex;justify-content:flex-end;">
        {{ $periodos->links() }}
      </div>
    </div>
  </div>
</div>

{{-- JS: datepicker sólo con click/botón, sin tecleo --}}
<script>
(function() {
  document.querySelectorAll('input[data-picker="date"]').forEach(function(inp){
    const box = inp.closest('.field__box');
    const btn = box?.querySelector('.open-picker');
    ['keydown','keypress','beforeinput','input'].forEach(ev =>
      inp.addEventListener(ev, e => { if(e.key && e.key.length===1) e.preventDefault(); })
    );
    inp.addEventListener('click', function(e){
      e.preventDefault();
      try { inp.showPicker(); } catch(err) { inp.readOnly=false; inp.focus(); setTimeout(()=>inp.readOnly=true,250); }
    });
    btn?.addEventListener('click', function(){
      try { inp.showPicker(); } catch(err) { inp.readOnly=false; inp.focus(); setTimeout(()=>inp.readOnly=true,250); }
    });
  });
})();
</script>
@endsection
