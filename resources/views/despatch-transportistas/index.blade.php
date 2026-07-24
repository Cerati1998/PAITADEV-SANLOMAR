<x-dashboard-layout title="Guías de remisión transportista | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'GRE - Transportista',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Emitir guía" href="{{ route('despatch-transportistas.create') }}" blue />

    </x-slot>

    @livewire('despatch-transportista-table')

</x-dashboard-layout>
