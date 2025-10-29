<x-dashboard-layout title="EIRs Gate IN | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Equipments Interchange Receipt',
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
            <h1 class="text-xl">Lista de EIRs</h1>
        </div>
        <x-wire-button label="Generar" icon="plus" href="{{ route('inspections.eir.create') }}" blue />
    </div>

    @livewire('operations.inspections.eir-table', [], 'operations.inspections.eir-table')
</x-dashboard-layout>
