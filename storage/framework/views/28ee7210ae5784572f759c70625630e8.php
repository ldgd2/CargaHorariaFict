
<?php $__env->startSection('title','Dashboard Admin'); ?>

<?php $__env->startSection('content'); ?>
  <h1 style="font-weight:700; margin-bottom: 16px;">Panel de Administración</h1>

  <div class="grid grid--3">
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Usuarios</h2>
      <p class="text-muted mb-3">Crear nuevas cuentas y asignar roles.</p>
      <a href="<?php echo e(route('usuarios.signup')); ?>" class="btn btn--primary">Registrar usuario</a>
    </div>

    <div class="card">
      <h2 style="margin:0 0 6px 0;">Roles y permisos</h2>
      <p class="text-muted mb-3">Gestión de roles (próximamente).</p>
      <button class="btn btn--outline" disabled>En desarrollo</button>
    </div>

    <div class="card">
      <h2 style="margin:0 0 6px 0;">Períodos</h2>
      <p class="text-muted mb-3">Define períodos académicos.</p>
      <button class="btn btn--outline" disabled>En desarrollo</button>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/usuarios/admin/admin/dashboard.blade.php ENDPATH**/ ?>