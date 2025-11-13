

<?php $__env->startSection('content'); ?>
<div class="app-container">

  
  <div class="mb-4">
    <h1 class="text-2xl font-bold">Uso de aulas</h1>
    <p class="text-muted mt-2">
      Reporte de ocupación de aulas por período, rango de fechas, origen de ocupación y motivo de bloqueo.
    </p>
  </div>

  
  <?php if(!empty($error)): ?>
    <div class="snackbar snackbar--error">
      Ocurrió un error al calcular el reporte: <?php echo e($error); ?>

    </div>
  <?php endif; ?>

  
  <div class="card mb-4">
    <form id="form-uso-aulas" method="GET">
      <div class="toolbar mb-3">
        <div class="text-muted">
          Filtros del reporte
        </div>
        <div class="savebar">
          <button type="submit" class="btn btn--primary">
            Buscar
          </button>
          <button type="button" onclick="exportar('pdf')" class="btn btn--tonal">
            PDF
          </button>
          <button type="button" onclick="exportar('xlsx')" class="btn btn--outline">
            Excel
          </button>
          <a href="<?php echo e(route('reportes.uso_aulas.view')); ?>" class="btn btn--text">
            Limpiar
          </a>
        </div>
      </div>

      <div class="filters">
        
        <div class="field">
          <label class="field__label">Período *</label>
          <div class="field__box">
            <select name="id_periodo" required class="field__select">
              <option value="">Seleccione un período</option>
              <?php $__currentLoopData = $periodos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id_periodo); ?>"
                  <?php echo e((string)request('id_periodo') === (string)$p->id_periodo ? 'selected' : ''); ?>>
                  <?php echo e($p->nombre); ?> (<?php echo e($p->fecha_inicio); ?> – <?php echo e($p->fecha_fin); ?>)
                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Desde *</label>
          <div class="field__box">
            <input type="date"
                   name="desde"
                   value="<?php echo e(request('desde')); ?>"
                   required
                   class="field__input">
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Hasta *</label>
          <div class="field__box">
            <input type="date"
                   name="hasta"
                   value="<?php echo e(request('hasta')); ?>"
                   required
                   class="field__input">
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Tipo de aula</label>
          <div class="field__box">
            <select name="tipo_aula" class="field__select">
              <option value="">Todos</option>
              <?php $__currentLoopData = $tipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($t); ?>" <?php echo e(request('tipo_aula')===$t ? 'selected' : ''); ?>>
                  <?php echo e($t); ?>

                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Aula</label>
          <div class="field__box">
            <select name="id_aula" class="field__select">
              <option value="">Todas</option>
              <?php $__currentLoopData = $aulasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($a->id_aula); ?>" <?php echo e(request('id_aula')==$a->id_aula ? 'selected' : ''); ?>>
                  <?php echo e($a->id_aula); ?> — <?php echo e($a->nombre_aula); ?>

                  (cap: <?php echo e($a->capacidad); ?>) [<?php echo e($a->tipo_aula); ?>]
                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Capacidad mínima</label>
          <div class="field__box">
            <select name="cap_min" class="field__select">
              <option value="">Cualquiera</option>
              <?php $__currentLoopData = $capacidades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cap); ?>" <?php echo e((string)request('cap_min') === (string)$cap ? 'selected' : ''); ?>>
                  ≥ <?php echo e($cap); ?> estudiantes
                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Origen de ocupación</label>
          <div class="field__box">
            <select name="origen" class="field__select">
              <option value="todos" <?php echo e(request('origen','todos')=='todos' ? 'selected' : ''); ?>>
                Clases + bloqueos
              </option>
              <option value="clases" <?php echo e(request('origen')=='clases' ? 'selected' : ''); ?>>
                Solo clases (carga horaria)
              </option>
              <option value="bloqueos" <?php echo e(request('origen')=='bloqueos' ? 'selected' : ''); ?>>
                Solo bloqueos / mantenimiento
              </option>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Motivo de bloqueo</label>
          <div class="field__box">
            <select name="motivo" class="field__select">
              <option value="">Todos</option>
              <?php $__currentLoopData = $motivos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($m); ?>" <?php echo e(request('motivo')===$m ? 'selected' : ''); ?>>
                  <?php echo e($m); ?>

                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        
        <div class="field">
          <label class="field__label">Estado</label>
          <div class="field__box">
            <select name="estado" class="field__select">
              <option value="todas" <?php echo e(request('estado','todas')=='todas' ? 'selected' : ''); ?>>Todas</option>
              <option value="ocupadas" <?php echo e(request('estado')=='ocupadas' ? 'selected' : ''); ?>>Solo ocupadas</option>
              <option value="libres" <?php echo e(request('estado')=='libres' ? 'selected' : ''); ?>>Solo libres</option>
            </select>
          </div>
        </div>

      </div> 
    </form>
  </div>

  
  <?php if(isset($reporte) && $reporte->count()): ?>

    
    <div class="grid grid--2 mb-4">
      <?php $__currentLoopData = $reporte; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $pct = (float) $r['ocupacion'];
          $claseBarra = $pct < 60 ? 'bg-emerald-400'
                       : ($pct < 100 ? 'bg-amber-400' : 'bg-red-500');
        ?>
        <div class="card">
          <div class="flex justify-between items-start gap-3">
            <div>
              <div class="text-sm text-muted mb-1">
                Aula <?php echo e($r['codigo']); ?> · <?php echo e($r['tipo'] ?? 'Sin tipo'); ?>

              </div>
              <div class="font-semibold text-base">
                <?php echo e($r['nombre']); ?>

              </div>
              <div class="text-sm text-muted mt-1">
                Capacidad: <span class="font-semibold"><?php echo e($r['capacidad']); ?></span> estudiantes
              </div>
              <div class="text-xs text-muted mt-2">
                Asignadas: <strong><?php echo e(number_format($r['horas_asignadas'], 2)); ?> h</strong> ·
                Bloqueadas: <strong><?php echo e(number_format($r['horas_bloqueadas'], 2)); ?> h</strong> ·
                Libres: <strong><?php echo e(number_format($r['horas_libres'], 2)); ?> h</strong>
              </div>
            </div>
            <div class="text-right" style="min-width: 120px;">
              <div class="text-xs text-muted">Ocupación</div>
              <div class="text-lg font-bold">
                <?php echo e(number_format($pct, 1)); ?>%
              </div>
              <div class="w-full h-2.5 rounded-full bg-slate-800 mt-1 overflow-hidden">
                <div class="h-full <?php echo e($claseBarra); ?>"
                     style="width: <?php echo e(max(0, min($pct, 100))); ?>%;"></div>
              </div>
              <div class="mt-1 text-xs <?php echo e($r['estado']=='Ocupada' ? 'text-rose-400' : 'text-emerald-400'); ?>">
                <?php echo e($r['estado']); ?>

              </div>
            </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="card">
      <div class="mb-2 font-semibold">Detalle tabular</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-slate-900/60">
              <th class="border border-slate-700 px-2 py-1 text-left">Código</th>
              <th class="border border-slate-700 px-2 py-1 text-left">Nombre</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Tipo</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Capacidad</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Horas asignadas</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Horas bloqueadas</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Horas libres</th>
              <th class="border border-slate-700 px-2 py-1 text-center">% ocupación</th>
              <th class="border border-slate-700 px-2 py-1 text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
          <?php $__currentLoopData = $reporte; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="hover:bg-slate-900/40">
              <td class="border border-slate-800 px-2 py-1"><?php echo e($r['codigo']); ?></td>
              <td class="border border-slate-800 px-2 py-1"><?php echo e($r['nombre']); ?></td>
              <td class="border border-slate-800 px-2 py-1 text-center"><?php echo e($r['tipo'] ?? '—'); ?></td>
              <td class="border border-slate-800 px-2 py-1 text-center"><?php echo e($r['capacidad']); ?></td>
              <td class="border border-slate-800 px-2 py-1 text-center"><?php echo e(number_format($r['horas_asignadas'], 2)); ?></td>
              <td class="border border-slate-800 px-2 py-1 text-center"><?php echo e(number_format($r['horas_bloqueadas'], 2)); ?></td>
              <td class="border border-slate-800 px-2 py-1 text-center"><?php echo e(number_format($r['horas_libres'], 2)); ?></td>
              <td class="border border-slate-800 px-2 py-1 text-center"><?php echo e(number_format($r['ocupacion'], 2)); ?>%</td>
              <td class="border border-slate-800 px-2 py-1 text-center">
                <?php echo e($r['estado']); ?>

              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif(request()->has('id_periodo')): ?>
    <div class="card">
      <p class="text-muted">No se encontraron aulas con ese criterio de búsqueda.</p>
    </div>
  <?php else: ?>
    <div class="card">
      <p class="text-muted">Completa los filtros y presiona <strong>Buscar</strong>.</p>
    </div>
  <?php endif; ?>
</div>

<script>
function exportar(formato) {
  const form = document.getElementById('form-uso-aulas');
  const periodo = form.querySelector('[name="id_periodo"]')?.value;
  if (!periodo) {
    alert('Debe ingresar un período');
    return;
  }
  const data   = new FormData(form);
  const params = new URLSearchParams(data);
  params.set('formato', formato);
  window.open('<?php echo e(route('reportes.uso_aulas')); ?>' + '?' + params.toString(), '_blank');
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/reportes/uso_aulas.blade.php ENDPATH**/ ?>