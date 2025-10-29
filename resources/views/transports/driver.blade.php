<div x-data="dataEdit">
    <form wire:submit="save">

        <x-wire-modal-card title="Datos de Conductor" name="driverModal" wire:model="openModal" width="3xl">

            <x-validation-errors class="mb-4" />

            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <x-label class="mb-1">
                        Tipo de Documento
                    </x-label>
                    <x-select required x-model="driver.tipoDoc" class="w-full">
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="flex-1">
                    <x-label class="mb-1">
                        Número de Documento
                    </x-label>
                    <x-input x-model="driver.numDoc" placeholder="Ingrese el número de documento" class="w-full"
                        x-bind:disabled="driver.tipoDoc == '-'" />
                </div>

                <div class="shrink-0 mt-6.5">
                    <x-wire-mini-button type="button" wire:click="searchDocument" spinner="searchDocument"
                        icon="magnifying-glass" x-bind:disabled="!(driver.tipoDoc == '1' || driver.tipoDoc == '6')" />
                </div>
            </div>


            <div class="grid grid-cols-2 gap-4 mb-4">

                <div class="col-span-2">
                    <x-label class="mb-1">
                        Nombres
                    </x-label>
                    <x-input required x-model="driver.nombres" placeholder="Ingrese los nombres" class="w-full" />
                </div>
                <div class="col-span-2">
                    <x-label class="mb-1">
                        Apellidos
                    </x-label>
                    <x-input required x-model="driver.apellidos" placeholder="Ingrese los apellidos" class="w-full" />
                </div>

            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <x-label class="mb-1">
                        Brevete <span class="text-red-500">(Colocar Letra previa*)</span>
                    </x-label>
                    <x-wire-input x-model="driver.brevete" placeholder="Ingrese el Brevete" />
                </div>

                <x-wire-input label="Dirección" x-model="driver.direccion"
                    placeholder="Ingrese la direccion (Opcional)" />

                <x-wire-input label="Teléfono" x-model="driver.telefono" placeholder="Ingrese el teléfono (Opcional)" />
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
                    //driver: @entangle('driver').live,
                    driver: @entangle('driver'),
                    init() {
                        this.$watch('driver.tipoDoc', value => {

                            if (value == '-') {
                                this.driver.numDoc = '';
                            }
                        });
                    }
                }
            }
              function confirmDelete(driverId) {
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
                        @this.call('destroy', driverId);
                    }
                });
            }
        </script>
    @endpush
</div>
