
<?php $__env->startSection('title','Asignar carga'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container" x-data="cargaUI()">
  <div class="card">
    <h2 class="appbar__title" style="margin:0">Asignar carga</h2>
    <p class="text-muted">Selecciona período/grupo/docente/aula y asigna TODAS las franjas del docente con un clic.</p>
  </div>

  <div class="card">
    <form method="post" action="<?php echo e(route('carga.storeBatch')); ?>"
          @submit="return beforeSubmit($el)"
          style="display:grid;gap:12px">
      <?php echo csrf_field(); ?>

      <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        
        <div class="field">
          <label class="field__label">Período</label>
          <div class="field__box">
            <select class="field__input" name="id_periodo" x-model.number="id_periodo"
                    @change="loadGrupos(); loadDisp()" required>
              <option value="">— Selecciona —</option>
              <?php $__currentLoopData = $periodos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id_periodo); ?>">
                  <?php echo e($p->nombre); ?>

                  (<?php echo e(\Illuminate\Support\Carbon::parse($p->fecha_inicio)->toDateString()); ?> — <?php echo e(\Illuminate\Support\Carbon::parse($p->fecha_fin)->toDateString()); ?>)
                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Grupo</label>
          <div class="field__box">
            <select class="field__input" name="id_grupo" x-model.number="id_grupo" required>
              <option value="">—</option>
              <template x-for="g in grupos" :key="g.id_grupo">
                <option :value="g.id_grupo" x-text="g.nombre_grupo ?? ('Grupo '+g.id_grupo)"></option>
              </template>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Docente</label>
          <div class="field__box">
            <select class="field__input" name="id_docente" x-model.number="id_docente"
                    @change="loadDisp()" required>
              <option value="">—</option>
              <template x-for="d in docentes" :key="d.id_docente">
                <option :value="d.id_docente" x-text="d.nombre ?? ('Doc '+d.id_docente)"></option>
              </template>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Aula</label>
          <div class="field__box">
            <select class="field__input" name="id_aula" x-model.number="id_aula" required>
              <option value="">—</option>
              <template x-for="a in aulas" :key="a.id_aula">
                <option :value="a.id_aula" x-text="a.codigo ?? ('Aula '+a.id_aula)"></option>
              </template>
            </select>
          </div>
        </div>
      </div>

      
      <template x-if="disp.length">
        <div class="card" style="background:rgba(255,255,255,.03)">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <p class="text-muted" style="margin:0">Disponibilidades del docente (período seleccionado)</p>
            <div style="display:flex;gap:8px">
              <button type="button" class="btn btn--outline" @click="selectAll(true)">Seleccionar todo</button>
              <button type="button" class="btn btn--outline" @click="selectAll(false)">Limpiar</button>
            </div>
          </div>

          <div class="coor-table-wrap" style="margin-top:10px">
            <table class="min-w-full coor-recent" style="width:100%">
              <thead>
                <tr>
                  <th class="coor-th">✔</th>
                  <th class="coor-th">Día</th>
                  <th class="coor-th">Inicio</th>
                  <th class="coor-th">Fin</th>
                  <th class="coor-th">Prioridad</th>
                  <th class="coor-th">Obs.</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <template x-for="d in disp" :key="d.id_disponibilidad">
                  <tr>
                    <td class="coor-td">
                      <input type="checkbox" :value="d.id_disponibilidad"
                             x-model="selectedIds">
                    </td>
                    <td class="coor-td" x-text="dias[d.dia_semana] + ` (${d.dia_semana})`"></td>
                    <td class="coor-td" x-text="d.hora_inicio"></td>
                    <td class="coor-td" x-text="d.hora_fin"></td>
                    <td class="coor-td" x-text="d.prioridad ?? '-'"></td>
                    <td class="coor-td" x-text="d.observaciones ?? ''"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <small class="text-muted">Se crearán cargas para cada fila marcada (mismo Grupo, Docente y Aula).</small>
        </div>
      </template>

      <div class="field">
        <label class="field__label">Observaciones</label>
        <div class="field__box">
          <input class="field__input" type="text" name="observaciones" placeholder="Opcional">
        </div>
      </div>

      
      <div id="hidden-items"></div>

      <div>
        <button class="btn btn--primary" type="submit" :disabled="!canSubmit()">Guardar cargas</button>
      </div>
    </form>
  </div>
</div>

<script>
function cargaUI(){
  return {
    id_periodo:'', id_grupo:'', id_docente:'', id_aula:'',
    grupos:[], docentes:[], aulas:[], disp:[],
    selectedIds:[],
    dias:{1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie',6:'Sáb',7:'Dom'},

    async init(){
      this.docentes = await (await fetch('<?php echo e(route('api.docentes')); ?>')).json();
      this.aulas    = await (await fetch('<?php echo e(route('api.aulas')); ?>')).json();
    },

    async loadGrupos(){
      if(!this.id_periodo){ this.grupos=[]; return; }
      const url = `<?php echo e(route('api.grupos')); ?>?id_periodo=${this.id_periodo}`;
      this.grupos = await (await fetch(url)).json();
    },

    async loadDisp(){
      this.disp=[]; this.selectedIds=[];
      if(!this.id_docente || !this.id_periodo) return;
      const url = `<?php echo e(route('api.docente.disponibilidad',['id'=>'__ID__'])); ?>`
                    .replace('__ID__', this.id_docente)
                  + `?id_periodo=${this.id_periodo}`;
      this.disp = await (await fetch(url)).json();
      // Preseleccionar todas (como pediste)
      this.selectedIds = this.disp.map(d => d.id_disponibilidad);
    },

    selectAll(flag){
      this.selectedIds = flag ? this.disp.map(d => d.id_disponibilidad) : [];
    },

    canSubmit(){
      return this.id_periodo && this.id_grupo && this.id_docente && this.id_aula && this.selectedIds.length>0;
    },

    beforeSubmit(formEl){
      if(!this.canSubmit()){
        alert('Completa Período, Grupo, Docente, Aula y marca al menos una franja.');
        return false;
      }
      // Construir inputs hidden items[] con los datos de cada disponibilidad seleccionada
      const host = formEl.querySelector('#hidden-items');
      host.innerHTML='';
      const marcadas = this.disp.filter(d => this.selectedIds.includes(d.id_disponibilidad));
      marcadas.forEach((d,i)=>{
        const add = (name,val)=>{
          const inp = document.createElement('input');
          inp.type='hidden'; inp.name=`items[${i}][${name}]`; inp.value=val;
          host.appendChild(inp);
        };
        add('dia_semana',  d.dia_semana);
        add('hora_inicio', d.hora_inicio);
        add('hora_fin',    d.hora_fin);
      });
      return true;
    }
  }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/carga/create.blade.php ENDPATH**/ ?>