<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de uso de aulas</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .header-left {
            text-align: left;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
            vertical-align: top;
            font-size: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .subtitle {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
    </style>
</head>
<body>


<table class="header-table">
    <tr>
        <td class="header-left">
            <div class="title">Reporte de uso de aulas</div>
            <div class="subtitle">Ocupación por período académico</div>
        </td>
        <td class="header-right">
            <div><strong>Fecha de generación:</strong> <?php echo e(now()->format('d/m/Y H:i')); ?></div>
            <?php if(!empty($periodo)): ?>
                <div>
                    <strong>Período:</strong>
                    <?php echo e($periodo->nombre); ?>

                    (<?php echo e($periodo->fecha_inicio); ?> – <?php echo e($periodo->fecha_fin); ?>)
                </div>
            <?php endif; ?>
        </td>
    </tr>
</table>


<table>
    <tr>
        <th style="width: 20%;">Rango de fechas</th>
        <td style="width: 30%;">
            <?php echo e($filtros['desde'] ?? '—'); ?> – <?php echo e($filtros['hasta'] ?? '—'); ?>

        </td>
        <th style="width: 20%;">Origen de ocupación</th>
        <td style="width: 30%;">
            <?php
                $origen = $filtros['origen'] ?? 'todos';
                echo $origen === 'clases'   ? 'Solo clases'
                    : ($origen === 'bloqueos' ? 'Solo bloqueos / mantenimiento'
                    : 'Clases + bloqueos');
            ?>
        </td>
    </tr>
    <tr>
        <th>Tipo de aula</th>
        <td><?php echo e($filtros['tipo_aula'] ?: 'Todos'); ?></td>
        <th>Motivo de bloqueo</th>
        <td><?php echo e($filtros['motivo'] ?: 'Todos'); ?></td>
    </tr>
</table>


<table>
    <thead>
    <tr>
        <th>Código</th>
        <th>Nombre</th>
        <th>Tipo</th>
        <th class="text-center">Capacidad</th>
        <th class="text-center">Horas asignadas</th>
        <th class="text-center">Horas bloqueadas</th>
        <th class="text-center">Horas libres</th>
        <th class="text-center">% ocupación</th>
        <th class="text-center">Estado</th>
    </tr>
    </thead>
    <tbody>
    <?php $__empty_1 = true; $__currentLoopData = $reporte; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($r['codigo']); ?></td>
            <td><?php echo e($r['nombre']); ?></td>
            <td><?php echo e($r['tipo'] ?? '—'); ?></td>
            <td class="text-center"><?php echo e($r['capacidad']); ?></td>
            <td class="text-center"><?php echo e(number_format($r['horas_asignadas'], 2)); ?></td>
            <td class="text-center"><?php echo e(number_format($r['horas_bloqueadas'], 2)); ?></td>
            <td class="text-center"><?php echo e(number_format($r['horas_libres'], 2)); ?></td>
            <td class="text-center"><?php echo e(number_format($r['ocupacion'], 2)); ?>%</td>
            <td class="text-center"><?php echo e($r['estado']); ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="9" class="text-center">
                No se encontraron aulas para los criterios seleccionados.
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
<?php /**PATH C:\Proyecto\CargaHorariaFict\resources\views/reportes/uso_aulas_pdf.blade.php ENDPATH**/ ?>