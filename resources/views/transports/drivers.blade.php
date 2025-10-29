<x-dashboard-layout title="Conductores | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Transportistas',
        'route' => route('transports.index'),
    ],
    [
        'name' => 'Conductores',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('driverCreate')" blue />

    </x-slot>

    @livewire(
        'driver-table',
        [
            'identities' => $identities,
            'transport' => $transport,
        ],
        key('driver-table')
    )

    @livewire(
        'driver-create',
        [
            'identities' => $identities,
            'transport' => $transport,
        ],
        key('driver-create')
    )

</x-dashboard-layout>
