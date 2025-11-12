<!DOCTYPE html>
<html>
<head>
    <title>{{ $titulo }}</title>
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
        <h1>{{ $titulo }}</h1>
        <p>Generado el: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="filters">
        <p><strong>Filtros Aplicados:</strong></p>
        {{-- Aquí se debería resolver el nombre real del periodo y carrera, por ahora se muestra el ID --}}
        <p>Periodo ID: {{ $filtros['periodo_id'] }} | Carrera ID: {{ $filtros['carrera_id'] }}</p>
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
            @forelse ($datos as $item)
                <tr>
                    {{-- Usando los nuevos campos de la consulta --}}
                    <td>{{ $item->docente_documento }}</td>
                    <td>{{ $item->docente_nombre }} {{ $item->docente_apellido }}</td>
                    <td>{{ $item->materia_nombre }}</td>
                    <td>{{ $item->grupo_nombre }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No se encontraron registros de carga horaria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>