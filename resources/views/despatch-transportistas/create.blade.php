<x-dashboard-layout title="Guías de remisión transportista | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'GRE - Transportista',
    ],
]">

    @livewire('generate-despatch-transportista', [
        'identities' => $identities,
        'units' => $units,
        'branches' => $branches,
        'related_documents' => $related_documents,
    ], key('generate-despatch-transportista'))

    @livewire('client-create', [
        'identities' => $identities
    ], key('client-create'))

</x-dashboard-layout>
