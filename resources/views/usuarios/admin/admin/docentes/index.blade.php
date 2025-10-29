@extends('layouts.app')
@section('title','Docentes')
@section('content')
<div class="app-container">
  <div class="card mb-4">
    <h1 class="text-xl font-bold mb-3">Docentes</h1>
    <form id="form-doc" class="grid grid--2">
      @csrf
      <div class="field"><label class="field__label">Nombre</label><div class="field__box"><input class="field__input" name="nombre" required></div></div>
      <div class="field"><label class="field__label">Documento</label><div class="field__box"><input class="field__input" name="nro_documento" required></div></div>
      <div class="field"><label class="field__label">Tipo de contrato</label><div class="field__box"><input class="field__input" name="tipo_contrato" required></div></div>
      <div class="field"><label class="field__label">Carrera principal</label><div class="field__box"><input class="field__input" name="carrera_principal" required></div></div>
      <div class="field"><label class="field__label">Tope horas/semana</label><div class="field__box"><input class="field__input" type="number" min="1" name="tope_horas_semana" required></div></div>
      <div class="field"><label class="field__label">Email</label><div class="field__box"><input class="field__input" type="email" name="email"></div></div>
      <div class="field"><label class="field__label">Teléfono</label><div class="field__box"><input class="field__input" name="telefono"></div></div>
      <div><button class="btn btn--primary">Guardar</button></div>
    </form>
  </div>

  <div class="card">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-lg font-semibold">Listado</h2>
      <input id="q" class="field__input" style="max-width:220px;border:1px solid var(--color-outline-variant);border-radius:10px;padding:8px" placeholder="Buscar...">
    </div>
    <div id="table"></div>
  </div>
</div>

<script>
const API={
  index:"{{ route('admin.docentes.index') }}",
  store:"{{ route('admin.docentes.store') }}",
  update:(id)=>"{{ url('admin/docentes') }}/"+id,
  toggle:(id)=>"{{ url('admin/docentes') }}/"+id+"/toggle",
};
const hdr={'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'};

async function load(page=1,q=''){
  const u=new URL(API.index,location.origin); u.searchParams.set('page',page); if(q)u.searchParams.set('q',q);
  const r=await fetch(u,{headers:hdr}); const d=await r.json(); render(d);
}
function render(d){
  const rows=(d.data||[]).map(x=>`
    <tr>
      <td class="coor-td"><b>${x.nombre}</b><div class="text-muted">${x.nro_documento}</div></td>
      <td class="coor-td">${x.tipo_contrato}</td>
      <td class="coor-td">${x.carrera_principal}</td>
      <td class="coor-td">${x.tope_horas_semana}</td>
      <td class="coor-td">${x.habilitado?'Habilitado':'Inhabilitado'}</td>
      <td class="coor-td">
        <button class="btn btn--outline" onclick="toggle(${x.id_docente})">${x.habilitado?'Desactivar':'Activar'}</button>
      </td>
      <td class="coor-td">
        <button class="btn btn--tonal" onclick="edit(${x.id_docente},'${encodeURIComponent(x.nombre)}','${encodeURIComponent(x.nro_documento)}','${encodeURIComponent(x.tipo_contrato)}','${encodeURIComponent(x.carrera_principal)}',${x.tope_horas_semana},'${encodeURIComponent(x.email??'')}','${encodeURIComponent(x.telefono??'')}')">Editar</button>
      </td>
    </tr>`).join('');
  document.getElementById('table').innerHTML=`
    <div class="coor-table-wrap"><table class="coor-recent" style="width:100%">
      <thead><tr><th class="coor-th">Docente</th><th class="coor-th">Contrato</th><th class="coor-th">Carrera</th><th class="coor-th">Tope</th><th class="coor-th">Estado</th><th class="coor-th">Acción</th><th class="coor-th">Editar</th></tr></thead>
      <tbody>${rows||'<tr><td class="coor-td" colspan="7">Sin registros</td></tr>'}</tbody></table></div>`;
}
async function toggle(id){ await fetch(API.toggle(id),{method:'PATCH',headers:hdr}); load(); }
function edit(id,nombre,doc,contr,carr,tope,email,tel){
  const f=document.getElementById('form-doc'); f.dataset.editing=id;
  f.nombre.value=decodeURIComponent(nombre); f.nro_documento.value=decodeURIComponent(doc);
  f.tipo_contrato.value=decodeURIComponent(contr); f.carrera_principal.value=decodeURIComponent(carr);
  f.tope_horas_semana.value=tope; f.email.value=decodeURIComponent(email); f.telefono.value=decodeURIComponent(tel);
  f.querySelector('button').textContent='Guardar cambios'; f.scrollIntoView({behavior:'smooth'});
}
document.getElementById('form-doc').addEventListener('submit',async e=>{
  e.preventDefault(); const fd=new FormData(e.target); const id=e.target.dataset.editing;
  if(id){ await fetch(API.update(id),{method:'PUT',headers:hdr,body:fd}); delete e.target.dataset.editing; e.target.querySelector('button').textContent='Guardar'; }
  else { await fetch(API.store,{method:'POST',headers:hdr,body:fd}); }
  e.target.reset(); load();
});
document.getElementById('q').addEventListener('input',e=>load(1,e.target.value));
load();
</script>
@endsection
