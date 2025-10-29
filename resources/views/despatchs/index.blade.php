<x-dashboard-layout title="Guías de remisión | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'GRE - Remitente',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Emitir guía" href="{{ route('despatchs.create') }}" blue />

    </x-slot>
    
    @livewire('despatch-table')

</x-dashboard-layout>