<x-wire-modal-card wire:model="whatsapp.openModal" width="lg">

    <p class="text-xl text-center mb-2">
        Enviar a Whatsapp
    </p>

    @if ($whatsapp->document)
    
        <p class="text-lg text-center uppercase mb-2">
            {{$whatsapp->document->type}} Electrónica {{$whatsapp->document->serie}}-{{$whatsapp->document->correlativo}}
        </p>

        <p class="text-center uppercase mb-2">
            {{$whatsapp->client['numDoc'] ?? 'S/N'}} - {{$whatsapp->client['rznSocial']}}
        </p>

    @endif

    <div class="mb-4">
        <x-wire-select
            label="Código país"
            wire:model="whatsapp.phone_code"
            :async-data="[
                'api' => route('api.phone-codes.index'),
                'method' => 'POST', // default is GET
            ]"
            option-label="name"
            option-value="id" />
    </div>

    <div class="mb-4">
        <x-wire-input 
            label="Número de teléfono" 
            wire:model="whatsapp.phone_number" />
    </div>

    <x-wire-button class="w-full mb-4" wire:click="sendWhatsapp('web')" spinner="sendWhatsapp('web')">
        Enviar a Whatsapp<span class="font-semibold">WEB</span><i class="fa-brands fa-whatsapp"></i>
    </x-wire-button>

    <x-wire-button class="w-full" wire:click="sendWhatsapp('app')" spinner="sendWhatsapp('app')">
        Enviar a Whatsapp<span class="font-semibold">APP</span><i class="fa-brands fa-whatsapp"></i>
    </x-wire-button>

</x-wire-modal-card>