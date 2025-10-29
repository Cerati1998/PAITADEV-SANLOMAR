<x-dashboard-layout title="Sucursales | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Sucursales',
    ],
]">

    @livewire('branches.branch-edit', [
        'branch' => $branch,
    ])

</x-dashboard-layout>