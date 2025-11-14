@extends('layouts.app')
@section('title', 'Gestión de Publicación y Reapertura')

@section('content')
<div class="app-container p-4 sm:p-6 lg:p-8 min-h-screen bg-gray-50">

    <h1 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-3 flex items-center">
        📅 Gestión de Publicación de Ciclos
    </h1>

    <p class="text-sm text-gray-600 mb-8">
        Esta vista lista los ciclos (períodos académicos) y permite al usuario **Publicar** (CU10) o **Reabrir** (CU11) sus estados.
        La información se extrae de las tablas `periodo_academico` y `carrera`.
    </p>

    ---

    {{-- Contenedor Principal de la Tabla --}}
    <div class="bg-white rounded-xl shadow-xl overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                Listado de Ciclos por Carrera y Estado
            </h2>
            
            <div class="overflow-x-auto">
                {{-- Verificación: Asegura que $periodos_carrera no esté vacía antes de intentar iterar --}}
                @if ($periodos_carrera->isEmpty())
                    <div class="text-center py-10 border border-gray-200 rounded-lg bg-gray-50">
                        <p class="text-lg text-gray-500">No hay ciclos listos para gestionar.</p>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Periodo
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Carrera
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($periodos_carrera as $item)
                            <tr class="hover:bg-indigo-50/50 transition duration-100">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $item->periodo }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $item->carrera }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{-- Colores y Texto según el nuevo esquema de estados --}}
                                    @if ($item->estado === 'Publicado')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Publicado
                                        </span>
                                    @elseif ($item->estado === 'Borrador')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Listo para Publicar
                                        </span>
                                    @elseif ($item->estado === 'Reabierto')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Reabierto (En Edición)
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $item->estado }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    
                                    {{-- LÓGICA CU10: PUBLICAR (Permitido para Coordinador O Administrador, si el estado es 'Borrador' o 'Reabierto') --}}
                                    @if (($is_coordinator || $is_admin) && ($item->estado === 'Borrador' || $item->estado === 'Reabierto'))
                                        <button type="button" 
                                            onclick="showConfirmModal({{ $item->id }}, '{{ $item->periodo }}', '{{ $item->carrera }}', 'publish')"
                                            class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-xs px-3 py-2 text-center transition duration-150 shadow-md">
                                            ✅ Publicar Horario (CU10)
                                        </button>
                                    @endif

                                    {{-- LÓGICA CU11: REABRIR (Permitido SOLO para Administrador, si el estado es 'Publicado') --}}
                                    @if ($is_admin && $item->estado === 'Publicado')
                                        <button type="button" 
                                            onclick="showConfirmModal({{ $item->id }}, '{{ $item->periodo }}', '{{ $item->carrera }}', 'reopen')"
                                            class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-xs px-3 py-2 text-center ml-2 transition duration-150 shadow-md">
                                            🔄 Reabrir Horario (CU11)
                                        </button>
                                    @endif

                                    @if (!$is_admin && !$is_coordinator)
                                         <span class="text-gray-400 text-xs">Sin permisos</span>
                                    @elseif ($item->estado !== 'Borrador' && $item->estado !== 'Publicado' && $item->estado !== 'Reabierto')
                                         <span class="text-gray-400 text-xs">Acción no disponible</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

---

{{-- PUNTO CLAVE 5: MODAL DE CONFIRMACIÓN (Sin cambios) --}}
<div id="action-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div id="modal-icon-container" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        {{-- Icono se inyectará aquí (Publicar o Reabrir) --}}
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Confirmar Acción
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-message">
                                ¿Está seguro que desea realizar esta acción?
                            </p>
                        </div>

                        {{-- PUNTO CLAVE 6: Campo para el Motivo (CU11 - Se registra en `reapertura_historial`) --}}
                        <div id="motive-form-group" class="mt-4 hidden">
                            <label for="reopen_motive" class="block text-sm font-medium text-gray-700">Motivo de Reapertura (CU11 - Obligatorio):</label>
                            <textarea id="reopen_motive" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" placeholder="Detalle la razón por la que el horario debe reabrirse para edición..."></textarea>
                            <p id="motive-error" class="mt-1 text-sm text-red-500 hidden">Debe ingresar un motivo para reabrir el horario.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirm-button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm">
                    Confirmar
                </button>
                <button type="button" onclick="hideModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

