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
        'name' => 'Factura - Boleta',
    ]
]">

    @livewire('generate-invoice', [
        'identities' => $identities,
        'units' => $units,
        'affectations' => $affectations,
        'detractions' => $detractions,
        'payment_methods' => $payment_methods,
        'perceptions' => $perceptions,
    ], key('generate-invoice'))

    @livewire('client-create', [
        'identities' => $identities
    ], key('client-create'))

</x-dashboard-layout>