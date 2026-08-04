<div>

    @if ($row->sunat_status === \App\Enums\VoidedStatus::Aceptada)

        <button wire:click="showResponse({{ $row->id }})" xs>
            <x-wire-icon name="check-circle" class="w-6.5 h-6.5 text-green-500" solid />
        </button>

    @elseif ($row->sunat_status === \App\Enums\VoidedStatus::Pendiente)

        {{-- D-09: nunca ofrece reenvío, aunque el JSON no sea de éxito --}}
        <button wire:click="showResponse({{ $row->id }})" xs>
            <x-wire-icon name="clock" class="w-6.5 h-6.5 text-amber-500" solid />
        </button>

    @elseif ($row->sunat_status === \App\Enums\VoidedStatus::Rechazada)

        @if (is_null($row->sunat_ticket))

            {{-- El envío inicial nunca llegó a SUNAT (D-06): reenviar es legítimo --}}
            <button wire:click="sendXml({{ $row->id }})" xs>
                <img class='h-6' src="{{ asset('img/icons/get_cdr.svg') }}"/>
            </button>

        @else

            {{-- SUNAT ya recibió el XML: reenviarlo devolvería 2109 (D-09) --}}
            <button wire:click="showResponse({{ $row->id }})" xs>
                <i class="text-xl fas fa-ban text-red-500"></i>
            </button>

        @endif

    @else

        {{-- Filas históricas anteriores a la migración (D-13): sunat_status es NULL.
             Solo se ofrece reenvío cuando nunca se envió nada (sin ticket y sin
             respuesta guardada); cualquier otra combinación pudo haber sido
             recibida por SUNAT y no debe inducir a reenviar. --}}

        @if (is_null($row->sunat_ticket) && empty($row->sunatResponse))

            <button wire:click="sendXml({{ $row->id }})" xs>
                <img class='h-6' src="{{ asset('img/icons/get_cdr.svg') }}"/>
            </button>

        @else

            <button wire:click="showResponse({{ $row->id }})" xs>
                <i class="text-xl fas fa-question-circle text-gray-400"></i>
            </button>

        @endif

    @endif

</div>