---

{{-- PUNTO CLAVE 7: CÓDIGO JAVASCRIPT (Sin cambios) --}}
<script>
    let currentAction = {}; 

    function toggleButtonLoading(isLoading, actionType) {
        const confirmButton = document.getElementById('confirm-button');
        // SVG spinner
        const spinnerSvg = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        
        if (isLoading) {
            confirmButton.disabled = true;
            confirmButton.innerHTML = (actionType === 'publish' ? spinnerSvg + ' Publicando...' : spinnerSvg + ' Reabriendo...');
            confirmButton.classList.add('cursor-not-allowed', 'opacity-75');
        } else {
            confirmButton.disabled = false;
            confirmButton.innerHTML = (actionType === 'publish' ? 'Confirmar Publicación' : 'Confirmar Reapertura');
            confirmButton.classList.remove('cursor-not-allowed', 'opacity-75');
        }
    }

    function showConfirmModal(id, periodo, carrera, actionType) {
        const modal = document.getElementById('action-modal');
        const title = document.getElementById('modal-title');
        const message = document.getElementById('modal-message');
        const confirmButton = document.getElementById('confirm-button');
        const motiveFormGroup = document.getElementById('motive-form-group');
        const motiveInput = document.getElementById('reopen_motive');
        const modalIconContainer = document.getElementById('modal-icon-container');

        // El ID pasado es el id_periodo, usado para la acción
        currentAction = { id_periodo: id, periodo, carrera, actionType }; 
        motiveInput.value = ''; 
        
        modalIconContainer.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
        motiveFormGroup.classList.add('hidden');
        confirmButton.classList.remove('bg-indigo-600', 'bg-red-600', 'hover:bg-indigo-700', 'hover:bg-red-700', 'cursor-not-allowed', 'opacity-75');
        toggleButtonLoading(false, actionType); 

        if (actionType === 'publish') {
            // CU10: Cambia el estado de `periodo_academico.estado_publicacion` a 'Publicado'
            title.textContent = 'Publicar Ciclo Académico (CU10)';
            message.innerHTML = `Está a punto de **PUBLICAR** los horarios de **${carrera}** para el período **${periodo}**. El estado en DB cambiará a 'Publicado'.`;
            
            modalIconContainer.classList.add('bg-indigo-100');
            modalIconContainer.innerHTML = '<svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            
            confirmButton.textContent = 'Confirmar Publicación';
            confirmButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');

        } else if (actionType === 'reopen') {
            // CU11: Cambia el estado de `periodo_academico.estado_publicacion` a 'Reabierto' y registra en `reapertura_historial`
            title.textContent = 'Reabrir Ciclo Publicado (CU11)';
            message.innerHTML = `Está a punto de **REABRIR** los horarios de **${carrera}** para el período **${periodo}**. Esto permitirá modificaciones.`;
            
            modalIconContainer.classList.add('bg-red-100');
            modalIconContainer.innerHTML = '<svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.962 8.962 0 0120 12c0 2.47-.9 4.7-2.344 6.31M4 12a8.962 8.962 0 011.06-4.577M18 4h.582m-15.356 2A8.962 8.962 0 0120 12c0 2.47-.9 4.7-2.344 6.31M4 12a8.962 8.962 0 011.06-4.577" /></svg>';
            
            motiveFormGroup.classList.remove('hidden');
            document.getElementById('motive-error').classList.add('hidden');
            
            confirmButton.textContent = 'Confirmar Reapertura';
            confirmButton.classList.add('bg-red-600', 'hover:bg-red-700');
        }

        modal.classList.remove('hidden');
    }

    function hideModal() {
        document.getElementById('action-modal').classList.add('hidden');
        document.getElementById('motive-error').classList.add('hidden');
    }

    document.getElementById('confirm-button').addEventListener('click', function() {
        // Usamos id_periodo
        const { id_periodo, periodo, carrera, actionType } = currentAction; 

        if (actionType === 'reopen') {
            const motiveInput = document.getElementById('reopen_motive');
            const motive = motiveInput.value.trim();

            if (motive === '') {
                // Flujo Alternativo A (CU11) - Validación del motivo
                document.getElementById('motive-error').classList.remove('hidden');
                return; 
            }
            
            performReopen(id_periodo, periodo, carrera, motive);

        } else if (actionType === 'publish') {
            performPublish(id_periodo, periodo, carrera);
        }
    });

    // Función de Publicación (CU10)
    async function performPublish(id_periodo, periodo, carrera) {
        toggleButtonLoading(true, 'publish');
        
        try {
            // Petición a la ruta de publicación
            const response = await fetch('{{ route("coordinador.gestion_ciclo.publicar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Token CSRF Obligatorio
                },
                body: JSON.stringify({
                    id_periodo: id_periodo, // Enviar id_periodo
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Error ${response.status} al publicar.`);
            }

            const data = await response.json();
            alertUser(`✅ Éxito: Ciclo de ${carrera} (${periodo}) Publicado. Estado en DB: 'Publicado'.`, 'success');
            // Recargar la página para ver el cambio de estado
            setTimeout(() => window.location.reload(), 1000); 

        } catch (error) {
            console.error('[Publicación Error]:', error);
            alertUser(`❌ Error: No se pudo publicar el ciclo. ${error.message}`, 'error');
        } finally {
            toggleButtonLoading(false, 'publish');
            // Mantener el modal oculto si hubo error
            // hideModal(); // Se comenta para que el usuario vea el toast de error si es necesario
        }
    }

    // Función de Reapertura (CU11)
    async function performReopen(id_periodo, periodo, carrera, motive) {
        toggleButtonLoading(true, 'reopen');
        
        try {
            // Petición a la ruta de reapertura
            const response = await fetch('{{ route("coordinador.gestion_ciclo.reabrir") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Token CSRF Obligatorio
                },
                body: JSON.stringify({
                    id_periodo: id_periodo, // Enviar id_periodo
                    motivo: motive, // Se guarda en `reapertura_historial`
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Error ${response.status} al reabrir.`);
            }

            const data = await response.json();
            alertUser(`🔄 Éxito: Ciclo de ${carrera} (${periodo}) Reabierto. Estado en DB: 'Reabierto'.`, 'success');
            // Recargar la página para ver el cambio de estado
            setTimeout(() => window.location.reload(), 1000); 

        } catch (error) {
            console.error('[Reapertura Error]:', error);
            alertUser(`❌ Error: No se pudo reabrir el ciclo. ${error.message}`, 'error');
        } finally {
            toggleButtonLoading(false, 'reopen');
            // Mantener el modal oculto si hubo error
            // hideModal(); // Se comenta para que el usuario vea el toast de error si es necesario
        }
    }

    // Función de Toast personalizada (reemplaza alert())
    function alertUser(message, type = 'success') {
        const toast = document.createElement('div');
        let bgColor = '';
        let icon = '';

        if (type === 'success') {
            bgColor = 'bg-green-500';
            icon = '✅';
        } else if (type === 'error') {
            bgColor = 'bg-red-600';
            icon = '❌';
        }

        toast.className = `fixed top-4 right-4 ${bgColor} text-white p-4 rounded-lg shadow-xl transition-opacity duration-300 transform translate-y-0 opacity-100 max-w-sm flex items-center z-50`;
        toast.innerHTML = `<span class="text-xl mr-2">${icon}</span> <span>${message}</span>`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
</script>
@endsection