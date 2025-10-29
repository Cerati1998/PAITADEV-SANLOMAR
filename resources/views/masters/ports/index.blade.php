<x-dashboard-layout title="Puertos | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Puertos',
    ],
]">

<x-slot name="action">
    <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('portCreate')" blue />
</x-slot>

@livewire('masters.port-table',[],key('port-table'))
 @livewire('masters.port-create',[],key('port-create'))
</x-dashboard-layout>