<div>
    <div x-data="dataCreate">
        <form wire:submit="save">

            <x-wire-modal-card title="Código de Ubicación" name="locationCreate" wire:model="openModal" width="3xl">

                <x-validation-errors class="mb-4" />

                <div class="grid grid-cols-2 gap-4">


                    <div class="col-span-2">
                        <x-label class="mb-1">
                            Código
                        </x-label>
                        <x-input required x-model="location.code" placeholder="Ingrese el código" class="w-full" />
                    </div>

                    <div class="col-span-2">
                        <x-label class="mb-1">
                            Descripción
                        </x-label>
                        <x-input x-model="location.description" placeholder="Ingrese la descripción" class="w-full" />
                    </div>

                </div>

                <x-slot name="footer" class="flex justify-end gap-x-4">
                    <x-wire-button flat label="Cancelar" x-on:click="close" />

                    <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
                </x-slot>

            </x-wire-modal-card>
        </form>

        @push('js')
            <script>
                function dataCreate() {
                    return {
                        location: @entangle('location'),
                    }
                }
            </script>
        @endpush
    </div>
</div>

