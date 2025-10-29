<x-dashboard-layout title="Contenedores Operados | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Contenedores operados',
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

    <div class="flex flex-wrap justify-between items-center mb-8 md:mb-3">
        <div>
            <h1 class="text-xl">Gestión de Contenedores</h1>
        </div>
    </div>
    @livewire('container-trace-table', [], 'container-trace-table')

    @push('js')
        <script>
            function confirmRedirect(url, message) {
                Swal.fire({
                    title: message,
                    text: "Esta acción no se puede deshacer",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Exito!",
                            text: "Redirigiendo...",
                            icon: 'success',
                        });
                        setTimeout(() => {
                            window.location.href = url;
                        }, 2000)
                    }
                });
            }
        </script>
    @endpush
</x-dashboard-layout>
