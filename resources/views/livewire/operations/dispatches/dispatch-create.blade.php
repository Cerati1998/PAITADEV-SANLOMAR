<div x-data="dataCreate">
    <form @submit.prevent="save">

        <x-wire-modal-card title="Registrar Lavado/Despacho" name="createDispatch" width="5xl" wire:model="openModal"
            :hide-close="true">

            <x-validation-errors class="mb-4" />
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <span
                    class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-500 dark:bg-red-700 w-6 h-6"
                    xl="xl">
                    1
                </span>
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Datos del Contenedor
                </h1>
            </header>
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
                {{-- Fecha apertura --}}
                <div class="col-span-1">
                    <x-label>Fecha Lavado</x-label>
                    <x-wire-input type="datetime-local" wire:model="dispatchData.wash_date" />
                </div>

                <div class="col-span-1">
                    <x-label>Fecha Despacho</x-label>
                    <x-wire-input type="datetime-local" wire:model="dispatchData.dispatch_date" />
                </div>
                <div class="col-span-1 lg:col-span-2">
                    <x-label>Contenedor</x-label>
                    <x-wire-select :options="$this->traces" wire:model.live="dispatchData.trace_id" option-label="name"
                        option-value="id" />
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <x-wire-input label="Tecnologia" wire:model="containerData.technology" readonly />
                <x-wire-input label="Maquina" wire:model="containerData.machine" readonly />
                <x-wire-input label="Año" wire:model="containerData.year" readonly />
                <x-wire-input label="EIR Salida" wire:model="dispatchData.eir_gate_out" />

            </div>

            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <span
                    class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-500 dark:bg-red-700 w-6 h-6"
                    xl="xl">
                    2
                </span>
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Datos del Despacho
                </h1>
            </header>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                <x-wire-select :options="$this->users" wire:model="dispatchData.dispatcher_id" option-label="name"
                    label="Despachador" option-value="id" />
                <x-wire-select :options="$this->vessels" wire:model="dispatchData.vessel_id" option-label="name" label="Nave"
                    option-value="id" />
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div class="col-span-2 lg:col-span-1">
                    <x-wire-select :options="$this->shippers" wire:model="dispatchData.shipper_id" option-label="name"
                        label="Shipper" option-value="id" />
                </div>
                <x-wire-input label="Booking" wire:model="dispatchData.reserva_booking" />
                <x-wire-toggle left-label="Lavado Oxalico" wire:model="dispatchData.oxalic_wash" sm />

            </div>
            <div class="mb-3 flex items-start text-xs font-medium">
                <p> <span class="text-red-500 semibold">Nota:</span> Si solo es lavado no llenes datos de
                    despacho</p>
            </div>



            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />
                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>
    <x-spinner :targets="['save']" message="Iniciando Test..." logo="{{ asset('img/logos/favicon.svg') }}"
        logoSize="56" />
    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
        <script>
            const {
                DateTime
            } = luxon;

            function dataCreate() {
                return {
                    fecha_wash: @entangle('dispatchData.wash_date'),
                    fecha_dispatch: @entangle('dispatchData.dispatch_date'),
                    message: '',

                    checkDates() {
                        if (this.fecha_dispatch && this.fecha_wash) {
                            const inicio = DateTime.fromISO(this.fecha_wash);
                            const fin = DateTime.fromISO(this.fecha_dispatch);

                            if (fin <= inicio) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error en las fechas',
                                    text: 'La fecha de despacho debe ser mayor a la fecha de lavado',
                                    confirmButtonColor: '#3085d6',
                                });
                                return false;
                            }
                        }
                        return true;
                    },
                    save() {
                        // Validar fechas antes de confirmar
                        if (!this.checkDates()) {
                            return;
                        }
                        if (this.fecha_dispatch) {
                            this.message = '';
                            this.message = 'El contenedor será despachado totalmente';
                        } else {
                            this.message = '';
                            this.message = 'El contenedor solo será lavado y no despachado';
                        }

                        Swal.fire({
                            title: "¿Estas seguro de Iniciar?",
                            text: this.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '¡Sí, Continua!',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                @this.call('save');
                            }
                        })
                    }
                }
            }
        </script>
    @endpush
</div>
