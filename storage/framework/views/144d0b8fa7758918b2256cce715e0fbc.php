<!DOCTYPE html>
<html>
<head>
    <title><?php echo e($titulo); ?></title>
    <style>
        /* (Estilos omitidos por brevedad, asumiendo que ya los tienes) */
        body { font-family: sans-serif; margin: 0; padding: 0; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1f2937; font-size: 16px; }
        .filters { margin-bottom: 15px; padding: 10px; background-color: #e5e7eb; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background-color: #3b82f6; color: white; font-weight: bold; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo e($titulo); ?></h1>
        <p>Generado el: <?php echo e(now()->format('d/m/Y H:i:s')); ?></p>
    </div>

    <div class="filters">
        <p><strong>Filtros Aplicados:</strong></p>
        
        <p>Periodo ID: <?php echo e($filtros['periodo_id']); ?> | Carrera ID: <?php echo e($filtros['carrera_id']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>C.I./Documento</th>
                <th>Docente</th>
                <th>Materia</th>
                <th>Grupo</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $datos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    
                    <td><?php echo e($item->docente_documento); ?></td>
                    <td><?php echo e($item->docente_nombre); ?> <?php echo e($item->docente_apellido); ?></td>
                    <td><?php echo e($item->materia_nombre); ?></td>
                    <td><?php echo e($item->grupo_nombre); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">No se encontraron registros de carga horaria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html><?php /**PATH C:\Users\user\Desktop\Larabel2\CargaHorariaFict\resources\views/reportes/carga_docente.blade.php ENDPATH**/ ?>