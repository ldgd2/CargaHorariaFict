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

    {{-- Contenedor de Alerta para Toast/Notificaciones --}}
    <div id="alert-container" class="fixed top-4 right-4 z-[999]"></div>

    ---

    {{-- Contenedor Principal de la Tabla --}}
    <div class="bg-white rounded-xl shadow-xl overflow-x-auto">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                Listado de Ciclos por Carrera y Estado
            </h2>
            
            <div class="inline-block min-w-full align-middle">
                @if ($periodos_carrera->isEmpty())
                    <div class="text-center py-10 border border-gray-200 rounded-lg bg-gray-50">
                        <p class="text-lg text-gray-500">No hay ciclos listos para gestionar.</p>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                                    Periodo
                                </th>
                                <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">
                                    Carrera
                                </th>
                                <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                                    Estado
                                </th>
                                <th scope="col" class="relative px-3 sm:px-6 py-3 text-center w-1/4">
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($periodos_carrera as $item)
                                @php
                                    $estado_db = strtolower($item->estado);
                                @endphp
                                
                            <tr class="hover:bg-indigo-50/50 transition duration-100">
                                <td class="px-3 sm:px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $item->periodo }}
                                </td>
                                <td class="px-3 sm:px-6 py-4 text-sm text-gray-600 max-w-xs">
                                    {{ $item->carrera }}
                                </td>
                                <td class="px-3 sm:px-6 py-4 text-sm">
                                    @if ($estado_db === 'publicado')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Publicado
                                        </span>
                                    @elseif ($estado_db === 'borrador')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Borrador
                                        </span>
                                    @elseif ($estado_db === 'reabierto') 
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Reabierto
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $item->estado }}
                                        </span>
                                    @endif
                                </td>
                                
                                {{-- COLUMNA DE ACCIONES --}}
                                <td class="px-3 sm:px-6 py-4 text-center text-sm font-medium">
                                    <div class="flex flex-col sm:flex-row items-center justify-end sm:justify-center space-y-2 sm:space-y-0 sm:space-x-2">
                                        
                                        {{-- LÓGICA CU10: PUBLICAR --}}
                                        @if (($is_coordinator || $is_admin) && ($estado_db === 'borrador' || $estado_db === 'reabierto'))
                                            <button type="button" 
                                                onclick="showConfirmModal({{ $item->id }}, '{{ $item->periodo }}', '{{ $item->carrera }}', 'publish')"
                                                class="w-full sm:w-auto text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-xs px-3 py-2 text-center transition duration-150 shadow-md">
                                                ✅ Publicar <span class="text-xs text-gray-200">(CU10)</span>
                                            </button>
                                        @endif

                                        {{-- LÓGICA CU11: REABRIR --}}
                                        @if (($is_coordinator || $is_admin) && $estado_db === 'publicado')
                                            <button type="button" 
                                                onclick="showConfirmModal({{ $item->id }}, '{{ $item->periodo }}', '{{ $item->carrera }}', 'reopen')"
                                                class="w-full sm:w-auto text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-xs px-3 py-2 text-center transition duration-150 shadow-md">
                                                🔄 Reabrir <span class="text-xs text-gray-200">(CU11)</span>
                                            </button>
                                        @endif

                                        {{-- Casos sin permisos / Acción no disponible --}}
                                        @if (!$is_admin && !$is_coordinator)
                                            <span class="text-gray-400 text-xs">Sin permisos</span>
                                        @elseif (
                                            !(($is_coordinator || $is_admin) && ($estado_db === 'borrador' || $estado_db === 'reabierto')) && 
                                            !(($is_coordinator || $is_admin) && $estado_db === 'publicado')
                                        )
                                            <span class="text-gray-400 text-xs">Acción no disponible</span>
                                        @endif
                                    </div>
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

