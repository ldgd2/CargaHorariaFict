

<?php $__env->startSection('title','Registrar usuario'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">

  
  <?php if(session('ok') || session('warning') || $errors->any()): ?>
    <?php if(session('ok')): ?>
      <?php
        $mensajeOk = strip_tags(session('ok'));
        $lineasOk = explode("\n", str_replace(["<br>", "<br/>", "<br />"], "\n", $mensajeOk));
      ?>
      <div class="snackbar snackbar--ok mb-3">
        <b>Operación exitosa</b>
        <ul class="mt-2">
          <?php $__currentLoopData = $lineasOk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(trim($l)!==''): ?>
              <li class="mt-1"><?php echo e(trim($l)); ?></li>
            <?php endif; ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if(session('warning')): ?>
      <div class="snackbar snackbar--error mb-3">
        <?php echo session('warning'); ?>

      </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
      <div class="snackbar snackbar--error mb-3">
        <b>Revisa los campos</b>
        <ul class="mt-2 list-disc list-inside">
          <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($e); ?></li>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  
  <div class="card mb-4">
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-bold">Registrar usuario</h1>
    </div>

    <form method="POST" action="<?php echo e(route('admin.usuarios.signup.post')); ?>" novalidate>
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

      <div class="mt-3 flex items-center gap-2">
        <input type="checkbox" name="activo" value="1" <?php echo e(old('activo', true) ? 'checked' : ''); ?>>
        <label class="text-muted">Habilitado</label>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <button type="submit" class="btn btn--primary w-full sm:w-auto">Guardar</button>
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn--text w-full sm:w-auto text-center">Cancelar</a>
      </div>
    </form>
  </div>

  
  <div class="card">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-lg font-semibold">Carga masiva desde Excel</h2>
    </div>

    <form method="POST" action="<?php echo e(route('admin.usuarios.import')); ?>" enctype="multipart/form-data" novalidate>
      <?php echo csrf_field(); ?>

      
      <div class="field">
        <label class="field__label">Archivo Excel (.xlsx o .xls)</label>
        <div class="field__box" style="padding: 18px;">
          <input id="archivo" type="file" name="archivo" accept=".xlsx,.xls" required class="w-full text-sm">
          <small class="field__hint">Arrastra y suelta o toca para seleccionar. Tamaño sugerido &lt; 10MB.</small>
        </div>
      </div>

      
      <div class="grid grid--2 mt-3">
        <div class="field">
          <label class="field__label">Formato esperado (hojas)</label>
          <div class="field__box">
            <div class="text-sm">
              <p class="mb-2"><b>Hojas válidas como roles:</b> <code>Docente</code>, <code>Estudiante</code>, <code>Coordinador</code>, <code>Usuario</code>.</p>
              <p class="mb-2">Si el nombre de la hoja no coincide con un rol, se omite y se reporta.</p>
              <p class="mb-2"><b>Encabezados base (flexibles):</b> Nombre, Apellido, Email, Teléfono, Dirección, Contraseña.</p>
              <p class="mb-1"><b>Estudiante</b> (PK del negocio): <code>Código Universitario</code> (obligatorio), Carrera, Semestre.</p>
            </div>
          </div>
        </div>

        <div class="field">
          <label class="field__label">Comportamiento de importación</label>
          <div class="field__box">
            <ul class="text-sm list-disc list-inside">
              <li>Si una hoja tiene error de estructura o campos vacíos, <b>se detiene esa hoja</b> y el proceso sigue con las demás.</li>
              <li>Si el rol de la hoja no existe, <b>se omite</b> y se muestra en el resumen.</li>
              <li>Usuarios se crean/actualizan por email y rol; Estudiantes por <code>código_universitario</code>.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <button type="submit" class="btn btn--tonal w-full sm:w-auto">Cargar Lista de Usuarios</button>
        
        
      </div>
    </form>

    
    <?php if(session('ok')): ?>
      <?php
        $mensaje = strip_tags(session('ok'));
        $lineas = explode("\n", str_replace(["<br>", "<br/>", "<br />"], "\n", $mensaje));
      ?>
      <div class="card mt-4">
        <h3 class="text-lg font-bold mb-3">📋 Resumen de importación</h3>
        <ul class="text-sm leading-relaxed">
          <?php $__currentLoopData = $lineas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linea): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $trim = trim($linea);
              $color = str_starts_with($trim, '⚠️') ? 'text-yellow-400' :
                      (str_starts_with($trim, '🚫') ? 'text-red-400' :
                      (str_starts_with($trim, '✔️') ? 'text-green-400' :
                      (str_starts_with($trim, '✅') ? 'text-emerald-400' : 'text-on-surface')));
            ?>
            <?php if($trim !== ''): ?>
              <li class="<?php echo e($color); ?>"><?php echo e($trim); ?></li>
            <?php endif; ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/usuarios/admin/signup.blade.php ENDPATH**/ ?>