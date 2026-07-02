<div class="w-8">
    <x-wire-button green wire:click="edit({{$user->id}})" xs>
        <i class="fas fa-edit"></i>
    </x-wire-button>

    @can('users.role.update')
        <x-wire-button blue wire:click="editRole({{ $user->id }})" xs>
            <i class="fas fa-user-tag"></i>
        </x-wire-button>
    @endcan

    <x-wire-button red onclick="confirmDelete({{$user->id}})" xs>
        <i class="fas fa-trash"></i>
    </x-wire-button>
</div>