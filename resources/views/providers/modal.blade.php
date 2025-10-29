<div x-data="dataEdit">
    <form wire:submit="save">

        <x-wire-modal-card title="Editar Proveedor" name="providerModal" wire:model="openModal" width="3xl">

            <x-validation-errors class="mb-4" />

            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <x-label class="mb-1">
                        Tipo de Documento
                    </x-label>
                    <x-select required x-model="provider.tipoDoc" class="w-full">
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="flex-1">
                    <x-label class="mb-1">
                        Número de Documento
                    </x-label>
                    <x-input x-model="provider.numDoc" placeholder="Ingrese el número de documento" class="w-full"
                        x-bind:disabled="provider.tipoDoc == '-'" />
                </div>

                <div class="shrink-0 mt-6.5">
                    <x-wire-mini-button type="button" wire:click="searchDocument" spinner="searchDocument"
                        icon="magnifying-glass" x-bind:disabled="!(provider.tipoDoc == '1' || provider.tipoDoc == '6')" />
                </div>
            </div>


            <div class="grid grid-cols-2 gap-4 mb-4">



                <div class="col-span-2">
                    <x-label class="mb-1">
                        Razón Social
                    </x-label>
                    <x-input required x-model="provider.rznSocial" placeholder="Ingrese la razón social" class="w-full" />
                </div>

                <div class="col-span-2">
                    <x-label class="mb-1">
                        Dirección
                    </x-label>
                    <x-input x-model="provider.direccion" placeholder="Ingrese la dirección" class="w-full" />
                </div>

                <x-wire-input label="Correo Electrónico" x-model="provider.email"
                    placeholder="Ingrese el correo electrónico (Opcional)" />

                <x-wire-input label="Teléfono" x-model="provider.telephone"
                    placeholder="Ingrese el teléfono (Opcional)" />

            </div>

             <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                <div>
                    <x-label class="mb-1">
                        Tipo de Servicios
                    </x-label>
                    <x-select required x-model="provider.categoria" class="w-full">
                        <option hidden>Seleccione...</option>
                        <option value="servicios">Servicios</option>
                        <option value="repuestos">Repuestos</option>
                        <option value="generales">Generales</option>
                    </x-select>
                </div>
                <div>
                    <x-wire-input label="Nº de Cuenta" x-model="provider.cuentaCte"
                        placeholder="Ingrese el número de Cuenta" />
 
                </div>
            </div>

            <x-wire-input label="Detalle de Servicios" x-model="provider.detalleServicio"
                placeholder="Ejem: Proveedor de soldadura" />

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="searchDocument,save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

    <x-spinner :targets="['save', 'searchDocument']" logo="{{ asset('img/logos/favicon.svg') }}" logoSize="56"
        message="Cargando información..." />
    @push('js')
        <script>
            function dataEdit() {
                return {
                    provider: @entangle('provider').live,
                    init() {
                        this.$watch('provider.tipoDoc', value => {

                            if (value == '-') {
                                this.provider.numDoc = '';
                            }
                        });
                    }
                }
            }

            function confirmDelete(providerId) {
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
                        @this.call('destroy', providerId);
                    }
                });
            }
        </script>
    @endpush
</div>
