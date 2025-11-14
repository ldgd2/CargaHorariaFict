@extends('layouts.app') 
@section('title', 'Generar Reportes Esenciales')

@section('content')
<div class="app-container">
    <h1 style="font-weight:700; margin-bottom: 16px;">⚙️ Reportes y Auditoría del Ciclo</h1>

    {{-- Pestañas de Navegación (SIN CAMBIOS) --}}
    @php
        $tabActivo = $tab_activo ?? 'reportes'; 
    @endphp
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('coordinador.reportes.index', ['tab' => 'reportes']) }}" 
                class="text-gray-900 border-indigo-500 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Reportes Esenciales
            </a>
        </nav>
    </div>
    
    {{-- Contenido Principal: Generación de Reportes --}}
    <div class="card p-6 shadow-lg bg-white">
        <h2 style="margin:0 0 16px 0;" class="text-xl font-semibold">📑 Generación de Reportes Esenciales</h2>

        <form id="report-form" action="{{ route('coordinador.reportes.generar') }}" method="GET" target="_blank" class="space-y-6">

             {{-- PASO 1: SELECCIÓN DE PARÁMETROS --}}
            <h3 class="text-lg font-medium border-b border-gray-200 pb-2 mb-4 text-gray-800">1. Selección de Parámetros</h3>
            
            {{-- SECCIÓN DE BOTONES DE TIPO DE REPORTE --}}
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Seleccionar Tipo de Reporte:</label>
                
                <div id="report-type-buttons" class="flex flex-wrap gap-3">
                    <button type="button" data-tipo="Carga" class="report-type-btn btn-activo px-4 py-2 rounded-md shadow transition-colors duration-150">
                        📚 Carga Docente
                    </button>
                    <button type="button" data-tipo="Asistencia" class="report-type-btn btn-inactivo px-4 py-2 rounded-md shadow transition-colors duration-150">
                        ✅ Asistencia
                    </button>
                    <button type="button" data-tipo="Horarios" class="report-type-btn btn-inactivo px-4 py-2 rounded-md shadow transition-colors duration-150">
                        ⏰ Horarios por Aula
                    </button>
                </div>

                {{-- CAMPO OCULTO para el tipo de reporte --}}
                <input type="hidden" name="tipo" id="tipo" value="Carga">
            </div>

            {{-- ⚠️ NUEVA SECCIÓN DE BOTONES: PERIODO --}}
            <div class="space-y-4 pt-4"> 
                <label class="block text-sm font-medium text-gray-700">Periodo:</label>
                <div id="periodo-buttons" class="flex flex-wrap gap-3">
                    <button type="button" data-periodo-id="all" class="periodo-btn btn-activo px-4 py-2 rounded-md shadow transition-colors duration-150">
                        Todos los Periodos
                    </button> 
                    @forelse ($periodos as $periodo)
                        <button type="button" data-periodo-id="{{ $periodo->id_periodo }}" class="periodo-btn btn-inactivo px-4 py-2 rounded-md shadow transition-colors duration-150">
                            {{ $periodo->nombre }}
                        </button>
                    @empty
                        <span class="text-gray-500">No hay períodos académicos</span>
                    @endforelse
                </div>
                {{-- CAMPO OCULTO para el periodo --}}
                <input type="hidden" name="periodo_id" id="periodo_id" value="all">
            </div>
            
            {{-- ⚠️ NUEVA SECCIÓN DE BOTONES: CARRERA --}}
            <div class="space-y-4 pt-4"> 
                <label class="block text-sm font-medium text-gray-700">Carrera:</label>
                <div id="carrera-buttons" class="flex flex-wrap gap-3">
                    <button type="button" data-carrera-id="all" class="carrera-btn btn-activo px-4 py-2 rounded-md shadow transition-colors duration-150">
                        Todas las Carreras
                    </button> 
                    @forelse ($carreras as $carrera)
                        <button type="button" data-carrera-id="{{ $carrera->id_carrera }}" class="carrera-btn btn-inactivo px-4 py-2 rounded-md shadow transition-colors duration-150">
                            {{ $carrera->nombre }}
                        </button>
                    @empty
                        <span class="text-gray-500">No hay carreras registradas</span>
                    @endforelse
                </div>
                {{-- CAMPO OCULTO para la carrera --}}
                <input type="hidden" name="carrera_id" id="carrera_id" value="all">
            </div> 
            
            <div class="mt-6 flex justify-end space-x-4 border-t border-gray-200 pt-4">
                {{-- Contenido --}}
            </div>

            {{-- PASO 2: SELECCIÓN DE FORMATO Y DESCARGA (Visible por defecto) --}}
            <div id="seccion-descarga"> 
                <h3 class="text-lg font-medium border-b pt-4 pb-2 mb-4 text-gray-800">2. Selección de Formato y Descarga</h3>
                
                <p class="text-gray-600 mb-4">Seleccione el formato deseado para generar el archivo. La descarga se iniciará automáticamente.</p>

                <div class="flex space-x-4">
                    {{-- Botón Generar PDF --}}
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

    {{--- VISTA PREVIA DE DATOS (Visible por defecto) ---}}
    <div class="card p-6 shadow-lg bg-white mt-6">
        <h2 id="preview-title" class="text-xl font-semibold mb-4">📊 Vista Previa de Datos</h2> 
        <div id="reporte-preview-container" class="overflow-x-auto min-h-40 bg-gray-50 p-4 border rounded-md">
            <p id="initial-message" class="text-gray-500 text-center py-4">Haga clic en un tipo de reporte y filtros para ver la vista previa.</p>
        </div>
    </div>

