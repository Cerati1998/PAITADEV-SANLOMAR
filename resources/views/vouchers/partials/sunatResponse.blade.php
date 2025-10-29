<div class="w-full text-xl space-y-5">
    <div class="flex justify-between">
    
        {{ match ($document->tipoDoc) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de crédito',
            '08' => 'Nota de débito',
            '09' => 'Guía de remisión',
            default => 'Otro',
        } }}
        <x-wire-badge lg primary :label="$document->serie . '-' . $document->correlativo" />

    </div>

    <div class="flex">
        @if ($document->sunatResponse['success'])
            
            <x-wire-icon name="check" class="w-6 h-6 text-green-500 mr-2" solid />
            Enviado a SUNAT

        @else
            
            <i class="text-xl fas fa-times text-red-500 mr-2"></i>
            Error al Enviar a SUNAT

        @endif
    </div>

    @if ($document->sunatResponse['success'])

        <div class="flex justify-between">
            Estado:

            @if ($document->sunatResponse['cdrResponse']['code'] == '0')
                <x-wire-badge lg primary label="ACEPTADO" />
            @elseif ($document->sunatResponse['cdrResponse']['code'] >= 2000 && $document->sunatResponse['cdrResponse']['code'] <= 3999)
                <x-wire-badge lg danger label="RECHAZADO" />
            @else
                <x-wire-badge lg warning label="EXCEPCIÓN" />
            @endif
        </div>

        <div class="flex justify-between">
            Código:
            <x-wire-badge lg primary :label="$document->sunatResponse['cdrResponse']['code']" />
        </div>

        <div class="whitespace-normal">
            {{ $document->sunatResponse['cdrResponse']['description'] }}
        </div>

        @if ($document->sunatResponse['cdrResponse']['notes'])

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
            <x-wire-badge lg primary :label="$document->sunatResponse['error']['code']" />
        </div>

        <div class="whitespace-normal">
            {!!$document->sunatResponse['error']['message']!!}
        </div>

        {{-- @if ($document->sunatResponse['error']['code'] >= '0100' && $document->sunatResponse['error']['code'] <= '1999')
            <div class="flex space-x-2 justify-center">
                <x-wire-button dark md onclick="prueba({{$document->id}})" xs>
                    <img class='h-6' src='/img/icons/get_cdr.svg'/> Volver a enviar
                </x-wire-button>
            </div>
        @endif --}}

    @endif

</div>