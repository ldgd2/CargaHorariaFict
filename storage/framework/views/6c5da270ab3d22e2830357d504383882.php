<?php $__env->startSection('title','Ingresar'); ?>

<?php $__env->startSection('content'); ?>
  <div style="display:grid; place-items:center; min-height: calc(100dvh - 72px);">
    <div class="card" style="width:min(520px, 100%);">
      <h1 style="margin:0 0 6px 0; font-weight:700;">Bienvenido</h1>
      <p class="text-muted" style="margin:0 0 18px 0;">
        Inicia sesión para acceder al panel de administración.
      </p>

      <?php if(session('status')): ?>
        <div class="snackbar snackbar--ok"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('login.post')); ?>" novalidate>
        <?php echo csrf_field(); ?>

        
        <div class="field">
          <label class="field__label" for="email">Correo electrónico</label>
          <div class="field__box">
            <input
              class="field__input"
              type="email"
              id="email"
              name="email"
              value="<?php echo e(old('email')); ?>"
              autocomplete="email"
              required
              autofocus
            >
          </div>
          <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <small class="field__hint" style="color:#ffb4ab;"><?php echo e($message); ?></small>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div class="field">
          <label class="field__label" for="password">Contraseña</label>
          <div class="field__box">
            <input
              class="field__input"
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              required
            >
          </div>
          <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <small class="field__hint" style="color:#ffb4ab;"><?php echo e($message); ?></small>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div class="mt-2" style="display:flex; align-items:center; gap:10px;">
          <input type="checkbox" id="remember" name="remember" value="1" <?php echo e(old('remember') ? 'checked' : ''); ?>>
          <label for="remember" class="text-muted">Mantener sesión iniciada</label>
        </div>

        
        <?php if($errors->has('general')): ?>
          <div class="snackbar snackbar--error mt-3">
            <?php echo e($errors->first('general')); ?>

          </div>
        <?php endif; ?>

        <div class="mt-3" style="display:flex; gap:12px; align-items:center;">
          <button type="submit" class="btn btn--primary" style="width:100%;">Ingresar</button>
          
        </div>
      </form>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/auth/login.blade.php ENDPATH**/ ?>