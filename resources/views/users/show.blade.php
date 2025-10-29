<x-dashboard-layout title="Usuarios | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Usuarios',
    ],
]">

</x-dashboard-layout>