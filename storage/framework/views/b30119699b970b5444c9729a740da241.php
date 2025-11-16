<!DOCTYPE html>
<html lang="es" class="dark h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo $__env->yieldContent('title', 'Sistema'); ?></title>
  <style>[x-cloak]{ display:none !important; }</style>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  
  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?>
</head>
<body class="h-full theme-dark">
  
  <?php echo $__env->make('partials.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <main class="app-container">
    
    <?php if(session('ok')): ?>
      <div class="snackbar snackbar--ok"><?php echo e(session('ok')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="snackbar snackbar--error">
        <strong>Revisa los campos:</strong>
        <ul>
          <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($e); ?></li>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php echo $__env->yieldContent('content'); ?>
  </main>
</body>
</html>
<?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/layouts/app.blade.php ENDPATH**/ ?>