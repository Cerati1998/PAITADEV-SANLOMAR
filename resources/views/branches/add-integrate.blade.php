<div x-data="dataEdit">
    <form wire:submit="save">

        <x-wire-modal-card title="Crear credenciales Integración" name="integrateModal" wire:model="openModal"
            width="3xl">

            <x-validation-errors class="mb-4" />

            <x-wire-alert class="mb-4"
                title="Importante: Guarda el TOKEN generado antes de guardar los cambios, ya que no podrás volver a verlo más adelante."
                info />

            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <x-label class="mb-1">
                        Tipo de Integración
                    </x-label>
                    <x-select required x-model="integrate.integration_id" class="w-full">
                        <option value="" hidden>Seleccione...</option>
                        @foreach ($integrations as $integration)
                            <option value="{{ $integration->id }}">{{ $integration->name }}</option>
                        @endforeach
                    </x-select>
                </div>

            </div>
            <div class="mb-4 flex items-center justify-start gap-2">
                <div class="flex-1">
                    <x-label class="mb-1">
                        Bearer Token
                    </x-label>
                    <x-input wire:model="integrate.api_token" placeholder="Genera tu token" class="w-full" />
                </div>

                <div class="shrink-0 mt-6.5">
                    <x-wire-mini-button type="button" wire:click="generatetoken" spinner="generatetoken" blue
                        icon="rocket-launch" title="Generar Token" />
                </div>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="generatetoken,save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

    @push('js')
        <script>
            function dataEdit() {
                return {
                    integrate: @entangle('integrate').live,
                }
            }

            function confirmDelete(integrateId) {
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
                        @this.call('destroy', integrateId);
                    }
                });
            }
        </script>
    @endpush
</div>
