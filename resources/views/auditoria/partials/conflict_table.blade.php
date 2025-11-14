{{-- Este archivo es cargado por listarAuditoria, y recibe la variable $conflictos (Collection/Array) --}}

<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            
            @if($conflictos->isEmpty())
                <div class="text-center py-10 border border-gray-200 rounded-lg">
                    <svg class="w-10 h-10 mx-auto text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">¡Auditoría Limpia!</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        No se encontraron conflictos de carga horaria en el periodo seleccionado.
                    </p>
                </div>
            @else
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Tipo Conflicto</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Docente / Módulo</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Detalles</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($conflictos as $conflicto)
                                <tr class="hover:bg-yellow-50/50">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-red-600 sm:pl-6">
                                        {{ $conflicto->tipo_conflicto ?? 'Desconocido' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $conflicto->docente_nombre ?? 'N/A' }} / {{ $conflicto->modulo_codigo ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $conflicto->mensaje ?? 'Sin detalles' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $conflicto->created_at->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</div>