{{-- MODAL DE CONFIRMACIÓN --}}
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

                        {{-- GRUPO DE MOTIVOS (CU11) --}}
                        {{-- CRÍTICO: Aseguramos la clase 'hidden' en el HTML. Controlado por JS para CU11 --}}
                        <div id="motive-form-group" class="mt-4 flex-col hidden"> 
                            
                            <label class="block text-sm font-medium text-gray-700 mb-2">Seleccione el motivo de Reapertura (CU11):</label>
                            
                            <div class="space-y-2">
                                {{-- Opciones de Motivo como Radio Buttons --}}
                                @php
                                    $motivos = [
                                        'Cambio de Docente' => 'Cambio o Asignación de Docente',
                                        'Ajuste de Capacidad' => 'Ajuste de capacidad de aula/cupos',
                                        'Correccion de Horario' => 'Corrección de horario o aula (error de tipeo)',
                                        'Solicitud de Coordinacion' => 'Aprobación o solicitud de la coordinación',
                                        'Otro motivo' => 'Otro motivo (especificar abajo)'
                                    ];
                                @endphp

                                @foreach ($motivos as $value => $label)
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-red-50 transition duration-150">
                                    <input type="radio" 
                                           name="reopen_motive" 
                                           value="{{ $value }}" 
                                           class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                           onchange="handleMotiveChange(this.value)">
                                    <span class="ml-3 text-sm font-medium text-gray-700">{{ $label }}</span>
                                </label>
                                @endforeach

                            </div>

                            {{-- Campo de Texto Libre Opcional (Oculto por defecto) --}}
                            <div id="custom-motive-group" class="mt-4 hidden">
                                <label for="custom_motive" class="block text-sm font-medium text-gray-700">Especifique el motivo (mínimo 10 caracteres):</label>
                                <textarea id="custom_motive" rows="2" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" 
                                          placeholder="Detalle el motivo específico..."
                                ></textarea>
                            </div>

                            {{-- CRÍTICO: Este mensaje de error también es ocultado explícitamente por JS --}}
                            <p id="motive-error" class="mt-1 text-sm text-red-500 hidden">Debe seleccionar un motivo válido para continuar.</p>
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

