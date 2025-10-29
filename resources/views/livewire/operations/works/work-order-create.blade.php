<div x-data="dataCreate">
    <form wire:submit="save">

        <x-wire-modal-card title="Crear Orden de Trabajo" name="createWorkOrder" width="7xl" wire:model="openModal" :hide-close="true">

            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label>Correlativo</x-label>
                    <x-input class="w-full" disabled
                        value="{{ $workOrderForm->serie . '-' . str_pad($workOrderForm->correlativo ?? '', 6, '0', STR_PAD_LEFT) }}" />
                </div>

                {{-- Fecha apertura --}}
                <div class="col-span-1">
                    <x-label>F. Apertura</x-label>
                    <x-wire-datetime-picker wire:model="workOrderForm.started_at" display-format="DD-MM-YYYY HH:mm"
                        parse-format="YYYY-MM-DD HH:mm" readonly />
                </div>

                <div class="col-span-1">
                    <x-label>Nº Estimado</x-label>
                    <x-input class="w-full" wire:model="workOrderForm.estimate_number" />
                </div>
                <div class="col-span-1">
                    <x-label>Fecha Estimado</x-label>
                    <x-wire-datetime-picker wire:model="workOrderForm.estimate_date" display-format="DD-MM-YYYY"
                        parse-format="YYYY-MM-DD" :max="now()" />
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label>Nº EIR</x-label>
                    <x-wire-select :options="$this->eirs" wire:model.live="workOrderForm.eir_inspection_id"
                        option-label="name" option-value="id" />
                </div>
                <div class="col-span-1">
                    <x-label>Tipo</x-label>
                    <x-wire-select :options="$this->types" wire:model="workOrderForm.type" option-label="label"
                        option-value="value" />
                </div>
                <div class="col-span-1">
                    <x-label>M.Repara</x-label>
                    <x-wire-input value="{{ $this->repair }}" readonly />
                </div>
                <div class="col-span-1">
                    <x-label>Contenedor</x-label>
                    <x-wire-input class="w-full" wire:model="container" readonly />
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div class="col-span-2 lg:col-span-1">
                    <x-wire-select label="Proveedor" :options="$this->providers" wire:model="workOrderForm.provider_id"
                        option-label="name" option-value="id" />
                </div>
                <div class="col-span-2">
                    <x-wire-input label="Observaciones" class="w-full" wire:model="workOrderForm.comments"/>
                </div>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />
                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>
    <x-spinner :targets="['save']" message="Iniciando Orden..." logo="{{ asset('img/logos/favicon.svg') }}" logoSize="56"/>
    @push('js')
        <script>
            function dataCreate() {

            }
        </script>
    @endpush
</div>
