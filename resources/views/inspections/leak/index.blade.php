<x-dashboard-layout title="Leak Test | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Leak Test',
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
            <h1 class="text-xl">Lista de Leak Tests</h1>
        </div>
        <x-wire-button label="Generar" icon="plus" x-on:click="$openModal('createLeakTest')" blue />
    </div>

    @livewire('operations.inspections.leak-test-table', [], 'operations.inspections.leak-test-table')
    @livewire('operations.inspections.leak-test-create', [], 'operations.inspections.leak-test-create')
</x-dashboard-layout>
