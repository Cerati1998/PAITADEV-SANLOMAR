<x-dashboard-layout title="Lineas | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Lineas',
    ],
]">

    <x-slot name="action">
        <x-wire-button label="Nueva" icon="plus" x-on:click="$openModal('lineCreate')" blue />
    </x-slot>

    @livewire('masters.line-table', [], key('line-table'))
    @livewire('masters.line-create', [], key('mastes.line-create'))
</x-dashboard-layout>
