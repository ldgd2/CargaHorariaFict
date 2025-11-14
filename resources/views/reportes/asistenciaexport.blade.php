<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencia</title>
</head>
<body>
    {{-- ENCABEZADO Y METADATOS (Replicando el formato PDF) --}}
    <table>
        <tr>
            {{-- Colspan debe ser igual al número total de columnas de la tabla de datos (8) --}}
            <td colspan="8" style="font-weight: bold; font-size: 16px;">Reporte de Asistencia</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Generado el:</td>
            <td colspan="7">{{ now()->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Filtros Aplicados:</td>
            {{-- Asumiendo que 'periodo_id' y 'carrera_id' vienen en el array $filtros --}}
            <td>Periodo ID: {{ $filtros['periodo_id'] ?? 'N/A' }}</td>
            <td colspan="6">Carrera ID: {{ $filtros['carrera_id'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td colspan="8"></td> {{-- Fila en blanco para separación --}}
        </tr>
    </table>

    {{-- TABLA DE DATOS PRINCIPAL --}}
    <table>
        <thead>
            <tr>
                <!-- Encabezados definidos por el reporte -->
                <th>Nombre Docente</th>
                <th>Apellido Docente</th>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Fecha Sesión</th>
                <th>Hora Registro</th>
                <th>Estado Asistencia</th>
                <th>Tipo Registro</th>
            </tr>
        </thead>
        <tbody>
            {{-- USAMOS @forelse para manejar el caso de no registros, igual que en el PDF --}}
            @forelse ($datos as $dato)
                <tr>
                    <!-- Campos extraídos de la consulta en ReporteAuditoriaController.php -->
                    <td>{{ $dato->docente_nombre }}</td>
                    <td>{{ $dato->docente_apellido }}</td>
                    <td>{{ $dato->materia_nombre }}</td>
                    <td>{{ $dato->grupo_nombre }}</td>
                    <td>{{ $dato->fecha_sesion }}</td>
                    <td>{{ $dato->hora_registro }}</td>
                    <td>{{ $dato->estado }}</td>
                    <td>{{ $dato->tipo_registro }}</td>
                </tr>
            @empty
                <tr>
                    {{-- Mensaje de no registros si la colección está vacía --}}
                    <td colspan="8" style="text-align: center; font-style: italic;">No se encontraron registros de asistencia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>