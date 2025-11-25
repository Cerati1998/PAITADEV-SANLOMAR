<div class="w-8 flex items-center">
    <div class="absolute">
        <x-wire-dropdown>
            <x-slot name="trigger">
                <i class="fa-solid fa-bars"></i>
            </x-slot>
            <x-wire-dropdown.item wire:click="summary({{ $cash->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clipboard-list mr-2 group-hover:text-green-500"></i>
                    <span class="group-hover:text-green-500">Ver Resumén</span>
                </div>
            </x-wire-dropdown.item>


            <x-wire-dropdown.item href="{{ route('cash.report', $cash->id) }}" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-download mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Descargar Reporte</span>
                </div>
            </x-wire-dropdown.item>


            <x-wire-dropdown.item onclick="confirmClose({{ $cash->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-lock mr-3 group-hover:text-red-500"></i>
                    <span class="group-hover:text-red-500">Cerrar</span>
                </div>
            </x-wire-dropdown.item>
        </x-wire-dropdown>
    </div>
</div>
