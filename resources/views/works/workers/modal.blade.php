<div>

    <form wire:submit="update">

        <x-wire-modal-card title="Datos de Trabajador" wire:model="workerEdit.open" width="xl" :hide-close="true">

            <x-validation-errors class="mb-6" />

            <div>

                <div>
                    <x-label value="{{ __('Name') }}" />
                    <x-input class="block mt-1 w-full disabled:opacity-50 disabled:bg-gray-100" type="text"
                        placeholder="Nombre del usuario" wire:model="workerEdit.name" />
                </div>

                <div class="mt-3">
                    <x-label class="mb-1">
                        Tipo de Documento
                    </x-label>
                    <x-select required wire:model="workerEdit.tipoDoc" class="w-full">
                        <option value="" hidden>Seleccione...</option>
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mt-3">
                    <x-label class="mb-1">
                        Nº de Documento
                    </x-label>
                    <x-input wire:model="workerEdit.numDoc" placeholder="Ingrese el número de documento"
                        class="w-full" />
                </div>

                <div class="mt-3">
                    <x-label value="{{ __('Email') }}" />
                    <x-input class="block mt-1 w-full disabled:opacity-50 disabled:bg-gray-100" type="email"
                        placeholder="Correo electrónico" wire:model="workerEdit.email" autocomplete="workername" />
                </div>
                <div class="mt-3">
                    <x-label value="{{ __('Telefono') }}" />
                    <x-input class="block mt-1 w-full disabled:opacity-50 disabled:bg-gray-100" type="number"
                        placeholder="Número telefonico" wire:model="workerEdit.telefono" />
                </div>

                <div class="mt-3">
                    <x-label value="Cargo" />

                    <x-select class="block mt-1 w-full" wire:model="workerEdit.cargo">
                        <option value="" hidden>Selecciona un Cargo</option>
                        @foreach ($cargos as $cargo)
                            <option value="{{ $cargo }}">
                                {{ $cargo }}
                            </option>
                        @endforeach
                    </x-select>

                </div>
                <div class="mt-3">
                    <x-label value="Sucursal" />

                    <x-select class="block mt-1 w-full" wire:model="workerEdit.branch_id">
                        <option value="" hidden>Selecciona una sucursal</option>
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
        function confirmDelete(workerId) {
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
                    @this.call('destroy', workerId);
                }
            });
        }
    </script>
@endpush
