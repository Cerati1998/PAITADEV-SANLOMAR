<x-dashboard-layout :title="'Códigos de Componentes | ' . session('company')->razonSocial" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Códigos de Componentes',
    ],
]">
    <x-slot name="action">
        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('componentCreate')" blue />
    </x-slot>
    @livewire('component-table', [], key('component-table'))
    @livewire('component-create', [], key('component-create'))
</x-dashboard-layout>
