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
    @livewire('operations.inspections.roof-evidence-modal',['eirId' => $eir_id],'operations.inspections.roof-evidence-modal')    
</x-dashboard-layout>