</div>

{{-- 💅 ESTILOS PARA LOS BOTONES --}}
<style>
    /* Estilo Activo (similar al de los enlaces de coordinación) */
    .btn-activo {
        background-color: #4f46e5; /* indigo-600 */
        color: white;
        border: 2px solid #4f46e5;
    }
    /* Estilo Inactivo (desactivado o sin seleccionar) */
    .btn-inactivo {
        background-color: #f3f4f6; /* gray-100/200 */
        color: #4b5563; /* gray-700 */
        border: 1px solid #d1d5db; /* gray-300 */
    }
    .btn-inactivo:hover {
        background-color: #e5e7eb; /* gray-200 */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Referencias a los elementos ---
        const tipoReporteInput = document.getElementById('tipo'); // Input hidden
        const periodoInput = document.getElementById('periodo_id'); // Input hidden
        const carreraInput = document.getElementById('carrera_id'); // Input hidden

        const tipoReporteBtns = document.querySelectorAll('.report-type-btn');
        const periodoBtns = document.querySelectorAll('.periodo-btn');
        const carreraBtns = document.querySelectorAll('.carrera-btn');

        const previewContainer = document.getElementById('reporte-preview-container');

        // --- Funciones de Utilidad ---
        
        /**
         * Maneja la actualización de estilos para un grupo de botones.
         * @param {NodeListOf<Element>} buttons - El NodeList de botones.
         * @param {string} activeValue - El valor (data-*) que debe estar activo.
         * @param {string} dataAttribute - El nombre del atributo de datos ('data-tipo', 'data-periodo-id', etc).
         */
        function updateButtonStyles(buttons, activeValue, dataAttribute) {
            buttons.forEach(btn => {
                const btnValue = btn.getAttribute(dataAttribute);
                if (btnValue === activeValue) {
                    btn.classList.remove('btn-inactivo');
                    btn.classList.add('btn-activo');
                } else {
                    btn.classList.remove('btn-activo');
                    btn.classList.add('btn-inactivo');
                }
            });
        }


        /**
         * Función AJAX para cargar la vista previa.
         */
        function loadReportPreview() {
            const tipo = tipoReporteInput.value;
            const periodo_id = periodoInput.value;
            const carrera_id = carreraInput.value;

            // Mostrar un mensaje de carga
            previewContainer.innerHTML = '<p class="text-center py-4 text-indigo-500">Cargando vista previa... ⏳</p>';
            
            const url = "{{ route('coordinador.reportes.preview') }}" + 
                                     `?tipo=${tipo}&periodo_id=${periodo_id}&carrera_id=${carrera_id}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text) }); 
                    }
                    return response.text();
                })
                .then(html => {
                    previewContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error al cargar la vista previa:', error);
                    let errorMessage = 'Error desconocido';
                    try {
                        const errorJson = JSON.parse(error.message);
                        errorMessage = errorJson.message || 'Error de validación o del servidor.';
                    } catch {
                        errorMessage = error.message; 
                    }
                    previewContainer.innerHTML = `<p class="text-center py-4 text-red-500">Error al cargar la vista previa: ${errorMessage}</p>`;
                });
        }

        // --- Manejadores de Eventos ---

        // Manejador genérico para Tipo de Reporte
        tipoReporteBtns.forEach(btn => {
            btn.addEventListener('click', function(event) {
                const newType = event.currentTarget.getAttribute('data-tipo');
                tipoReporteInput.value = newType;
                updateButtonStyles(tipoReporteBtns, newType, 'data-tipo');
                loadReportPreview();
            });
        });

        // Manejador genérico para Periodo
        periodoBtns.forEach(btn => {
            btn.addEventListener('click', function(event) {
                const newPeriodoId = event.currentTarget.getAttribute('data-periodo-id');
                periodoInput.value = newPeriodoId;
                updateButtonStyles(periodoBtns, newPeriodoId, 'data-periodo-id');
                loadReportPreview();
            });
        });

        // Manejador genérico para Carrera
        carreraBtns.forEach(btn => {
            btn.addEventListener('click', function(event) {
                const newCarreraId = event.currentTarget.getAttribute('data-carrera-id');
                carreraInput.value = newCarreraId;
                updateButtonStyles(carreraBtns, newCarreraId, 'data-carrera-id');
                loadReportPreview();
            });
        });

        // --- Inicialización ---
        
        // Sincronizar estilos iniciales (asegura que el botón activo tenga el estilo correcto al cargar)
        updateButtonStyles(tipoReporteBtns, tipoReporteInput.value, 'data-tipo');
        updateButtonStyles(periodoBtns, periodoInput.value, 'data-periodo-id');
        updateButtonStyles(carreraBtns, carreraInput.value, 'data-carrera-id');
        
        // Carga inicial de la vista previa
        loadReportPreview(); 
    });

    /**
     * Función para gestionar la descarga XLSX (SIN CAMBIOS)
     */
    function downloadReport(format) {
        const form = document.getElementById('report-form');
        const originalAction = "{{ route('coordinador.reportes.generar') }}";

        if (format === 'xlsx') {
            form.action = "{{ route('coordinador.reportes.exportXLSX') }}";
            form.submit();
            
            setTimeout(() => {
                form.action = originalAction;
            }, 100); 
        } 
    }
</script>
@endsection