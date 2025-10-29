<div class="w-8">
    <x-wire-button green wire:click="edit({{$broker->id}})" xs>
        <i class="fas fa-edit"></i>
    </x-wire-button>

    <x-wire-button red onclick="confirmDelete({{$broker->id}})" xs>
        <i class="fas fa-trash"></i>
    </x-wire-button>
</div>