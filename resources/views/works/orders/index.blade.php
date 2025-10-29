<x-dashboard-layout title="Ordenes de Trabajo | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Ordenes de Trabajo',
    ],
]">

    @push('css')
        <style>
            /* body{
                                                                background-color: #f3f4f6 !important;
                                                            } */
            @media screen and (min-width: 575.98px) {

                table tbody td,
                table thead th span {
                    font-size: 0.75rem !important;
                    line-height: 1rem !important;
                }
            }
        </style>
    @endpush

    <x-slot name="action">
        <x-wire-button icon="plus" label="Nueva" x-on:click="$openModal('createWorkOrder')" blue />
    </x-slot>
    <x-wire-card>
        {{-- Tabs --}}
        <x-tabs active="box-repairs">
            <x-slot name="header">
                <x-tab-link tab="box-repairs">
                    <i class="fa-solid fa-table-cells-large me-1"></i>
                    Estructura
                </x-tab-link>
                <x-tab-link tab="mach-repairs">
                    <i class="fa-solid fa-gears me-1"></i>
                    <span class="truncate w-12 md:w-auto">
                        Máquina
                    </span>
                </x-tab-link>
                <x-tab-link tab="others">
                    <i class="fa-solid fa-bridge-circle-check me-1"></i>
                    <span class="truncate w-12 md:w-auto">
                        Otros
                    </span>
                </x-tab-link>
            </x-slot>

            <x-tab-content tab="box-repairs">
                <div class="mt-4">
                    @livewire('operations.works.work-order-table', [], 'work-order-box')
                </div>
            </x-tab-content>

            <x-tab-content tab="mach-repairs">
                {{-- <div class="mt-4">
                    @livewire('operations.works.work-order-table', ['type' => 'mach'], 'work-order-mach')
                </div>
 --}}
            </x-tab-content>
            <x-tab-content tab="others">
                {{-- <div class="mt-4">
                    @livewire('operations.works.work-order-table', ['type' => 'others'], 'work-order-others')
                </div> --}}

            </x-tab-content>
        </x-tabs>
    </x-wire-card>

    @livewire('operations.works.work-order-create', [], 'work-order-create')
    @include('works.workers.create')
</x-dashboard-layout>
