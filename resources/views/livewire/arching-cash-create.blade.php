<div x-data="dataArchingCreate">
    <form wire:submit="save">
        <x-wire-modal-card title="Aperturar Caja Chica" name="archingCreate" width="4xl">

            <x-validation-errors class="mb-4" />

            <!-- Alerta de caja abierta -->
            @if($this->existeCajaAbierta)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-2 text-red-800">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="font-semibold">Ya existe una caja abierta para esta sucursal</span>
                </div>
                <p class="mt-1 text-sm text-red-700">Debe cerrar la caja actual antes de abrir una nueva.</p>
            </div>
            @endif

            <!-- Información de caja anterior -->
            @if($this->ultimaCaja)
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-blue-900">Información de Caja Anterior</h4>
                        <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-blue-700">Correlativo:</span>
                                <span class="font-medium text-blue-900">{{ $this->ultimaCaja->correlativo }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700">Cerrada:</span>
                                <span class="font-medium text-blue-900">
                                    {{ $this->ultimaCaja->fecha_fin?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700">Monto Inicial:</span>
                                <span class="font-medium text-blue-900">
                                    S/ {{ number_format($this->ultimaCaja->monto_inicial, 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700">Saldo Final:</span>
                                <span class="font-medium text-green-700">
                                    S/ {{ number_format($this->ultimaCaja->monto_final, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <!-- Fecha de Inicio -->
                <div class="col-span-2 sm:col-span-1">
                    <x-wire-datetime-picker label="Fecha de Inicio *" wire:model="fecha_inicio"
                        placeholder="Seleccione fecha y hora" :max="now()->setTimezone('America/Lima')->format('Y-m-d')" required />
                </div>

                <!-- Sucursal (readonly) -->
                <div class="col-span-2 sm:col-span-1">
                    <x-label class="mb-1">Sucursal</x-label>
                    <x-input value="{{ session('branch')->name ?? 'N/A' }}" class="w-full bg-gray-50" disabled />
                </div>

                <!-- Opción de heredar saldo -->
                @if($this->ultimaCaja)
                <div class="col-span-2">
                    <div
                        class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3">
                            <x-checkbox wire:model.live="heredar_saldo" id="heredar_saldo" />
                            <label for="heredar_saldo" class="cursor-pointer">
                                <span class="font-medium text-gray-900">Heredar saldo de caja anterior</span>
                                <span class="block text-sm text-gray-600">
                                    Saldo disponible: S/ {{ number_format($this->ultimaCaja->monto_final, 2) }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Saldo Heredado (readonly) -->
                <div class="col-span-2 sm:col-span-1">
                    <x-label class="mb-1">Saldo Heredado</x-label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">S/</span>
                        <input type="text" value="{{ number_format($this->saldoHeredado, 2) }}"
                            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 font-medium"
                            disabled />
                    </div>
                </div>

                <!-- Depósito Inicial -->
                <div class="col-span-2 sm:col-span-1">
                    <x-wire-currency label="Depósito Inicial *" wire:model.live="deposito_inicial"
                        placeholder="0.00" prefix="S/" thousands="," decimal="." precision="2" required />
                </div>

                <!-- Monto Inicial Total (calculado) -->
                <div class="col-span-2">
                    <div class="p-4 bg-green-50 border-2 border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-green-800">Monto Inicial Total</span>
                                <p class="text-xs text-green-600 mt-1">Saldo heredado + Depósito inicial</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-green-700">
                                    S/ {{ number_format($this->montoInicial, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="col-span-2">
                    <x-wire-textarea label="Observaciones" wire:model="observaciones"
                        placeholder="Ingrese observaciones adicionales (opcional)" rows="3" />
                </div>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Aperturar Caja" spinner="save"
                    wire:loading.attr="disabled" wire:target="save" icon="lock-open"
                    :disabled="$this->existeCajaAbierta" />
            </x-slot>

        </x-wire-modal-card>
    </form>

    @push('js')
        <script>
            function dataArchingCreate() {
                return {
                    init() {
                        // Escuchar evento de cierre del modal
                        Livewire.on('close-modal', (modalName) => {
                            if (modalName === 'archingCreate') {
                                this.close();
                            }
                        });
                    }
                }
            }
        </script>
    @endpush
</div>