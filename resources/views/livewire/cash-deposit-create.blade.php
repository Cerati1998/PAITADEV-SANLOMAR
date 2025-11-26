<div x-data="dataCashDeposit">
    <form wire:submit="save">
        <x-wire-modal-card title="Registrar Depósito" name="depositCreate" wire:model="openModal" width="3xl">

            <x-validation-errors class="mb-4" />

            <!-- Información de la Caja -->
            @if($archingCash)
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-blue-900">Caja: {{ $archingCash->correlativo }}</h4>
                        <div class="mt-2 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <span class="text-blue-700">Monto Inicial:</span>
                                <span class="font-medium text-blue-900">
                                    S/ {{ number_format($archingCash->monto_inicial, 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700">Total Salidas:</span>
                                <span class="font-medium text-red-600">
                                    S/ {{ number_format($archingCash->total_salidas, 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700">Saldo Actual:</span>
                                <span class="font-medium text-green-700">
                                    S/ {{ number_format($archingCash->monto_inicial - $archingCash->total_salidas + $archingCash->total_ingresos, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <!-- Tipo de Depósito -->
                <div class="col-span-2 sm:col-span-1">
                    <x-wire-select label="Tipo de Depósito *" wire:model.live="tipo_deposito" placeholder="Seleccione">
                        <x-wire-select.option value="adicional" label="Depósito Adicional" />
                        <x-wire-select.option value="reembolso" label="Reembolso" />
                    </x-wire-select>
                </div>

                <!-- Moneda -->
                <div class="col-span-2 sm:col-span-1">
                    <x-wire-select label="Moneda *" wire:model="moneda" placeholder="Seleccione">
                        <x-wire-select.option value="PEN" label="PEN - Soles" />
                        <x-wire-select.option value="USD" label="USD - Dólares" />
                    </x-wire-select>
                </div>

                <!-- Monto -->
                <div class="col-span-2 sm:col-span-1">
                    <x-wire-currency label="Monto *" wire:model="monto" placeholder="0.00" 
                        :prefix="$moneda === 'PEN' ? 'S/' : '$'" thousands="," decimal="." precision="2" required />
                </div>

                <!-- Fecha de Depósito -->
                <div class="col-span-2 sm:col-span-1">
                    <x-wire-datetime-picker label="Fecha de Depósito *" wire:model="fecha_deposito"
                        placeholder="Seleccione fecha y hora" :max="now()->setTimezone('America/Lima')->format('Y-m-d')" required />
                </div>

                <!-- Método de Pago -->
                <div class="col-span-2">
                    <x-wire-select label="Método de Pago *" wire:model.live="metodo" placeholder="Seleccione">
                        <x-wire-select.option value="efectivo">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-money-bill-wave text-green-600"></i>
                                <span>Efectivo</span>
                            </div>
                        </x-wire-select.option>
                        <x-wire-select.option value="transferencia">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-exchange-alt text-blue-600"></i>
                                <span>Transferencia Bancaria</span>
                            </div>
                        </x-wire-select.option>
                        <x-wire-select.option value="cheque">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-money-check text-purple-600"></i>
                                <span>Cheque</span>
                            </div>
                        </x-wire-select.option>
                    </x-wire-select>
                </div>

                <!-- Número de Operación (condicional) -->
                @if(in_array($metodo, ['transferencia', 'cheque']))
                <div class="col-span-2">
                    <x-wire-input 
                        label="Número de Operación *" 
                        wire:model="numero_operacion"
                        placeholder="Ingrese el número de {{ $metodo === 'transferencia' ? 'operación' : 'cheque' }}" 
                        required 
                    />
                </div>
                @endif

                <!-- Detalle -->
                <div class="col-span-2">
                    <x-wire-textarea label="Detalle" wire:model="detalle"
                        placeholder="Ingrese detalles adicionales del depósito (opcional)" rows="3" />
                </div>

                <!-- Comprobante -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Comprobante (Opcional)
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label for="comprobante-upload"
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mb-2 text-sm text-gray-500">
                                    <span class="font-semibold">Click para subir</span> o arrastra aquí
                                </p>
                                <p class="text-xs text-gray-500">PNG, JPG o PDF (MAX. 2MB)</p>
                            </div>
                            <input id="comprobante-upload" type="file" class="hidden" wire:model="comprobante"
                                accept=".jpg,.jpeg,.png,.pdf" />
                        </label>
                    </div>

                    @if ($comprobante)
                    <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-alt text-green-600"></i>
                            <span class="text-sm text-green-800 font-medium">{{ $comprobante->getClientOriginalName() }}</span>
                        </div>
                        <button type="button" wire:click="$set('comprobante', null)"
                            class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    @endif

                    @error('comprobante')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div wire:loading wire:target="comprobante" class="mt-2">
                        <div class="flex items-center gap-2 text-blue-600">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span class="text-sm">Subiendo archivo...</span>
                        </div>
                    </div>
                </div>

                <!-- Resumen del Depósito -->
                @if($monto)
                <div class="col-span-2">
                    <div class="p-4 bg-green-50 border-2 border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-green-800">Monto a Depositar</span>
                                <p class="text-xs text-green-600 mt-1">
                                    {{ $tipo_deposito === 'adicional' ? 'Depósito Adicional' : 'Reembolso' }}
                                    por {{ $metodo === 'efectivo' ? 'Efectivo' : ($metodo === 'transferencia' ? 'Transferencia' : 'Cheque') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-green-700">
                                    {{ $moneda === 'PEN' ? 'S/' : '$' }} {{ number_format($monto, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Registrar Depósito" spinner="save"
                    wire:loading.attr="disabled" wire:target="save,comprobante" icon="plus-circle" />
            </x-slot>

        </x-wire-modal-card>
    </form>

    @push('js')
        <script>
            function dataCashDeposit() {
                return {
                    init() {
                        // Escuchar evento de cierre del modal
                        Livewire.on('close-modal', (modalName) => {
                            if (modalName === 'depositCreate') {
                                this.close();
                            }
                        });
                    }
                }
            }
        </script>
    @endpush
</div>