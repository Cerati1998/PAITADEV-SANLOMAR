<div>
    <div x-data="devolutionEdit">
        <form wire:submit="save">

            <x-wire-modal-card title="Subida masiva de contenedores" name="devolutionEdit" wire:model="openModal"
                :hide-close="true" width="3xl">

                <x-validation-errors class="mb-4" />
                <header class="flex items-center space-x-4 mb-4">
                    <hr class="flex-1">
                    <p class="flex items-center">
                        <i class="fa-solid fa-circle-exclamation text-gray-600"></i>
                        <span class="ml-2 text-sm">
                            Datos Mandatorios
                        </span>
                    </p>
                    <hr class="flex-1">
                </header>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <!-- Línea Naviera -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label for="shipping_line_id" class="text-sm text-gray-700">
                                Línea <x-required-tag />
                            </label>
                            <button type="button" x-on:click="$openModal('lineCreate')"
                                class="text-xs text-blue-600 font-semibold hover:underline">
                                + Nueva línea
                            </button>
                        </div>
                        <x-wire-select id="shipping_line_id" :async-data="route('line.search')" option-label="name" option-value="id"
                            placeholder="Selecciona la Línea" wire:model.live="devolution.shipping_line_id" />
                    </div>

                    <!-- Nave -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label for="vessel_id" class="text-sm text-gray-700">
                                Nave <x-required-tag />
                            </label>
                            <button type="button" x-on:click="openVesselModal"
                                class="text-xs text-blue-600 font-semibold hover:underline">
                                + Nueva Nave
                            </button>
                        </div>
                        <x-wire-select :options="$vessels" option-label="name" option-value="id"
                            wire:model="devolution.vessel_id" placeholder="Selecciona la Nave" />
                    </div>

                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-sm text-gray-700">
                                Cliente/Importador <x-required-tag />
                            </label>
                            <button type="button" x-on:click="$openModal('clientCreate')"
                                class="text-xs  text-blue-600 font-semibold hover:underline">
                                + Nuevo Cliente
                            </button>
                        </div>
                        <x-wire-select :async-data="route('client.search')" option-label="rznSocial" option-value="id"
                            wire:model="devolution.client_id" placeholder="Seleccione el Importador" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-sm text-gray-700">
                                Agente Aduanero <x-required-tag />
                            </label>
                            <button type="button" x-on:click="$openModal('brokerCreate')"
                                class="text-xs  text-blue-600 font-semibold hover:underline">
                                + Nuevo Agente
                            </button>
                        </div>
                        <x-wire-select :async-data="route('broker.search')" option-label="rznSocial" option-value="id"
                            wire:model="devolution.custom_broker_id" placeholder="Seleccione el Agente" />
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-3">
                    <!-- bl -->
                    <div>
                        <label class="text-sm text-gray-700">BL <x-required-tag /></label>
                        <x-wire-input wire:model="devolution.bl_number" />
                    </div>

                    <!-- ETA -->
                    <div>
                        <label class="text-sm text-gray-700">ETA <x-required-tag /></label>
                        {{-- <x-wire-input type="date" wire:model="devolution.returned_date" class="col-span-1" /> --}}
                        <x-wire-datetime-picker wire:model="devolution.returned_date" display-format="DD-MM-YYYY"
                            parse-format="YYYY-MM-DD" :min="now()->setTimezone('America/Lima')->format('Y-m-d')" />
                    </div>

                    <!-- Semana -->
                    <div class="col-span-2 sm:col-span-1">
                        <x-label class="mb-1">
                            Regimén
                        </x-label>
                        <x-select placeholder="Seleccione el Regimen" wire:model="devolution.regimen" class="w-full">
                            <option value="-" hidden>Seleccione...</option>
                            <option value="exportacion">Exportación</option>
                            <option value="importacion">Importación</option>
                        </x-select>
                    </div>
                </div>
                <header class="flex items-center space-x-4 mb-4">
                    <hr class="flex-1">
                    <p class="flex items-center">
                        <i class="fa-solid fa-thumbtack text-gray-600"></i>
                        <span class="ml-2 text-sm">
                            Datos Complementarios
                        </span>
                    </p>
                    <hr class="flex-1">
                </header>
                <div class="grid grid-cols-3 gap-4 mb-3">
                    <x-wire-input label="Memo" wire:model="devolution.memo_number" />
                    <x-wire-input label="Viaje" wire:model="devolution.voyage" />
                    <!-- Semana -->
                    <x-wire-input label="Semana" wire:model="devolution.week" />
                </div>
                <template x-if="devolution.regimen === 'importacion'">
                    <div
                        class="flex items-center justify-center col-span-1 bg-gray-100 shadow-md cursor-pointer sm:col-span-2 dark:bg-secondary-700 rounded-xl h-64">

                        {{-- Input oculto --}}
                        <input id="fileUpload" type="file" class="hidden" wire:model="attach"
                            accept=".xls,.xlsx,.csv" wire:loading.attr="disabled" />

                        {{-- Ícono que dispara el input al hacer click --}}
                        <label for="fileUpload" class="flex flex-col items-center justify-center cursor-pointer">
                            <x-wire-icon name="cloud-arrow-up" class="w-16 h-16 text-blue-600 dark:text-teal-600" />
                            <p class="text-blue-600 dark:text-teal-600">Click o suelta Archivo aqui</p>
                        </label>
                    </div>
                    <div wire:loading wire:target="attach,save" class="text-blue-500 text-sm">
                        ⏳ Procesando Data... Por favor, espere.
                    </div>
                </template>
                <x-slot name="footer" class="flex justify-between gap-x-4">
                    <div class="flex gap-x-4">
                        <x-wire-button flat label="Cancel" x-on:click="close" />

                        <x-wire-button primary label="Guardar" type="submit" spinner icon="arrow-down-tray"
                            wire:loading.attr="disabled" wire:target="attach,save" />
                    </div>
                </x-slot>
            </x-wire-modal-card>
        </form>
    </div>
    <div>

        <x-wire-modal-card title="Ingrese Motivo de Anulación" name="devolutionAnulate" wire:model="openModalAnulate"
            :hide-close="true" width="3xl">

            <x-validation-errors class="mb-4" />
            <div class="mb-3">
                <x-text-area name="motive" label="Motivo" placeholder="Detalla el motivo de la Anulación..."
                    wire:model="anulateReason" />
            </div>


            <x-slot name="footer" class="flex justify-between gap-x-4">
                <div class="flex gap-x-4">
                    <x-wire-button flat label="Cancel" x-on:click="close" />

                    <x-wire-button primary label="Guardar" type="submit" spinner icon="arrow-down-tray"
                        onclick="confirmDelete({{ $this->selectedDevolutionId }})" />
                </div>
            </x-slot>
        </x-wire-modal-card>

    </div>
    @push('js')
        <script>
            function devolutionEdit() {
                return {
                    devolution: @entangle('devolution'),
                    openVesselModal() {
                        /* if (!this.devolution.shipping_line) {
                            alert('Debes seleccionar una línea naviera antes de crear una nave');
                            return;
                        } */
                        this.$wire.dispatch('setShippingLineId', {
                            shippingLineId: this.devolution.shipping_line_id,
                            isExtern: true
                        });
                        $openModal('vesselCreate');
                    }
                }
            }

            function confirmDelete(devolutionId) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '¡Sí, bórralo!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('destroy', devolutionId);
                    }
                });
            }
        </script>
    @endpush
</div>
