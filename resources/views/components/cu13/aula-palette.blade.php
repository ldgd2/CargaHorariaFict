@props(['aulas'=>[]])

<aside class="side panel">
  <div>
    <div class="text-muted" style="font-weight:700;margin-bottom:6px">
      Aulas (arrástrala para seleccionar)
    </div>
    <div id="aula-list" class="chips">
      @foreach($aulas as $a)
        <div class="chip tool-aula" draggable="true" data-id="{{ $a->id_aula }}">
          {{ $a->codigo }}
        </div>
      @endforeach
    </div>
  </div>

  <div class="panel" style="margin-top:12px">
    <div class="text-muted" style="font-weight:700;margin-bottom:6px">Aula activa</div>
    <div id="aula-active" class="active-slot" data-drop="aula">
      <span class="text-muted">Suelta aquí una aula</span>
    </div>
  </div>
</aside>
