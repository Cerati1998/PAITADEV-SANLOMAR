<x-wire-modal-card wire:model="email.openModal" width="lg">

    <p class="text-xl text-center mb-2">
        Enviar email
    </p>

    @if ($email->document)
        <p class="text-lg text-center uppercase mb-2">
            {{$email->document->type}} electrónica {{$email->document->serie}}-{{$email->document->correlativo}}
        </p>

        <p class="text-center uppercase mb-2">
            {{$email->client['numDoc'] ?? 'S/N'}} - {{$email->client['rznSocial']}}
        </p>
    @endif

    <form wire:submit="sendEmail">
        <div class="mb-4">
            <x-wire-input 
                label="Correo electrónico" 
                wire:model="email.value" />
        </div>

        <x-wire-button class="w-full" type="submit" spinner="sendEmail">
            Enviar correo
        </x-wire-button>
    </form>

</x-wire-modal-card>