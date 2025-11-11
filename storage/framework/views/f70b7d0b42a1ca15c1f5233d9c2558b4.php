
<?php $__env->startSection('title','Importación masiva'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">
  <div class="card">
    <h2 class="appbar__title" style="margin:0">Importación CSV/XLSX</h2>
    <p class="text-muted">Soporta varias hojas en un mismo archivo. Se detecta por el <strong>nombre de la hoja</strong>.</p>
  </div>

  <?php if(session('ok')): ?>
    <div class="snackbar snackbar--ok"><?php echo session('ok'); ?></div>
  <?php endif; ?>
  <?php if($errors->any()): ?>
    <div class="snackbar snackbar--error">
      <ul style="margin:0 0 0 18px;">
      <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="post" action="<?php echo e(route('admin.import.run')); ?>" enctype="multipart/form-data" style="display:grid;gap:12px">
      <?php echo csrf_field(); ?>
      <div class="field">
        <label class="field__label">Archivo Excel</label>
        <div class="field__box">
          <input type="file" name="archivo" class="field__input" accept=".xlsx,.xls" required>
        </div>
        <small class="field__hint">Hojas esperadas: USUARIOS, CARRERAS, AULAS, PERIODOS, MATERIAS, GRUPOS, CARGA_HORARIA.</small>
      </div>
      <button class="btn btn--primary" type="submit">Importar</button>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/usuarios/admin/importacion/importacion.blade.php ENDPATH**/ ?>