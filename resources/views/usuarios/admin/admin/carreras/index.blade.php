@extends('layouts.app')
@section('title','Carreras')

@section('content')
<div class="app-container">

  {{-- FORM CREAR/EDITAR --}}
  <div class="card mb-4">
    <h1 class="text-xl font-bold mb-3">Carreras</h1>

    <form id="form-create" class="grid grid--2">
      @csrf

      <div class="field">
        <label class="field__label">Nombre</label>
        <div class="field__box"><input class="field__input" name="nombre" required></div>
      </div>

      {{-- Jefe de carrera al crear/editar --}}
      <div class="field">
        <label class="field__label">Jefe de carrera (docente)</label>
        <div class="field__box">
          <select name="jefe_docente_id" id="form_jefe_docente_id" class="field__select">
            <option value="">— Sin jefe por ahora —</option>
          </select>
        </div>
        <small class="field__hint">Puedes dejarlo vacío y asignarlo después.</small>
        <div class="mt-2" style="display:flex; gap:10px; align-items:center;">
          <label class="text-muted" style="display:flex;gap:8px;align-items:center;">
            <input type="checkbox" id="form_ver_todos"> Ver todos
          </label>
          <input id="form_buscar_docente" placeholder="Buscar docente..." class="field__input"
                 style="max-width:260px;border:1px solid var(--color-outline-variant);border-radius:10px;padding:8px">
        </div>
      </div>

      <div><button class="btn btn--primary" type="submit">Guardar</button></div>
    </form>
    <div id="msg" class="text-sm mt-2"></div>
  </div>

  {{-- LISTADO (SOLO LECTURA DE JEFE) --}}
  <div class="card">
    <div class="flex items-center justify-between mb-3" style="gap:10px; flex-wrap:wrap;">
      <h2 class="text-lg font-semibold">Listado</h2>
      <div style="display:flex; gap:10px; align-items:center;">
        <input id="q" class="field__input"
               style="max-width:240px;border:1px solid var(--color-outline-variant);border-radius:10px;padding:8px"
               placeholder="Buscar carrera...">
      </div>
    </div>
    <div id="table"></div>
  </div>

</div>

<script>
/* =========================
   API endpoints (named routes)
   ========================= */
const API = {
  index : "{{ route('admin.carreras.index') }}",        // GET ?q=&per_page=
  store : "{{ route('admin.carreras.store') }}",        // POST
  update: id => "{{ url('admin/carreras') }}/"+id,      // POST + _method=PUT
  toggle: id => "{{ url('admin/carreras') }}/"+id+"/toggle", // PATCH
  docentes: (params={}) => {
    const u = new URL("{{ route('admin.docentes.index') }}", window.location.origin);
    if (params.per_page) u.searchParams.set('per_page', params.per_page);
    if (params.q)        u.searchParams.set('q', params.q);
    return u;
  }
};

const HDR = {
  'Accept':'application/json',
  'X-CSRF-TOKEN':'{{ csrf_token() }}',
  'X-Requested-With':'XMLHttpRequest'
};

function flash(msg, ok=true) {
  const el = document.getElementById('msg');
  el.textContent = msg;
  el.style.color = ok ? '#00E5A8' : '#ff6b6b';
  setTimeout(()=>{ el.textContent=''; }, 3500);
}

async function xhr(url, opts={}) {
  const res = await fetch(url, opts);
  if (!res.ok) {
    let msg = res.status+' '+res.statusText;
    try { const j = await res.json(); if (j?.message) msg = j.message; } catch {}
    throw new Error(msg);
  }
  return res.json();
}

/* =========================
   Cargar docentes para el SELECT del formulario (nombre + apellido)
   ========================= */
async function fetchDocentes(opts={}) {
  try {
    const url = API.docentes({
      per_page: opts.all ? 2000 : 50,
      q: opts.q || ''
    });
    return await xhr(url, {headers: HDR});
  } catch(e) {
    flash('Error cargando docentes: '+e.message, false);
    return {data:[]};
  }
}

function nomCompletoUsuario(u) {
  if (!u) return '';
  const n = u.nombre ? u.nombre.trim() : '';
  const a = u.apellido ? u.apellido.trim() : '';
  return (n + ' ' + a).trim() || (u.username ?? '');
}

async function populateFormDocentes() {
  const data = await fetchDocentes({
    all: document.getElementById('form_ver_todos').checked,
    q: document.getElementById('form_buscar_docente').value.trim()
  });
  const sel = document.getElementById('form_jefe_docente_id');
  const keep = sel.value || '';
  sel.innerHTML = `<option value="">— Sin jefe por ahora —</option>` +
      (data.data||[]).map(d => {
        const label = d.usuario ? nomCompletoUsuario(d.usuario) : ('Docente #'+d.id_docente);
        return `<option value="${d.id_docente}">${label}</option>`;
      }).join('');
  if (keep) sel.value = keep;
}

