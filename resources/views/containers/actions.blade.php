<div class="w-8 flex items-center">
    <div class="absolute">
        <x-wire-dropdown>
            <x-slot name="trigger">
                <i class="fa-solid fa-bars"></i>
            </x-slot>

            {{-- EIR --}}
            <x-wire-dropdown.item
                onclick="confirmRedirect('{{ route('inspections.eir.create', $trace->id) }}', '¿Deseas generar el EIR?')"
                class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-receipt mr-2 group-hover:text-blue-500"></i>
                    <span class="group-hover:text-blue-500">Generar EIR</span>
                </div>
            </x-wire-dropdown.item>

            {{-- PTI --}}
            <x-wire-dropdown.item
                onclick="confirmRedirect('{{ route('inspections.pti.create', $trace->id) }}', '¿Deseas generar el PTI?')"
                class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-snowflake mr-2 group-hover:text-green-500"></i>
                    <span class="group-hover:text-green-500">Generar PTI</span>
                </div>
            </x-wire-dropdown.item>

            {{-- Leak Test --}}
            <x-wire-dropdown.item
                onclick="confirmRedirect('{{ route('inspections.leak.create', $trace->id) }}', '¿Deseas generar el Leak Test?')"
                class="group">
                <div class="flex items-center gap-2">
                    <i class="fas fa-bars-staggered mr-2 group-hover:text-purple-500"></i>
                    <span class="group-hover:text-purple-500">Generar Leak Test</span>
                </div>
            </x-wire-dropdown.item>

        </x-wire-dropdown>
    </div>
</div>
