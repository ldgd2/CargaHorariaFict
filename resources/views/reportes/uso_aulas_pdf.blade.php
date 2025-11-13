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

{{-- Encabezado: título + datos del período --}}
<table class="header-table">
    <tr>
        <td class="header-left">
            <div class="title">Reporte de uso de aulas</div>
            <div class="subtitle">Ocupación por período académico</div>
        </td>
        <td class="header-right">
            <div><strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            @if(!empty($periodo))
                <div>
                    <strong>Período:</strong>
                    {{ $periodo->nombre }}
                    ({{ $periodo->fecha_inicio }} – {{ $periodo->fecha_fin }})
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- Resumen de filtros --}}
<table>
    <tr>
        <th style="width: 20%;">Rango de fechas</th>
        <td style="width: 30%;">
            {{ $filtros['desde'] ?? '—' }} – {{ $filtros['hasta'] ?? '—' }}
        </td>
        <th style="width: 20%;">Origen de ocupación</th>
        <td style="width: 30%;">
            @php
                $origen = $filtros['origen'] ?? 'todos';
                echo $origen === 'clases'   ? 'Solo clases'
                    : ($origen === 'bloqueos' ? 'Solo bloqueos / mantenimiento'
                    : 'Clases + bloqueos');
            @endphp
        </td>
    </tr>
    <tr>
        <th>Tipo de aula</th>
        <td>{{ $filtros['tipo_aula'] ?: 'Todos' }}</td>
        <th>Motivo de bloqueo</th>
        <td>{{ $filtros['motivo'] ?: 'Todos' }}</td>
    </tr>
</table>

{{-- Tabla principal --}}
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
    @forelse($reporte as $r)
        <tr>
            <td>{{ $r['codigo'] }}</td>
            <td>{{ $r['nombre'] }}</td>
            <td>{{ $r['tipo'] ?? '—' }}</td>
            <td class="text-center">{{ $r['capacidad'] }}</td>
            <td class="text-center">{{ number_format($r['horas_asignadas'], 2) }}</td>
            <td class="text-center">{{ number_format($r['horas_bloqueadas'], 2) }}</td>
            <td class="text-center">{{ number_format($r['horas_libres'], 2) }}</td>
            <td class="text-center">{{ number_format($r['ocupacion'], 2) }}%</td>
            <td class="text-center">{{ $r['estado'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="9" class="text-center">
                No se encontraron aulas para los criterios seleccionados.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
