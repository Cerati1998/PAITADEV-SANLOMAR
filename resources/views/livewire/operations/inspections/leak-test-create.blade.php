<div x-data="dataCreate">
    <form @submit.prevent="save">

        <x-wire-modal-card title="Crear Leak Test" name="createLeakTest" width="7xl" wire:model="openModal"
            :hide-close="true">

            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label>Serie</x-label>
                    <x-select wire:model="leaktest.serie" class="w-full">
                        @forelse ($series as $serie)
                            <option value="{{ $serie->name }}">{{ $serie->name }}</option>
                        @empty
                            <option value="">No hay series disponibles</option>
                        @endforelse
                    </x-select>
                </div>
                <div class="col-span-1">
                    <x-label>Correlativo</x-label>
                    <x-input class="w-full" disabled
                        value="{{ str_pad($this->leaktest['correlativo'] ?? '', 6, '0', STR_PAD_LEFT) }}" />
                </div>

                {{-- Fecha apertura --}}
                <div class="col-span-1">
                    <x-label>Fecha Inicio</x-label>
                    <x-wire-input type="datetime-local" wire:model="leaktest.started_at"
                        display-format="DD-MM-YYYY HH:mm:ss" parse-format="YYYY-MM-DD HH:mm:ss" />
                </div>

                <div class="col-span-1">
                    <x-label>Fecha Termino</x-label>
                    <x-wire-input type="datetime-local" wire:model="leaktest.finished_at" display-format="DD-MM-YYYY"
                        parse-format="YYYY-MM-DD" />
                </div>
                <div class="col-span-2">
                    <x-label>Contenedor</x-label>
                    <x-wire-select :options="$this->traces" wire:model.live="leaktest.trace_id" option-label="name"
                        option-value="id" />
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label>Tecnología</x-label>
                    <x-wire-select wirex-bind:key="select-technology-{{ $containerData['technology'] ?? 'null' }}"
                        :options="$this->reefer_technologies" option-label="name" option-value="id" wire:model="containerData.technology" />
                </div>
                <div class="col-span-1">
                    <x-label>Máquina</x-label>
                    <x-wire-select wirex-bind:key="select-machine-{{ $containerData['machine'] ?? 'null' }}"
                        :options="$this->reefer_machines" option-label="code" option-value="id" wire:model.live="containerData.machine" />
                </div>
                <x-wire-input label="Año" wire:model="containerData.year" />
                <x-wire-number :min="0" :max="12" label="Duración(minutos)" wire:model="leaktest.hours" />
                <div class="col-span-2">
                    <x-wire-select label="Estado" wire:model="leaktest.status">
                        <x-wire-select.option value="">Pendiente</x-wire-select.option>
                        <x-wire-select.option value="ok">Ok Test</x-wire-select.option>
                        <x-wire-select.option value="fail">Fail Test</x-wire-select.option>
                    </x-wire-select>
                </div>
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
                    fecha_inicio: @entangle('leaktest.started_at'),
                    fecha_fin: @entangle('leaktest.finished_at'),

                    checkDates() {
                        if (this.fecha_fin && this.fecha_inicio) {
                            const inicio = DateTime.fromISO(this.fecha_inicio);
                            const fin = DateTime.fromISO(this.fecha_fin);

                            if (fin <= inicio) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error en las fechas',
                                    text: 'La fecha de término debe ser mayor a la fecha de inicio',
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

                        Swal.fire({
                            title: "¿Estas seguro de Iniciar?",
                            text: "¡No podrás revertir esto!",
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
