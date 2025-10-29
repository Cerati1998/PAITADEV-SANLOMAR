<div class="w-8">
    <x-wire-button green wire:click="edit({{ $workorder->id }})" title="Gestionar Trabajos" xs>
        <i class="fas fa-person-digging"></i>
    </x-wire-button>
    <x-wire-button blue href="{{-- {{ route('inspections.workorder.pdf', $workorder->id) }} --}}" target="_blank" xs>
        <i class="fas fa-file-pdf"></i>
    </x-wire-button>

    <x-wire-button red onclick="confirmAction('No podrás revertir esto','destroy',{{ $workorder->id }})" xs>
        <i class="fas fa-trash"></i>
    </x-wire-button>
</div>
