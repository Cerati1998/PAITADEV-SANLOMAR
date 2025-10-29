<x-dashboard-layout title="Clientes | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Clientes',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('clientCreate')" blue />

    </x-slot>

    @livewire('client-table', [
        'identities' => $identities,
    ], key('client-table'))

    @livewire('client-create', ['identities' => $identities], key('client-create'))

</x-dashboard-layout>