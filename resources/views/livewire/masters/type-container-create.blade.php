<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="Tipo de Contenedor" name="typeContainerCreate" width="3xl" wire:model="openModal"
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
            <div class="mb-3">
                <x-wire-input required label="Descripción" wire:model="containerType.description"
                    placeholder="Ingrese la descripción" />
            </div>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <x-wire-input required label="Código" wire:model="containerType.code" placeholder="Ingrese el Código" />
                <x-wire-input required label="ISO" wire:model="containerType.iso_code"
                    placeholder="Ingrese el ISO" />
            </div>

            <div class="grid grid-cols-2 gap-4 mb-3 place-items-center">
                <x-wire-number label="Altura" min="0" step="0.01" wire:model="containerType.height" />
                <x-wire-toggle label="Tiene tecnología de Frio" wire:model="containerType.is_reefer" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-wire-number label="Largo" min="0" step="0.01" wire:model="containerType.length" />
                </div>
                <div>
                    <x-wire-number label="Ancho" min="0" step="0.01" wire:model="containerType.width" />
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
                containerType: @entangle('containerType'),
            }
        }
    </script>
@endpush
