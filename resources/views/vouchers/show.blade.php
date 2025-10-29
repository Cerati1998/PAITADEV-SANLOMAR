<x-dashboard-layout title="Comprobantes | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Comprobantes',
    ],
]">

    <x-container width="3xl">

        <x-wire-card>

            <h1 class="font-semibold text-3xl text-center uppercase mb-4">
                FACTURA ELECTRÓNICA
            </h1>

            <p class="font-semibold text-xl text-center uppercase mb-4">
                {{$invoice->serie}} - {{$invoice->correlativo}}
            </p>

            <p class="font-semibold text-xl text-center uppercase mb-4">
                Total: {{$invoice->mtoImpVenta}}
            </p>

            <div class="border rounded-lg p-4">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <x-wire-button red class="w-full">
                            <i class="fa-regular fa-file-pdf"></i>
                            VER PDF
                        </x-wire-button>
                    </div>
                    <div>
                        <x-wire-button green class="w-full">
                            <i class="fa-regular fa-file-excel"></i>
                            DESCARGAR XML
                        </x-wire-button>
                    </div>
                    <div>
                        <x-wire-button blue class="w-full">
                            <i class="fa-regular fa-file-lines"></i>
                            DESCARGAR CDR
                        </x-wire-button>
                    </div>
                </div>
            </div>

        </x-wire-card>

    </x-container>


</x-dashboard-layout>