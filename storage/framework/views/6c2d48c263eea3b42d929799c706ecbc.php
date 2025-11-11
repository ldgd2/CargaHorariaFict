

<?php $__env->startSection('title', 'Roles y permisos'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">

  
  <?php if(session('ok')): ?>
    <div class="snackbar snackbar--ok"><?php echo e(session('ok')); ?></div>
  <?php endif; ?>

  <?php if($errors->any()): ?>
    <div class="snackbar snackbar--error">
      <strong>Revisa los campos:</strong>
      <ul class="mt-2">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="grid grid--2">
    
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <h2 class="appbar__title" style="font-size:1.05rem">Catálogo de roles</h2>
      </div>

      
      <div class="card" style="margin-bottom:16px">
        <form method="POST" action="<?php echo e(route('roles.store')); ?>">
          <?php echo csrf_field(); ?>

          <div class="grid grid--3">
            <div class="field">
              <label class="field__label">Nombre del rol</label>
              <div class="field__box">
                <input name="nombre_rol" value="<?php echo e(old('nombre_rol')); ?>" class="field__input" required>
              </div>
            </div>

            <?php if(\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion')): ?>
              <div class="field" style="grid-column: span 2;">
                <label class="field__label">Descripción (opcional)</label>
                <div class="field__box">
                  <input name="descripcion" value="<?php echo e(old('descripcion')); ?>" class="field__input">
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div style="display:flex;align-items:center;gap:12px;justify-content:flex-end">
            <label class="text-muted" style="display:inline-flex;gap:8px;align-items:center">
              <input type="checkbox" name="habilitado" value="1" checked>
              <span>Habilitado</span>
            </label>

            <button class="btn btn--primary" type="submit">Crear</button>
          </div>
        </form>
      </div>

      
      <div class="card" style="padding:0">
        <div style="overflow:auto">
          <table style="width:100%;border-collapse:collapse">
            <thead>
              <tr style="background:var(--color-surface-2);border-bottom:1px solid var(--color-outline-variant)">
                <th style="text-align:left;padding:10px 12px">#</th>
                <th style="text-align:left;padding:10px 12px">Rol</th>
                <?php if(\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion')): ?>
                  <th style="text-align:left;padding:10px 12px">Descripción</th>
                <?php endif; ?>
                <th style="text-align:left;padding:10px 12px">Estado</th>
                <th style="width:1%;padding:10px 12px"></th>
              </tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rol): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr style="border-top:1px solid var(--color-outline-variant)">
                  <td style="padding:10px 12px"><?php echo e($rol->id_rol); ?></td>
                  <td style="padding:10px 12px;font-weight:700"><?php echo e($rol->nombre_rol); ?></td>
                  <?php if(\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion')): ?>
                    <td style="padding:10px 12px"><?php echo e($rol->descripcion); ?></td>
                  <?php endif; ?>
                  <td style="padding:10px 12px">
                    <?php if($rol->habilitado): ?>
                      <span class="text-muted" style="padding:3px 8px;border-radius:10px;background:color-mix(in srgb, var(--color-primary) 18%, transparent)">Habilitado</span>
                    <?php else: ?>
                      <span class="text-muted" style="padding:3px 8px;border-radius:10px;background:#5a3c05">Inhabilitado</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:10px 12px;text-align:right;white-space:nowrap">
                    
                    <details style="display:inline-block;margin-right:6px">
                      <summary class="btn btn--text">Editar</summary>
                      <div class="card" style="margin-top:8px;width:320px">
                        <form method="POST" action="<?php echo e(route('roles.update', $rol)); ?>">
                          <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

                          <div class="field">
                            <label class="field__label">Nombre</label>
                            <div class="field__box">
                              <input name="nombre_rol" value="<?php echo e(old('nombre_rol', $rol->nombre_rol)); ?>" class="field__input">
                            </div>
                          </div>

                          <?php if(\Illuminate\Support\Facades\Schema::hasColumn('rol','descripcion')): ?>
                            <div class="field">
                              <label class="field__label">Descripción</label>
                              <div class="field__box">
                                <input name="descripcion" value="<?php echo e(old('descripcion', $rol->descripcion)); ?>" class="field__input">
                              </div>
                            </div>
                          <?php endif; ?>

                          <label class="text-muted" style="display:inline-flex;gap:8px;align-items:center">
                            <input type="checkbox" name="habilitado" value="1" <?php echo e($rol->habilitado ? 'checked' : ''); ?>>
                            <span>Habilitado</span>
                          </label>

                          <div style="text-align:right;margin-top:10px">
                            <button class="btn btn--primary" type="submit">Guardar</button>
                          </div>
                        </form>
                      </div>
                    </details>

                    
                    <form method="POST" action="<?php echo e(route('roles.toggle', $rol)); ?>" style="display:inline-block">
                      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                      <button class="btn btn--tonal" type="submit">
                        <?php echo e($rol->habilitado ? 'Inhabilitar' : 'Habilitar'); ?>

                      </button>
                    </form>

                    
                    <form method="POST" action="<?php echo e(route('roles.destroy', $rol)); ?>" style="display:inline-block"
                          onsubmit="return confirm('¿Eliminar este rol?');">
                      <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                      <button class="btn btn--outline" type="submit" style="margin-left:6px;border-color:#ff6b6b;color:#ff6b6b">
                        Eliminar
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" style="padding:16px 12px;text-align:center" class="text-muted">No hay roles aún.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3" style="padding:10px 12px">
          <?php echo e($roles->links()); ?>

        </div>
      </div>
    </div>

    
    <div class="card">
      <h2 class="appbar__title" style="font-size:1.05rem;margin-bottom:12px">Asignación de roles a usuarios</h2>

      
      <div class="card" style="margin-bottom:16px">
        <form method="POST" action="<?php echo e(route('roles.asignar')); ?>">
          <?php echo csrf_field(); ?>

          <div class="grid grid--2">
            <div class="field">
              <label class="field__label">Usuario (lista rápida)</label>
              <div class="field__box">
                <select name="usuario_id" class="field__select">
                  <option value="">— Selecciona —</option>
                  <?php $__currentLoopData = $usuarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($u->id_usuario); ?>">
                      <?php echo e($u->apellido); ?>, <?php echo e($u->nombre); ?> — <?php echo e($u->email); ?>

                    </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
              <div class="field__hint">También puedes buscar por email (campo derecho).</div>
            </div>

            <div class="field">
              <label class="field__label">Buscar por email (opcional)</label>
              <div class="field__box">
                <input name="email" placeholder="usuario@correo.com" class="field__input">
              </div>
              <div class="field__hint">Si completas email, no es necesario elegir en el combo.</div>
            </div>

            <div class="field" style="grid-column: 1 / -1">
              <label class="field__label">Rol a asignar</label>
              <div class="field__box">
                <select name="id_rol" class="field__select" required>
                  <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($r->habilitado): ?>
                      <option value="<?php echo e($r->id_rol); ?>"><?php echo e($r->nombre_rol); ?></option>
                    <?php endif; ?>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
            </div>
          </div>

          <div style="text-align:right">
            <button class="btn btn--primary" type="submit">Asignar rol</button>
          </div>
        </form>
      </div>

      
      <div class="card">
        <form method="POST" action="<?php echo e(route('roles.revocar')); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>

          <h3 style="margin:0 0 10px 0">Revocar rol</h3>

          <div class="grid grid--3">
            <div class="field">
              <label class="field__label">Usuario</label>
              <div class="field__box">
                <select name="usuario_id" class="field__select" required>
                  <option value="">— Selecciona —</option>
                  <?php $__currentLoopData = $usuarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($u->id_usuario); ?>">
                      <?php echo e($u->apellido); ?>, <?php echo e($u->nombre); ?> — <?php echo e($u->email); ?>

                    </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
            </div>

            <div class="field">
              <label class="field__label">Rol</label>
              <div class="field__box">
                <select name="id_rol" class="field__select" required>
                  <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($r->id_rol); ?>"><?php echo e($r->nombre_rol); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
            </div>

            <div style="align-self:end;text-align:right">
              <button class="btn btn--tonal" type="submit">Revocar</button>
            </div>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/usuarios/admin/roles/index.blade.php ENDPATH**/ ?>