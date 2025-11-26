<x-dashboard-layout title="Gestión de Gastos | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Gestión de Gastos',
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

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('billCreate')" blue />

    </x-slot>

    @livewire('bill-table', [], key('bill-table'))
    @livewire('provider-create',['identities'=>$identities],key('provider-create'))

</x-dashboard-layout>
