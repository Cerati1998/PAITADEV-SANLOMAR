<x-dashboard-layout title="Codigos de Daños | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Codigos de Daños',
    ],
]">
    <x-slot name="action">
        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('damageModal')" blue />
    </x-slot>
    @livewire('damage-table', [], key('damage-table'))
</x-dashboard-layout>
