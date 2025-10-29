<x-dashboard-layout title="Sucursales | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Sucursales',
    ],
]">
    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" href="{{route('branches.create')}}" blue />

    </x-slot>

    @livewire('branches.branch-table')

</x-dashboard-layout>