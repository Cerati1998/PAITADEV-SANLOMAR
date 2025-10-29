<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="Puerto" name="portModal" width="3xl" wire:model="openModal" :hide-close="true">

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
                    <x-wire-input required label="Código" wire:model="port.code" placeholder="Ingrese el Código" />
                </div>

                <div>
                    <x-wire-input required label="Nombre" wire:model="port.name" placeholder="Ingrese el Nombre" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-wire-select label="Código Pais" placeholder="Seleccione un País" wire:model="port.country_code"
                        :options="$countryOptions" option-label="description" option-value="A3" />
                </div>
                <div>
                    <x-label class="mb-1">
                        Coordenadas
                    </x-label>
                    <x-input wire:model="port.location" placeholder="Ingrese las coordenadas" class="w-full" />
                </div>

            </div>



            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar"
                    x-on:click="close" />

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
                port: @entangle('port'),

                save() {
                    axios.post('/ports', this.port)
                        .then(response => {
                            $closeModal('portModal');

                            Livewire.dispatch('portAdded', {
                                portId: response.data.id,
                            });

                            this.errors = [];

                            // Reinicia el objeto port (Livewire también lo hace en updated)
                            this.port = {
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

        function confirmDelete(portId) {
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
                    @this.call('destroy', portId);
                }
            });
        }
    </script>
@endpush