{{-- CÓDIGO JAVASCRIPT CORREGIDO (Versión 4.0) --}}
<script>
    let currentAction = {}; 

    function toggleButtonLoading(isLoading, actionType) {
        const confirmButton = document.getElementById('confirm-button');
        const defaultText = actionType === 'publish' ? 'Confirmar Publicación' : 'Confirmar Reapertura';
        const loadingText = actionType === 'publish' ? ' Publicando...' : ' Reabriendo...';
        
        const spinnerSvg = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        
        if (isLoading) {
            confirmButton.disabled = true;
            if (confirmButton.textContent !== 'Confirmar' && confirmButton.textContent !== 'Cancelar') {
                confirmButton.innerHTML = spinnerSvg + loadingText;
            }
            confirmButton.classList.add('cursor-not-allowed', 'opacity-75');
        } else {
            confirmButton.disabled = false;
            confirmButton.innerHTML = actionType === 'publish' ? 'Confirmar Publicación' : 'Confirmar Reapertura';
            confirmButton.classList.remove('cursor-not-allowed', 'opacity-75');
        }
    }

    function showConfirmModal(id, periodo, carrera, actionType) {
        const modal = document.getElementById('action-modal');
        const title = document.getElementById('modal-title');
        const message = document.getElementById('modal-message');
        const confirmButton = document.getElementById('confirm-button');
        const motiveFormGroup = document.getElementById('motive-form-group');
        const modalIconContainer = document.getElementById('modal-icon-container');
        const customMotiveGroup = document.getElementById('custom-motive-group');
        const radioButtons = document.getElementsByName('reopen_motive');
        const motiveError = document.getElementById('motive-error'); // Capturamos el error

        currentAction = { id_periodo: id, periodo, carrera, actionType }; 
        
        // **1. RESET DE ESTADO Y LIMPIEZA DE FORMULARIO (CRÍTICO)**

        motiveFormGroup.classList.add('hidden'); 
        motiveError.classList.add('hidden'); 

        radioButtons.forEach(radio => radio.checked = false); 
        if (customMotiveGroup) {
            customMotiveGroup.classList.add('hidden');
            customMotiveGroup.querySelector('textarea').value = ''; 
        }
        
        modalIconContainer.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
        confirmButton.classList.remove('bg-indigo-600', 'bg-red-600', 'hover:bg-indigo-700', 'hover:bg-red-700');
        toggleButtonLoading(false, actionType); 

        // **2. LÓGICA POR CASO DE USO**

        if (actionType === 'publish') {
            // CU10: Publicar
            title.textContent = 'Publicar Ciclo Académico (CU10)';
            message.innerHTML = `Está a punto de **PUBLICAR** los horarios de **${carrera}** para el período **${periodo}**.`;
            
            modalIconContainer.classList.add('bg-indigo-100');
            modalIconContainer.innerHTML = '<svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            
            // Permanece oculto por el paso 1.
            
            confirmButton.textContent = 'Confirmar Publicación';
            confirmButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');

        } else if (actionType === 'reopen') {
            // CU11: Reabrir
            title.textContent = 'Reabrir Ciclo Publicado (CU11)';
            message.innerHTML = `Está a punto de **REABRIR** los horarios de **${carrera}** para el período **${periodo}**. Se requerirá un motivo.`;
            
            modalIconContainer.classList.add('bg-red-100');
            modalIconContainer.innerHTML = '<svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';

            // CRÍTICO: Mostrar el campo de motivo SOLO para Reabrir
            motiveFormGroup.classList.remove('hidden'); 

            confirmButton.textContent = 'Confirmar Reapertura';
            confirmButton.classList.add('bg-red-600', 'hover:bg-red-700');
        }

        modal.classList.remove('hidden');
    }

    function hideModal() {
        document.getElementById('action-modal').classList.add('hidden');
        document.getElementById('motive-error').classList.add('hidden');
        document.getElementById('motive-form-group').classList.add('hidden');
        const customMotiveGroup = document.getElementById('custom-motive-group');
        if (customMotiveGroup) customMotiveGroup.classList.add('hidden');
    }

    function handleMotiveChange(selectedValue) {
        const customMotiveGroup = document.getElementById('custom-motive-group');
        const motiveError = document.getElementById('motive-error');
        
        if (selectedValue === 'Otro motivo') {
            customMotiveGroup.classList.remove('hidden');
            customMotiveGroup.querySelector('textarea').focus(); 
        } else {
            customMotiveGroup.classList.add('hidden');
        }
        motiveError.classList.add('hidden'); 
    }

    document.getElementById('confirm-button').addEventListener('click', function() {
        const { id_periodo, periodo, carrera, actionType } = currentAction; 

        if (actionType === 'reopen') {
            const radioButtons = document.getElementsByName('reopen_motive');
            const motiveError = document.getElementById('motive-error');
            const customMotiveInput = document.getElementById('custom_motive');
            
            let motive = '';
            for (const radio of radioButtons) {
                if (radio.checked) {
                    motive = radio.value;
                    break;
                }
            }

            // 1. Validación de selección
            if (!motive) {
                motiveError.textContent = 'Debe seleccionar un motivo válido para continuar.';
                motiveError.classList.remove('hidden');
                return; 
            }
            
            // 2. Validación de motivo personalizado
            if (motive === 'Otro motivo') {
                motive = customMotiveInput.value.trim();
                if (motive.length < 10) {
                    motiveError.textContent = 'Debe especificar el motivo (mínimo 10 caracteres).';
                    motiveError.classList.remove('hidden');
                    customMotiveInput.focus();
                    return;
                }
            }

            motiveError.classList.add('hidden');
            
            // Si pasa la validación, ejecuta la acción
            performReopen(id_periodo, periodo, carrera, motive);

        } else if (actionType === 'publish') {
            performPublish(id_periodo, periodo, carrera);
        }
    });

    // Función de Publicación (CU10)
    async function performPublish(id_periodo, periodo, carrera) {
        toggleButtonLoading(true, 'publish');
        hideModal(); 
        
        try {
            const url = '{{ route("coordinador.gestion_ciclo.publicar") }}';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify({
                    id_periodo: id_periodo,
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Error ${response.status} al publicar.`);
            }

            const data = await response.json();
            alertUser(`✅ Éxito: Ciclo de ${carrera} (${periodo}) Publicado.`, 'success');
            
            setTimeout(() => window.location.reload(), 1000); 

        } catch (error) {
            console.error('[Publicación Error]:', error);
            alertUser(`❌ Error: No se pudo publicar el ciclo. ${error.message || 'Error de conexión/servidor.'}`, 'error');
        } finally {
            toggleButtonLoading(false, 'publish'); 
        }
    }

    // Función de Reapertura (CU11)
    async function performReopen(id_periodo, periodo, carrera, motive) {
        toggleButtonLoading(true, 'reopen');
        hideModal(); 
        
        try {
            const url = '{{ route("coordinador.gestion_ciclo.reabrir") }}';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify({
                    id_periodo: id_periodo, 
                    motivo: motive, // Se asegura que el motivo se envíe
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Error ${response.status} al reabrir.`);
            }

            const data = await response.json();
            alertUser(`🔄 Éxito: Ciclo de ${carrera} (${periodo}) Reabierto.`, 'success');
            
            setTimeout(() => window.location.reload(), 1000); 

        } catch (error) {
            console.error('[Reapertura Error]:', error);
            alertUser(`❌ Error: No se pudo reabrir el ciclo. ${error.message || 'Error de conexión/servidor.'}`, 'error');
        } finally {
            toggleButtonLoading(false, 'reopen');
        }
    }

    // Función de Toast personalizada
    function alertUser(message, type = 'success') {
        const container = document.getElementById('alert-container');
        const toast = document.createElement('div');
        let bgColor = '';
        let icon = '';

        if (type === 'success') {
            bgColor = 'bg-green-600';
            icon = '✅';
        } else if (type === 'error') {
            bgColor = 'bg-red-600';
            icon = '❌';
        }

        toast.className = `${bgColor} text-white p-4 rounded-lg shadow-xl transition-opacity duration-300 transform translate-y-0 opacity-100 max-w-sm flex items-center mt-2`;
        toast.innerHTML = `<span class="text-xl mr-2">${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
</script>
@endsection