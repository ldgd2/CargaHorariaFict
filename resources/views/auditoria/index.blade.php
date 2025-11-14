@extends('layouts.app')
@section('title', 'Auditoría y Conflictos de Carga')

@section('content')
<div class="app-container p-4 sm:p-6 lg:p-8 min-h-screen bg-gray-50">
	
	{{-- Se eliminó el icono grande (rayo) de la cabecera --}}
	<h1 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-3 flex items-center">
		Auditoría y Conflictos de Carga (CU12)
	</h1>

	{{-- Enlaces de Navegación (Pestañas simuladas) --}}
	<div class="flex space-x-4 mb-8">
		{{-- Enlace a Reportes (CU9) --}}
		<a href="{{ route('coordinador.reportes.index') }}" 
			class="py-2 px-4 rounded-lg font-medium text-sm text-gray-600 hover:bg-gray-200 transition duration-150">
			📊 Generar Reportes (CU9)
		</a>
		{{-- Enlace a Auditoría (ACTIVO) --}}
		<span class="py-2 px-4 rounded-lg font-medium text-sm bg-red-50 text-red-600 border border-red-300">
			⚠️ Auditoría de Conflictos (CU12)
		</span>
	</div>

	
	{{-- FORMULARIO DE FILTROS Y ACCIÓN --}}
	<div class="grid lg:grid-cols-3 gap-8">
		
		{{-- Columna de Ejecución (Paso 1 y 2) --}}
		<div class="lg:col-span-1">
			<div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-red-500">
				{{-- Se eliminó el icono del título "Ajuste y Ejecución" --}}
				<h2 class="text-xl font-bold text-gray-700 mb-4 flex items-center">
					Ajuste y Ejecución
				</h2>

				<form id="audit-form" class="space-y-5">
					
					{{-- Filtro Periodo --}}
					<div>
						<label for="periodo_id_audit" class="block text-sm font-semibold text-gray-600 mb-1">Periodo a Analizar:</label>
						<select name="periodo_id" id="periodo_id_audit" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 transition duration-150">
							<option value="" disabled selected>-- Seleccione Periodo --</option> 
							@forelse ($periodos as $periodo)
								<option value="{{ $periodo->id_periodo }}">
									{{ $periodo->nombre }}
								</option>
							@empty
								<option value="" disabled>No hay períodos académicos</option>
							@endforelse
						</select>
					</div>

					{{-- Filtro Carrera --}}
					<div>
						<label for="carrera_id_audit" class="block text-sm font-semibold text-gray-600 mb-1">Carrera (Opcional):</label>
						<select name="carrera_id" id="carrera_id_audit" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 transition duration-150">
							<option value="all" selected>Todas las Carreras</option>
							@forelse ($carreras as $carrera)
								<option value="{{ $carrera->id_carrera }}">
									{{ $carrera->nombre }}
								</option>
							@empty
								<option value="" disabled>No hay carreras habilitadas</option>
							@endforelse
						</select>
					</div>

					{{-- Botón de Ejecución --}}
					<div class="pt-4 border-t border-gray-100">
						<p class="text-sm text-gray-500 mb-3">
							⚠️ Al ejecutar, se calcularán y guardarán los conflictos actuales. Este proceso puede tomar varios segundos.
						</p>
						
						<button type="button" id="btn-refrescar-auditoria" 
								// ⚠️ CLASES DE ESTILO CAMBIADAS AQUÍ 
								class="w-full flex justify-center items-center px-4 py-3 text-sm rounded-xl shadow-md disabled:opacity-50 transition duration-150 ease-in-out btn btn--primary" 
								disabled>
							<svg class="w-5 h-5 mr-2 animate-spin hidden" id="loading-spinner" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> <path d="M5 22h14" /><path d="M5 2h14" /><path d="M17 22V2L7 12l10 10" /></svg>
							<span id="button-text"> Ejecutar Auditoría Ahora</span>
						</button>
						
						<p id="ultima-ejecucion" class="text-xs text-gray-400 mt-2 text-center">Última ejecución: No registrada.</p>
					</div>
				</form>
			</div>
		</div>

		{{-- Columna de Resultados (Paso 3) --}}
		<div class="lg:col-span-2">
			<div class="bg-white p-6 rounded-xl shadow-lg">
				<h2 class="text-xl font-bold text-gray-700 mb-4">
					Resultados de Conflictos
				</h2>
				
				<div id="conflictos-container" class="overflow-x-auto p-4 border border-gray-200 rounded-lg bg-gray-50 min-h-80">
					{{-- Contenedor donde se cargará la tabla AJAX --}}
					<div id="loading-results-message" class="text-center py-10">
						<p class="text-gray-500 text-lg">
							Seleccione un período y presione **"Ejecutar Auditoría Ahora"** para analizar y cargar los resultados.
						</p>
						<p class="text-sm text-gray-400 mt-2">
							Los resultados de auditoría son persistentes y se mantendrán hasta la próxima ejecución.
						</p>
					</div>
					
					{{-- La tabla de resultados se inyectará aquí --}}
				</div>
			</div>
		</div>
		
	</div>
</div>

