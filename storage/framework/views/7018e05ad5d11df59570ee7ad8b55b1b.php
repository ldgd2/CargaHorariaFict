<!DOCTYPE html>
<html>
<head>
    <title><?php echo e($titulo ?? 'Reporte de Horarios'); ?></title>
    <meta charset="UTF-8">
    <style>
        /* Estilos CSS adaptados para impresión o PDF */
        body { font-family: sans-serif; margin: 0; padding: 0; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1f2937; font-size: 16px; }
        /* Usando el formato de fecha exacto que solicitaste en el ejemplo anterior */
        .header p { margin: 5px 0 0 0; color: #4b5563; font-size: 12px; } 
        .filters { margin-bottom: 15px; padding: 10px; background-color: #e5e7eb; border-radius: 5px; }
        .filters p { margin: 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        /* Color diferente para distinguir: verde (basado en tu archivo de asistencia) */
        th { background-color: #10b981; color: white; font-weight: bold; font-size: 10px; } 
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo e($titulo ?? 'Reporte de Horarios'); ?></h1>
        <p>Generado el: <?php echo e(now()->format('d/m/Y H:i:s')); ?></p> 
    </div>

    <div class="filters">
        <p><strong>Filtros Aplicados:</strong></p>
        <p>Periodo ID: <?php echo e($filtros['periodo_id'] ?? 'all'); ?> | Carrera ID: <?php echo e($filtros['carrera_id'] ?? 'all'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Aula</th>
                <th>Día</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Materia / Grupo</th>
                <th>Docente</th> 
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $datos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($item->aula_nombre ?? 'N/A'); ?></td>
                    <td><?php echo e($item->dia_semana ?? 'N/A'); ?></td>
                    <td><?php echo e($item->hora_inicio ?? 'N/A'); ?></td>
                    <td><?php echo e($item->hora_fin ?? 'N/A'); ?></td>
                    <td><?php echo e($item->materia_grupo ?? 'N/A'); ?></td>
                    <td><?php echo e(($item->docente_nombre ?? '') . ' ' . ($item->docente_apellido ?? 'N/A')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No se encontraron horarios para las aulas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html><?php /**PATH C:\Users\user\Desktop\Larabel2\CargaHorariaFict\resources\views/reportes/horariosaulaexport.blade.php ENDPATH**/ ?>