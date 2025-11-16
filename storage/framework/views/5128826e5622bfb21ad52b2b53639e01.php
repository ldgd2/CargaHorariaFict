<?php $__env->startSection('title','Dashboard Admin'); ?>

<?php $__env->startSection('content'); ?>
<div class="app-container">
  <h1 style="font-weight:700; margin-bottom: 16px;">Panel de Administración</h1>

  <div class="grid grid--3">
    
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Usuarios</h2>
      <p class="text-muted mb-3">Crear nuevas cuentas y asignar roles.</p>
      <a href="<?php echo e(route('admin.usuarios.signup')); ?>" class="btn btn--primary">Registrar usuario</a>
    </div>

    
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Roles y permisos</h2>
      <p class="text-muted mb-3">Administra los roles y la asignación a usuarios.</p>
      <a href="<?php echo e(route('roles.index')); ?>" class="btn btn--primary">Ir a Roles</a>
    </div>

    
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Períodos</h2>
      <p class="text-muted mb-3">Define períodos académicos.</p>
      <a href="<?php echo e(route('periodos.index')); ?>" class="btn btn--primary">Gestionar períodos</a>
    </div>

    
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Carreras</h2>
      <p class="text-muted mb-3">Catálogo de carreras.</p>
      <a href="<?php echo e(route('admin.carreras.view')); ?>" class="btn btn--primary">Abrir</a>
    </div>

    <!-- eliminado: tarjeta sin botón -->
    
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Importación masiva</h2>
      <p class="text-muted mb-3">Importa datos desde un archivo Excel (hojas: USUARIOS, CARRERAS, AULAS, PERIODOS, MATERIAS, GRUPOS, CARGA_HORARIA, DISPONIBILIDAD, BLOQUEO_AULA).</p>
      <a href="<?php echo e(route('admin.import.form')); ?>" class="btn btn--primary">Importar datos</a>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/usuarios/admin/admin/dashboard.blade.php ENDPATH**/ ?>