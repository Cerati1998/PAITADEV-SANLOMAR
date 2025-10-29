<x-dashboard-layout title="Comprobantes | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Comprobantes',
    ],
]">

    @push('css')
        
        <style>
            /* body{
                background-color: #f3f4f6 !important;
            } */

            table tbody td, table thead th span{
                font-size: 0.75rem !important;
                line-height: 1rem !important;
            }

        </style>

    @endpush

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 mb-8">
        <x-wire-button href="{{route('vouchers.invoice')}}">
            Factura
        </x-wire-button>

        <x-wire-button href="{{route('vouchers.invoice') . '?tipoDoc=03'}}">
            Boleta
        </x-wire-button>

        <x-wire-button href="{{route('vouchers.note')}}">
            Nota de Crédito
        </x-wire-button>

        <x-wire-button href="{{route('vouchers.note') . '?tipoDoc=08'}}">
            Nota de Débito
        </x-wire-button>
    </div>

    <div>
        @livewire('voucher-table')
    </div>

</x-dashboard-layout>