<x-dashboard-layout title="Trabajadores | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Trabajadores',
    ],
]">

    <x-slot name="action">

        <x-wire-button label="Nuevo" icon="plus" x-on:click="$openModal('simpleModal')" blue />

    </x-slot>


    @livewire('operations.works.worker-table', ['branches' => $branches, 'identities' => $identities,'cargos'=>$cargos], 'operations.works.worker-table')
    @include('works.workers.create')
    </x-dashboard-layout>
