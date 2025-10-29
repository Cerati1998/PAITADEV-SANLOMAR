<x-dashboard-layout title="Proveedores | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Proveedores',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('providerCreate')" blue />

    </x-slot>

    @livewire(
        'provider-table',
        [
            'identities' => $identities,
        ],
        key('provider-table')
    )

    @livewire('provider-create', ['identities' => $identities], key('provider-create'))
</x-dashboard-layout>
