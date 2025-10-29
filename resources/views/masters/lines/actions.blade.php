<div class="w-8 flex items-center">
    <div class="absolute">
        <x-wire-dropdown>
            <x-slot name="trigger">
                <i class="fa-solid fa-bars"></i>
            </x-slot>
            <x-wire-dropdown.item wire:click="edit({{ $line->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-edit mr-2 group-hover:text-green-500"></i>
                    <span class="group-hover:text-green-500">Editar</span>
                </div>
            </x-wire-dropdown.item>


            <x-wire-dropdown.item href="{{ route('line.vessels', $line->id) }}" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-ship mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Naves</span>
                </div>
            </x-wire-dropdown.item>

            <x-wire-dropdown.item href="{{ route('line.contecategories', $line->id) }}" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-table-columns mr-2 group-hover:text-stone-500"></i>
                    <span class="group-hover:text-stone-500">Categorias</span>
                </div>
            </x-wire-dropdown.item>

            <x-wire-dropdown.item onclick="confirmDelete({{ $line->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-trash mr-3 group-hover:text-red-500"></i>
                    <span class="group-hover:text-red-500">Eliminar</span>
                </div>
            </x-wire-dropdown.item>
        </x-wire-dropdown>
    </div>
</div>
