<x-dashboard-layout title="Anulaciones | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Anulaciones',
    ],
]">

    @livewire('voided-table')

</x-dashboard-layout>