<div>

    <form wire:submit="update">

        <x-wire-modal-card title="USUARIO" wire:model="userEdit.open" width="xl">

            <x-validation-errors class="mb-6" />

            <div>

                <div>
                    <x-label value="{{ __('Name') }}" />
                    <x-input class="block mt-1 w-full disabled:opacity-50 disabled:bg-gray-100" type="text"
                        placeholder="Nombre del usuario" wire:model="userEdit.name" disabled />
                </div>

                <div class="mt-4">
                    <x-label class="mb-1">
                        Tipo de Documento
                    </x-label>
                    <x-select required wire:model="userEdit.tipoDoc" class="w-full">
                        <option value="" hidden>Seleccione...</option>
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mt-4">
                    <x-label class="mb-1">
                        Nº de Documento
                    </x-label>
                    <x-input wire:model="userEdit.numDoc" placeholder="Ingrese el número de documento" class="w-full"/>
                </div>

                <div class="mt-4">
                    <x-label value="{{ __('Email') }}" />
                    <x-input class="block mt-1 w-full disabled:opacity-50 disabled:bg-gray-100" type="email"
                        placeholder="Correo electrónico" wire:model="userEdit.email" disabled autocomplete="username" />
                </div>

                <div class="mt-4">
                    <x-label value="Sucursal" />

                    <x-select class="block mt-1 w-full" wire:model="userEdit.branch_id">
                        <option value="">Selecciona una sucursal</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-select>

                </div>

            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Actualizar" spinner />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>

@push('js')
    <script>
        function confirmDelete(userId) {
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
                    @this.call('destroy', userId);
                }
            });
        }
    </script>
@endpush
