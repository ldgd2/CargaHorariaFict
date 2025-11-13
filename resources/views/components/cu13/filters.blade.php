@props([
  'periodos'=>[], 'docentes'=>[], 'aulas'=>[],
  'gridUrl'=>'#', 'checkUrl'=>'#', 'dragUrl'=>'#',
])

<div id="cu13-filters"
     class="card toolbar"
     data-url-grid="{{ $gridUrl }}"
     data-url-check="{{ $checkUrl }}"
     data-url-drag="{{ $dragUrl }}">
  <div class="filters">
    <div>
      <label class="text-muted">Período</label>
      <select id="f-per" class="field__input">
        @foreach($periodos as $p)
          <option value="{{ $p->id_periodo }}">{{ $p->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-muted">Docente (opcional)</label>
      <select id="f-doc" class="field__input">
        <option value="">— Todos —</option>
        @foreach($docentes as $d)
          <option value="{{ $d->id_docente }}">{{ $d->nombre_completo }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-muted">Aula (opcional)</label>
      <select id="f-aula" class="field__input">
        <option value="">— Todas —</option>
        @foreach($aulas as $a)
          <option value="{{ $a->id_aula }}">{{ $a->codigo }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div style="display:flex;justify-content:flex-end;align-items:end">
    <button id="btn-load" class="btn btn--primary">Cargar</button>
  </div>
</div>
