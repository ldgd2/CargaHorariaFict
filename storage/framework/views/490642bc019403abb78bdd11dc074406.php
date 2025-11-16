<?php $__env->startSection('title','Panel del Docente'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">

  
  <?php if(session('ok')): ?>    <div class="snackbar snackbar--ok"><?php echo e(session('ok')); ?></div><?php endif; ?>
  <?php if($errors->any()): ?>
    <div class="snackbar snackbar--error">
      <strong>Revisa los campos:</strong>
      <ul class="mt-2" style="margin-left:18px;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li><?php echo e($e); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  
  <div class="card" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
    <div>
      <h2 class="appbar__title" style="margin:0">¡Hola, <?php echo e(auth()->user()->nombre); ?>!</h2>
      <p class="text-muted" style="margin:.25rem 0 0 0;">
        Panel de <strong>Docente</strong> — consulta tus grupos, sesiones y registra tu disponibilidad.
      </p>
    </div>

    
    <div>
      <?php
        $pid = $pid ?? null;
        $dispUrl = $pid
          ? route('docente.disp.index', ['id_periodo' => $pid])
          : route('docente.disp.index'); // mostrará validación en la vista si falta periodo
      ?>
      <a href="<?php echo e($dispUrl); ?>" class="btn btn--primary">🗓️ Registrar disponibilidad</a>
    </div>
  </div>

  
  <div class="card" style="margin-top:16px;">
    <h3 style="margin:0 0 6px 0;">Período actual</h3>
    <?php if($periodoActivo): ?>
      <?php
        $fi = optional(\Illuminate\Support\Carbon::parse($periodoActivo->fecha_inicio ?? null))->toDateString();
        $ff = optional(\Illuminate\Support\Carbon::parse($periodoActivo->fecha_fin ?? null))->toDateString();
      ?>
      <p class="mb-2"><strong><?php echo e($periodoActivo->nombre ?? ('ID '.$periodoActivo->id_periodo)); ?></strong></p>
      <p class="text-muted" style="margin:.25rem 0 0 0;">
        <time class="js-date" data-iso="<?php echo e($fi); ?>"><?php echo e($fi ?? '—'); ?></time> –
        <time class="js-date" data-iso="<?php echo e($ff); ?>"><?php echo e($ff ?? '—'); ?></time>
        <span class="badge badge--tonal" style="margin-left:8px"><?php echo e($periodoActivo->estado); ?></span>
      </p>
    <?php else: ?>
      <p class="text-muted" style="margin:0">No hay período activo. Solicita al coordinador la publicación o reapertura.</p>
    <?php endif; ?>
  </div>

  
  <div class="card" style="margin-top:16px;padding:0">
    <div class="grid grid--4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;padding:16px">
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">Grupos asignados</h4><div style="font-size:28px;font-weight:800"><?php echo e($kpis['grupos']); ?></div></div>
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">Horas/semana</h4><div style="font-size:28px;font-weight:800"><?php echo e(number_format($kpis['horas_semana'],1)); ?></div></div>
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">Franjas de disponibilidad</h4><div style="font-size:28px;font-weight:800"><?php echo e($kpis['disp_franjas']); ?></div></div>
      <div class="card" style="margin:0"><h4 class="mb-2" style="margin:0 0 4px 0">% Asistencia OK</h4><div style="font-size:28px;font-weight:800"><?php echo e($kpis['asistencia_ok']); ?>%</div></div>
    </div>
  </div>

  
  <div class="grid grid--2" style="margin-top:16px">
    <div class="card">
      <p class="text-muted mb-2" style="margin:0 0 6px 0;">Acciones rápidas</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="<?php echo e($dispUrl); ?>" class="btn btn--primary">🗓️ Registrar disponibilidad</a>
        <a href="<?php echo e(route('grupos.index')); ?>" class="btn btn--tonal">👥 Ver mis grupos</a>
        <a href="<?php echo e(route('asistencia-sesion.index')); ?>" class="btn btn--outline">✅ Pasar asistencia</a>
      </div>
    </div>

    
    <div class="card" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
      <div>
        <h3 style="margin:0 0 6px 0;">Mi disponibilidad</h3>
        <p class="text-muted" style="margin:0">Declara tus franjas por día para que el sistema asigne carga sin conflictos.</p>
      </div>
      <div>
        <a href="<?php echo e($dispUrl); ?>" class="btn btn--primary">Abrir “Mi disponibilidad”</a>
      </div>
    </div>
  </div>

  
  <div class="card" style="margin-top:16px">
    <h3 style="margin:0 0 8px 0;">Próximas sesiones</h3>
    <div class="coor-table-wrap">
      <table class="min-w-full coor-recent" style="width:100%">
        <thead>
          <tr>
            <th class="coor-th">Fecha</th>
            <th class="coor-th">Hora</th>
            <th class="coor-th">Materia/Grupo</th>
            <th class="coor-th">Aula</th>
            <th class="coor-th">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          <?php $__empty_1 = true; $__currentLoopData = $proximas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $f = optional(\Illuminate\Support\Carbon::parse($s->fecha))->toDateString();
            ?>
            <tr>
              <td class="coor-td"><time class="js-date" data-iso="<?php echo e($f); ?>"><?php echo e($f); ?></time></td>
              <td class="coor-td"><?php echo e($s->hora_inicio); ?>–<?php echo e($s->hora_fin); ?></td>
              <td class="coor-td"><?php echo e($s->nombre_materia ?? '—'); ?> <?php echo e($s->nombre_grupo ?? ''); ?></td>
              <td class="coor-td"><?php echo e($s->aula ?? '—'); ?></td>
              <td class="coor-td"><a href="<?php echo e(route('asistencia-sesion.index')); ?>" class="btn btn--outline">Abrir</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="coor-td text-center text-muted">No hay sesiones próximas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>


<script>
(function(){
  const months=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  function pad(n){return n<10?'0'+n:''+n;}
  function fmtDate(iso){ if(!iso) return '—'; const d=new Date(iso+'T00:00:00'); if(isNaN(d)) return iso;
    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear();
  }
  document.querySelectorAll('.js-date').forEach(el=>{
    const iso=el.getAttribute('data-iso'); el.textContent=fmtDate(iso);
  });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/docente/dashboard.blade.php ENDPATH**/ ?>