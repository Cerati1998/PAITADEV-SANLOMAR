<div x-data="dataEdit">

    <form wire:submit="update">

        <x-wire-modal-card title="Datos de Categoria"  width="3xl" wire:model="openModal"
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

            <div class="w-full mb-3">
                <x-wire-input required label="Codigo" x-model="lineCategory.code"
                    placeholder="Ingrese el Código de categoria" />
            </div>
            <div class="w-full mb-3">
                <x-wire-input label="Descripción" x-model="lineCategory.description"
                    placeholder="Describe las propiedades de la categoria" />
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
        function dataEdit() {
            return {
                lineCategory: @entangle('lineCategory'),
            }
        }

          function confirmDelete(vesselId) {
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
                    @this.call('destroy', vesselId);
                }
            });
        }
    </script>
@endpush
