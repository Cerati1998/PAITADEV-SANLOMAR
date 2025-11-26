<!-- Modal de Resumen -->
@if ($showSummaryModal && !empty($summaryData))
    <x-wire-modal wire:model="showSummaryModal" max-width="2xl">
        <x-wire-card>
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-800">Resumen</h3>
                </div>
            </x-slot>

            <!-- Header con info de la caja -->
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-lg font-bold text-blue-900">{{ $summaryData['caja']['correlativo'] }}</h4>
                    @if ($summaryData['caja']['estado'] === 'abierta')
                        <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">ABIERTA</span>
                    @else
                        <span class="px-3 py-1 bg-gray-500 text-white text-xs font-bold rounded-full">CERRADA</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-blue-700">Apertura:</span>
                        <span class="font-medium text-blue-900">{{ $summaryData['caja']['fecha_inicio'] }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Abierta por:</span>
                        <span class="font-medium text-blue-900">{{ $summaryData['caja']['abierta_por'] }}</span>
                    </div>
                    @if ($summaryData['caja']['fecha_fin'])
                        <div>
                            <span class="text-blue-700">Cierre:</span>
                            <span class="font-medium text-blue-900">{{ $summaryData['caja']['fecha_fin'] }}</span>
                        </div>
                        <div>
                            <span class="text-blue-700">Cerrada por:</span>
                            <span class="font-medium text-blue-900">{{ $summaryData['caja']['cerrada_por'] }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{--   <!-- Total de Depósitos (Destacado) -->
        <div class="mb-6 p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border-2 border-green-300">
            <p class="text-sm text-green-700 font-medium mb-2">Total de Depósitos</p>
            <h2 class="text-4xl font-bold text-green-800">
                S/{{ number_format($summaryData['depositos']['total'], 2) }}
            </h2>
        </div> --}}

            <!-- Detalles de la Caja -->
            <div class="space-y-3 mb-6">
                <!-- Saldo Heredado -->
                @if (!empty($summaryData['caja']['saldo_heredado']) && $summaryData['caja']['saldo_heredado'] > 0)
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <span class="text-gray-700 font-medium">Saldo heredado</span>
                        <span class="text-lg font-bold text-green-700">
                            S/{{ number_format($summaryData['caja']['saldo_heredado'], 2) }}
                        </span>
                    </div>
                @endif
                <!-- Monto Inicial -->
                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Monto inicial</span>
                    <span
                        class="text-lg font-bold text-gray-900">S/{{ number_format($summaryData['caja']['monto_inicial'], 2) }}</span>
                </div>

                <!-- Depósitos por Tipo -->
                @if (!empty($summaryData['depositos']['por_tipo']))
                    <div class="py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 font-medium">Depósitos</span>
                        </div>
                        <div class="pl-4 space-y-2">
                            @foreach ($summaryData['depositos']['por_tipo'] as $deposito)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-green-600">
                                        {{ $deposito['label'] }}:
                                        <span class="text-gray-500">({{ $deposito['cantidad'] }})</span>
                                    </span>
                                    <span class="font-semibold text-green-700">
                                        S/{{ number_format($deposito['monto'], 2) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Detalle de Gastos -->
                <!-- Detalle de Gastos -->
                @if (!empty($summaryData['gastos']['detalle']))
                    <div class="py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 font-medium">Gastos</span>
                            <span class="text-sm text-red-700 font-bold">
                                Total: S/{{ number_format($summaryData['gastos']['total'], 2) }}
                            </span>
                        </div>

                        <!-- Contenedor con scroll cuando hay demasiados gastos -->
                        <div class="pl-4 space-y-2 max-h-56 overflow-y-auto pr-2">

                            @foreach ($summaryData['gastos']['detalle'] as $gasto)
                                <div class="flex items-center justify-between text-sm border-b pb-1">

                                    <!-- Producto o Descripción -->
                                    <span class="text-gray-700">
                                        {{ $gasto['producto'] ?? ($gasto['descripcion'] ?? 'Sin detalle') }}
                                        <span class="text-gray-400 text-xs">({{ $gasto['fecha'] }})</span>
                                    </span>

                                    <!-- Monto -->
                                    <span class="font-semibold text-red-700">
                                        S/{{ number_format($gasto['monto'], 2) }}
                                    </span>
                                </div>
                            @endforeach

                        </div>
                    </div>
                @else
                    <div class="py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium">Gastos</span>
                            <span class="text-gray-400 text-sm">Sin gastos registrados</span>
                        </div>
                    </div>
                @endif

                <!-- Cantidad de registros -->
                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Cantidad de depósitos</span>
                    <span class="text-lg font-bold text-gray-900">{{ $summaryData['depositos']['cantidad'] }}</span>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Cantidad de gastos</span>
                    <span class="text-lg font-bold text-gray-900">{{ $summaryData['gastos']['cantidad'] }}</span>
                </div>

                <!-- Saldo Calculado -->
                <div class="flex items-center justify-between py-4 bg-blue-50 rounded-lg px-4 mt-4">
                    <span class="text-gray-700 font-bold">Saldo Calculado</span>
                    <span class="text-2xl font-bold text-blue-700">
                        S/{{ number_format($summaryData['saldo_calculado'], 2) }}
                    </span>
                </div>

                <!-- Total (si la caja está cerrada) -->
                @if ($summaryData['caja']['estado'] === 'cerrada' && $summaryData['caja']['monto_final'])
                    <div class="py-4 bg-gray-100 rounded-lg px-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 font-bold">Monto Final (Contado)</span>
                            <span class="text-2xl font-bold text-gray-900">
                                S/{{ number_format($summaryData['caja']['monto_final'], 2) }}
                            </span>
                        </div>

                        @if (abs($summaryData['diferencia']) > 0.01)
                            <div class="mt-3 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium {{ $summaryData['diferencia'] > 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $summaryData['diferencia'] > 0 ? 'Sobrante' : 'Faltante' }}
                                    </span>
                                    <span
                                        class="text-lg font-bold {{ $summaryData['diferencia'] > 0 ? 'text-green-700' : 'text-red-700' }}">
                                        S/{{ number_format(abs($summaryData['diferencia']), 2) }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Nota explicativa -->
            <div class="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    El <strong>saldo calculado</strong> se obtiene sumando el monto inicial más los depósitos menos los
                    gastos.
                    @if ($summaryData['caja']['estado'] === 'cerrada')
                        El <strong>monto final</strong> es el efectivo contado al cierre de caja.
                    @endif
                </p>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end">
                    <x-wire-button flat label="Cerrar" x-on:click="close" />
                </div>
            </x-slot>
        </x-wire-card>
    </x-wire-modal>
@endif
