<div x-data="brokerCreate">
    <form wire:submit="save">

        <x-wire-modal-card title="Agregar Agente Aduanero" name="brokerCreate" wire:model="openModal" width="3xl">

            <x-validation-errors class="mb-4" />

            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <x-label class="mb-1">
                        Tipo de Documento
                    </x-label>
                    <x-select required x-model="broker.tipoDoc" class="w-full">
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="flex-1">
                    <x-label class="mb-1">
                        Número de Documento
                    </x-label>
                    <x-input x-model="broker.numDoc" placeholder="Ingrese el número de documento" class="w-full"
                        x-bind:disabled="broker.tipoDoc == '-'" />
                </div>

                <div class="shrink-0 mt-6.5">
                    <x-wire-mini-button type="button" wire:click="searchDocument" spinner="searchDocument"
                        icon="magnifying-glass" x-bind:disabled="!(broker.tipoDoc == '1' || broker.tipoDoc == '6')" />
                </div>
            </div>


            <div class="grid grid-cols-2 gap-4">



                <div class="col-span-2">
                    <x-label class="mb-1">
                        Razón Social
                    </x-label>
                    <x-input required x-model="broker.rznSocial" placeholder="Ingrese la razón social" class="w-full" />
                </div>

                <div class="col-span-2">
                    <x-label class="mb-1">
                        Dirección
                    </x-label>
                    <x-input x-model="broker.direccion" placeholder="Ingrese la dirección" class="w-full" />
                </div>

                <x-wire-input label="Correo Electrónico" x-model="broker.email"
                    placeholder="Ingrese el correo electrónico (Opcional)" />

                <x-wire-input label="Teléfono" x-model="broker.telephone"
                    placeholder="Ingrese el teléfono (Opcional)" />

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
            function brokerCreate() {
                return {

                    broker: @entangle('broker'),
                    init() {
                        this.$watch('broker.tipoDoc', value => {

                            if (value == '-') {
                                this.broker.numDoc = '';
                            }
                        });
                    }
                }
            }
        </script>
    @endpush
</div>
