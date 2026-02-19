<div x-data="dataCreate">
    <form @submit.prevent="save">

        <x-wire-modal-card title="Gestionar Evidencias de Techo" name="manageRoofEvidence" width="3xl"
            wire:model="openModal" :hide-close="true">

            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-4">
                <x-wire-number :min="0" :max="100" label="Parches soldados"
                    wire:model="form.qty_welded_patch" />
                <x-wire-number :min="0" :max="100" label="Parches remachados"
                    wire:model="form.qty_riveted_patch" />
                <x-wire-number :min="0" :max="100" label="Parches en el riel"
                    wire:model="form.qty_rail_patch" />

            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 mb-4">
                <x-wire-number :min="0" :max="100" label="Corte sellado"
                    wire:model="form.qty_sealed_cut" />
                <x-wire-number :min="0" :max="100" label="Corte soldado"
                    wire:model="form.qty_welded_cut" />
            </div>
            <div class="mb-4">
                <x-wire-toggle left-label="Delaminaciones, Abolladuras Y Reseñas leves"
                    wire:model="form.has_additional_damages" sm />
            </div>


            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />
                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>
    <x-spinner :targets="['save']" message="Guardando Evidencias..." logo="{{ asset('img/logos/favicon.svg') }}"
        logoSize="56" />
    @push('js')
    <script>
        function dataCreate(){
            return{
                save() {
                        
                        Swal.fire({
                            title: "¿Estas seguro de Guardar?",
                            text: "¡No podrás revertir esto!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '¡Sí, Continua!',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                @this.call('save');
                            }
                        })
                    }
            }
        }

    </script>
    @endpush
</div>
