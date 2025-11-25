<div class="w-8 flex items-center">
    <div class="absolute">
        <x-wire-dropdown>
            <x-slot name="trigger">
                <i class="fa-solid fa-bars"></i>
            </x-slot>
            <x-wire-dropdown.item wire:click="summary({{ $cash->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clipboard-list mr-2 group-hover:text-stone-500"></i>
                    <span class="group-hover:text-stone-500">Ver Resumén</span>
                </div>
            </x-wire-dropdown.item>


            <x-wire-dropdown.item wire:click="register_deposit({{ $cash->id }})" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-money-bill-transfer mr-2 group-hover:text-green-500"></i>
                    <span class="group-hover:text-green-500">Registrar Depósito</span>
                </div>
            </x-wire-dropdown.item>

            <x-wire-dropdown.item href="{{ route('cash.report', $cash->id) }}" class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-download mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Descargar Reporte</span>
                </div>
            </x-wire-dropdown.item>


            @if ($cash->estado === 'abierta')
                <x-wire-dropdown.item onclick="confirmAction({{ $cash->id }},'closeCash')" class="group">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-lock mr-3 group-hover:text-red-500"></i>
                        <span class="group-hover:text-red-500">Cerrar</span>
                    </div>
                </x-wire-dropdown.item>
            @else
                <x-wire-dropdown.item onclick="confirmAction({{ $cash->id }},'voidCash')" class="group">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-lock-open mr-3 group-hover:text-red-500"></i>
                        <span class="group-hover:text-red-500">Reaperturar</span>
                    </div>
                </x-wire-dropdown.item>
            @endif
        </x-wire-dropdown>
    </div>
    @push('js')
        <script>
            function confirmAction(cashId, action) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '¡Sí, proceder!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call(action, cashId);
                    }
                });
            }
        </script>
    @endpush
</div>
