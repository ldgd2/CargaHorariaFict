// resources/js/cu13/editor.js
export function initCU13Editor() {
  const filters = document.getElementById('cu13-filters');
  const grid    = document.getElementById('grid');

  // URLs desde data-attrs
  const URL_GRID  = filters?.dataset.urlGrid  || '#';
  const URL_CHECK = filters?.dataset.urlCheck || '#';
  const URL_DRAG  = filters?.dataset.urlDrag  || '#';

  // Filtros (null-safe)
  const fPer = document.getElementById('f-per');
  const fDoc = document.getElementById('f-doc');
  const fAuEl= document.getElementById('f-aula');
  const btnLoad = document.getElementById('btn-load');

  // Si #f-aula no existe, generamos objeto con value vacío
  const fAu = fAuEl ?? { value: '' };

  // Aula palette
  const aulaList   = document.getElementById('aula-list');
  const aulaActive = document.getElementById('aula-active');

  // Save bar
  const btnSave    = document.getElementById('btn-save');
  const pendingInfo= document.getElementById('pending-info');

  const HINI = 7, HFIN = 21, pxPerHour = 64;

  function hhmmToMin(hhmm){ const [h,m]=hhmm.split(':').map(Number); return h*60+m; }
  function minToTop(min){ return ((min - HINI*60)/60)*pxPerHour; }

  // ---------- Estado ----------
  let dragData = null;             // datos del bloque
  let dragKind = null;             // 'block' | 'aula'
  let activeAulaId = null;         // aula seleccionada
  let pendingOps = [];             // {payload, elRef}

  // ---------- Grid ----------
  function buildGrid(){
    while (grid.children.length > 8) grid.removeChild(grid.lastChild);
    for(let h=HINI; h<HFIN; h++){
      const t = document.createElement('div');
      t.className = 'time-cell';
      t.textContent = String(h).padStart(2,'0')+':00';
      grid.appendChild(t);

      for(let d=1; d<=7; d++){
        const c = document.createElement('div');
        c.className = 'slot-cell';
        c.dataset.dia = d; c.dataset.hora = h;
        c.addEventListener('dragover', onDragOverCell);
        c.addEventListener('drop', onDropCell);
        grid.appendChild(c);
      }
    }
  }

  function paintDisp(disps){
    [...grid.querySelectorAll('.disp-band')].forEach(n=>n.remove());
    disps.forEach(x=>{
      const d = +x.dia_semana;
      const y1 = minToTop(hhmmToMin(x.hora_inicio));
      const y2 = minToTop(hhmmToMin(x.hora_fin));
      const host = grid.querySelector(`.slot-cell[data-dia="${d}"][data-hora="${HINI}"]`);
      if(!host) return;
      const band = document.createElement('div');
      band.className = 'disp-band';
      band.style.top = y1+'px'; band.style.height = Math.max(6, y2-y1)+'px';
      host.appendChild(band);
    });
  }

  function blockEl(b){
    const el = document.createElement('div');
    el.className = 'block ok';
    el.draggable = true;
    el.innerHTML = `
      <div class="b-title">${b.grupo || 'Grupo'}</div>
      <div class="b-sub">${(b.docente || 'Docente')} · Aula ${b.aula || '—'}</div>
      <div class="b-time">${b.hora_inicio}–${b.hora_fin}</div>
    `;
    el.dataset.id         = b.id || b.id_carga || b.id_carga_horaria || '';
    el.dataset.id_docente = b.id_docente;
    el.dataset.id_aula    = b.id_aula;
    el.dataset.id_grupo   = b.id_grupo;

    el.addEventListener('dragstart', e=>{
      dragKind='block';
      dragData = {
        id_carga: el.dataset.id,
        id_docente: el.dataset.id_docente,
        id_aula: el.dataset.id_aula,
        id_grupo: el.dataset.id_grupo,
        elRef: el
      };
      e.dataTransfer.effectAllowed = 'move';
    });

    const top = minToTop(hhmmToMin(b.hora_inicio));
    const height = Math.max(32, minToTop(hhmmToMin(b.hora_fin)) - top);
    el.style.top = top+'px';
    el.style.height = height+'px';
    return el;
  }

  function placeBlocks(cargas){
    [...grid.querySelectorAll('.block')].forEach(n=>n.remove());
    cargas.forEach(b=>{
      const host = grid.querySelector(`.slot-cell[data-dia="${b.dia_semana}"][data-hora="${HINI}"]`);
      if(host) host.appendChild(blockEl(b));
    });
  }

  async function loadData(){
    try{
      btnLoad.disabled = true; btnLoad.textContent = 'Cargando…';
      buildGrid();
      const params = new URLSearchParams({
        id_periodo: fPer?.value ?? '',
        id_docente: fDoc?.value ?? '',
        id_aula:    fAu?.value  ?? ''
      });
      const r = await fetch(`${URL_GRID}?${params}`, {headers:{'Accept':'application/json'}});
      if(!r.ok){
        const msg = await r.text().catch(()=> '');
        alert(`No se pudo cargar el grid (HTTP ${r.status}).\n${msg.slice(0,300)}`);
        return;
      }
      const j = await r.json();
      paintDisp(j.disponibilidades || []);
      placeBlocks(j.cargas || []);
      clearPending();
    } catch(e){
      alert('Error al cargar el grid: '+(e?.message || e));
    } finally {
      btnLoad.disabled = false; btnLoad.textContent = 'Cargar';
    }
  }

  // ---------- Aula palette ----------
  aulaList?.querySelectorAll('.tool-aula').forEach(chip=>{
    chip.addEventListener('dragstart', e => { dragKind='aula'; dragData={aulaId: chip.dataset.id, label: chip.textContent}; });
  });
  aulaActive?.addEventListener('dragover', e => { if(dragKind==='aula') e.preventDefault(); });
  aulaActive?.addEventListener('drop', e => {
    e.preventDefault();
    if(dragKind!=='aula' || !dragData) return;
    activeAulaId = +dragData.aulaId;
    aulaActive.innerHTML = `<span class="active-pill">Aula ${dragData.label}</span>`;
    dragData=null; dragKind=null;
  });

  // ---------- Drag preview ----------
  async function onDragOverCell(e){
    if(dragKind!=='block' || !dragData) return;
    e.preventDefault();
    const cell = e.currentTarget;
    const dia  = +cell.dataset.dia;
    const h    = +cell.dataset.hora;
    const ini = `${String(h).padStart(2,'0')}:00`;
    const fin = `${String(h+1).padStart(2,'0')}:00`;

    const body = {
      id_periodo: +(fPer?.value ?? 0),
      id_carga: +dragData.id_carga,
      id_docente: +dragData.id_docente,
      id_aula: +(activeAulaId ?? dragData.id_aula ?? 0),
      dia_semana: dia, hora_inicio: ini, hora_fin: fin
    };
    const res = await fetch(URL_CHECK,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content},body:JSON.stringify(body)});
    if(!res.ok) return;
    const j = await res.json();

    const blk = dragData.elRef;
    blk.classList.remove('ok','warn','err');
    if(j.conflictos?.docente || j.conflictos?.aula) blk.classList.add('err');
    else if(j.advertencias?.fueraDisponibilidad)     blk.classList.add('warn');
    else blk.classList.add('ok');
  }

  // ---------- Pendientes y guardado ----------
  function pushPending(op){
    pendingOps.push(op);
    op.elRef.classList.add('pending');
    btnSave.disabled = false;
    btnSave.textContent = `Guardar (${pendingOps.length})`;
    pendingInfo.textContent = `${pendingOps.length} cambio(s) sin guardar`;
  }
  function clearPending(){
    pendingOps = [];
    btnSave.disabled = true;
    btnSave.textContent = 'Guardar (0)';
    pendingInfo.textContent = 'Sin cambios';
    document.querySelectorAll('.block.pending').forEach(b=>b.classList.remove('pending'));
  }

  function onDropCell(e){
    if(dragKind!=='block' || !dragData) return;
    e.preventDefault();
    const cell = e.currentTarget;
    const dia  = +cell.dataset.dia;
    const h    = +cell.dataset.hora;
    const ini = `${String(h).padStart(2,'0')}:00`;
    const fin = `${String(h+1).padStart(2,'0')}:00`;

    const payload = {
      modo:'mover',
      id_carga: +dragData.id_carga,
      id_periodo: +(fPer?.value ?? 0),
      id_grupo: +dragData.id_grupo,
      id_docente: +dragData.id_docente,
      id_aula: +(activeAulaId ?? dragData.id_aula ?? 0),
      dia_semana: dia, hora_inicio: ini, hora_fin: fin
    };
    pushPending({payload, elRef: dragData.elRef});

    // feedback visual inmediato
    dragData.elRef.style.top = minToTop(hhmmToMin(ini))+'px';
    const sub = dragData.elRef.querySelector('.b-sub');
    if(sub && payload.id_aula) sub.textContent = (sub.textContent.replace(/Aula .*/,'')).trim() + ` · Aula ${payload.id_aula}`;
    dragData=null; dragKind=null;
  }

  btnSave?.addEventListener('click', async ()=>{
    btnSave.disabled = true; btnSave.textContent = 'Guardando…';
    try{
      for(const op of pendingOps){
        const r = await fetch(URL_DRAG,{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content},body:JSON.stringify(op.payload)});
        if(!r.ok){
          const err = await r.json().catch(()=>({}));
          alert('No se pudo mover una carga: '+(err?.error || r.status));
        }else{
          op.elRef.classList.remove('pending');
        }
      }
      await loadData();
    } finally {
      clearPending();
    }
  });

  // Eventos
  btnLoad?.addEventListener('click', loadData);
  loadData();
}
