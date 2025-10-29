<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="Linea Naviera" name="lineModal" width="3xl" wire:model="openModal" :hide-close="true">

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

            <div class="grid grid-cols-1 gap-4">

                <div class="col-span-1">
                    <x-label class="mb-1">
                        Código
                    </x-label>
                    <x-input x-model="line.code" placeholder="Ingrese el código" class="w-full" />
                </div>

                <div class="col-span-2">
                    <x-wire-input required label="Nombre" x-model="line.name" placeholder="Ingrese el Nombre" />
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
                line: @entangle('line'),

                save() {
                    axios.post('/lines', this.line)
                        .then(response => {
                            $closeModal('lineModal');

                            Livewire.dispatch('lineAdded', {
                                lineId: response.data.id,
                            });

                            this.errors = [];

                            // Reinicia el objeto line (Livewire también lo hace en updated)
                            this.line = {
                                code: '',
                                name: ''
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

        function confirmDelete(lineId) {
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
                    @this.call('destroy', lineId);
                }
            });
        }
    </script>
@endpush
