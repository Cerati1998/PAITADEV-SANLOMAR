<div>
    <x-wire-card>
        <div class="grid grid-cols-2 xl:grid-cols-5 gap-4 mb-4">

            <!-- Tipo Comprobante -->
            <div class="col-span-1">
                <x-label>
                    Tipo Comprobante
                </x-label>

                <x-select wire:model.live="note.tipoDoc" class="w-full">
                    <option value="07">
                        NOTA DE CREDITO
                    </option>
                    <option value="08">
                        NOTA DE DEBITO
                    </option>
                </x-select>
            </div>

            {{-- Serie --}}
            <div class="col-span-1">
                <x-label>
                    Serie
                </x-label>

                <x-select wire:model="note.serie" class="w-full">

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

                <x-input class="w-full" wire:model="note.correlativo" disabled />
            </div>

            {{-- Moneda --}}
            <div class="col-span-1">
                <x-label>
                    Moneda
                </x-label>

                <x-select wire:model="note.tipoMoneda" class="w-full">
                    <option value="PEN">
                        Soles
                    </option>
                    <option value="USD">
                        Dolares
                    </option>
                </x-select>
            </div>

            {{-- Fecha --}}
            <div class="col-span-1">
                <x-label>
                    Fecha
                </x-label>

                <x-input wire:model="note.fechaEmision" type="date" class="w-full" />
            </div>

            {{-- Codigo motivo --}}
            <div class="col-span-1">
                <x-label>
                    Codigo Motivo
                </x-label>

                <x-select wire:model.live="note.codMotivo" class="w-full">

                    @foreach ($reasons as $reason)
                        <option value="{{ $reason->id }}">
                            {{ Str::limit($reason->description, 60) }}
                        </option>
                    @endforeach

                </x-select>

            </div>

            {{-- Cliente --}}
            <div class="col-span-2 xl:col-span-3">

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
                <x-wire-button x-on:click="$openModal('clientCreationModal')" class="w-full xl:mt-5.5">
                    NUEVO CLIENTE
                </x-wire-button>
            </div>

            {{-- Producto --}}
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
            <div class="col-span-2 xl:col-span-1">
                <x-wire-button x-on:click="$openModal('simpleModal')" class="w-full xl:mt-5.5">
                    ITEM DETALLADO
                </x-wire-button>
            </div>

            {{-- Documento que modifica --}}
            <div class="col-span-2 xl:col-span-5">

                <div class="flex justify-center items-center space-x-4">
                    <x-label class="whitespace-nowrap">
                        Documento Afectado
                    </x-label>
    
                    <x-input class="w-36"
                        wire:model="note.numDocfectado"
                        placeholder="Ejm: F001-111" />
                </div>

            </div>

        </div>

        {{-- Detail --}}
        <div class="overflow-x-auto mt-4 min-h-48">
            @if (count($note->details))

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

                        @foreach ($note->details as $key => $item)
                            <tr wire:key="detail-{{ $key }}">
                                <td>
                                    <x-input wire:change="recalculateDetail({{ $key }})"
                                        wire:model="note.details.{{ $key }}.cantidad" class="w-20"
                                        type="number" min="1" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ $item['codProducto'] }}" class="w-28" />
                                </td>
                                <td class="w-full">
                                    <x-input disabled value="{{ $item['descripcion'] }}" class="w-full" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ $item['mtoValorUnitario'] }}" class="w-28"
                                        type="number" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ $item['mtoValorUnitario'] * $item['cantidad'] }}"
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
        <div class="flex flex-col items-end space-y-4 mt-4">

            @if ($note->mtoOperGravadas)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        Ope. Gravada
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoOperGravadas, 2)}}" />
                </div>
            @endif

            @if ($note->mtoOperExoneradas)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        Ope. Exonerada
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoOperExoneradas, 2)}}" />
                </div>
            @endif

            @if ($note->mtoOperInafectas)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        Ope. Inafecta
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoOperInafectas, 2)}}" />
                </div>
            @endif

            @if ($note->mtoOperGratuitas)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        Ope. Gratuita
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoOperGratuitas, 2)}}" />
                </div>
            @endif

            @if ($note->mtoOperExportacion)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        Exportacion
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoOperExportacion, 2)}}" />
                </div>
            @endif

            @if ($note->mtoIGVGratuitas)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        IGV Gratuito
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoIGVGratuitas, 2)}}" />
                </div>
            @endif

            <div class="flex items-center space-x-2">
                <span class="whitespace-nowrap">
                    IGV
                </span>

                <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoIGV, 2)}}" />
            </div>
            
            @if ($note->mtoIvap)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        IVAP
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoIvap, 2)}}" />
                </div>
            @endif

            @if ($note->icbper)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        ICBPER
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->icbper, 2)}}" />
                </div>
            @endif

            @if ($note->mtoISC)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        ISC
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoISC, 2)}}" />
                </div>
            @endif

            @if ($note->redondeo)
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap">
                        Redondeo
                    </span>

                    <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->redondeo, 2)}}" />
                </div>
            @endif

            <div class="flex items-center space-x-2">
                <span class="whitespace-nowrap">
                    Importe Total
                </span>

                <x-input disabled class="w-36 text-sm text-right" value="{{number_format($note->mtoImpVenta, 2)}}" />
            </div>

        </div>

        {{-- Leyendas --}}
        <div class="space-y-2 mt-4">
            @forelse ($note->legends as $legend)
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
                EMITIR NOTA
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
