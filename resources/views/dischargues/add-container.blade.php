<div x-data="containerAdd">

    <form wire:submit="save">

        <x-wire-modal-card title="Agregar Contenedor a Gate" name="containerAdd" wire:model="openModal" :hide-close="true"
            width="3xl">

            <x-validation-errors class="mb-4" />

            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <x-wire-select label="Tipo" placeholder="Seleccione el Tipo de Contenedor"
                        wire:model.live="container.container_type_id" :options="$this->typeContainers" option-value="id"
                        option-label="description" />
                </div>
                <div class="flex-1">
                    <x-wire-input label="Contenedor" wire:model="container.code"
                        placeholder="Ingrese en formato: CAIU1234567" />
                </div>
                <div class="shrink-0 mt-6.5">
                    <x-wire-mini-button type="button" wire:click="searchContainer" spinner="searchContainer" blue
                        icon="magnifying-glass" title="Buscar Contenedor en Historial" />
                </div>

            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between mb-1">
                    <x-label>Puerto Origén</x-label>
                    <button type="button" x-on:click="$openModal('portCreate')"
                        class="text-xs text-blue-600 font-semibold hover:underline">
                        + Nuevo Puerto
                    </button>
                </div>
                <x-wire-select placeholder="Seleccione el puerto de Proveniencia" wire:model="container.port_id"
                    :async-data="route('port.search')" option-value="id" option-label="full_name" />
            </div>

            <div class="grid grid-cols-2  gap-4 mb-4">
                <x-wire-input wire:model="container.iso_code" label="Código ISO" class="w-full" readonly />
                <template x-if="isReeferType()">
                    <x-wire-select label="Técnologia Reefer" wire:model="container.reefer_technology_id"
                        placeholder="Seleccione la Tecnología de Frio" :options="$this->reeferTechnologies" option-value="id"
                        option-label="name" />
                </template>
            </div>

            <x-slot name="footer" class="flex justify-between gap-x-4">
                <div class="flex gap-x-4">
                    <x-wire-button flat label="Cancel" x-on:click="close" />

                    <x-wire-button primary label="Guardar" type="submit" spinner icon="arrow-down-tray"
                        wire:loading.attr="disabled" wire:target="save" />
                </div>
            </x-slot>
        </x-wire-modal-card>
    </form>

</div>

@push('js')
    <script>
        function containerAdd() {
            return {
                // Enlace bidireccional con Livewire
                container: @entangle('container'),
                container_type_id: @entangle('container.container_type_id'),
                isReeferType() {
                    return this.container_type_id == '11' || this.container_type_id == '12';
                }
            }
        }

        function confirmRedirect(url, message) {
            Swal.fire({
                title: message,
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Exito!",
                        text: "Redirigiendo...",
                        icon: 'success',
                    });
                    setTimeout(() => {
                        window.location.href = url;
                    }, 2000)
                }
            });
        }
    </script>
@endpush
