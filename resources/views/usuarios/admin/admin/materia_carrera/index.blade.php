@extends('layouts.app')
@section('title','Vínculo Materia ↔ Carrera')
@section('content')
<div class="app-container">
  <div class="card mb-4">
    <h1 class="text-xl font-bold mb-3">Vincular Materia con Carrera</h1>
    <form id="form-mc" class="grid grid--2">
      @csrf
      <div class="field"><label class="field__label">ID Materia</label><div class="field__box"><input class="field__input" name="id_materia" required></div></div>
      <div class="field"><label class="field__label">ID Carrera</label><div class="field__box"><input class="field__input" name="id_carrera" required></div></div>
      <div><button class="btn btn--primary">Vincular</button></div>
    </form>
  </div>

  <div class="card">
    <h2 class="text-lg font-semibold mb-3">Relaciones</h2>
    <div id="table"></div>
  </div>
</div>

<script>
const API = {
  index: "{{ route('admin.materia_carrera.index') }}",
  store: "{{ route('admin.materia_carrera.store') }}",
  show:  (m,c)=>"{{ url('admin/materia-carrera') }}/"+m+"/"+c,
  del:   (m,c)=>"{{ url('admin/materia-carrera') }}/"+m+"/"+c,
};
const hdr={'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'};

async function load(){
  const r=await fetch(API.index,{headers:hdr}); const d=await r.json();
  const rows=(d.data||d||[]).map(x=>`
    <tr>
      <td class="coor-td">Materia #${x.id_materia}</td>
      <td class="coor-td">Carrera #${x.id_carrera}</td>
      <td class="coor-td"><button class="btn btn--outline" onclick="del(${x.id_materia},${x.id_carrera})">Eliminar</button></td>
    </tr>`).join('');
  document.getElementById('table').innerHTML=`
    <div class="coor-table-wrap"><table class="coor-recent" style="width:100%">
    <thead><tr><th class="coor-th">Materia</th><th class="coor-th">Carrera</th><th class="coor-th">Acción</th></tr></thead>
    <tbody>${rows||'<tr><td class="coor-td" colspan="3">Sin vínculos</td></tr>'}</tbody></table></div>`;
}
document.getElementById('form-mc').addEventListener('submit',async e=>{
  e.preventDefault(); const fd=new FormData(e.target);
  await fetch(API.store,{method:'POST',headers:hdr,body:fd}); e.target.reset(); load();
});
async function del(m,c){ await fetch(API.del(m,c),{method:'DELETE',headers:hdr}); load(); }
load();
</script>
@endsection
