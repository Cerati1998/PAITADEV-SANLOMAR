<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="Maquina Contenedor" name="machineModal" width="3xl" wire:model="openModal"
            :hide-close="true">

            {{-- Si hay errores, se mostrarán aquí --}}
            {{-- <div x-show="errors.length"
                class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">¡Ups! Algo salió mal.</strong>
                <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                    <template x-for="error in errors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div> --}}
            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <x-wire-input required label="Código" wire:model="machine.code" placeholder="Ingrese el Código" />
                </div>

                <div>
                    <x-wire-input required label="Nombre" wire:model="machine.name"
                        placeholder="Ingrese el Nombre" />
                </div>
            </div>


            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Actualizar" spinner />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>

@push('js')
    <script>
        function data() {
            return {
                errors: [],

                // Enlace bidireccional con Livewire
                machine: @entangle('machine'),

                save() {
                    axios.post('/machines', this.machine)
                        .then(response => {
                            $closeModal('machineModal');

                            Livewire.dispatch('machineAdded', {
                                machineId: response.data.id,
                            });

                            this.errors = [];

                            // Reinicia el objeto machine (Livewire también lo hace en updated)
                            this.machine = {
                                code: '',
                                name: '',
                                country_code: ''
                            };
                        })
                        .catch(error => {
                            if (error.response.status === 422) {
                                this.errors = Object.values(error.response.data.errors).flat();
                            }

                            console.error(error.response.data);
                        });
                }
            }
        }

        function confirmDelete(machineId) {
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
                    @this.call('destroy', machineId);
                }
            });
        }
    </script>
@endpush
