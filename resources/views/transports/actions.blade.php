<div class="w-8 flex items-center">
    <div class="absolute">
        <x-wire-dropdown>
            <x-slot name="trigger">
                <i class="fa-solid fa-bars"></i>
            </x-slot>
            <x-wire-dropdown.item wire:click="edit({{ $transport->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-edit mr-2 group-hover:text-green-500"></i>
                    <span class="group-hover:text-green-500">Editar</span>
                </div>
            </x-wire-dropdown.item>


            <x-wire-dropdown.item href="{{ route('transport.vehicles', $transport->id) }}" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-truck mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Vehiculos</span>
                </div>
            </x-wire-dropdown.item>

            <x-wire-dropdown.item href="{{ route('transport.drivers', $transport->id) }}" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-user-astronaut mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Conductores</span>
                </div>
            </x-wire-dropdown.item>

            <x-wire-dropdown.item onclick="confirmDelete({{ $transport->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-trash mr-3 group-hover:text-red-500"></i>
                    <span class="group-hover:text-red-500">Eliminar</span>
                </div>
            </x-wire-dropdown.item>
        </x-wire-dropdown>
    </div>
</div>
