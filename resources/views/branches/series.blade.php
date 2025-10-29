<x-dashboard-layout title="Sucursales | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Sucursales',
    ],
]">

    @livewire('branches.manage-vouchers', [
        'branch' => $branch,
    ])

</x-dashboard-layout>