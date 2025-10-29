<x-dashboard-layout title="Códigos de Ubicación | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Ubicaciones',
    ],
]">
    <x-slot name="action">
        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('locationCreate')" blue />
    </x-slot>

    @livewire('location-table', [], key('location-table'))
    @livewire('location-create',[],key('location-create'))
</x-dashboard-layout>
