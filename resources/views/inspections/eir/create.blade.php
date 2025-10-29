<x-dashboard-layout title="Generar EIR | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'EIRs',
        'route' => route('inspections.eir.list'),
    ],
    [
        'name' => 'Generar EIR',
    ],
]">
    @livewire('operations.inspections.eir-create', ['trace' => $trace,'catalogs' => $catalogs], 'operations.inspections.eir-create')
</x-dashboard-layout>
