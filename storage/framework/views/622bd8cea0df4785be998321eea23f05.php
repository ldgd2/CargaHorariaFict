<?php $__env->startSection('title','Importación masiva'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">
  <div class="card">
    <h2 class="appbar__title" style="margin:0">Importación CSV/XLSX</h2>
    <p class="text-muted">Soporta varias hojas en un mismo archivo. Se detecta por el <strong>nombre de la hoja</strong>.</p>
  </div>

  

  <div class="card">
    <form method="post" action="<?php echo e(route('admin.import.run')); ?>" enctype="multipart/form-data" style="display:grid;gap:12px">
      <?php echo csrf_field(); ?>
      <div class="field">
        <div class="alert alert--info">Este formulario usa el importador multi-hojas (ImportacionController). Si quieres importar usuarios individuales usa la pantalla de Usuarios → Importar.</div>
      </div>
      <div class="field">
        <label class="field__label">Archivo Excel</label>
        <div class="field__box">
          <input type="file" name="archivo" class="field__input" accept=".xlsx,.xls" required>
        </div>
        <small class="field__hint">Hojas esperadas: USUARIOS, CARRERAS, AULAS, PERIODOS, MATERIAS, GRUPOS, CARGA_HORARIA.</small>
      </div>
      <button class="btn btn--primary" type="submit">Importar (multi-hojas)</button>
    </form>
  </div>
  
  <?php if(session('ok')): ?>
    <div class="card card--success" style="margin-top:16px;border-left:4px solid #2ecc71;">
      <h3 style="margin-top:0">Importación completada</h3>
      <p class="text-muted">Los datos se importaron correctamente. Resumen por hoja:</p>
      <div class="import-summary"><?php echo session('ok'); ?></div>
    </div>
  <?php endif; ?>
  <?php if($errors->any()): ?>
    <div class="card card--error" style="margin-top:12px;border-left:4px solid #e74c3c;">
      <h3 style="margin-top:0">Errores / Filas omitidas</h3>
      <div class="import-errors">
        <ul style="margin:0 0 0 18px;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/usuarios/admin/importacion/importacion.blade.php ENDPATH**/ ?>