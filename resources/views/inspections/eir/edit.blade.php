<x-dashboard-layout title="Editar EIR | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'EIRs',
        'route' => route('inspections.eir.list'),
    ],
    [
        'name' => 'Editar EIR',
    ],
]">
    @livewire('operations.inspections.eir-edit', ['eir_id'=>$eir_id,'catalogs' => $catalogs], 'operations.inspections.eir-edit')
</x-dashboard-layout>
