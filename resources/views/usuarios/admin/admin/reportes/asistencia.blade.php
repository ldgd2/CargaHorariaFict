<!DOCTYPE html>
<html>
<head>
    <title>{{ $titulo }}</title>
    <style>
        /* (Usar los mismos estilos CSS que en carga_docente.blade.php) */
        body { font-family: sans-serif; margin: 0; padding: 0; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1f2937; font-size: 16px; }
        .header p { margin: 5px 0 0 0; color: #4b5563; font-size: 12px; }
        .filters { margin-bottom: 15px; padding: 10px; background-color: #e5e7eb; border-radius: 5px; }
        .filters p { margin: 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background-color: #10b981; color: white; font-weight: bold; font-size: 10px; } /* Color diferente para distinguir */
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Generado el: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="filters">
        <p><strong>Filtros Aplicados:</strong></p>
        <p>Periodo ID: {{ $filtros['periodo_id'] }} | Carrera ID: {{ $filtros['carrera_id'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Docente</th>
                <th>Sesión</th>
                <th>Fecha</th>
                <th>Estado</th>
                {{-- Columnas de ejemplo para asistencia --}}
            </tr>
        </thead>
        <tbody>
            @forelse ($datos as $item)
                <tr>
                    {{-- ¡Ajusta los nombres de las propiedades a tu consulta DB de asistencia! --}}
                    <td>{{ $item->docente_nombre ?? 'N/A' }}</td> 
                    <td>{{ $item->sesion_nombre ?? 'N/A' }}</td>
                    <td>{{ $item->fecha_sesion ?? 'N/A' }}</td>
                    <td>{{ $item->estado_asistencia ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No se encontraron registros de asistencia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>