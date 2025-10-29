<div x-data="dataEdit">
    <form wire:submit="save">

        <x-wire-modal-card title="Datos de Vehiculo" name="vehicleModal" wire:model="openModal" width="3xl">

            <x-validation-errors class="mb-4" />

            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <x-label class="mb-1">
                        Número de Placa
                    </x-label>
                    <x-input x-model="vehicle.code" placeholder="Ingrese el número de placa, ejemplo: ANH808"
                        class="w-full" />
                </div>

                <div class="shrink-0 mt-6.5">
                    <x-wire-mini-button type="button" wire:click="searchPlaca" spinner="searchDocument"
                        icon="magnifying-glass" x-bind:disabled="(vehicle.code === '')" />
                </div>
            </div>


            <div class="grid grid-cols-3 gap-4 mb-4">

                <div class="col-span-1">
                    <x-label class="mb-1">
                        Marca
                    </x-label>
                    <x-input required x-model="vehicle.brand" placeholder="Ingrese la marca" class="w-full" />
                </div>
                <div class="col-span-1">
                    <x-label class="mb-1">
                        Modelo
                    </x-label>
                    <x-input required x-model="vehicle.model" placeholder="Ingrese el modelo" class="w-full" />
                </div>
                <div class="col-span-1">
                    <x-label class="mb-1">
                        Categoria
                    </x-label>
                    <x-select required x-model="vehicle.category" placeholder="Ingrese el modelo" class="w-full">
                        <option hidden value="">Seleccione...</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </x-select>
                </div>

            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="col-span-1">
                    <x-label class="mb-1">
                        Carroceria
                    </x-label>
                    <x-select x-model="vehicle.type" required class="w-full">
                        <option hidden value="">Seleccione...</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="col-span-1">
                    <x-label class="mb-1">
                        Color
                    </x-label>
                    <x-input x-model="vehicle.color" placeholder="Ingrese el color (Opcional)" class="w-full" />
                </div>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="searchDocument,save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

    @push('js')
         <script>
             function dataEdit() {
                return {
                    //vehicle: @entangle('vehicle').live,
                    vehicle: @entangle('vehicle'),
                    init() {
                        this.$watch('vehicle.tipoDoc', value => {

                            if (value == '-') {
                                this.vehicle.numDoc = '';
                            }
                        });
                    }
                }
            }
              function confirmDelete(vehicleId) {
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
                        @this.call('destroy', vehicleId);
                    }
                });
            }
        </script>
    @endpush
</div>
