<x-dashboard-layout title="Agencias de Aduana | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Agencias de Aduana',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('brokerCreate')" blue />

    </x-slot>

    @livewire('custom-broker-table', [
        'identities' => $identities,
    ], key('custom-broker-table'))

    @livewire('custom-broker-create', ['identities' => $identities], key('custom-broker-create'))

</x-dashboard-layout>