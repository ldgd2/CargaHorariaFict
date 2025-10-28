<nav class="appbar">
  <div class="appbar__inner">
    <div class="appbar__left">
      <span class="appbar__title">Carga Horaria</span>
    </div>
    <div class="appbar__right">
      <?php if(auth()->guard()->check()): ?>
        <a class="btn btn--text" href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a>
        <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
          <?php echo csrf_field(); ?>
          <button type="submit" class="btn btn--text">Salir</button>
        </form>
      <?php endif; ?>
      <?php if(auth()->guard()->guest()): ?>
        <a class="btn btn--text" href="<?php echo e(route('login')); ?>">Ingresar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/partials/nav.blade.php ENDPATH**/ ?>