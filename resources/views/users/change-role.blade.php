<div>

    <form wire:submit="updateRole">

        <x-wire-modal-card title="Cambiar Rol: {{ $roleChange['name'] }}" wire:model="roleChange.open" width="md">

            <x-validation-errors class="mb-6" />

            <div>
                <x-label class="mb-1">
                    Rol
                </x-label>

                <x-select class="w-full" wire:model="roleChange.role_id">
                    <option value="" hidden>Seleccione un rol</option>
                    @foreach ($roles as $role)
                        @if ($role->name !== 'Super' || auth()->user()->hasRole('Super'))
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endif
                    @endforeach
                </x-select>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Actualizar" spinner />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>
