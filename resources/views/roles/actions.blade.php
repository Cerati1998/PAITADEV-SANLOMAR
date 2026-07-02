<div class="w-8">
    @can('roles.update')
        <x-wire-button blue wire:click="managePermissions({{ $role->id }})" xs>
            <i class="fas fa-key"></i>
        </x-wire-button>
    @endcan

    @can('roles.delete')
        @if (!in_array($role->name, $protectedRoles))
            <x-wire-button red onclick="confirmDeleteRole({{ $role->id }})" xs>
                <i class="fas fa-trash"></i>
            </x-wire-button>
        @endif
    @endcan
</div>
