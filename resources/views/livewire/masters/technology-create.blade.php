<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="Tecnología Contenedor" name="technologyCreate" width="3xl" wire:model="openModal"
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
                <div>
                    <x-wire-input required label="Nombre" wire:model="technology.name"
                        placeholder="Ej: CONVENCIONAL, SEACARE, etc." />
                </div>

                <div>
                    <x-wire-input label="Uso" wire:model="technology.usage"
                        placeholder='Ej: Carga general refrigerada...' />
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                <x-wire-number label="T° minima(°C)" wire:model="technology.temperature_min" min="-100"
                    max="100" step="0.1" placeholder="°C" />

                <x-wire-number label="T° maxima(°C)" wire:model="technology.temperature_max" max="100"
                    step="0.1" placeholder="°C" />

                <x-wire-number label="Vent. minima(Cbm/h)" wire:model="technology.ventilation_min" min="0"
                    max="100" step="0.1" placeholder="(Cbm/h)" />

                <x-wire-number label="Vent. maxima(Cbm/h)" wire:model="technology.ventilation_max" max="100"
                    step="0.1" placeholder="Cbm/h" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
                <div class="grid grid-cols-2 gap-2">
                    <x-wire-number label="Hum. minima(%)" wire:model="technology.humidity_min" min="0"
                        max="100" step="0.1" placeholder="%" />
                    <x-wire-number label="Hum. maxima(%)" wire:model="technology.humidity_max" max="100"
                        step="0.1" placeholder="%" />
                </div>

                <div class="grid grid-cols-2  gap-2">
                    <x-wire-number label="o2 minimo(%)" wire:model="technology.atmosphere_o2_min" min="0"
                        max="100" step="0.1" placeholder="%" />
                    <x-wire-number label="o2 maximo(%)" wire:model="technology.atmosphere_o2_max" max="100"
                        step="0.1" placeholder="%" />
                </div>

                <div class="grid grid-cols-2  gap-2">
                    <x-wire-number label="co2 minimo(%)" wire:model="technology.atmosphere_co2_min" min="0"
                        max="100" step="0.1" placeholder="%" />
                    <x-wire-number label="co2 maximo(%)" wire:model="technology.atmosphere_co2_max" max="100"
                        step="0.1" placeholder="%" />
                </div>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>

@push('js')
    <script>
        function dataCreate() {
            return {
                technology: @entangle('technology'),
            }
        }
    </script>
@endpush
