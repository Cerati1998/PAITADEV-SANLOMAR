<div x-data="dataEdit">
    <form @submit.prevent="savePrevent">

        <x-wire-modal-card title="Editar Leak Test" width="7xl" wire:model="openModal" :hide-close="true">

            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label>Serie</x-label>
                    <x-wire-input wire:model="leaktest.serie" class="w-full" disabled readonly />

                </div>
                <div class="col-span-1">
                    <x-label>Correlativo</x-label>
                    <x-input class="w-full" disabled readonly
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
                        option-value="id" readonly />
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
                <x-wire-input label="Tecnologia" wire:model="containerData.technology" readonly />
                <x-wire-input label="Maquina" wire:model="containerData.machine" readonly />
                <x-wire-input label="Año" wire:model="containerData.year" readonly />
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
    <x-spinner :targets="['save']" message="Actualizando Test..." logo="{{ asset('img/logos/favicon.svg') }}"
        logoSize="56" />
    @push('js')
        <script>
            function dataEdit() {
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
                    savePrevent(){
                          if (!this.checkDates()) {
                            return;
                        }

                        @this.call('save');
                    }
                }
            }

            function confirmDelete(leaktestId) {
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
                        @this.call('destroy', leaktestId);
                    }
                });
            }
        </script>
    @endpush
</div>
