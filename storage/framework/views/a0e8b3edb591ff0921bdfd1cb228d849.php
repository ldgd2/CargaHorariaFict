<?php $__env->startSection('content'); ?>
<div class="app-container">
  <div class="card">
    <h1 class="text-xl font-bold mb-1">Código QR de asistencia</h1>
    <p class="text-muted mb-4">
      Pide a los docentes / estudiantes que escaneen este código con su celular
      antes de iniciar la clase. El sistema registrará la asistencia de forma automática.
    </p>

    <div class="flex flex-col md:flex-row gap-6 items-center">
      <div class="p-4 bg-black rounded-xl">
        
        <?php echo $qrSvg; ?>

      </div>

      <div class="space-y-2 text-sm">
        <p><strong>Carga:</strong> #<?php echo e($carga->id_carga); ?></p>
        <p><strong>Aula:</strong> <?php echo e($carga->id_aula); ?></p>
        <p><strong>Horario:</strong> <?php echo e($carga->hora_inicio); ?> – <?php echo e($carga->hora_fin); ?></p>
        <p><strong>Fecha de sesión:</strong> <?php echo e($fechaSesion); ?></p>
        <p class="text-xs text-muted">
          El código es válido solo para la fecha indicada y para esta carga horaria.
        </p>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/asistencia/qr.blade.php ENDPATH**/ ?>