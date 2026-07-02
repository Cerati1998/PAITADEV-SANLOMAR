<div>

    <form wire:submit="save">

        <x-wire-modal-card title="Nuevo Rol" name="roleCreate" width="4xl" wire:model="openModal" :hide-close="true">

            <x-validation-errors class="mb-4" />

            <div class="mb-4">
                <x-wire-input required label="Nombre del Rol" wire:model="role.name"
                    placeholder="Ingrese el nombre del rol" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-h-[50vh] overflow-y-auto pr-2">
                @foreach ($permissionGroups as $group => $permissions)
                    <div>
                        <h4 class="font-semibold text-sm uppercase text-gray-500 mb-2 border-b pb-1">
                            {{ $group }}
                        </h4>
                        <div class="space-y-2">
                            @foreach ($permissions as $permissionName)
                                <label class="flex items-center">
                                    <x-checkbox wire:model="role.permissions" :value="$permissionName" />
                                    <span class="ms-2 text-sm text-gray-600">
                                        {{ $permissionName }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>
