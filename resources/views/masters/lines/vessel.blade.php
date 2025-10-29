<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="Datos de Nave" name="vesselModal" width="3xl" wire:model="openModal" :hide-close="true">

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

            <div class="w-full mb-3">
                <x-wire-input required label="Nombre" wire:model="vessel.name"
                    placeholder="Ingrese el Nombre de la nave" />
            </div>
            <div class="w-full mb-3">
                <x-wire-input label="IMO" wire:model="vessel.imo_number" placeholder="Ingrese el IMO"
                    class="w-full" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <x-wire-number label="Capacidad" wire:model="vessel.pallets" placeholder="Ingrese la capacidad"
                        class="w-full" min="0" value="0"/>
                </div>

                <div>
                    <x-wire-select label="Categoria" wire:model="vessel.type" :options="['container', 'bulk', 'tanker']"
                        placeholder="Seleccione..." />
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
                vessel: @entangle('vessel'),

                save() {
                    axios.post('/vessels', this.vessel)
                        .then(response => {
                            $closeModal('vesselModal');

                            Livewire.dispatch('vesselAdded', {
                                vesselId: response.data.id,
                            });

                            this.errors = [];

                            // Reinicia el objeto vessel (Livewire también lo hace en updated)
                            this.vessel = {
                                imo_number: '',
                                name: '',
                                type: ''
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

        function confirmDelete(vesselId) {
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
                    @this.call('destroy', vesselId);
                }
            });
        }
    </script>
@endpush
