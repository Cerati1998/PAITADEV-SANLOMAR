<x-dashboard-layout title="Contenedores Devolucion | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Devoluciones',
        'route' => route('devolutions.index'),
    ],
    [
        'name' => 'Contenedores',
    ],
]">
    @push('css')
        <style>
            /* body{
                        background-color: #f3f4f6 !important;
                    } */
            @media screen and (min-width: 575.98px) {

                table tbody td,
                table thead th span {
                    font-size: 0.75rem !important;
                    line-height: 1rem !important;
                }
            }
        </style>
    @endpush
    <x-slot name="action">
        <x-wire-button label="Agregar" icon="plus" x-on:click="$openModal('containerAdd')" blue />
    </x-slot>

    @livewire(
        'DvContainerTable',
        [
            'originType' => \App\Models\Devolution::class,
            'originId' => $devolution->id,
        ],
        key('dvcontainers-table')
    )
    @livewire('masters.port-create', [], key('port-create'))
    @push('js')
        <script>
            Livewire.on('bulkAnulateConsult', data => {
                console.log(data[0].msg);
                Swal.fire({
                    title: data[0].title ?? "¿Estas seguro de Anular?",
                    text: data[0].msg ?? "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '¡Sí, bórralo!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('bulkAnulate');
                    }
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
