@extends('layouts.app')
@section('title','Aulas')
@section('content')
<div class="app-container">
  <div class="card mb-4">
    <h1 class="text-xl font-bold mb-3">Aulas</h1>
    <form id="form-aula" class="grid grid--2">
      @csrf
      <div class="field"><label class="field__label">Código</label><div class="field__box"><input class="field__input" name="codigo" required></div></div>
      <div class="field"><label class="field__label">Nombre</label><div class="field__box"><input class="field__input" name="nombre" required></div></div>
      <div class="field"><label class="field__label">Capacidad</label><div class="field__box"><input class="field__input" type="number" min="0" name="capacidad" required></div></div>
      <div class="field"><label class="field__label">Tipo</label><div class="field__box"><input class="field__input" name="tipo" placeholder="teoria/lab" required></div></div>
      <div class="field" style="grid-column:1/-1"><label class="field__label">Ubicación</label><div class="field__box"><input class="field__input" name="ubicacion"></div></div>
      <div><button class="btn btn--primary">Guardar</button></div>
    </form>
  </div>

  <div class="card">
    <h2 class="text-lg font-semibold mb-3">Listado</h2>
    <div id="table"></div>
  </div>
</div>

<script>
const API={
  index:"{{ route('admin.aulas.index') }}",
  store:"{{ route('admin.aulas.store') }}",
  update:(id)=>"{{ url('admin/aulas') }}/"+id,
  toggle:(id)=>"{{ url('admin/aulas') }}/"+id+"/toggle",
};
const hdr={'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'};

async function load(){
  const r=await fetch(API.index,{headers:hdr}); const d=await r.json();
  const rows=(d.data||[]).map(a=>`
    <tr>
      <td class="coor-td"><b>${a.nombre}</b><div class="text-muted">${a.codigo}</div></td>
      <td class="coor-td">${a.capacidad}</td>
      <td class="coor-td">${a.tipo}</td>
      <td class="coor-td">${a.ubicacion??''}</td>
      <td class="coor-td">${a.habilitado?'Habilitado':'Inhabilitado'}</td>
      <td class="coor-td">
        <button class="btn btn--outline" onclick="toggle(${a.id_aula})">${a.habilitado?'Desactivar':'Activar'}</button>
      </td>
      <td class="coor-td">
        <button class="btn btn--tonal" onclick="edit(${a.id_aula},'${encodeURIComponent(a.codigo)}','${encodeURIComponent(a.nombre)}',${a.capacidad},'${encodeURIComponent(a.tipo)}','${encodeURIComponent(a.ubicacion??'')}')">Editar</button>
      </td>
    </tr>`).join('');
  document.getElementById('table').innerHTML=`
  <div class="coor-table-wrap"><table class="coor-recent" style="width:100%">
    <thead><tr><th class="coor-th">Aula</th><th class="coor-th">Capacidad</th><th class="coor-th">Tipo</th><th class="coor-th">Ubicación</th><th class="coor-th">Estado</th><th class="coor-th">Acción</th><th class="coor-th">Editar</th></tr></thead>
    <tbody>${rows||'<tr><td class="coor-td" colspan="7">Sin registros</td></tr>'}</tbody></table></div>`;
}
async function toggle(id){ await fetch(API.toggle(id),{method:'PATCH',headers:hdr}); load(); }
function edit(id,cod,nom,cap,tipo,ubi){
  const f=document.getElementById('form-aula'); f.dataset.editing=id;
  f.codigo.value=decodeURIComponent(cod); f.nombre.value=decodeURIComponent(nom);
  f.capacidad.value=cap; f.tipo.value=decodeURIComponent(tipo); f.ubicacion.value=decodeURIComponent(ubi);
  f.querySelector('button').textContent='Guardar cambios'; f.scrollIntoView({behavior:'smooth'});
}
document.getElementById('form-aula').addEventListener('submit',async e=>{
  e.preventDefault(); const fd=new FormData(e.target); const id=e.target.dataset.editing;
  if(id){ await fetch(API.update(id),{method:'PUT',headers:hdr,body:fd}); delete e.target.dataset.editing; e.target.querySelector('button').textContent='Guardar'; }
  else { await fetch(API.store,{method:'POST',headers:hdr,body:fd}); }
  e.target.reset(); load();
});
load();
</script>
@endsection
