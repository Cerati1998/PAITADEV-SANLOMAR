<x-dashboard-layout title="Roles y Permisos | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Roles y Permisos',
    ],
]">

    <x-slot name="action">
        @can('roles.create')
            <x-wire-button label="Nuevo Rol" icon="plus" x-on:click="$openModal('roleCreate')" blue />
        @endcan
    </x-slot>

    @livewire('roles.role-table', [], key('role-table'))
    @livewire('roles.role-create', [], key('role-create'))

</x-dashboard-layout>