/* =========================
   Cargar Carreras (todas)
   ========================= */
async function loadCarreras(q='') {
  try {
    const url = new URL(API.index, window.location.origin);
    if (q) url.searchParams.set('q', q);
    url.searchParams.set('per_page', 2000);
    const data = await xhr(url, {headers: HDR});
    renderTable(data.data || []);
  } catch(e) {
    flash('Error cargando carreras: '+e.message, false);
    document.querySelector('#table').innerHTML = `
      <div class="coor-table-wrap">
        <table class="coor-recent" style="width:100%">
          <thead>
            <tr>
              <th class="coor-th">Carrera</th>
              <th class="coor-th">Jefe de carrera</th>
              <th class="coor-th">Estado</th>
              <th class="coor-th">Acción</th>
              <th class="coor-th">Editar</th>
            </tr>
          </thead>
          <tbody><tr><td class="coor-td" colspan="5">No se pudieron cargar carreras.</td></tr></tbody>
        </table>
      </div>`;
  }
}

function renderTable(items) {
  const tbody = (items).map(c => {
    const jefeNombre = c.jefe && c.jefe.usuario ? nomCompletoUsuario(c.jefe.usuario) : '—';
    return `
      <tr>
        <td class="coor-td"><b>${c.nombre ?? ''}</b></td>

        <!-- SOLO LECTURA DEL JEFE (nombre + apellido) -->
        <td class="coor-td">${jefeNombre}</td>

        <td class="coor-td">${c.habilitado ? 'Habilitado' : 'Inhabilitado'}</td>

        <td class="coor-td">
          <button class="btn btn--outline" onclick="toggleCarrera(${c.id_carrera})">
            ${c.habilitado ? 'Desactivar' : 'Activar'}
          </button>
        </td>

        <td class="coor-td">
          <button class="btn btn--tonal"
            onclick="editCarrera(${c.id_carrera},'${encodeURIComponent(c.nombre ?? '')}','${c.jefe_docente_id ?? ''}')">
            Editar
          </button>
        </td>
      </tr>
    `;
  }).join('');

  document.querySelector('#table').innerHTML = `
    <div class="coor-table-wrap">
      <table class="coor-recent" style="width:100%">
        <thead>
          <tr>
            <th class="coor-th">Carrera</th>
            <th class="coor-th">Jefe de carrera</th>
            <th class="coor-th">Estado</th>
            <th class="coor-th">Acción</th>
            <th class="coor-th">Editar</th>
          </tr>
        </thead>
        <tbody>${tbody || '<tr><td class="coor-td" colspan="5">Sin registros</td></tr>'}</tbody>
      </table>
    </div>
  `;
}

/* =========================
   Acciones: toggle y editar (el cambio de jefe es SOLO en el form)
   ========================= */
async function toggleCarrera(id){
  try {
    await xhr(API.toggle(id), {method:'PATCH', headers: HDR});
    flash('Estado actualizado.');
    loadCarreras(document.getElementById('q').value.trim());
  } catch(e) {
    flash('No se pudo cambiar el estado: '+e.message, false);
  }
}

function editCarrera(id,nombre,jefe_id){
  nombre = decodeURIComponent(nombre);
  const frm = document.getElementById('form-create');
  frm.nombre.value = nombre;
  document.getElementById('form_jefe_docente_id').value = jefe_id || '';
  frm.dataset.editing = id;
  frm.querySelector('button[type="submit"]').textContent = 'Guardar cambios';
  frm.scrollIntoView({behavior:'smooth',block:'start'});
}

/* =========================
   Submit crear/editar
   ========================= */
document.getElementById('form-create').addEventListener('submit', async e=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const id = e.target.dataset.editing;
  const url = id ? API.update(id) : API.store;
  if (id) fd.append('_method', 'PUT');

  try {
    await xhr(url, {method:'POST', headers: HDR, body: fd});
    delete e.target.dataset.editing;
    e.target.querySelector('button[type="submit"]').textContent='Guardar';
    e.target.reset();
    flash('Guardado correctamente.');
    loadCarreras(document.getElementById('q').value.trim());
  } catch(err) {
    flash(err.message || 'Error al guardar', false);
  }
});

/* =========================
   Eventos de búsqueda y toggles
   ========================= */
document.getElementById('q').addEventListener('input', e=> loadCarreras(e.target.value));
document.getElementById('form_ver_todos').addEventListener('change', populateFormDocentes);
document.getElementById('form_buscar_docente').addEventListener('input', populateFormDocentes);

/* init */
populateFormDocentes();
loadCarreras();
</script>
@endsection
