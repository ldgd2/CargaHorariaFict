<?php $__env->startSection('title','Editor semanal (CU13)'); ?>

<?php $__env->startSection('content'); ?>
  <div class="app-container" style="display:grid;gap:12px">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h1 style="margin:0">Editor semanal (CU13)</h1>
      <div class="legend" style="display:flex;gap:8px;align-items:center">
        <span class="pill"><span class="dot dot--disp"></span> Disponibilidad</span>
        <span class="pill"><span class="dot dot--ok"></span> OK</span>
        <span class="pill"><span class="dot dot--warn"></span> Advertencia</span>
        <span class="pill"><span class="dot dot--err"></span> Conflicto</span>
      </div>
    </div>

    <div class="controls" style="display:flex;gap:12px;align-items:flex-end">
      <div>
        <label>Período</label>
        <select id="id_periodo">
          <option value="">-- seleccionar --</option>
          <?php $__currentLoopData = $periodos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($p->id_periodo); ?>"><?php echo e($p->nombre); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div>
        <label>Ver por</label>
        <div>
          <label><input type="radio" name="view_by" value="docente" checked> Docente</label>
          <label style="margin-left:8px;"><input type="radio" name="view_by" value="aula"> Aula</label>
        </div>
      </div>

      <div id="filter_docente">
        <label>Docente</label>
        <select id="id_docente">
          <option value="">-- todos --</option>
          <?php $__currentLoopData = $docentes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($d->id_docente); ?>"><?php echo e($d->nombre_completo); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div>
        <label>Carrera</label>
        <select id="id_carrera">
          <option value="">-- todas --</option>
          <?php $__currentLoopData = $carreras ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($c->id_carrera); ?>"><?php echo e($c->nombre); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div>
        <label>Materia</label>
        <select id="id_materia">
          <option value="">-- todas --</option>
          <?php $__currentLoopData = $materias ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option data-carrera="<?php echo e($m->id_carrera ?? ''); ?>" value="<?php echo e($m->id_materia); ?>"><?php echo e($m->nombre); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div id="filter_aula" style="display:none;">
        <label>Aula</label>
        <select id="id_aula">
          <option value="">-- todas --</option>
          <?php $__currentLoopData = $aulas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($a->id_aula); ?>"><?php echo e($a->codigo); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div>
        <button id="btn_refresh" class="btn btn--primary">Actualizar</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:16px;align-items:start">
      <div class="card" style="height:calc(100vh - 260px);overflow:auto">
        <h3 style="margin-top:0">Paleta — Grupos pendientes</h3>
        <div id="weekly-summary" style="margin-bottom:8px;font-size:13px;color:#222">
          <strong>Horas por docente (esta semana):</strong>
          <div id="hours-list" style="margin-top:6px;color:#555">Cargando...</div>
        </div>
        <div id="palette">
          <!-- pending groups will be loaded here -->
        </div>
  <style>
    .week-grid { display:grid; grid-template-columns: 90px repeat(6,1fr); gap:6px; width:100%; }
    .week-grid .header { background:#f4f4f4; padding:8px; font-weight:600; text-align:center }
    .time-col { background:#fafafa; padding:8px; text-align:right }
    .cell { min-height:56px; background:#fff; border:1px solid #eee; padding:6px; position:relative; word-break:break-word }
    .cell.disp { background: linear-gradient(90deg, rgba(220,245,255,0.4), rgba(220,245,255,0.1)); }
    .slot { background:#007bff; color:#fff; padding:6px; border-radius:6px; margin-bottom:4px; font-size:13px; cursor:grab }
    .slot.dragging { opacity:0.6; }
    .slot.warn { background: #f0ad4e }
    .slot.err { background: #dc3545 }
    .cell.over-ok { outline:3px solid rgba(40,167,69,0.25) }
    .cell.over-warn { outline:3px dashed rgba(255,193,7,0.5) }
    .cell.over-err { outline:3px solid rgba(220,53,69,0.45) }
    .pill { display:inline-flex;gap:6px;align-items:center;padding:4px 8px;background:#fafafa;border-radius:6px }
    .dot { display:inline-block;width:12px;height:12px;border-radius:50% }
    .dot--disp{ background:#87e0ff }
    .dot--ok{ background:#28a745 }
    .dot--warn{ background:#f0ad4e }
    .dot--err{ background:#dc3545 }
    #palette .pending { padding:8px;border:1px dashed #ccc;margin-bottom:8px;border-radius:6px;cursor:grab }
    .cu13-modal{ position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center }
    .cu13-modal-content{ background:#fff;padding:16px;border-radius:8px;min-width:320px }
    /* responsive */
    @media (max-width: 900px){
      .controls { flex-wrap:wrap }
      .controls > div { min-width:140px }
      .week-grid { grid-template-columns: 60px repeat(6,minmax(120px,1fr)); gap:6px }
      .cell { min-height:44px; padding:4px }
      .cu13-modal-content{ width: min(92%,420px) }
    }
    @media (max-width: 480px){
      .controls > div { min-width:120px }
      .week-grid { grid-template-columns: 50px repeat(6,minmax(100px,1fr)); }
    }
  </style>

  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?>

  <script>
  (function(){
  const gridUrl = "<?php echo e(route('cargas.grid')); ?>";
  const dragUrl = "<?php echo e(route('cargas.drag')); ?>";
  const checkUrl = "<?php echo e(route('cargas.check')); ?>";
  const csrf = '<?php echo e(csrf_token()); ?>';
  const MATERIAS = <?php echo json_encode($materias ?? [], 15, 512) ?>;
  const CARRERAS = <?php echo json_encode($carreras ?? [], 15, 512) ?>;
  const DOCENTES = <?php echo json_encode($docentes->map(function($d){ return ['id'=>$d->id_docente, 'nombre'=> $d->nombre_completo]; }) ?? [], 512) ?>;
  const AULAS = <?php echo json_encode($aulas->map(function($a){ return ['id'=>$a->id_aula, 'codigo'=> $a->codigo]; }) ?? [], 512) ?>;

  let state = { cargas:[], disponibilidades:[], bloqueos:[], pendientes:[] };
  let __renderGridRetry = 0;
    let dragPayload = null; 

    function minutes(hm){ const [h,m] = hm.split(':').map(Number); return h*60+m }
    function overlap(aStart,aEnd,bStart,bEnd){ return aStart < bEnd && bStart < aEnd }

    function getParams(){
      return { id_periodo: document.getElementById('id_periodo').value, id_docente: document.getElementById('id_docente').value, id_aula: document.getElementById('id_aula').value };
    }

    function clearGrid(){ document.getElementById('grid_wrap').innerHTML = '' }

    function renderPalette(){
      const pal = document.getElementById('palette'); pal.innerHTML='';
      // defensive: pendientes must be an array
      if (!Array.isArray(state.pendientes)){
        pal.innerHTML = '<div style="color:#c00">Error: datos de grupos pendientes inválidos.</div>';
        console.warn('pendientes inválidos:', state.pendientes);
        updateHoursSummary();
        return;
      }

      state.pendientes.forEach(p=>{
        const div = document.createElement('div'); div.className='pending'; div.draggable=true;
        div.dataset.tipo='pendiente'; div.dataset.id_grupo = (p && p.id_grupo) ? p.id_grupo : '';
        div.dataset.duracion = (p && (p.duracion_min||p.duracion)) ? (p.duracion_min||p.duracion) : 60;
        const label = (p && (p.grupo || p.nombre)) ? (p.grupo || p.nombre) : 'Grupo';
        const dur = (p && (p.duracion_min||p.duracion)) ? (p.duracion_min||p.duracion) : 60;
        div.innerText = `${label} - ${dur}min`;
        div.addEventListener('dragstart', onDragStart);
        pal.appendChild(div);
      });
      if (state.pendientes.length===0) pal.innerHTML='<div style="color:#666">No hay grupos pendientes</div>';
      updateHoursSummary();
    }

    function updateHoursSummary(){
      const map = {};
      if (Array.isArray(state.cargas)){
        state.cargas.forEach(c=>{
          if (!c || !c.id_docente) return;
          const id = c.id_docente; const dur = minutes(c.hora_fin)-minutes(c.hora_inicio);
          map[id] = (map[id]||0) + dur;
        });
      }
      const container = document.getElementById('hours-list'); container.innerHTML='';
      if (Object.keys(map).length===0) { container.innerText = 'Sin asignaciones'; return; }
      // map of docentes provided server-side
      const DOC_MAP = <?php echo json_encode($docentes->pluck('nombre_completo', 'id_docente') ?? [], 512) ?>;
      Object.keys(map).forEach(id=>{
        const d = (DOC_MAP || {})[id] || id;
        const hours = Math.floor(map[id]/60); const mins = map[id]%60;
        const el = document.createElement('div'); el.innerText = `${d}: ${hours}h ${mins}m`; container.appendChild(el);
      });
    }

    function renderGrid(data){
      state.cargas = data.cargas || [];
      state.disponibilidades = data.disponibilidades || [];
      state.bloqueos = data.bloqueos || [];
      state.pendientes = data.pendientes || [];

      renderPalette();

      const cargas = state.cargas;
      const disps = state.disponibilidades;

      const daysPresent = new Set(cargas.map(c=>parseInt(c.dia_semana)).concat(disps.map(d=>parseInt(d.dia_semana))));
      const showSat = daysPresent.has(6);
      const days = [1,2,3,4,5]; if (showSat) days.push(6);

      // 45-minute slots from 07:00 to 21:15
      const hours = [];
      const startMin = 7*60; const endMin = 21*60 + 15; const slotLen = 45;
      for (let m = startMin; m <= endMin; m += slotLen){ const hh = String(Math.floor(m/60)).padStart(2,'0'); const mm = String(m%60).padStart(2,'0'); hours.push(hh+':'+mm); }

  const wrap = document.getElementById('grid_wrap');
  if (!wrap){
    // retry a few times in case of race (script executed slightly before DOM insertion)
    if (__renderGridRetry < 10){
      __renderGridRetry++;
      setTimeout(()=>{ renderGrid(data); }, 100);
      return;
    }
    console.warn('renderGrid: #grid_wrap not found in DOM after retries');
    __renderGridRetry = 0;
    return;
  }
  // reset retry counter on success
  __renderGridRetry = 0;
  wrap.innerHTML='';
      const grid = document.createElement('div'); grid.className='week-grid';
      const corner = document.createElement('div'); corner.className='header'; corner.innerText='HORARIO'; grid.appendChild(corner);
      const dayNames = ['Lun','Mar','Mie','Jue','Vie','Sab']; days.forEach(d=>{ const h = document.createElement('div'); h.className='header'; h.innerText = dayNames[d-1]; grid.appendChild(h); });

      // build empty grid cells first
      hours.forEach(hour=>{
        const tcol = document.createElement('div'); tcol.className='time-col'; tcol.innerText = hour + ' - ' + (function(){ const t = minutes(hour)+slotLen; return String(Math.floor(t/60)).padStart(2,'0')+':'+String(t%60).padStart(2,'0'); })(); grid.appendChild(tcol);
        days.forEach(day=>{
          const cell = document.createElement('div'); cell.className='cell'; cell.dataset.dia = day; cell.dataset.hora = hour;
          const anyDisp = disps.some(d=> parseInt(d.dia_semana)===day && minutes(d.hora_inicio) <= minutes(hour) && minutes(d.hora_fin) > minutes(hour));
          if (anyDisp) cell.classList.add('disp');
          cell.addEventListener('dragover', onDragOver);
          cell.addEventListener('drop', onDrop);
          grid.appendChild(cell);
        });
      });

      wrap.appendChild(grid);

      // slots rendered (absolute positioned) — done
    }

    function fetchGrid(){
      const params = getParams();
      if (!params.id_periodo) { alert('Selecciona un período'); return; }
      const q = new URLSearchParams(params).toString();
      fetch(gridUrl + '?' + q, {credentials:'same-origin'})
        .then(r=>r.json()).then(json=>{ if (json.ok) renderGrid(json); else alert('Error al obtener datos'); })
        .catch(e=>{console.error(e); alert('Error al conectar');});
    }

    function onDragStart(e){
      const el = e.currentTarget;
      if (!el || !el.dataset){ dragPayload = null; return; }
      const tipo = el.dataset.tipo || (el.dataset.id_carga ? 'existente' : 'pendiente');
      dragPayload = { tipo, id_carga: el.dataset.id_carga||null, id_grupo: el.dataset.id_grupo||null, duracion: parseInt(el.dataset.duracion||60), id_docente: el.dataset.id_docente||null, id_aula: el.dataset.id_aula||null, start: el.dataset.start||null };
      e.dataTransfer.setData('text/plain','cu13');
      e.dataTransfer.effectAllowed = 'move';
      el.classList.add('dragging');
    }

    function onDragOver(e){
      e.preventDefault();
      const cell = e.currentTarget;
      const dia = parseInt(cell.dataset.dia); const hora = cell.dataset.hora;
      const dur = dragPayload ? dragPayload.duracion : 60;
      const newStart = minutes(hora); const newEnd = newStart + dur;

      const conflict = state.cargas.some(c=>{
        if (dragPayload && dragPayload.id_carga && String(c.id_carga)===String(dragPayload.id_carga)) return false;
        const cStart = minutes(c.hora_inicio); const cEnd = minutes(c.hora_fin);
        if (parseInt(c.dia_semana)!==dia) return false;
        if ((dragPayload.id_docente && String(c.id_docente)===String(dragPayload.id_docente)) || (dragPayload.id_aula && String(c.id_aula)===String(dragPayload.id_aula))) {
          return overlap(newStart,newEnd,cStart,cEnd);
        }
        return false;
      });
      let warn = false;
      if (dragPayload && dragPayload.id_docente){
        const hasDisp = state.disponibilidades.some(d=> parseInt(d.dia_semana)===dia && minutes(d.hora_inicio) <= newStart && minutes(d.hora_fin) >= newEnd && String(d.id_docente)===String(dragPayload.id_docente));
        if (!hasDisp) warn = true;
      }
      let blocked = false;
      if (dragPayload && dragPayload.id_aula){
        blocked = state.bloqueos.some(b=> parseInt(b.id_aula)===parseInt(dragPayload.id_aula) && overlap(newStart,newEnd, minutes(b.fecha_inicio_hm||'00:00'), minutes(b.fecha_fin_hm||'23:59')) );
      }

      cell.classList.remove('over-ok','over-warn','over-err');
      if (blocked || conflict) cell.classList.add('over-err');
      else if (warn) cell.classList.add('over-warn');
      else cell.classList.add('over-ok');
    }

    function onDrop(e){
      e.preventDefault();
      const cell = e.currentTarget; const dia = parseInt(cell.dataset.dia); const hora = cell.dataset.hora;
      document.querySelectorAll('.cell').forEach(c=>c.classList.remove('over-ok','over-warn','over-err'));

      if (!dragPayload) return;
      const startHM = hora; const dur = dragPayload.duracion || 60; const endHM = (function(){ const t = minutes(startHM)+dur; return String(Math.floor(t/60)).padStart(2,'0')+':'+String(t%60).padStart(2,'0'); })();

      const body = document.getElementById('modal-body');
      // build selects for docente and aula so user picks names
      const docenteOptions = DOCENTES.map(d=>`<option value="${d.id}" ${ (String(d.id)===String(dragPayload.id_docente) ? 'selected':'') }>${d.nombre}</option>`).join('');
      const aulaOptions = AULAS.map(a=>`<option value="${a.id}" ${ (String(a.id)===String(dragPayload.id_aula) ? 'selected':'') }>${a.codigo}</option>`).join('');
      body.innerHTML = `
        <div>Dia: <strong>${dia}</strong></div>
        <div>Inicio: <input id="m_start" type="time" value="${startHM}" /></div>
        <div>Fin: <input id="m_end" type="time" value="${endHM}" /></div>
        <div>Docente (opcional): <select id="m_docente"><option value="">-- ninguno --</option>${docenteOptions}</select></div>
        <div>Aula (opcional): <select id="m_aula"><option value="">-- ninguna --</option>${aulaOptions}</select></div>
      `;
      showModal(()=>{
        const payload = { modo: dragPayload.tipo==='existente' ? 'mover' : 'crear', id_carga: dragPayload.id_carga, id_periodo: document.getElementById('id_periodo').value, id_grupo: dragPayload.id_grupo||null, id_docente: document.getElementById('m_docente').value||null, id_aula: document.getElementById('m_aula').value||null, dia_semana: dia, hora_inicio: document.getElementById('m_start').value, hora_fin: document.getElementById('m_end').value };

        const pStart = minutes(payload.hora_inicio), pEnd = minutes(payload.hora_fin);
        const conflict = state.cargas.some(c=>{
          if (payload.id_carga && String(c.id_carga)===String(payload.id_carga)) return false;
          if (parseInt(c.dia_semana)!==dia) return false;
          if ((payload.id_docente && String(c.id_docente)===String(payload.id_docente)) || (payload.id_aula && String(c.id_aula)===String(payload.id_aula))) {
            return overlap(pStart,pEnd, minutes(c.hora_inicio), minutes(c.hora_fin));
          }
          return false;
        });
        if (conflict){ alert('Conflicto detectado. Revisa la posición.'); return; }

        fetch(dragUrl, { method:'PATCH', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf }, body: JSON.stringify(payload), credentials:'same-origin' })
          .then(r=>r.json()).then(j=>{ if (j.ok) { fetchGrid(); } else alert('Error: '+(j.error||j.msg||'server')); })
          .catch(e=>{ console.error(e); alert('Error al guardar'); });
      });
    }

    function onSlotClick(carga){
      dragPayload = { tipo:'existente', id_carga:carga.id_carga, duracion: minutes(carga.hora_fin)-minutes(carga.hora_inicio), id_docente: carga.id_docente, id_aula: carga.id_aula };
 
      const cell = document.querySelector(`.cell[data-dia='${carga.dia_semana}'][data-hora='${carga.hora_inicio}']`);
      if (cell) {
        cell.scrollIntoView({behavior:'smooth',block:'center'});
      }
    }

    function showModal(onConfirm){
      const modal = document.getElementById('cu13-modal'); modal.style.display='flex';
      document.getElementById('modal-cancel').onclick = ()=>{ modal.style.display='none'; };
      document.getElementById('modal-confirm').onclick = ()=>{ modal.style.display='none'; onConfirm(); };
    }

    // double-click edit: open modal prefilled for editing start/end
    function onSlotDoubleClick(carga){
      dragPayload = { tipo:'existente', id_carga:carga.id_carga, duracion: minutes(carga.hora_fin)-minutes(carga.hora_inicio), id_docente: carga.id_docente, id_aula: carga.id_aula };
      const body = document.getElementById('modal-body');
      const docenteOptions2 = DOCENTES.map(d=>`<option value="${d.id}" ${ (String(d.id)===String(carga.id_docente) ? 'selected':'') }>${d.nombre}</option>`).join('');
      const aulaOptions2 = AULAS.map(a=>`<option value="${a.id}" ${ (String(a.id)===String(carga.id_aula) ? 'selected':'') }>${a.codigo}</option>`).join('');
      body.innerHTML = `
        <div>ID: ${carga.id_carga}</div>
        <div>Inicio: <input id="m_start" type="time" value="${carga.hora_inicio}" /></div>
        <div>Fin: <input id="m_end" type="time" value="${carga.hora_fin}" /></div>
        <div>Docente: <select id="m_docente"><option value="">-- ninguno --</option>${docenteOptions2}</select></div>
        <div>Aula: <select id="m_aula"><option value="">-- ninguna --</option>${aulaOptions2}</select></div>
      `;
      showModal(()=>{
        const payload = { modo:'mover', id_carga: carga.id_carga, id_periodo: document.getElementById('id_periodo').value, id_docente: document.getElementById('m_docente').value||null, id_aula: document.getElementById('m_aula').value||null, dia_semana: carga.dia_semana, hora_inicio: document.getElementById('m_start').value, hora_fin: document.getElementById('m_end').value };
        fetch(dragUrl, { method:'PATCH', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf }, body: JSON.stringify(payload), credentials:'same-origin' })
          .then(r=>r.json()).then(j=>{ if (j.ok) fetchGrid(); else alert('Error: '+(j.error||j.msg||'server')); })
          .catch(e=>{ console.error(e); alert('Error al guardar'); });
      });
    }

    // adjust slot duration by deltaMinutes (positive or negative)
    function adjustSlotDuration(id_carga, deltaMinutes){
      const carga = state.cargas.find(c=> String(c.id_carga)===String(id_carga));
      if (!carga) return alert('Carga no encontrada');
      const start = minutes(carga.hora_inicio); const end = minutes(carga.hora_fin); let newEnd = end + deltaMinutes;
      if (newEnd <= start + 30) return alert('Duración mínima 30 minutos');
      const payload = { modo:'mover', id_carga: id_carga, id_periodo: document.getElementById('id_periodo').value, id_docente: carga.id_docente||null, id_aula: carga.id_aula||null, dia_semana: carga.dia_semana, hora_inicio: carga.hora_inicio, hora_fin: String(Math.floor(newEnd/60)).padStart(2,'0')+':'+String(newEnd%60).padStart(2,'0') };
      fetch(dragUrl, { method:'PATCH', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf }, body: JSON.stringify(payload), credentials:'same-origin' })
        .then(r=>r.json()).then(j=>{ if (j.ok) fetchGrid(); else alert('Error: '+(j.error||j.msg||'server')); })
        .catch(e=>{ console.error(e); alert('Error al guardar'); });
    }

  // bulk assign handlers (guard elements exist)
  const btnBulkAssign = document.getElementById('btn_bulk_assign');
  if (btnBulkAssign) btnBulkAssign.addEventListener('click', ()=>{ const m = document.getElementById('cu13-bulk'); if (m) m.style.display='flex'; });
  const bulkCancel = document.getElementById('bulk-cancel');
  if (bulkCancel) bulkCancel.addEventListener('click', ()=>{ const m = document.getElementById('cu13-bulk'); if (m) m.style.display='none'; });
  const bulkConfirm = document.getElementById('bulk-confirm');
  if (bulkConfirm) bulkConfirm.addEventListener('click', async ()=>{
      const days = Array.from(document.querySelectorAll('.bulk-day:checked')).map(i=>parseInt(i.value));
      const times = parseInt(document.getElementById('bulk-times').value)||1;
      const duration = parseInt(document.getElementById('bulk-duration').value)||60;
      const gap = parseInt(document.getElementById('bulk-gap').value)||15;
      const start = document.getElementById('bulk-start').value || '08:00';
      const id_docente = document.getElementById('bulk-docente').value || null;
      if (days.length===0) return alert('Selecciona al menos un día');
      document.getElementById('cu13-bulk').style.display='none';
      // build requests
      const promises = [];
      for (const d of days){
        let curStart = minutes(start);
        for (let t=0; t<times; t++){
          const sHM = String(Math.floor(curStart/60)).padStart(2,'0')+':'+String(curStart%60).padStart(2,'0');
          const eMin = curStart + duration; const eHM = String(Math.floor(eMin/60)).padStart(2,'0')+':'+String(eMin%60).padStart(2,'0');
          const payload = { modo:'crear', id_periodo: document.getElementById('id_periodo').value, id_grupo: null, id_docente: id_docente, id_aula: null, dia_semana: d, hora_inicio: sHM, hora_fin: eHM };
          promises.push(fetch(dragUrl, { method:'PATCH', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf }, body: JSON.stringify(payload), credentials:'same-origin' }).then(r=>r.json()));
          curStart = eMin + gap;
        }
      }
      // run sequentially to reduce DB contention
      for (const p of promises){
        const res = await p; if (!res.ok) console.warn('bulk error',res);
      }
      fetchGrid();
    });

    // filter materias by carrera
    const carreraEl = document.getElementById('id_carrera');
    if (carreraEl) carreraEl.addEventListener('change', ()=>{
      const selected = document.getElementById('id_carrera').value;
        const materSelect = document.getElementById('id_materia');
      if (materSelect){
        Array.from(materSelect.querySelectorAll('option')).forEach(opt=>{
          if (!opt.dataset.carrera) return; // keep default option
          opt.style.display = (selected=='' || opt.dataset.carrera===selected) ? 'block' : 'none';
        });
      }
    });

 
    const btnRefresh = document.getElementById('btn_refresh');
    if (btnRefresh) btnRefresh.addEventListener('click', fetchGrid);
    const viewInputs = document.querySelectorAll('input[name=view_by]');
    if (viewInputs && viewInputs.length) viewInputs.forEach(inp=>inp.addEventListener('change', ()=>{
      const by = document.querySelector('input[name=view_by]:checked').value;
      document.getElementById('filter_docente').style.display = by==='docente' ? 'block' : 'none';
      document.getElementById('filter_aula').style.display = by==='aula' ? 'block' : 'none';
    }));
    const periodoEl = document.getElementById('id_periodo');
    if (periodoEl) periodoEl.addEventListener('change', fetchGrid);


  document.addEventListener('dragend', ()=>{ Array.from(document.querySelectorAll('.slot')).forEach(s=>s.classList.remove('dragging')); dragPayload=null; Array.from(document.querySelectorAll('.cell')).forEach(c=>c.classList.remove('over-ok','over-warn','over-err')); });

    window.CU13 = window.CU13 || {}; window.CU13.initCU13Editor = function(){ fetchGrid(); };
  })();
  </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/carga/editor-semanal.blade.php ENDPATH**/ ?>