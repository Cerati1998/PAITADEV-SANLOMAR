<x-dashboard-layout title="Ordenes de Trabajo | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Ordenes de Trabajo',
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
        <x-wire-button icon="plus" label="Nueva" x-on:click="$openModal('createWorkOrder')" blue />
    </x-slot>

    @livewire('operations.works.work-order-table', [], 'work-order-box')
    @livewire('operations.works.work-order-create', [], 'work-order-create')
    @include('works.workers.create')
</x-dashboard-layout>
