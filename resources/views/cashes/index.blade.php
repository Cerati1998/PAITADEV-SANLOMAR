<x-dashboard-layout title="Arqueo de Cajas | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Listado de Cajas',
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

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('archingCreate')" blue />

    </x-slot>

    @livewire('arching-cash-table', [], key('arching-cash-table'))
    @livewire('arching-cash-create', [], key('arching-cash-create'))


</x-dashboard-layout>