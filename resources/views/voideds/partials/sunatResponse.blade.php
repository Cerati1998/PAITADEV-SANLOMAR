<div class="w-full text-xl space-y-5">
    <div class="flex justify-between">

        Comunicación de baja
        <x-wire-badge lg primary :label="$document->correlativo" />

    </div>

    @if ($document->sunat_status === \App\Enums\VoidedStatus::Aceptada)

        <div class="flex justify-between">
            Estado:
            <x-wire-badge lg :color="$document->sunat_status->badgeColor()" :label="$document->sunat_status->label()" />
        </div>

        <div class="flex">
            <x-wire-icon name="check" class="w-6 h-6 text-green-500 mr-2" solid />
            Aceptado por SUNAT
        </div>

        <div class="flex justify-between">
            Código:
            <x-wire-badge lg primary :label="$document->sunatResponse['cdrResponse']['code']" />
        </div>

        <div class="whitespace-normal">
            {{ $document->sunatResponse['cdrResponse']['description'] }}
        </div>

        @if ($document->sunatResponse['cdrResponse']['notes'] ?? false)

            <div class="whitespace-normal bg-red-100">
                <div class="w-full">
                    <p class="font-semibold">Observaciones:</p>
                    <p class="font-semibold py-2">(Corregir estas observaciones en siguientes emisiones)</p>
                    <ul>
                        @foreach ($document->sunatResponse['cdrResponse']['notes'] as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                </div>

            </div>

        @endif

    @elseif ($document->sunat_status === \App\Enums\VoidedStatus::Pendiente)

        <div class="flex justify-between">
            Estado:
            <x-wire-badge lg :color="$document->sunat_status->badgeColor()" :label="$document->sunat_status->label()" />
        </div>

        <div class="flex">
            <x-wire-icon name="clock" class="w-6 h-6 text-amber-500 mr-2" solid />
            SUNAT recibió la comunicación de baja y aún la procesa
        </div>

        @php
            $pending = $document->sunatResponse['pending'] ?? [];
            $reasonLabel = match ($pending['reason'] ?? null) {
                'codigo_98' => 'SUNAT aún está procesando la comunicación',
                'fallo_transporte' => 'No se pudo confirmar el estado con SUNAT',
                default => null,
            };
        @endphp

        @if ($reasonLabel)
            <div class="whitespace-normal">
                {{ $reasonLabel }}
            </div>
        @endif

        @if (!is_null($pending['code'] ?? null))
            <div class="flex justify-between">
                Código:
                <x-wire-badge lg warning :label="$pending['code']" />
            </div>
        @endif

        @if ($pending['message'] ?? null)
            <div class="whitespace-normal">
                {{ $pending['message'] }}
            </div>
        @endif

        <div class="flex justify-between">
            Ticket:
            <x-wire-badge lg primary :label="$document->sunat_ticket" />
        </div>

    @elseif ($document->sunat_status === \App\Enums\VoidedStatus::Rechazada)

        <div class="flex justify-between">
            Estado:
            <x-wire-badge lg :color="$document->sunat_status->badgeColor()" :label="$document->sunat_status->label()" />
        </div>

        <div class="flex">
            <i class="text-xl fas fa-times text-red-500 mr-2"></i>
            Rechazado por SUNAT
        </div>

        @if (isset($document->sunatResponse['cdrResponse']))

            <div class="flex justify-between">
                Código:
                <x-wire-badge lg primary :label="$document->sunatResponse['cdrResponse']['code']" />
            </div>

            <div class="whitespace-normal">
                {{ $document->sunatResponse['cdrResponse']['description'] }}
            </div>

            @if ($document->sunatResponse['cdrResponse']['notes'] ?? false)

                <div class="whitespace-normal bg-red-100">
                    <div class="w-full">
                        <p class="font-semibold">Observaciones:</p>
                        <p class="font-semibold py-2">(Corregir estas observaciones en siguientes emisiones)</p>
                        <ul>
                            @foreach ($document->sunatResponse['cdrResponse']['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>

                </div>

            @endif

        @else

            <div class="flex justify-between">
                Código:
                <x-wire-badge lg primary :label="$document->sunatResponse['error']['code'] ?? null" />
            </div>

            <div class="whitespace-normal">
                {{ $document->sunatResponse['error']['message'] ?? null }}
            </div>

        @endif

    @else

        {{-- Filas históricas anteriores a la migración (D-13): sunat_status es NULL.
             Fallback al discriminador antiguo basado en el booleano success. --}}

        <div class="flex justify-between">
            Estado:
            <x-wire-badge lg label="DESCONOCIDO" />
        </div>

        <div class="flex">
            @if ($document->sunatResponse['success'] ?? false)

                <x-wire-icon name="check" class="w-6 h-6 text-green-500 mr-2" solid />
                Enviado a SUNAT

            @else

                <i class="text-xl fas fa-times text-red-500 mr-2"></i>
                Error al Enviar a SUNAT

            @endif
        </div>

        @if ($document->sunatResponse['success'] ?? false)

            @if (isset($document->sunatResponse['cdrResponse']))

                <div class="flex justify-between">
                    Código:
                    <x-wire-badge lg primary :label="$document->sunatResponse['cdrResponse']['code']" />
                </div>

                <div class="whitespace-normal">
                    {{ $document->sunatResponse['cdrResponse']['description'] }}
                </div>

                @if ($document->sunatResponse['cdrResponse']['notes'] ?? false)

                    <div class="whitespace-normal bg-red-100">
                        <div class="w-full">
                            <p class="font-semibold">Observaciones:</p>
                            <p class="font-semibold py-2">(Corregir estas observaciones en siguientes emisiones)</p>
                            <ul>
                                @foreach ($document->sunatResponse['cdrResponse']['notes'] as $note)
                                    <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>

                    </div>

                @endif

            @endif

        @else

            <div class="flex justify-between">
                Código:
                <x-wire-badge lg primary :label="$document->sunatResponse['error']['code'] ?? null" />
            </div>

            <div class="whitespace-normal">
                {{ $document->sunatResponse['error']['message'] ?? null }}
            </div>

        @endif

    @endif

</div>
