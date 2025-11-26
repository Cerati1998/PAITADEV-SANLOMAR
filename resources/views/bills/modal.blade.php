<div>
    <x-wire-modal wire:model="openModal" max-width="4xl" name="billCreate">
        <x-wire-card title="Registrar Gasto">

            <x-validation-errors class="mb-4" />

            <!-- Información de Caja Actual -->
            @if($this->cajaActual)
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-blue-900">Caja Actual: {{ $this->cajaActual->correlativo }}</h4>
                        <div class="mt-2 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <span class="text-blue-700">Monto Inicial:</span>
                                <span class="font-medium text-blue-900">S/ {{ number_format($this->cajaActual->monto_inicial, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700">Total Salidas:</span>
                                <span class="font-medium text-red-600">S/ {{ number_format($this->cajaActual->total_salidas, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700">Saldo Disponible:</span>
                                <span class="font-bold text-green-700">S/ {{ number_format($this->saldoDisponible, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-12 gap-4">
                <!-- Fecha de Emisión -->
                <div class="col-span-12 sm:col-span-4">
                    <x-wire-datetime-picker label="Fecha de Emisión *" wire:model="fecha_emision" 
                        placeholder="Seleccione fecha" without-time :max="now()->setTimezone('America/Lima')->format('Y-m-d')" required />
                </div>

                <!-- Tipo de Comprobante -->
                <div class="col-span-12 sm:col-span-4">
                    <x-wire-select label="Tipo Comprobante *" wire:model="tipo_comprobante" required>
                        <x-wire-select.option value="factura" label="Factura" />
                        <x-wire-select.option value="boleta" label="Boleta" />
                        <x-wire-select.option value="recibo" label="Recibo" />
                        <x-wire-select.option value="ticket" label="Ticket" />
                    </x-wire-select>
                </div>

                <!-- Número -->
                <div class="col-span-12 sm:col-span-4">
                    <x-wire-input label="Comprobante" wire:model="numero_comprobante" placeholder="00001" />
                </div>

                <!-- Proveedor -->
                <div class="col-span-12">
                    <div class="flex justify-between items-center mb-1">
                        <label for="shipping_line_id" class="text-sm text-gray-700">
                            Proveedor <x-required-tag />
                        </label>
                        <button type="button" x-on:click="$openModal('providerCreate')"
                            class="text-xs text-blue-500 hover:underline">
                            + Nuevo
                        </button>
                    </div>
                    <x-wire-select :async-data="route('provider.search')" option-value="id" option-label="rznSocial" wire:model="provider_id" placeholder="Seleccione un proveedor" />
                </div>

                <!-- Producto (opcional) -->
                <div class="col-span-12">
                    <x-wire-select label="Producto (Opcional)" wire:model="product_id" placeholder="Seleccione un producto">
                        @foreach($this->products as $product)
                        <x-wire-select.option value="{{ $product->id }}" label="{{ $product->name }}" />
                        @endforeach
                    </x-wire-select>
                </div>

                <!-- Moneda -->
                <div class="col-span-12 sm:col-span-4">
                    <x-wire-select label="Moneda *" wire:model.live="moneda" required>
                        <x-wire-select.option value="PEN" label="PEN - Soles" />
                        <x-wire-select.option value="USD" label="USD - Dólares" />
                    </x-wire-select>
                </div>

                <!-- Monto -->
                <div class="col-span-12 sm:col-span-4">
                    <x-wire-currency label="Monto *" wire:model="monto" placeholder="0.00" 
                        :prefix="$moneda === 'PEN' ? 'S/' : '$'" thousands="," decimal="." precision="2" required />
                </div>

                <!-- Tipo de Cambio (solo si es USD) -->
                @if($moneda === 'USD')
                <div class="col-span-12 sm:col-span-4">
                    <x-wire-input label="Tipo de Cambio *" wire:model="tipo_cambio" 
                        type="number" step="0.0001" placeholder="3.7500" required />
                    @if($monto && $tipo_cambio)
                    <p class="mt-1 text-xs text-gray-600">
                        Equivalente: S/ {{ number_format($monto * $tipo_cambio, 2) }}
                    </p>
                    @endif
                </div>
                @endif

                <!-- Detalle -->
                <div class="col-span-12">
                    <x-wire-textarea label="Detalle del Gasto *" wire:model="detalle"
                        placeholder="Describa el motivo del gasto" rows="3" required />
                </div>

                <!-- Observaciones -->
                <div class="col-span-12">
                    <x-wire-textarea label="Observaciones" wire:model="observaciones"
                        placeholder="Observaciones adicionales (opcional)" rows="2" />
                </div>

                <!-- Comprobante -->
        {{--         <div class="col-span-12">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Comprobante / Voucher (Opcional)
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label for="bill-comprobante-upload"
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mb-2 text-sm text-gray-500">
                                    <span class="font-semibold">Click para subir</span> o arrastra aquí
                                </p>
                                <p class="text-xs text-gray-500">PNG, JPG o PDF (MAX. 2MB)</p>
                            </div>
                            <input id="bill-comprobante-upload" type="file" class="hidden" wire:model="comprobante"
                                accept=".jpg,.jpeg,.png,.pdf" />
                        </label>
                    </div>

                    @if ($comprobante)
                    <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-alt text-green-600"></i>
                            <span class="text-sm text-green-800 font-medium">{{ $comprobante->getClientOriginalName() }}</span>
                        </div>
                        <button type="button" wire:click="$set('comprobante', null)" class="text-red-600 hover:text-red-800">
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
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span class="text-sm">Subiendo archivo...</span>
                        </div>
                    </div> 
                </div> --}}

                <!-- Resumen del gasto -->
                @if($monto)
                <div class="col-span-12">
                    <div class="p-4 bg-red-50 border-2 border-red-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-red-800">Total del Gasto</span>
                                <p class="text-xs text-red-600 mt-1">
                                    @if($moneda === 'USD')
                                        ${{ number_format($monto, 2) }} USD × {{ $tipo_cambio }} = S/ {{ number_format($monto * ($tipo_cambio ?? 1), 2) }}
                                    @else
                                        Moneda: PEN
                                    @endif
                                </p>
                                @if($this->cajaActual)
                                <p class="text-xs text-gray-600 mt-1">
                                    Saldo después del gasto: S/ {{ number_format($this->saldoDisponible - ($moneda === 'PEN' ? $monto : $monto * ($tipo_cambio ?? 1)), 2) }}
                                </p>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-red-700">
                                    S/ {{ number_format($moneda === 'PEN' ? $monto : $monto * ($tipo_cambio ?? 1), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" wire:click="closeModal" />

                <x-wire-button primary label="Registrar Gasto" wire:click="save" spinner="save"
                    wire:loading.attr="disabled" wire:target="save,comprobante" icon="minus-circle" />
            </x-slot>

        </x-wire-card>
    </x-wire-modal>
</div>