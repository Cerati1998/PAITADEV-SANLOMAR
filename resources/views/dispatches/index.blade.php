<x-dashboard-layout title="Despachos | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Despachos',
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

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('createDispatch')" blue />

    </x-slot>

    @livewire('operations.dispatches.dispatch-table', [], 'operations.dispatches.dispatch-table')
    @livewire('operations.dispatches.dispatch-create', [], 'operations.dispatches.dispatch-create')
</x-dashboard-layout>
