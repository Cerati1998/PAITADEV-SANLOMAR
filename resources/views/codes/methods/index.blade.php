<x-dashboard-layout title="Métodos de Reparación | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Métodos de Reparación',
    ],
]">
    <x-slot name="action">
        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('methodCreate')" blue />
    </x-slot>

    @livewire('method-table', [], key('method-table'))
    @livewire('method-create', [], key('method-create'))

</x-dashboard-layout>
