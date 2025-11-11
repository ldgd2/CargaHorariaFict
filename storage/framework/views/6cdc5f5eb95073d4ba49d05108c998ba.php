
<?php $__env->startSection('title','Asignar carga'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container" x-data="cargaUI()" x-init="init()">
  <div class="card">
    <h2 class="appbar__title" style="margin:0">Asignar carga</h2>
    <p class="text-muted">Selecciona período/grupo/docente/aula y asigna TODAS las franjas del docente con un clic.</p>
  </div>

  <div class="card">
    <form method="post" action="<?php echo e(route('carga.storeBatch')); ?>"
          @submit.prevent="beforeSubmit($el)" 
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
                <option :value="g.id_grupo"
                        x-text="(g.nombre_grupo || ('Grupo '+g.id_grupo)) + (g.cod_materia ? (' — '+g.cod_materia) : '')">
                </option>
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
                <option :value="d.id_docente"
                        x-text="d.nombre_completo ? d.nombre_completo : ('Doc '+d.id_docente)">
                </option>
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
                <option :value="a.id_aula" x-text="a.codigo"></option>
              </template>
            </select>
          </div>
        </div>
      </div>

      
      <div class="card" x-show="id_periodo && id_docente" style="background:rgba(255,255,255,.03)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
          <p class="text-muted" style="margin:0">Disponibilidades del docente (período seleccionado)</p>
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn--outline" @click="selectAll(true)"  :disabled="!disp.length">Seleccionar todo</button>
            <button type="button" class="btn btn--outline" @click="selectAll(false)" :disabled="!disp.length">Limpiar</button>
          </div>
        </div>

        <div x-show="loadingDisp" class="text-muted" style="margin-top:10px">Cargando disponibilidades…</div>

        <div x-show="!loadingDisp && !disp.length" class="text-muted" style="margin-top:10px">
          No hay disponibilidades registradas para este docente en el período elegido.
        </div>

        <div class="coor-table-wrap" x-show="!loadingDisp && disp.length" style="margin-top:10px">
          <table class="min-w-full coor-recent" style="width:100%">
            <thead>
              <tr>
                <th class="coor-th">✔</th>
                <th class="coor-th">Día</th>
                <th class="coor-th">Inicio</th>
                <th class="coor-th">Fin</th>
                <th class="coor-th">Prioridad</th>
                <th class="coor-th">Obs.</th>
                <th class="coor-th">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <template x-for="d in disp" :key="d.id_disponibilidad">
                <tr :class="d.ocupado ? 'opacity-50 pointer-events-none' : ''">
                  <td class="coor-td">
                    <input type="checkbox" :value="d.id_disponibilidad" x-model="selectedIds" :disabled="d.ocupado">
                  </td>
                  <td class="coor-td" x-text="dias[d.dia_semana] + ` (${d.dia_semana})`"></td>
                  <td class="coor-td" x-text="d.hora_inicio"></td>
                  <td class="coor-td" x-text="d.hora_fin"></td>
                  <td class="coor-td" x-text="d.prioridad ?? '-'"></td>
                  <td class="coor-td" x-text="d.observaciones ?? ''"></td>
                  <td class="coor-td">
                    <span x-show="d.ocupado" class="badge" style="background:#2f3341;color:#f3a8a8;padding:.15rem .35rem;border-radius:6px;">Ocupado</span>
                    <span x-show="!d.ocupado" class="badge" style="background:#203a2a;color:#76e3a3;padding:.15rem .35rem;border-radius:6px;">Libre</span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <small class="text-muted">Se crearán cargas para cada fila marcada (mismo Grupo, Docente y Aula).</small>
      </div>

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
  const fetchJSON = async (url) => {
    try {
      const res = await fetch(url, {
        headers:{ 'X-Requested-With':'XMLHttpRequest','Accept':'application/json' },
        credentials:'same-origin'
      });
      return await res.json();
    } catch(e){ console.error(e); return []; }
  };

  return {
    id_periodo:'', id_grupo:'', id_docente:'', id_aula:'',
    grupos:[], docentes:[], aulas:[], disp:[],
    selectedIds:[], loadingDisp:false,
    dias:{1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie',6:'Sáb',7:'Dom'},

    async init(){
      this.docentes = await fetchJSON('<?php echo e(route('api.docentes')); ?>');
      this.aulas    = await fetchJSON('<?php echo e(route('api.aulas')); ?>');
      this.$watch('id_periodo', () => { this.loadGrupos(); this.loadDisp(); });
      this.$watch('id_docente', () => this.loadDisp());
    },

    async loadGrupos(){
      if(!this.id_periodo){ this.grupos=[]; return; }
      const url = `<?php echo e(route('api.grupos')); ?>?id_periodo=${this.id_periodo}`;
      this.grupos = await fetchJSON(url);
    },

    async loadDisp(){
      this.loadingDisp = true;
      this.disp = []; this.selectedIds = [];
      if(!this.id_periodo || !this.id_docente){ this.loadingDisp=false; return; }

      const url = `<?php echo e(route('api.docente.disponibilidad',['docenteId'=>'__ID__'])); ?>`
                    .replace('__ID__', this.id_docente) + `?id_periodo=${this.id_periodo}`;

      const rows = await fetchJSON(url);
      this.disp = rows;
      this.selectedIds = rows.filter(r => !r.ocupado).map(r => r.id_disponibilidad);
      this.loadingDisp = false;
    },

    selectAll(flag){
      this.selectedIds = flag
        ? this.disp.filter(r => !r.ocupado).map(r => r.id_disponibilidad)
        : [];
    },

    canSubmit(){
      return this.id_periodo && this.id_grupo && this.id_docente && this.id_aula && this.selectedIds.length>0;
    },

    beforeSubmit(formEl){
      if(!this.canSubmit()){
        alert('Selecciona al menos una franja libre.');
        return;
      }
      const host = formEl.querySelector('#hidden-items');
      host.innerHTML = '';

      this.disp
        .filter(d => this.selectedIds.includes(d.id_disponibilidad))
        .forEach((d,i)=>{
          [['dia_semana',d.dia_semana],['hora_inicio',d.hora_inicio],['hora_fin',d.hora_fin]]
            .forEach(([k,v])=>{
              const inp = document.createElement('input');
              inp.type = 'hidden';
              inp.name = `items[${i}][${k}]`;
              inp.value = v;
              host.appendChild(inp);
            });
        });

      formEl.submit();
    }
  }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/carga/create.blade.php ENDPATH**/ ?>