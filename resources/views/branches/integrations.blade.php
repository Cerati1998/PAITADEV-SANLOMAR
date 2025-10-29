<x-dashboard-layout title="Sucursales | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Sucursales',
        'route' => route('branches.index')
    ],
    [
        'name' => 'Integración API'
    ]
]">
    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('integrateModal')" blue />

    </x-slot>

    @livewire('branches.manage-integrations', [
        'branch' => $branch,
    ])

</x-dashboard-layout>