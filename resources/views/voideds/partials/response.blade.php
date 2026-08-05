<div>

    @if ($row->sunat_status === \App\Enums\VoidedStatus::Aceptada)

        <div class="flex items-center justify-between gap-2">
            <button wire:click="showResponse({{ $row->id }})" xs>
                <x-wire-icon name="check-circle" class="w-6.5 h-6.5 text-green-500" solid />
            </button>
            {{-- D-06: badge de texto junto al icono, mismo precedente que sunatResponse.blade.php --}}
            <x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
        </div>

    @elseif ($row->sunat_status === \App\Enums\VoidedStatus::Pendiente)

        {{-- D-01/D-02: el botón dispara la reconsulta del ticket ya
             guardado, no un reenvío — canResendXml() sigue devolviendo false
             para este estado. --}}
        <div class="flex items-center justify-between gap-2">
            <button wire:click="sendXml({{ $row->id }})" xs>
                <x-wire-icon name="clock" class="w-6.5 h-6.5 text-amber-500" solid />
            </button>
            <x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
        </div>

    @elseif ($row->sunat_status === \App\Enums\VoidedStatus::Rechazada)

        @if (is_null($row->sunat_ticket))

            {{-- El envío inicial nunca llegó a SUNAT (D-06): reenviar es legítimo --}}
            <div class="flex items-center justify-between gap-2">
                <button wire:click="sendXml({{ $row->id }})" xs>
                    <img class='h-6' src="{{ asset('img/icons/get_cdr.svg') }}"/>
                </button>
                <x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
            </div>

        @else

            {{-- SUNAT ya recibió el XML: reenviarlo devolvería 2109 (D-09) --}}
            <div class="flex items-center justify-between gap-2">
                <button wire:click="showResponse({{ $row->id }})" xs>
                    <i class="text-xl fas fa-ban text-red-500"></i>
                </button>
                <x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
            </div>

        @endif

    @else

        {{-- Filas históricas anteriores a la migración (D-13): sunat_status es NULL.
             Solo se ofrece reenvío cuando nunca se envió nada (sin ticket y sin
             respuesta guardada); cualquier otra combinación pudo haber sido
             recibida por SUNAT y no debe inducir a reenviar. D-08: el badge se
             resuelve directamente en Blade, sin pasar por el enum — declara el
             estado desconocido, no "sin enviar". --}}

        @if (is_null($row->sunat_ticket) && empty($row->sunatResponse))

            <div class="flex items-center justify-between gap-2">
                <button wire:click="sendXml({{ $row->id }})" xs>
                    <img class='h-6' src="{{ asset('img/icons/get_cdr.svg') }}"/>
                </button>
                <x-wire-badge sm color="gray" label="SIN ESTADO" />
            </div>

        @elseif (! is_null($row->sunat_ticket))

            {{-- CR-01: fila histórica con ticket vivo cuyo desenlace SUNAT
                 todavía no confirmó. El servidor ya la encamina hacia
                 resolveTicket() en sendXml(); sin esta hoja el ticket
                 quedaría huérfano, sin ninguna vía de recuperación. --}}
            <div class="flex items-center justify-between gap-2">
                <button wire:click="sendXml({{ $row->id }})" xs title="Consultar el estado de este ticket en SUNAT">
                    <x-wire-icon name="arrow-path" class="w-6.5 h-6.5 text-blue-500" solid />
                </button>
                <x-wire-badge sm color="gray" label="SIN ESTADO" />
            </div>

        @else

            <div class="flex items-center justify-between gap-2">
                <button wire:click="showResponse({{ $row->id }})" xs>
                    <i class="text-xl fas fa-question-circle text-gray-400"></i>
                </button>
                <x-wire-badge sm color="gray" label="SIN ESTADO" />
            </div>

        @endif

    @endif

</div>
