<div x-data="dischargueEdit">

    <form wire:submit="save">

        <x-wire-modal-card title="Subida masiva de contenedores" name="dischargueEdit" wire:model="openModal"
            :hide-close="true" width="3xl">

            <x-validation-errors class="mb-4" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <!-- Línea Naviera -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="shipping_line_id" class="text-sm text-gray-700">
                            Línea <x-required-tag />
                        </label>
                        <button type="button" x-on:click="$openModal('lineCreate')"
                            class="text-xs text-blue-500 hover:underline">
                            + Nueva línea
                        </button>
                    </div>
                    <x-wire-select id="shipping_line_id" :async-data="route('line.search')" option-label="name" option-value="id"
                        placeholder="Selecciona la Línea" wire:model.live="dischargue.shipping_line_id" />
                </div>

                <!-- Nave -->
                <div>
                    <div class="flex justify-between items-center">
                        <label for="vessel_id" class="text-sm text-gray-700">
                            Nave <x-required-tag />
                        </label>
                        <button type="button" x-on:click="openVesselModal"
                            class="text-xs text-blue-500 hover:underline">
                            + Nueva Nave
                        </button>
                    </div>
                    <x-wire-select :options="$vessels" option-label="name" option-value="id"
                        wire:model="dischargue.vessel_id" placeholder="Selecciona la Nave" />
                </div>

            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
                <!-- bl -->
                <x-wire-input label="BL" wire:model="dischargue.bl_number" />

                <!-- ETA -->
                {{-- <x-wire-input type="date" label="ETA" wire:model="dischargue.eta_date" class="col-span-1" /> --}}
                <x-wire-datetime-picker label="ETA" wire:model="dischargue.eta_date" display-format="DD-MM-YYYY"
                    parse-format="YYYY-MM-DD" :min="now()->format('Y-m-d')" />

                <!-- Semana -->
                <x-wire-input label="Semana" wire:model="dischargue.week" />
            </div>

            <div class="grid grid-cols-2 gap-4 mb-3">
                <x-wire-input label="Viaje" wire:model="dischargue.voyage" />
                <x-wire-input label="Manifiesto" wire:model="dischargue.manifiest_number" />
            </div>


            <x-slot name="footer" class="flex justify-between gap-x-4">
                <div class="flex gap-x-4">
                    <x-wire-button flat label="Cancel" x-on:click="close" />

                    <x-wire-button primary label="Guardar" type="submit" spinner icon="arrow-down-tray"
                        wire:loading.attr="disabled" wire:target="save" />
                </div>
            </x-slot>
        </x-wire-modal-card>
    </form>

</div>

<div>

    <x-wire-modal-card title="Ingrese Motivo de Anulación" name="dischargueAnulate" wire:model="openModalAnulate"
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
                    onclick="confirmDelete({{ $this->selectedDischargeId }})" />
            </div>
        </x-slot>
    </x-wire-modal-card>

</div>

@push('js')
    <script>
        function dischargueEdit() {
            return {
                // Enlace bidireccional con Livewire
                dischargue: @entangle('dischargue'),
                openVesselModal() {
                    /* if (!this.dischargue.shipping_line) {
                        alert('Debes seleccionar una línea naviera antes de crear una nave');
                        return;
                    } */
                    this.$wire.dispatch('setShippingLineId', {
                        shippingLineId: this.dischargue.shipping_line,
                        isExtern: true
                    });
                    $openModal('vesselCreate');
                }
            }
        }

        function confirmDelete(dischargueId) {
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
                    @this.call('destroy', dischargueId);
                }
            });
        }
    </script>
@endpush
