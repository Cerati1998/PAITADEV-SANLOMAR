@include('send.email')

@include('send.whatsapp')


@push('js')
    <script>

        Livewire.on('redirect', (data) => {
            url = data[0];
            window.open(url, '_blank');
        });

    </script>
@endpush