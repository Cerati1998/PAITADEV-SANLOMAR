<div class="w-8">
    <x-wire-button green href="{{ route('inspections.eir.edit', $eir->id) }}" xs>
        <i class="fas fa-edit"></i>
    </x-wire-button>
    <x-wire-button blue href="{{ route('inspections.eir.pdf', $eir->id) }}" target="_blank" xs>
        <i class="fas fa-file-pdf"></i>
    </x-wire-button>

    <x-wire-button red onclick="confirmDelete({{ $eir->id }})" xs>
        <i class="fas fa-trash"></i>
    </x-wire-button>
</div>
