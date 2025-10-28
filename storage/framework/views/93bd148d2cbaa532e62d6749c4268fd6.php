
<?php $__env->startSection('title','Registrar usuario'); ?>

<?php $__env->startSection('content'); ?>
  <h1 style="font-weight:700; margin-bottom: 16px;">Registrar usuario</h1>

  <form method="POST" action="<?php echo e(route('usuarios.signup.post')); ?>" novalidate>
    <?php echo csrf_field(); ?>

    <div class="grid grid--2">
      <div class="field">
        <label class="field__label">Nombre</label>
        <div class="field__box">
          <input class="field__input" type="text" name="nombre" value="<?php echo e(old('nombre')); ?>" required>
        </div>
      </div>

      <div class="field">
        <label class="field__label">Apellido</label>
        <div class="field__box">
          <input class="field__input" type="text" name="apellido" value="<?php echo e(old('apellido')); ?>" required>
        </div>
      </div>

      <div class="field">
        <label class="field__label">Email</label>
        <div class="field__box">
          <input class="field__input" type="email" name="email" value="<?php echo e(old('email')); ?>" required>
        </div>
      </div>

      <div class="field">
        <label class="field__label">Teléfono (opcional)</label>
        <div class="field__box">
          <input class="field__input" type="text" name="telefono" value="<?php echo e(old('telefono')); ?>">
        </div>
      </div>

      <div class="field">
        <label class="field__label">Dirección (opcional)</label>
        <div class="field__box">
          <input class="field__input" type="text" name="direccion" value="<?php echo e(old('direccion')); ?>">
        </div>
      </div>

      <div class="field">
        <label class="field__label">Rol</label>
        <div class="field__box">
          <select class="field__select" name="id_rol" required>
            <option value="">— Selecciona —</option>
            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($r->id_rol); ?>"><?php echo e($r->nombre_rol); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label class="field__label">Contraseña inicial (opcional)</label>
        <div class="field__box">
          <input class="field__input" type="password" name="password" autocomplete="new-password">
        </div>
        <small class="field__hint">Si la dejas vacía, se generará una aleatoria segura.</small>
      </div>

      <div class="field">
        <label class="field__label">Confirmar contraseña</label>
        <div class="field__box">
          <input class="field__input" type="password" name="password_confirmation" autocomplete="new-password">
        </div>
      </div>
    </div>

    <div class="mt-3">
      <label class="text-muted" style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="activo" value="1" <?php echo e(old('activo', true) ? 'checked' : ''); ?>>
        Habilitado
      </label>
    </div>
    
    <div class="mb-4">
  <label class="block text-sm font-semibold mb-1" for="entidad">Entidad</label>
  <input
    id="entidad"
    name="entidad"
    type="text"
    value="<?php echo e(old('entidad')); ?>"
    required
    class="w-full px-4 py-3 rounded-xl bg-[#141920] border border-zinc-700 focus:border-emerald-400 outline-none text-sm"
    placeholder="Ej: UAGRM, Dirección Académica, etc."
  >
  <?php $__errorArgs = ['entidad'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <p class="text-red-400 text-xs mt-1"><?php echo e($message); ?></p>
  <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>


    <div class="mt-3" style="display:flex; gap:12px;">
      <button type="submit" class="btn btn--primary">Guardar</button>
      <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn--text">Cancelar</a>
    </div>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/usuarios/admin/signup.blade.php ENDPATH**/ ?>