<div>
    <div v x-data="dataCreate">

        <form wire:submit="save">

            <x-wire-modal-card title="Puerto" name="portCreate" width="3xl" wire:model="openModal" :hide-close="true">

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
                        <x-wire-select label="Código Pais" placeholder="Seleccione un País"
                            wire:model="port.country_code" :options="$countryOptions" option-label="description" option-value="A3" />
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

                    <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
                </x-slot>

            </x-wire-modal-card>
        </form>

    </div>
</div>

@push('js')
    <script>
        function dataCreate() {
            return {
                port: @entangle('port'),
            }
        }
    </script>
@endpush
