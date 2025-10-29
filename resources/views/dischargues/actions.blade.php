<div class="w-8 flex items-center">
    <div class="absolute">
        <x-wire-dropdown>
            <x-slot name="trigger">
                <i class="fa-solid fa-bars"></i>
            </x-slot>
            <x-wire-dropdown.item wire:click="edit({{ $dischargue->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-edit mr-2 group-hover:text-green-500"></i>
                    <span class="group-hover:text-green-500">Editar</span>
                </div>
            </x-wire-dropdown.item>


            <x-wire-dropdown.item href="{{ route('discharge.containers', $dischargue->id) }}" target="_blank"
                class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-box mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Contenedores</span>
                </div>
            </x-wire-dropdown.item>

            <x-wire-dropdown.item x-on:click="$wire.setSelectedDischargeId({{ $dischargue->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-trash mr-3 group-hover:text-red-500"></i>
                    <span class="group-hover:text-red-500">Cancelar</span>
                </div>
            </x-wire-dropdown.item>
        </x-wire-dropdown>
    </div>
</div>
