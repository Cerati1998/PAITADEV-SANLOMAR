<x-dashboard-layout title="Guías de remisión | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'GRE - Remitente',
    ],
]">

    @livewire('generate-despatch', [
        'identities' => $identities,
        'units' => $units,
    ], key('generate-despatch'))
    
    {{-- @include('clients.create') --}}
    @livewire('client-create', [
        'identities' => $identities
    ], key('client-create'))

</x-dashboard-layout>