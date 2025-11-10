
<?php $__env->startSection('title','Mi disponibilidad'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">

  <div class="card" style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
    <div>
      <h2 class="appbar__title" style="margin:0">Mi disponibilidad</h2>
      <p class="text-muted" style="margin:.25rem 0 0 0;">Declara tus franjas por período y día. Sin solapes.</p>
    </div>
    <a href="<?php echo e(route('docente.dashboard')); ?>" class="btn btn--outline">⬅ Volver al panel</a>
  </div>

  
  <div class="card" style="margin-top:16px">
    <form method="get" action="<?php echo e(route('docente.disp.view')); ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div class="field" style="min-width:260px">
        <label class="field__label">Período</label>
        <div class="field__box">
          <select id="id_periodo" name="id_periodo" class="...">
                <option value="">— Selecciona —</option>
                <?php $__currentLoopData = $periodos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($p->id_periodo); ?>"
                    <?php echo e((isset($idPeriodo) && (int)$idPeriodo === (int)$p->id_periodo) ? 'selected' : ''); ?>>
                    <?php echo e($p->nombre); ?> (<?php echo e(\Illuminate\Support\Carbon::parse($p->fecha_inicio)->format('Y-m-d')); ?>

                    – <?php echo e(\Illuminate\Support\Carbon::parse($p->fecha_fin)->format('Y-m-d')); ?>)
                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

        </div>
        <small class="field__hint">El período debe estar EnAsignacion/Reabierto/Activo/Publicado.</small>
      </div>

      <button class="btn btn--primary" type="submit">Abrir</button>
    </form>
  </div>

  
<?php if($idPeriodo): ?>
<div class="card"
     x-data="{
        items: [{dia_semana:'',hora_inicio:'',hora_fin:'',prioridad:'',observaciones:''}],
        add(){ this.items.push({dia_semana:'',hora_inicio:'',hora_fin:'',prioridad:'',observaciones:''}) },
        remove(i){ this.items.splice(i,1) }
     }"
     x-cloak>
  <form method="post" action="<?php echo e(route('docente.disp.storeBatch')); ?>"
        style="display:flex;flex-direction:column;gap:12px">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id_periodo" value="<?php echo e($idPeriodo); ?>">

    <template x-for="(row, i) in items" :key="i">
      <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end">
        <!-- Día -->
        <div class="field">
          <label class="field__label">Día</label>
          <div class="field__box">
            <select class="field__input" :name="`items[${i}][dia_semana]`" x-model="row.dia_semana" required>
              <option value="">—</option>
              <option value="1">Lunes</option><option value="2">Martes</option>
              <option value="3">Miércoles</option><option value="4">Jueves</option>
              <option value="5">Viernes</option><option value="6">Sábado</option>
              <option value="7">Domingo</option>
            </select>
          </div>
        </div>

        <!-- Inicio -->
        <div class="field">
          <label class="field__label">Inicio</label>
          <div class="field__box">
            <input class="field__input" type="time" :name="`items[${i}][hora_inicio]`" x-model="row.hora_inicio" required>
          </div>
        </div>

        <!-- Fin -->
        <div class="field">
          <label class="field__label">Fin</label>
          <div class="field__box">
            <input class="field__input" type="time" :name="`items[${i}][hora_fin]`" x-model="row.hora_fin" required>
          </div>
        </div>

        <!-- Prioridad -->
        <div class="field">
          <label class="field__label">Prioridad (1–9)</label>
          <div class="field__box">
            <input class="field__input" type="number" min="1" max="9"
                   :name="`items[${i}][prioridad]`" x-model="row.prioridad" placeholder="1">
          </div>
        </div>

        <!-- Observaciones -->
        <div class="field" style="grid-column:1/-1">
          <label class="field__label">Observaciones</label>
          <div class="field__box">
            <textarea class="field__input field__textarea" rows="2"
                      :name="`items[${i}][observaciones]`" x-model="row.observaciones"
                      placeholder="Opcional"></textarea>
          </div>
        </div>

        <!-- Botones fila -->
        <div style="grid-column:1/-1;display:flex;gap:8px">
          <button class="btn btn--outline" type="button" @click="add()">+ Agregar horario</button>
          <button class="btn btn--outline" type="button" @click="remove(i)" x-show="items.length>1">— Quitar</button>
        </div>
      </div>
    </template>

    <div style="grid-column:1/-1">
      <button class="btn btn--primary" type="submit">Guardar horarios</button>
      <span class="text-muted" style="margin-left:8px">Puedes añadir varios por día. Sin solapes.</span>
    </div>
  </form>
</div>
<?php endif; ?>


  
  <?php if($idPeriodo): ?>
  <div class="card">
    <h3 style="margin:0 0 8px 0;">Franjas registradas</h3>
    <div class="coor-table-wrap">
      <table class="min-w-full coor-recent" style="width:100%">
        <thead>
          <tr>
            <th class="coor-th">Día</th>
            <th class="coor-th">Inicio</th>
            <th class="coor-th">Fin</th>
            <th class="coor-th">Prioridad</th>
            <th class="coor-th">Obs.</th>
            <th class="coor-th">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          <?php $__empty_1 = true; $__currentLoopData = $disponibilidades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td class="coor-td">
                <?php $dias=[1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo']; ?>
                <?php echo e($dias[$d->dia_semana] ?? $d->dia_semana); ?>

              </td>
              <td class="coor-td"><?php echo e($d->hora_inicio); ?></td>
              <td class="coor-td"><?php echo e($d->hora_fin); ?></td>
              <td class="coor-td"><?php echo e($d->prioridad); ?></td>
              <td class="coor-td"><?php echo e($d->observaciones); ?></td>
              <td class="coor-td">
                <form method="post" action="<?php echo e(route('docente.disp.destroy',$d->id_disponibilidad)); ?>"
                      onsubmit="return confirm('¿Eliminar franja?')">
                  <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                  <button class="btn btn--outline" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="coor-td text-center text-muted">Sin franjas registradas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/docente/disponibilidad.blade.php ENDPATH**/ ?>