<script>
	// Configuración inicial de la lógica de la vista
	document.addEventListener('DOMContentLoaded', function () {
		const periodoSelect = document.getElementById('periodo_id_audit');
		const carreraSelect = document.getElementById('carrera_id_audit');
		const refreshButton = document.getElementById('btn-refrescar-auditoria');
		const buttonText = document.getElementById('button-text');
		const spinner = document.getElementById('loading-spinner');
		const conflictosContainer = document.getElementById('conflictos-container');
		const initialMessage = document.getElementById('loading-results-message');
		const ultimaEjecucion = document.getElementById('ultima-ejecucion');

		// --- FUNCIONES DE UTILIDAD ---

		function toggleLoading(isLoading, action) {
			refreshButton.disabled = isLoading;
			if (isLoading) {
				spinner.classList.remove('hidden');
				refreshButton.classList.add('cursor-not-allowed', 'bg-red-400');
				refreshButton.classList.remove('hover:bg-red-700');
				if (action === 'refresh') {
					buttonText.textContent = 'Procesando... No refresque la página';
				} else if (action === 'list') {
					// NUEVO CARGADOR: Tres puntos rebotando
					conflictosContainer.innerHTML = `
						<div class="flex items-end justify-center py-12 text-red-600 space-x-2">
							<div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
							<div class="w-3 h-3 bg-red-500 rounded-full animate-pulse delay-75"></div>
							<div class="w-3 h-3 bg-red-500 rounded-full animate-pulse delay-150"></div>
							<p class="ml-4 text-base font-medium text-gray-600">
								Cargando resultados de la auditoría...
							</p>
						</div>
					`;
				}
			} else {
				spinner.classList.add('hidden');
				refreshButton.classList.remove('cursor-not-allowed', 'bg-red-400');
				refreshButton.classList.add('hover:bg-red-700');
				buttonText.textContent = '🔁 Ejecutar Auditoría Ahora';
			}
		}

		// --- LÓGICA DE EVENTOS ---

		// Habilitar el botón si se selecciona un período
		periodoSelect.addEventListener('change', function() {
			if (periodoSelect.value) {
				refreshButton.disabled = false;
				// Intentar cargar la lista al cambiar el periodo (para ver la auditoría ya existente)
				// listAuditoria(); 
			} else {
				refreshButton.disabled = true;
				conflictosContainer.innerHTML = '';
				conflictosContainer.appendChild(initialMessage.cloneNode(true));
			}
		});
		
		carreraSelect.addEventListener('change', function() {
			// Recargar la lista de auditoría cada vez que cambia el filtro de carrera
			// if (periodoSelect.value) {
			//     listAuditoria();
			// }
		});


		// Evento al presionar el botón de refrescar (Paso 7 - Ejecución pesada)
		refreshButton.addEventListener('click', function () {
			// Implementar modal de confirmación aquí, NO USAR alert/confirm
			// Por simplicidad, se omite el modal aquí y se llama la función directamente.
			refrescarAuditoria();
		});


		// Función para ejecutar el análisis (Paso 7 y 8)
		function refrescarAuditoria() {
			const periodoId = periodoSelect.value;
			if (!periodoId) {
				console.error("Debe seleccionar un período.");
				return;
			}

			toggleLoading(true, 'refresh');

			fetch("{{ route('coordinador.auditoria.refrescar') }}", {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': '{{ csrf_token() }}' 
				},
				body: JSON.stringify({
					periodo_id: periodoId,
					// carrera_id: carreraSelect.value // Si la lógica de refresco se limita a una carrera
				})
			})
			.then(response => {
				if (!response.ok) {
					throw new Error('Error al ejecutar la auditoría.');
				}
				return response.json();
			})
			.then(data => {
				// Simulación de mostrar un mensaje de éxito
				console.log(data.message || 'Auditoría ejecutada con éxito.');
				
				// Actualizar la hora de ejecución
				const now = new Date();
				ultimaEjecucion.textContent = `Última ejecución: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`;

				// Después de refrescar, cargar los resultados (Paso 6)
				listAuditoria(); 
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Hubo un error al ejecutar la auditoría. Consulte la consola.');
				toggleLoading(false, 'refresh');
			});
		}

		// Función para listar los resultados (Paso 6)
		function listAuditoria() {
			const periodoId = periodoSelect.value;
			const carreraId = carreraSelect.value;
			if (!periodoId) return;

			toggleLoading(true, 'list');
			
			// Construir URL con parámetros de consulta
			const url = new URL("{{ route('coordinador.auditoria.listar') }}");
			url.searchParams.append('periodo_id', periodoId);
			url.searchParams.append('carrera_id', carreraId);

			fetch(url)
			.then(response => {
				if (!response.ok) {
					throw new Error('Error al listar resultados.');
				}
				// La respuesta debe ser una tabla HTML o un mensaje.
				return response.text(); 
			})
			.then(htmlContent => {
				conflictosContainer.innerHTML = htmlContent;
			})
			.catch(error => {
				conflictosContainer.innerHTML = `<p class="text-center py-10 text-red-600">
					Error al cargar los resultados: ${error.message}
				</p>`;
				console.error('Error listando auditoría:', error);
			})
			.finally(() => {
				toggleLoading(false, 'refresh'); // Desactivar el loading del botón de refrescar
			});
		}
	});
</script>
@endsection