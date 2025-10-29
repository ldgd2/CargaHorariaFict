@extends('layouts.app')
@section('title','Materias')
@section('content')
<div class="app-container">
  <div class="card mb-4">
    <h1 class="text-xl font-bold mb-3">Materias</h1>
    <form id="form-mat" class="grid grid--2">
      @csrf
      <div class="field"><label class="field__label">Carrera (ID)</label><div class="field__box"><input class="field__input" name="id_carrera" required></div></div>
      <div class="field"><label class="field__label">Código</label><div class="field__box"><input class="field__input" name="codigo" required></div></div>
      <div class="field"><label class="field__label">Nombre</label><div class="field__box"><input class="field__input" name="nombre" required></div></div>
      <div class="field"><label class="field__label">Horas/Semana</label><div class="field__box"><input class="field__input" type="number" min="1" name="horas_semanales" required></div></div>
      <div class="field" style="grid-column:1/-1"><label class="field__label">Programa (opcional)</label><div class="field__box"><textarea name="programa" class="field__textarea" rows="2"></textarea></div></div>
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
const API = {
  index:  "{{ route('admin.materias.index') }}",
  store:  "{{ route('admin.materias.store') }}",
  update: (id)=>"{{ url('admin/materias') }}/"+id,
  toggle: (id)=>"{{ url('admin/materias') }}/"+id+"/toggle",
};
const hdr = {'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'};

async function load(page=1,q=''){
  const u=new URL(API.index,location.origin); u.searchParams.set('page',page); if(q)u.searchParams.set('q',q);
  const res=await fetch(u,{headers:hdr}); const data=await res.json(); render(data);
}
function render(data){
  const tr=(data.data||[]).map(m=>`
    <tr>
      <td class="coor-td"><b>${m.nombre}</b><div class="text-muted">${m.codigo}</div></td>
      <td class="coor-td">Carrera #${m.id_carrera}</td>
      <td class="coor-td">${m.horas_semanales}</td>
      <td class="coor-td">${m.habilitado?'Habilitado':'Inhabilitado'}</td>
      <td class="coor-td">
        <button class="btn btn--outline" onclick="toggle(${m.id_materia})">${m.habilitado?'Desactivar':'Activar'}</button>
      </td>
      <td class="coor-td">
        <button class="btn btn--tonal" onclick="edit(${m.id_materia},${m.id_carrera},'${encodeURIComponent(m.codigo)}','${encodeURIComponent(m.nombre)}',${m.horas_semanales},'${encodeURIComponent(m.programa??'')}')">Editar</button>
      </td>
    </tr>`).join('');
  document.getElementById('table').innerHTML=`
    <div class="coor-table-wrap"><table class="coor-recent" style="width:100%">
      <thead><tr><th class="coor-th">Materia</th><th class="coor-th">Carrera</th><th class="coor-th">Horas</th><th class="coor-th">Estado</th><th class="coor-th">Acción</th><th class="coor-th">Editar</th></tr></thead>
      <tbody>${tr||'<tr><td class="coor-td" colspan="6">Sin registros</td></tr>'}</tbody></table></div>`;
}
async function toggle(id){ await fetch(API.toggle(id),{method:'PATCH',headers:hdr}); load(); }
function edit(id,idc,cod,nom,horas,prog){
  cod=decodeURIComponent(cod); nom=decodeURIComponent(nom); prog=decodeURIComponent(prog);
  const f=document.getElementById('form-mat'); f.dataset.editing=id;
  f.id_carrera.value=idc; f.codigo.value=cod; f.nombre.value=nom; f.horas_semanales.value=horas; f.programa.value=prog;
  f.querySelector('button').textContent='Guardar cambios'; f.scrollIntoView({behavior:'smooth'});
}
document.getElementById('form-mat').addEventListener('submit',async e=>{
  e.preventDefault(); const fd=new FormData(e.target); const id=e.target.dataset.editing;
  if(id){ await fetch(API.update(id),{method:'PUT',headers:hdr,body:fd}); delete e.target.dataset.editing; e.target.querySelector('button').textContent='Guardar'; }
  else { await fetch(API.store,{method:'POST',headers:hdr,body:fd}); }
  e.target.reset(); load();
});
document.getElementById('q').addEventListener('input',e=>load(1,e.target.value));
load();
</script>
@endsection
