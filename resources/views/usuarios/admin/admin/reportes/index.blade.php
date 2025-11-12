@extends('layouts.app') 
@section('title', 'Generar Reportes Esenciales')

@section('content')
<div class="app-container">
    <h1 style="font-weight:700; margin-bottom: 16px;">⚙️ Reportes y Auditoría del Ciclo</h1>

    {{-- Pestañas de Navegación --}}
    @php
        // Asumiendo que esta variable llega desde el controlador
        $tabActivo = $tab_activo ?? 'reportes'; 
    @endphp
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
        </nav>
    </div>

    {{-- Contenido Principal: Generación de Reportes --}}
    <div class="card p-6 shadow-lg bg-white">
        <h2 style="margin:0 0 16px 0;" class="text-xl font-semibold">📑 Generación de Reportes Esenciales</h2>

        <form id="report-form" action="{{ route('coordinador.reportes.generar') }}" method="GET" target="_blank" class="space-y-6">

            {{-- PASO 1: SELECCIÓN DE PARÁMETROS --}}
            <h3 class="text-lg font-medium border-b pb-2 mb-4">1. Selección de Parámetros</h3>
            <div class="grid grid--3 gap-4">
                
                {{-- Dropdown Tipo de Reporte (Estático) --}}
                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Reporte:</label>
                    <select name="tipo" id="tipo" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md">
                        <option value="" selected disabled>-- Seleccione un tipo --</option> 
                        <option value="Carga">Carga Docente</option>
                        <option value="Asistencia">Asistencia</option>
                        <option value="Horarios">Horarios por Aula</option>
                    </select>
                </div>
                
                {{-- Dropdown Periodo (DINÁMICO CON OPCIÓN 'TODOS') --}}
                <div>
                    <label for="periodo_id" class="block text-sm font-medium text-gray-700">Periodo:</label>
                    <select name="periodo_id" id="periodo_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md">
                        <option value="all">Todos los Periodos</option> 
                        @forelse ($periodos as $periodo)
                            <option value="{{ $periodo->id_periodo }}">
                                {{ $periodo->nombre }}
                            </option>
                        @empty
                            <option value="" disabled>No hay períodos académicos</option>
                        @endforelse
                    </select>
                </div>

                {{-- Dropdown Carrera (DINÁMICO usando $carreras) --}}
                <div>
                    <label for="carrera_id" class="block text-sm font-medium text-gray-700">Carrera:</label>
                    <select name="carrera_id" id="carrera_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md">
                        <option value="all" selected>Todas las Carreras</option>
                        @forelse ($carreras as $carrera)
                            <option value="{{ $carrera->id_carrera }}">
                                {{ $carrera->nombre }}
                            </option>
                        @empty
                            <option value="" disabled>No hay carreras registradas</option>
                        @endforelse
                    </select>
                </div>
            </div> {{-- Fin grid --}}


            {{-- PASO 2: SELECCIÓN DE FORMATO Y DESCARGA --}}
            <div id="seccion-descarga" style="display: none;">
                <h3 class="text-lg font-medium border-b pt-4 pb-2 mb-4">2. Selección de Formato y Descarga</h3>
                
                <p class="text-muted mb-4">Seleccione el formato deseado para generar el archivo. La descarga se iniciará automáticamente.</p>

                <div class="flex space-x-4">
                    {{-- Botón Generar PDF (Usa la acción por defecto del formulario) --}}
                    <button type="submit" class="btn btn--primary text-white px-6 py-3 rounded-md shadow-md bg-red-600 hover:bg-red-700">
                        Generar PDF 📄
                    </button>

                    {{-- Botón Generar XLSX --}}
                    <button type="button" onclick="downloadReport('xlsx')" class="btn btn--primary text-white px-6 py-3 rounded-md shadow-md bg-green-600 hover:bg-green-700">
                        Generar XLSX 📈
                    </button>
                </div>
            </div>
            
        </form>
    </div>

    {{--- NUEVA SECCIÓN: VISTA PREVIA DE DATOS ---}}
    <div class="card p-6 shadow-lg bg-white mt-6">
        <h2 id="preview-title" class="text-xl font-semibold mb-4" style="display: none;">📊 Vista Previa de Datos</h2>
        <div id="reporte-preview-container" class="overflow-x-auto min-h-40 bg-gray-50 p-4 border rounded-md">
            <p id="initial-message" class="text-gray-500 text-center py-4">Seleccione un Tipo de Reporte, Periodo y Carrera para ver la vista previa.</p>
        </div>
    </div>
    {{--- FIN NUEVA SECCIÓN ---}}

