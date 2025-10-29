<x-dashboard-layout title="Lineas | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Lineas',
        'route' => route('lines.index'),
    ],
    [
        'name' => 'Naves',
    ],
]">

    <x-slot name="action">
        <x-wire-button label="Nueva" x-on:click="$openModal('vesselCreate')" blue />
    </x-slot>

    @livewire('masters.vessel-table', ['shipping_line' => $shipping_line], key('vessel-table'))
    @livewire('masters.vessel-create', ['shipping_line' => $shipping_line], key('vessel-create'))
</x-dashboard-layout>
