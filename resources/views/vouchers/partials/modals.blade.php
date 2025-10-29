@include('send.email')

@include('send.whatsapp')


<x-wire-modal-card  wire:model="openVoidedModal">

    <x-wire-alert warning title="Muy importante" class="mb-4">
        <p>
            Si la Comunicación de Baja es rechazada se deberá emitir una Nota de Crédito para anular el comprobante que no se pudo anular usando esta opción.
        </p>
    </x-wire-alert>


    <div class="mb-4">
        <x-wire-input label="Motivo" wire:model="voided.details.0.desMotivoBaja" />
    </div>

    <x-wire-button class="w-full" wire:click="sendVoided" spinner="sendVoided">
        Crear Comunicación de baja
    </x-wire-button>

</x-wire-modal-card>

@push('js')
    <script>

        Livewire.on('redirect', (data) => {
            url = data[0];
            window.open(url, '_blank');
        });

    </script>
@endpush