</div>

{{-- SCRIPT PARA CONTROLAR LA VISIBILIDAD, DESCARGA XLSX Y AJAX DE VISTA PREVIA --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoReporteSelect = document.getElementById('tipo');
        const periodoSelect = document.getElementById('periodo_id');
        const carreraSelect = document.getElementById('carrera_id');
        const seccionDescarga = document.getElementById('seccion-descarga');
        const previewContainer = document.getElementById('reporte-preview-container');
        const previewTitle = document.getElementById('preview-title');
        const initialMessage = document.getElementById('initial-message');

        function toggleDescargaVisibility() {
            // Muestra la sección de descarga si se ha seleccionado un tipo de reporte
            const isTipoSelected = tipoReporteSelect.value !== "";
            seccionDescarga.style.display = isTipoSelected ? 'block' : 'none';
            previewTitle.style.display = isTipoSelected ? 'block' : 'none';
            
            // Si el tipo está seleccionado, cargamos la vista previa
            if (isTipoSelected) {
                loadReportPreview();
            } else {
                previewContainer.innerHTML = '<p id="initial-message" class="text-gray-500 text-center py-4">Seleccione un Tipo de Reporte, Periodo y Carrera para ver la vista previa.</p>';
            }
        }

        // Función AJAX para cargar la vista previa
        function loadReportPreview() {
            const tipo = tipoReporteSelect.value;
            const periodo_id = periodoSelect.value;
            const carrera_id = carreraSelect.value;

            if (!tipo) {
                return; // No hacer nada si no hay tipo seleccionado
            }

            // Mostrar un mensaje de carga
            previewContainer.innerHTML = '<p class="text-center py-4 text-indigo-500">Cargando vista previa... ⏳</p>';
            
            // Usamos la ruta de AJAX que configuraremos en el controlador
            const url = "{{ route('coordinador.reportes.preview') }}" + 
                        `?tipo=${tipo}&periodo_id=${periodo_id}&carrera_id=${carrera_id}`;
            
            fetch(url)
                .then(response => {
                    // Si la respuesta no es un 200 OK, mostramos un error
                    if (!response.ok) {
                        throw new Error(`Error en el servidor: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Insertamos el HTML de la tabla de datos en el contenedor
                    previewContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error al cargar la vista previa:', error);
                    previewContainer.innerHTML = `<p class="text-center py-4 text-red-500">Error al cargar la vista previa: ${error.message}</p>`;
                });
        }

        // 1. Manejadores de eventos: Llama a loadReportPreview cuando cambian los filtros
        tipoReporteSelect.addEventListener('change', toggleDescargaVisibility);
        periodoSelect.addEventListener('change', loadReportPreview);
        carreraSelect.addEventListener('change', loadReportPreview);

        // Inicializar al cargar
        toggleDescargaVisibility(); 
    });

    /**
     * Función para gestionar la descarga XLSX
     */
    function downloadReport(format) {
        const form = document.getElementById('report-form');
        const originalAction = "{{ route('coordinador.reportes.generar') }}";

        if (format === 'xlsx') {
            form.action = "{{ route('coordinador.reportes.export.xlsx') }}";
            form.submit();
            
            // Devolver la acción original (PDF) después de un breve retraso.
            setTimeout(() => {
                form.action = originalAction;
            }, 100); 
        } 
    }
</script>
@endsection