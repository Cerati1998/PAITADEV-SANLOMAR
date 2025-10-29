<div x-data="{
    tipoOperacion: @entangle('invoice.tipoOperacion').live,
}">

    @push('css')
        <style>
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            input[type=number] {
                -moz-appearance: textfield;
            }
        </style>
    @endpush

    <x-wire-card>

        {{-- Datos del comprobante --}}
        <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
            <!-- Tipo Comprobante -->
            <div class="col-span-1">
                <x-label>
                    Tipo Comprobante
                </x-label>

                <x-wire-native-select wire:model.live="invoice.tipoDoc">
                    <option value="01" class="p-4">FACTURA</option>
                    <option value="03" class="p-4">BOLETA</option>
                </x-wire-native-select>
            </div>

            {{-- Serie --}}
            <div class="col-span-1">
                <x-label>
                    Serie
                </x-label>

                <x-select wire:model.live="invoice.serie" class="w-full">

                    @forelse ($series as $serie)
                        <option value="{{ $serie->name }}">
                            {{ $serie->name }}
                        </option>
                    @empty

                        <option value="">
                            No hay series disponibles
                        </option>
                    @endforelse

                </x-select>
            </div>

            {{-- Correlativo --}}
            <div class="col-span-1">
                <x-label>
                    Correlativo
                </x-label>

                <x-input class="w-full" disabled value="{{ str_pad($invoice->correlativo, 4, '0', STR_PAD_LEFT) }}" />
            </div>

            {{-- Moneda --}}
            <div class="col-span-1">
                <x-label>
                    Moneda
                </x-label>

                <x-select wire:model="invoice.tipoMoneda" class="w-full">
                    <option value="PEN">
                        Soles
                    </option>
                    <option value="USD">
                        Dolares
                    </option>
                </x-select>
            </div>

            {{-- Tipo de Cambio --}}
            <div class="col-span-1">
                <x-label>
                    Tipo Cambio
                </x-label>

                <x-wire-input wire:model="invoice.tipoCambio" type="number" disabled readonly/>
            </div>
            {{-- Fecha emision --}}
            <div class="col-span-1">
                <x-label>
                    Fecha Emisión
                </x-label>

                <x-wire-input wire:model.live="invoice.fechaEmision" type="date"
                    min="{{ date('Y-m-d', strtotime('-7 days')) }}" max="{{ date('Y-m-d') }}" />
            </div>

            {{-- Fecha Vencimiento --}}
            <div class="col-span-1">
                <x-label>
                    F. Vencimiento
                </x-label>

                <x-wire-input wire:model="invoice.fecVencimiento" type="date" :min="date('Y-m-d', strtotime($invoice->fechaEmision))" class="w-full" />
            </div>

            <!-- Tipo Operación -->
            <div class="col-span-2 xl:col-span-1">
                <x-label>
                    Tipo de operación
                </x-label>

                <x-select x-model="tipoOperacion" class="w-full">

                    @foreach ($operations as $operation)
                        <option value="{{ $operation->id }}">
                            {{ $operation->description }}
                        </option>
                    @endforeach

                </x-select>
            </div>

            {{-- Cliente --}}
            <div class="col-span-2 xl:col-span-4">

                <x-label>
                    Cliente
                </x-label>

                <x-wire-select class="!text-sm" placeholder="Buscar cliente por numero de documento o razón social"
                    :async-data="[
                        'api' => route('api.clients.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id" wire:model.live="client_id" />

            </div>

            {{-- Nuevo Cliente --}}
            <div class="col-span-2 xl:col-span-1">
                <x-wire-button x-on:click="$openModal('clientCreate')" class="w-full xl:mt-5.5">
                    NUEVO CLIENTE
                </x-wire-button>
            </div>
        </div>

        
        {{-- Huesped --}}
        <template x-if="['0202', '0205'].includes(tipoOperacion)">
            <div class="mb-6">
                <header class="flex items-center space-x-4 mb-4">
                    <hr class="flex-1">

                    <p class="flex items-center">
                        <i class="fas fa-user text-gray-600"></i>

                        <span class="ml-2 text-sm">
                            Turista
                        </span>
                    </p>

                    <hr class="flex-1">
                </header>

                <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">

                    {{-- Pais del documento --}}
                    <div class="col-span-1">

                        <x-wire-select
                            label="Pais del documento"
                            wire:model="invoice.huesped.paisDoc"
                            :async-data="[
                                'api' => route('api.countries.index'),
                                'method' => 'POST',
                            ]" 
                            placeholder="País"
                            option-label="name"
                            option-value="id"/>

                    </div>
                    
                    {{-- Pais de residencia --}}
                    <template x-if="tipoOperacion == '0202'">
                        <div class="col-span-1">

                            <x-wire-select
                                label="Pais de residencia"
                                wire:model="invoice.huesped.paisRes"
                                :async-data="[
                                    'api' => route('api.countries.index'),
                                    'method' => 'POST',
                                ]" 
                                placeholder="País"
                                option-label="name"
                                option-value="id"/>

                        </div>
                    </template>
                    
                    {{-- Fecha ingreso al pais --}}
                    <template x-if="tipoOperacion == '0202'">
                        <div class="col-span-1">
                            <x-wire-input 
                                label="Fecha de ingreso al pais"
                                wire:model="invoice.huesped.fecIngresoPais"
                                type="date" />
                        </div>
                    </template>
                        
                    {{-- Check In --}}
                    <template x-if="tipoOperacion == '0202'">
                        <div class="col-span-1">
                            <x-wire-input 
                                label="Check In - Hotel"
                                wire:model.live="invoice.huesped.fecIngresoEst"
                                type="date" />
                        </div>
                    </template>

                    {{-- Check Out --}}
                    <template x-if="tipoOperacion == '0202'">
                        <div class="col-span-1">
                            <x-wire-input 
                                label="Check Out - Hotel"
                                wire:model="invoice.huesped.fecSalidaEst"
                                {{-- Min un dia despues de la fecha de ingreso --}}
                                :min="date('Y-m-d', strtotime($invoice->huesped['fecIngresoEst'] . ' + 1 day'))"
                                type="date" />
                        </div>
                    </template>

                    {{-- Nombre del huesped --}}
                    <div class="col-span-1 xl:col-span-2">
                        <x-wire-input 
                            label="Nombre del huesped"
                            wire:model="invoice.huesped.nombre"
                            placeholder="Nombre del huesped" />
                    </div> 

                    {{-- Tipo de documento --}}
                    <div class="col-span-1">
                        <x-wire-select 
                            label="Tipo de documento"
                            wire:model="invoice.huesped.tipoDoc"
                            placeholder="Tipo de documento">
                            
                            @foreach ($identities as $identity)
                                <x-wire-select.option label="{{$identity->description}}" value="{{$identity->id}}" />
                            @endforeach
                            
                        </x-wire-select>
                    </div>

                    {{-- Numero de documento --}}
                    <div class="col-span-1">
                        <x-wire-input 
                            label="Número de documento"
                            wire:model="invoice.huesped.numDoc"
                            placeholder="Número de documento" />
                    </div>
                    
                </div>

            </div>
        </template>

        {{-- Detraccion --}}
        <template x-if="tipoOperacion == '1001'">
            <div class="mb-6">

                <header class="flex items-center space-x-4 mb-4">
                    <hr class="flex-1">

                    <p class="flex items-center">
                        {{-- <i class="fas fa-map-marker-alt text-gray-600"></i> --}}
                        <i class="fas fa-info-circle text-gray-600"></i>

                        <span class="ml-2 text-sm">
                            Detraccion
                        </span>
                    </p>

                    <hr class="flex-1">
                </header>

                <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">

                    {{-- Cod Detraccion --}}
                    <div class="col-span-2">
                        <x-wire-select label="Tipo de detracción" placeholder="Tipo de detracción"
                            wire:model.live="invoice.detraccion.codBienDetraccion">
                            @foreach ($detractions as $detraction)
                                <x-wire-select.option label="{{ $detraction->description }}"
                                    value="{{ $detraction->id }}" />
                            @endforeach
                        </x-wire-select>
                    </div>

                    {{-- Medio de Pago --}}
                    <div class="col-span-2">
                        <x-wire-select label="Medio de pago" 
                            placeholder="Medio de pago"
                            wire:model="invoice.detraccion.codMedioPago">
                            @foreach ($payment_methods as $payment_method)
                                <x-wire-select.option label="{{ $payment_method->description }}"
                                    value="{{ $payment_method->id }}" />
                            @endforeach
                        </x-wire-select>
                    </div>

                    {{-- Porcentaje --}}
                    <div class="col-span-1">
                        <x-wire-input 
                            label="Porcentaje" 
                            placeholder="Porcentaje"
                            value="{{$invoice->detraccion['percent']}}"
                            disabled />
                    </div>

                    {{-- Monto --}}
                    <div class="col-span-1">
                        <x-wire-input 
                            label="Monto" 
                            placeholder="Monto" 
                            step="0.01"
                            value="{{$invoice->detraccion['mount']}}"
                            disabled />
                    </div>

                    {{-- Número de cuenta --}}
                    <div class="col-span-2 xl:col-span-6">
                        <x-wire-input label="Número de cuenta" placeholder="Número de cuenta"
                            wire:model="invoice.detraccion.ctaBanco" />
                    </div>

                </div>

            </div>
        </template>

        {{-- Producto --}}
        <div class="mb-6">
            <header class="flex items-center space-x-4 mb-4">
                <hr class="flex-1">

                <p class="flex items-center">
                    {{-- <i class="fas fa-map-marker-alt text-gray-600"></i> --}}
                    <i class="fa-solid fa-boxes text-gray-600"></i>

                    <span class="ml-2 text-sm">
                        Productos
                    </span>
                </p>

                <hr class="flex-1">
            </header>

            <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="col-span-2 xl:col-span-4">
                    <x-label>
                        Producto
                    </x-label>

                    <x-wire-select placeholder="Buscar producto por código o descripción" :async-data="[
                        'api' => route('api.products.index'),
                        'method' => 'POST',
                    ]"
                        option-label="name" option-value="id" wire:model.live="product_id" />
                </div>

                {{-- Boton item detallado --}}
                <div class="col-span-2">
                    <x-wire-button x-on:click="$openModal('simpleModal')" class="w-full xl:mt-5.5">
                        ITEM DETALLADO
                    </x-wire-button>
                </div>
            </div>
        </div>

        {{-- Detail --}}
        <div class="overflow-x-auto mt-4 min-h-48">

            @if (count($invoice->details))

                <table class="w-full text-left text-sm border-separate border-spacing-2">
                    <thead>
                        <tr class="uppercase text-gray-400">
                            <th>Cantidad</th>
                            <th>Código</th>
                            <th class="p-2">Descripción</th>

                            <th>V.Unit</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($invoice->details as $key => $item)
                            <tr wire:key="detail-{{ $key }}">
                                <td>
                                    <x-input wire:change="recalculateDetail({{ $key }})"
                                        wire:model="invoice.details.{{ $key }}.cantidad" class="w-20"
                                        type="number" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ $item['codProducto'] }}" class="w-28" />
                                </td>
                                <td class="w-full">
                                    <x-input disabled value="{{ $item['descripcion'] }}" class="w-full" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ ($item['mtoValorUnitario'] ?: $item['mtoValorGratuito']) }}" class="w-28"
                                        type="number" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ ($item['mtoValorUnitario'] ?: $item['mtoValorGratuito']) * $item['cantidad'] }}"
                                        class="w-28" type="number" />
                                </td>
                                <td>
                                    <div class="flex space-x-2">
                                        <x-wire-mini-button green 
                                            wire:click="editDetail({{ $key }})"
                                            spinner="editDetail({{ $key }})"
                                            icon="pencil" />

                                        <x-wire-mini-button red 
                                            wire:click="removeDetail({{ $key }})"
                                            spinner="removeDetail({{ $key }})"
                                            icon="trash" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>
            @else
                <div class="flex justify-center mt-8">

                    <button class="w-80" x-on:click="$openModal('simpleModal')">

                        <i class="fa-solid fa-inbox text-6xl text-gray-400"></i>

                        <div class="border border-dashed border-gray-400 p-2 mt-4 flex justify-center items-center">

                            <span class="pt-0.5">
                                <i class="fa-solid fa-plus"></i>
                            </span>

                            <span class="ml-2">
                                Agregar un item
                            </span>
                        </div>

                    </button>

                </div>
            @endif
        </div>

        {{-- Totales --}}
        <div class="flex flex-col lg:items-end space-y-4 mt-4">

            @if ($invoice->mtoOperGravadas)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Ope. Gravada
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoOperGravadas, 2) }}" />
                </div>
            @endif

            @if ($invoice->mtoOperInafectas)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Ope. Inafecta
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoOperInafectas, 2) }}" />
                </div>
            @endif

            @if ($invoice->mtoOperExoneradas)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Ope. Exonerada
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoOperExoneradas, 2) }}" />
                </div>
            @endif
            
            @if ($invoice->mtoOperGratuitas)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Ope. Gratuita
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoOperGratuitas, 2) }}" />
                </div>
            @endif

            @if ($invoice->mtoOperExportacion)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Exportacion
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoOperExportacion, 2) }}" />
                </div>
            @endif

            @if ($invoice->mtoBaseIvap)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Base Ivap
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoBaseIvap, 2) }}" />
                </div>
            @endif

            @if ($invoice->mtoIGVGratuitas)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        IGV Gratuito
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoIGVGratuitas, 2) }}" />
                </div>
            @endif

            {{-- IGV --}}
            <div class="lg:flex lg:items-center lg:space-x-2">
                <span class="lg:whitespace-nowrap">
                    IGV
                </span>

                <x-input disabled class="w-full lg:w-36 text-sm lg:text-right" value="{{ number_format($invoice->mtoIGV, 2) }}" />
            </div>

            @if ($invoice->mtoIvap)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        IVAP
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoIvap, 2) }}" />
                </div>
            @endif

            @if ($invoice->icbper)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        ICBPER
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->icbper, 2) }}" />
                </div>
            @endif

            @if ($invoice->mtoISC)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        ISC
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->mtoISC, 2) }}" />
                </div>
            @endif

            @if ($invoice->redondeo)
                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Redondeo
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->redondeo, 2) }}" />
                </div>
            @endif

            {{-- Importe total --}}
            <div class="lg:flex lg:items-center lg:space-x-2">
                <span class="lg:whitespace-nowrap">
                    Importe Total
                </span>

                <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                    value="{{ number_format($invoice->mtoImpVenta, 2) }}" />
            </div>

            {{-- Percepción --}}
            @if ($invoice->tipoOperacion == '2001')
            
                <div class="lg:w-[22rem]">
                    <span class="lg:whitespace-nowrap lg:hidden">
                        Tipo de percepción
                    </span>

                    <x-wire-select
                        wire:model.live="invoice.perception.codReg"
                        placeholder="Tipo de percepción">
                        @foreach ($perceptions as $perception)
                            <x-wire-select.option label="{{$perception->description}}" value="{{$perception->id}}" />
                        @endforeach
                    </x-wire-select>
                </div>

                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Base imponible Percepción
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->perception['mtoBase'], 2) }}" />
                </div>

                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="lg:whitespace-nowrap">
                        Total Percepción
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->perception['mto'], 2) }}" />
                </div>

                <div class="lg:flex lg:items-center lg:space-x-2">
                    <span class="whitespace-nowrap">
                        Total incluído Percepción
                    </span>

                    <x-input disabled class="w-full lg:w-36 text-sm lg:text-right"
                        value="{{ number_format($invoice->perception['mtoTotal'], 2) }}" />
                </div>

            @endif

            {{-- Forma de pago --}}
            <div class="lg:flex lg:items-center lg:space-x-2">
                <span class="whitespace-nowrap">
                    Forma de Pago
                </span>

                <x-select wire:model.live="invoice.formaPago.tipo" class="w-full lg:w-36 text-sm">
                    <option value="Contado">Contado</option>
                    <option value="Credito">Crédito</option>
                </x-select>
            </div>

            @if ($invoice->formaPago['tipo'] == 'Credito')

                <div class="w-full lg:w-96 space-y-4">

                    @foreach ($invoice->cuotas as $key => $cuota)
                        
                        <div class="space-y-4 lg:space-y-0 lg:space-x-2 lg:flex lg:justify-between" wire:key="cuota-{{ $key }}">

                            <div class="w-full">
                                <span class="lg:hidden">
                                    Cuota {{ $key + 1 }}
                                </span>

                                <x-wire-input 
                                    wire:model="invoice.cuotas.{{ $key }}.monto"
                                    placeholder="0.00 (Cuota {{ $key + 1 }})" 
                                    type="number"
                                    step="0.01" />
                            </div>

                            <x-wire-input wire:model="invoice.cuotas.{{ $key }}.fechaPago" :min="date('Y-m-d', strtotime($invoice->fechaEmision . ' + 1 day'))"
                                type="date" />

                            <x-wire-button red wire:click="removeCuota({{ $key }})" class="w-full lg:w-auto lg:shrink-0">
                                <span class="lg:hidden">
                                    Eliminar cuota
                                </span>
                                <i class="fas fa-close hidden lg:inline"></i>
                            </x-wire-button>
                        </div>
                    @endforeach

                    <x-wire-button class="w-full" black outline label="Agregar cuota" wire:click="addCuota" spinner="addCuota" />
                </div>

            @endif

        </div>

        {{-- Leyendas --}}
        <div class="space-y-2 mt-4">
            @forelse ($invoice->legends as $legend)
                <div class="border border-gray-300 rounded overflow-hidden text-sm xl:flex">

                    @if ($legend['code'] == '1000')
                        <div class="p-2 bg-white border-b xl:border-b-0 xl:border-r border-gray-300">
                            <span class="font-bold">
                                IMPORTE EN LETRAS
                            </span>
                        </div>
                    @endif

                    <div class="p-2 flex-1 uppercase">
                        {{ $legend['value'] }}
                    </div>
                </div>
            @empty

                <div class="border border-gray-300 rounded overflow-hidden text-sm xl:flex">
                    <div class="p-2 bg-white border-b xl:border-b-0 xl:border-r border-gray-300">
                        <span class="font-bold">
                            IMPORTE EN LETRAS
                        </span>
                    </div>

                    <div class="p-2 flex-1 uppercase">
                        CERO CON 00/100 SOLES
                    </div>
                </div>
            @endforelse
        </div>

        <div class="flex justify-end mt-4">
            <x-wire-button wire:click="save" spinner="save">
                EMITIR COMPROBANTE
            </x-wire-button>
        </div>

    </x-wire-card>

    {{-- Modal para agregar productos --}}
    <div x-data="dataProduct">

        <form wire:submit="saveDetail">
            <x-wire-modal-card 
                title="PRODUCTO"
                name="simpleModal"
                wire:model.live="openModal"
                width="2xl">
    
                <x-wire-errors :only="[
                    'product.codProducto',
                    'product.unidad',
                    'product.descripcion',
                    'product.cantidad',
                    'product.mtoValor',
                    'product.tipAfeIgv',
                    'product.porcentajeIgv',
                    'product.tipSisIsc',
                    'product.porcentajeIsc',
                    'product.icbper',
                    'product.factorIcbper',
                ]" class="mb-4" />
    
                <div class="mb-4">
                    <x-label>
                        AFECTACION IGV
                    </x-label>
    
                    <x-select x-model="product.tipAfeIgv" class="w-full text-xs">
                        <template x-for="affectation in affectations" :key="affectation.id">
                            <option x-text="affectation.description" :value="affectation.id"></option>
                        </template>
                    </x-select>
                </div>
    
                <div class="grid grid-cols-3 gap-4 mb-4">
                    {{-- Cantidad --}}
                    <div>
                        <x-label>
                            CANTIDAD
                        </x-label>
                        <x-input x-model="product.cantidad" type="number" class="w-full text-xs" />
                    </div>
                    
                    {{-- Unidad --}}
                    <div>
                        <x-label>
                            UNIDAD
                        </x-label>
    
                        <x-select x-model="product.unidad" class="w-full text-xs">
                            @foreach ($units as $unit)
                                <option value="{{ $unit['id'] }}">
                                    {{ $unit['description'] }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>
    
                    {{-- Codigo --}}
                    <div>
                        <x-label>
                            CÓDIGO
                        </x-label>
    
                        <x-input x-model="product.codProducto" class="w-full text-xs" />
                    </div>
                </div>
    
                {{-- Descripción --}}
                <div class="mb-4">
                    <x-label>
                        DESCRIPCIÓN
                    </x-label>
    
                    <x-input x-model="product.descripcion" class="w-full text-xs" />
                </div>
    
                <div class="flex flex-col items-end space-y-4">
                    {{-- Valor unitario --}}
                    <div class="flex items-center space-x-2">
                        <span class="whitespace-nowrap text-sm">
                            Valor Unitario
                        </span>
    
                        <x-input 
                            x-model="product.mtoValor" 
                            type="number"
                            step="0.01"
                            class="w-48 text-xs"
                            x-on:change="product.mtoValor = roundToTwoDecimals(product.mtoValor)"
                            x-on:input="calculateTotal('mtoValor')"
                            />
                    </div>
    
                    {{-- IGV --}}
                    <div class="flex items-center space-x-2">
                        <span class="whitespace-nowrap text-sm">
                            IGV
                        </span>
    
                        <x-select x-model="product.porcentajeIgv" class="w-48 text-xs">
    
                            <option value="0" x-bind:class="product.tipAfeIgv <= 17 ? 'hidden' : ''">
                                IGV 0%
                            </option>
    
                            <option value="4" x-bind:class="product.tipAfeIgv != 17 ? 'hidden' : ''">
                                IGV 4%
                            </option>
    
                            <option value="10" x-bind:class="product.tipAfeIgv >= 17 ? 'hidden' : ''">
                                IGV 10%
                            </option>
    
                            <option value="18" x-bind:class="product.tipAfeIgv >= 17 ? 'hidden' : ''">
                                IGV 18%
                            </option>
    
                        </x-select>
                    </div>
    
                    {{-- Precio unitario --}}
                    <div class="flex items-center space-x-2">
                        <span class="whitespace-nowrap text-sm">
                            Precio Unitario
                        </span>
    
                        <x-input 
                            x-model="product.precioUnitario"
                            type="number"
                            step="0.01"
                            class="w-48 text-xs"
                            x-on:change="product.precioUnitario = roundToTwoDecimals(product.precioUnitario)"
                            x-on:input="calculateTotal('precioUnitario')"
                            />
                    </div>
    
                    {{-- ISC --}}
                    <div class="flex items-center space-x-2">
                        <span class="whitespace-nowrap text-sm">
                            ISC
                        </span>
    
                        <x-select x-model="product.tipSisIsc" class="w-48 text-xs">
                            <option value="">
                                Ninguno
                            </option>
    
                            <option value="01">
                                Sistema al valor
                            </option>
    
                            <option value="02">
                                Monto Fijo
                            </option>
    
                            <option value="03">
                                Precios Público
                            </option>
    
                        </x-select>
                    </div>
    
                    {{-- Porcentaje Isc --}}
                    <template x-if="product.tipSisIsc">
                        <div class="flex items-center space-x-2">
                            <span class="whitespace-nowrap text-sm">
                                Porcentaje Isc
                            </span>
    
                            <x-input x-model="product.porcentajeIsc" type="number" class="w-48 text-xs" />
                        </div>
                    </template>
    
                    {{-- ICBPER --}}
                    <div class="flex items-center space-x-2">
                        <span class="whitespace-nowrap text-sm">
                            ICBPER
                        </span>
    
                        <x-select x-model="product.icbper" class="w-48 text-xs">
                            <option value="0">
                                No
                            </option>
    
                            <option value="1">
                                Si
                            </option>
    
                        </x-select>
                        
                    </div>
    
                    {{-- Factor Icbper --}}
                    <template x-if="product.icbper == 1">
                        <div class="flex items-center space-x-2">
                            <span class="whitespace-nowrap text-sm">
                                Factor Icbper
                            </span>
        
                            <x-input x-model="product.factorIcbper" step="0.01" type="number" class="w-48 text-xs" />
                            
                        </div>
                    </template>
                </div>
    
                <x-slot name="footer" class="flex justify-end gap-x-4">
                    <x-wire-button flat label="Cancelar" x-on:click="close" />
    
                    <x-wire-button type="submit" primary :label="isset($product_key) ? 'Actualizar' : 'Agregar'" />
                </x-slot>
    
            </x-wire-modal-card>
        </form>
    
    </div>

    @push('js')
        <script>
            function dataProduct() {
                return {
                    errors: [],

                    product: @entangle('product'),

                    affectations: @json($affectations),

                    calculateTotal(name) {

                        if(name == 'mtoValor') {
                            let mtoValor = this.roundToTwoDecimals(this.product.mtoValor);
                            this.product.precioUnitario = this.roundToTwoDecimals(mtoValor * (1 + this.product.porcentajeIgv/100));
                        } else {
                            let precioUnitario = this.roundToTwoDecimals(this.product.precioUnitario);
                            this.product.mtoValor = this.roundToTwoDecimals(precioUnitario / (1 + this.product.porcentajeIgv/100));
                        }

                    },

                    roundToTwoDecimals(value) {
                        return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
                    },

                    init() {

                        this.$watch('product.tipAfeIgv', value => {
                            affectation = this.affectations.find(affectation => affectation.id == value);

                            if (affectation.igv) {
                                let porcentajeIgv = affectation.id == 17 ? 4 : 18;
                                this.product.porcentajeIgv = porcentajeIgv;
                            } else {
                                this.product.porcentajeIgv = 0;
                            }

                            this.calculateTotal('mtoValor');
                        });

                        this.$watch('product.porcentajeIgv', value => {
                            this.calculateTotal('mtoValor');
                        });
                    }
                }
            }
        </script>
    @endpush

</div>
