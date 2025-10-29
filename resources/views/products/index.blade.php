<x-dashboard-layout title="Productos | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Productos',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('simpleModal')" blue />

    </x-slot>

    @livewire('product-table', [
        'affectations' => $affectations,
        'units' => $units,
        'categories' => $categories
    ])

</x-dashboard-layout>
