<x-dashboard-layout title="Transportistas | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Transportistas',
        'route' => route('transports.index'),
    ],
    [
        'name' => 'Vehiculos',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('vehicleCreate')" blue />

    </x-slot>

    @livewire(
        'vehicle-table',
        [
            'transport' => $transport,
        ],
        key('vehicle-table')
    )

    @livewire('vehicle-create', ['transport' => $transport,], key('vehicle-create'))

</x-dashboard-layout>
