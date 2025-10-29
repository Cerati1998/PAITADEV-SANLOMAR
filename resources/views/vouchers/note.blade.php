<x-dashboard-layout title="Comprobantes | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Comprobantes',
        'route' => route('vouchers.index'),
    ],
    [
        'name' => 'Nota',
    ]
]">

    @livewire('generate-note', [
        'units' => $units,
        'affectations' => $affectations,
    ])

    @include('clients.create')

</x-dashboard-layout>