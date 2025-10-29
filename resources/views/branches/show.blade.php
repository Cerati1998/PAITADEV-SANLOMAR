<x-dashboard-layout title="Sucursales | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Sucursales',
    ],
]">

</x-dashboard-layout>