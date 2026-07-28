<div x-data="{
    despatch: @entangle('despatch'),
    showSubcontratador: @entangle('showSubcontratador').live,
    showDocumentoRelacionado: @entangle('showDocumentoRelacionado').live,
}">
    @unless ($ownTransport)
        <x-wire-alert warning class="mb-4" title="No hay un transportista propio configurado">
            Para emitir guías de remisión transportista, registra en el módulo <a href="{{ route('transports.index') }}"
                class="underline font-semibold">Transportistas</a> un transportista con el mismo RUC de la empresa
            y su N° MTC/RNT.
        </x-wire-alert>
    @endunless

    <x-wire-card>
        <div class="mb-6">
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <x-wire-mini-badge xl primary label="1" rounded />
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Datos del comprobante
                </h1>
            </header>

            <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
                {{-- Serie --}}
                <div class="col-span-1">
                    <x-label>
                        Serie
                    </x-label>

                    <x-select wire:model="despatch.serie" class="w-full">

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

                    <x-input class="w-full" wire:model="despatch.correlativo" disabled />
                </div>

                {{-- Fecha emisión --}}
                <div class="col-span-2 xl:col-span-1">
                    <x-label>
                        Fecha
                    </x-label>

                    <x-wire-input wire:model.live="despatch.fechaEmision" type="date"
                        min="{{ date('Y-m-d', strtotime('-1 days')) }}"
                        max="{{ date('Y-m-d', strtotime('+3 days')) }}" />
                </div>
            </div>
        </div>

        <div class="mb-6">
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <x-wire-mini-badge xl primary label="2" rounded />
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Remitente (entrega los bienes al transportista)
                </h1>
            </header>

            <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="col-span-2">
                    <x-label>
                        Buscar remitente
                    </x-label>

                    <x-wire-select class="!text-sm" placeholder="Buscar por número de documento o razón social"
                        :async-data="[
                            'api' => route('api.clients.index'),
                            'method' => 'POST',
                        ]" option-label="name" option-value="id" wire:model.live="remitente_id" />
                </div>

                <div class="col-span-2 xl:col-span-1">
                    <x-wire-button x-on:click="$openModal('clientCreate')" class="w-full xl:mt-5.5">
                        NUEVO REMITENTE
                    </x-wire-button>
                </div>

                <div>
                    <x-label>
                        Tipo de documento
                    </x-label>

                    <x-select wire:model="despatch.remitente.tipoDoc" class="w-full">
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">
                                {{ $identity->description }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-label>
                        Número de documento
                    </x-label>

                    <x-input wire:model="despatch.remitente.numDoc" class="w-full" placeholder="Num. Doc." />
                </div>

                <div class="col-span-2 xl:col-span-1">
                    <x-label>
                        Razón social
                    </x-label>

                    <x-input wire:model="despatch.remitente.rznSocial" class="w-full" />
                </div>
            </div>
        </div>

        <div class="mb-6">
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <x-wire-mini-badge xl primary label="3" rounded />
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Destinatario
                </h1>
            </header>

            <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="col-span-2">
                    <x-label>
                        Buscar destinatario
                    </x-label>

                    <x-wire-select class="!text-sm" placeholder="Buscar por número de documento o razón social"
                        :async-data="[
                            'api' => route('api.clients.index'),
                            'method' => 'POST',
                        ]" option-label="name" option-value="id" wire:model.live="destinatario_id" />
                </div>

                <div>
                    <x-label>
                        Tipo de documento
                    </x-label>

                    <x-select wire:model="despatch.destinatario.tipoDoc" class="w-full">
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">
                                {{ $identity->description }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-label>
                        Número de documento
                    </x-label>

                    <x-input wire:model="despatch.destinatario.numDoc" class="w-full" placeholder="Num. Doc." />
                </div>

                <div class="col-span-2 xl:col-span-1">
                    <x-label>
                        Razón social
                    </x-label>

                    <x-input wire:model="despatch.destinatario.rznSocial" class="w-full" />
                </div>
            </div>
        </div>

        <div class="mb-6">
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <x-wire-mini-badge xl primary label="4" rounded />
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Datos de envío
                </h1>
            </header>

            <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 mb-4">

                {{-- Motivo de traslado --}}
                <div class="col-span-1">
                    <x-label>
                        Motivo de traslado
                    </x-label>

                    <x-select wire:model.live="despatch.envio.codTraslado" class="w-full">
                        @foreach ($reason_transfers as $reason_transfer)
                            <option value="{{ $reason_transfer->id }}">
                                {{ $reason_transfer->description }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <template x-if="despatch.envio.codTraslado == '13'">
                    <div class="col-span-1">
                        <x-label>
                            Especifique
                        </x-label>
                        <x-wire-input wire:model="despatch.envio.desTraslado" />
                    </div>
                </template>

                {{-- Fecha de traslado --}}
                <div class="col-span-1">
                    <x-label>
                        Fecha de traslado
                    </x-label>

                    <x-wire-input wire:model.live="despatch.envio.fecTraslado" type="date"
                        min="{{ $despatch->fechaEmision }}" />
                </div>

                {{-- Peso total --}}
                <div class="col-span-1">
                    <x-label>
                        Peso total
                    </x-label>

                    <x-wire-input wire:model="despatch.envio.pesoTotal" type="number" step="0.01"
                        placeholder="Peso total" />
                </div>

                {{-- N. MTC / RNT del transportista (propio, autocompletado y de solo lectura) --}}
                <div class="col-span-1">
                    <x-label>
                        N° MTC / RNT del transportista
                    </x-label>

                    <x-wire-input wire:model="despatch.envio.mtc" disabled placeholder="Registro MTC" />
                </div>

                {{-- Unidad --}}
                <div class="col-span-1">
                    <x-label>
                        Unidad
                    </x-label>

                    <x-select wire:model="despatch.envio.undPesoTotal" class="w-full">
                        <option value="KGM">Kilogramos</option>
                        <option value="TNE">Toneladas</option>
                    </x-select>
                </div>
            </div>

            {{-- Punto de partida y llegada --}}
            <div class="mb-4">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

                    <div>
                        <header class="flex items-center space-x-4 mb-4">
                            <hr class="flex-1">
                            <p class="flex items-center">
                                <i class="fas fa-map-marker-alt text-gray-600"></i>
                                <span class="ml-2 text-sm">Punto de partida</span>
                            </p>
                            <hr class="flex-1">
                        </header>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label>Dirección</x-label>
                                <x-wire-input wire:model="despatch.envio.partida.direccion"
                                    placeholder="Dirección de partida" />
                            </div>

                            <div>
                                <x-label>Ubigeo</x-label>
                                <x-wire-select placeholder="Buscar ubigeo" :async-data="[
                                    'api' => route('api.ubigeos.index'),
                                    'method' => 'POST',
                                ]" option-label="name"
                                    option-value="id" wire:model.live="despatch.envio.partida.ubigueo" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <header class="flex items-center space-x-4 mb-4">
                            <hr class="flex-1">
                            <p class="flex items-center">
                                <i class="fas fa-map-marker-alt text-gray-600"></i>
                                <span class="ml-2 text-sm">Punto de llegada</span>
                            </p>
                            <hr class="flex-1">
                        </header>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label>Dirección</x-label>
                                <x-wire-input wire:model="despatch.envio.llegada.direccion"
                                    placeholder="Dirección de llegada" class="w-full" />
                            </div>

                            <div>
                                <x-label>Ubigeo</x-label>
                                <x-wire-select placeholder="Buscar ubigeo" :async-data="[
                                    'api' => route('api.ubigeos.index'),
                                    'method' => 'POST',
                                ]" option-label="name"
                                    option-value="id" wire:model.live="despatch.envio.llegada.ubigueo" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vehiculos y Choferes (siempre obligatorio: el transportista es quien traslada) --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

                <div>
                    <div class="flex items-center space-x-4 mb-4">
                        <hr class="flex-1">
                        <p class="flex items-center">
                            <x-wire-icon name="truck" class="w-4 h-4" />
                            <span class="ml-2 text-sm">Vehículos (flota propia)</span>
                        </p>
                        <hr class="flex-1">
                    </div>

                    <ul class="space-y-4 mb-4">
                        <li>
                            <x-label>Vehículo 1</x-label>
                            <div class="flex items-center space-x-2">
                                <x-wire-select class="!text-sm w-full" placeholder="Buscar placa" :async-data="[
                                    'api' => route('api.vehicles.index'),
                                    'method' => 'POST',
                                    'params' => ['transport_id' => $ownTransport?->id],
                                ]" option-label="name" option-value="id" wire:model.live="vehiculo_id"
                                    :disabled="!$ownTransport" />

                                <x-wire-mini-button type="button" sm rounded icon="plus"
                                    @disabled(!$ownTransport) x-on:click="$openModal('vehicleCreate')" />
                            </div>
                            @if ($vehiculo_info)
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $vehiculo_info->brand }} {{ $vehiculo_info->model }} —
                                    {{ $vehiculo_info->category }}
                                </p>
                            @endif
                        </li>

                        @foreach ($despatch->envio['vehiculo']['secundarios'] as $index => $secundario)
                            <li wire:key="vehiculo-sec-{{ $index }}">
                                <x-label>Vehículo {{ $index + 2 }}</x-label>
                                <div class="flex items-center space-x-2">
                                    <x-wire-select class="!text-sm w-full" placeholder="Buscar placa" :async-data="[
                                        'api' => route('api.vehicles.index'),
                                        'method' => 'POST',
                                        'params' => ['transport_id' => $ownTransport?->id],
                                    ]" option-label="name" option-value="id"
                                        wire:model.live="vehiculo_secundario_ids.{{ $index }}" />

                                    <x-wire-mini-button sm rounded negative icon="x-mark"
                                        wire:click="removeVehicle({{ $index }})"
                                        spinner="removeVehicle({{ $index }})" />
                                </div>
                                @if ($vehiculo_secundario_info[$index] ?? null)
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $vehiculo_secundario_info[$index]->brand }}
                                        {{ $vehiculo_secundario_info[$index]->model }} —
                                        {{ $vehiculo_secundario_info[$index]->category }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <x-wire-button sm icon="plus" outline secondary wire:click="addVehicle"
                        spinner="addVehicle" class="w-full">
                        Agregar vehículos
                    </x-wire-button>
                </div>

                <div>
                    <div class="flex items-center space-x-4 mb-4">
                        <hr class="flex-1">
                        <p class="flex items-center">
                            <x-wire-icon name="user" class="w-4 h-4" />
                            <span class="ml-2 text-sm">Choferes (flota propia)</span>
                        </p>
                        <hr class="flex-1">
                    </div>

                    <ul class="space-y-4 mb-4">
                        @foreach ($despatch->envio['choferes'] as $index => $chofer)
                            <li wire:key="choferes-{{ $index }}">
                                <div class="flex items-center space-x-2 mb-2">
                                    <x-label class="grow mb-0">Chofer {{ $index + 1 }}</x-label>
                                    <x-wire-mini-button type="button" sm rounded icon="plus"
                                        @disabled(!$ownTransport) x-on:click="$openModal('driverCreate')" />
                                </div>

                                <x-wire-select class="!text-sm w-full mb-2" placeholder="Buscar chofer" :async-data="[
                                    'api' => route('api.drivers.index'),
                                    'method' => 'POST',
                                    'params' => ['transport_id' => $ownTransport?->id],
                                ]" option-label="name" option-value="id"
                                    wire:model.live="chofer_ids.{{ $index }}" :disabled="!$ownTransport" />

                                <div class="grid grid-cols-6 gap-4">
                                    <div class="col-span-2">
                                        <x-select disabled class="w-full"
                                            wire:model="despatch.envio.choferes.{{ $index }}.tipoDoc">
                                            @foreach ($identities as $identity)
                                                <option value="{{ $identity->id }}">
                                                    {{ $identity->description }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                    </div>

                                    <div class="col-span-4">
                                        <x-wire-input disabled
                                            wire:model="despatch.envio.choferes.{{ $index }}.nroDoc"
                                            placeholder="Num. Doc." />
                                    </div>

                                    <div class="col-span-3">
                                        <x-wire-input disabled
                                            wire:model="despatch.envio.choferes.{{ $index }}.nombres"
                                            placeholder="Nombres" />
                                    </div>

                                    <div class="col-span-3">
                                        <x-wire-input disabled
                                            wire:model="despatch.envio.choferes.{{ $index }}.apellidos"
                                            placeholder="Apellidos" />
                                    </div>

                                    <div class="col-span-6">
                                        <div class="flex items-center space-x-4">
                                            <x-wire-input disabled class="w-full"
                                                wire:model="despatch.envio.choferes.{{ $index }}.licencia"
                                                placeholder="Licencia de conducir" />

                                            @if (!$loop->first)
                                                <x-wire-mini-button sm rounded negative icon="x-mark"
                                                    wire:click="removeDriver({{ $index }})"
                                                    spinner="removeDriver({{ $index }})" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <x-wire-button sm icon="plus" outline secondary wire:click="addDriver"
                        spinner="addDriver" class="w-full">
                        Agregar choferes
                    </x-wire-button>
                </div>
            </div>
        </div>

        @if ($ownTransport)
            <livewire:vehicle-create :transport="$ownTransport" :key="'vehicle-create-' . $ownTransport->id" />
            <livewire:driver-create :transport="$ownTransport" :identities="$identities"
                :key="'driver-create-' . $ownTransport->id" />
        @endif

        <div class="mb-6">
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <x-wire-mini-badge xl primary label="5" rounded />
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Subcontratación y documento relacionado (opcional)
                </h1>
            </header>

            <div class="mb-4">
                <x-wire-toggle lg x-model="showSubcontratador" label="El transportista subcontrata a otra empresa"
                    name="toggleSubcontratador" />
            </div>

            <template x-if="showSubcontratador">
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-label>RUC empresa subcontratada</x-label>
                        <x-input wire:model="despatch.subcontratador.numDoc" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <x-label>Razón social</x-label>
                        <x-input wire:model="despatch.subcontratador.rznSocial" class="w-full" />
                    </div>
                </div>
            </template>

            <div class="mb-4">
                <x-wire-toggle lg x-model="showDocumentoRelacionado"
                    label="Sustentar con documento emitido por el remitente (Guía Remitente, Cita/Orden de Terminal Portuario, etc.)"
                    name="toggleDocumentoRelacionado" />
            </div>

            <template x-if="showDocumentoRelacionado">
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
                    <div>
                        <x-label>Tipo de documento</x-label>
                        <x-select wire:model="despatch.documentoRelacionado.tipo" class="w-full">
                            @foreach ($related_documents as $related_document)
                                <option value="{{ $related_document->id }}">
                                    {{ $related_document->description }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <x-label>Número</x-label>
                        <x-input wire:model="despatch.documentoRelacionado.nro" class="w-full" />
                    </div>
                    <div>
                        <x-label>RUC emisor (opcional)</x-label>
                        <x-input wire:model="despatch.documentoRelacionado.emisor" class="w-full" />
                    </div>
                </div>
            </template>
        </div>

        <div class="mb-6">
            <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                <x-wire-mini-badge xl primary label="6" rounded />
                <h1 class="ml-3 font-semibold text-sm uppercase">
                    Productos
                </h1>
            </header>

            <div class="grid xl:grid-cols-3 gap-4">
                <div class="col-span-1 xl:col-span-2">
                    <x-label>Producto</x-label>
                    <x-wire-select placeholder="Buscar producto por código o descripción" :async-data="[
                        'api' => route('api.products.index'),
                        'method' => 'POST',
                    ]"
                        option-label="name" option-value="id" wire:model.live="product_id" />
                </div>

                <div class="col-span-1">
                    <x-wire-button x-on:click="$openModal('simpleModal')" class="w-full xl:mt-5.5">
                        ITEM DETALLADO
                    </x-wire-button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto mt-4 min-h-48">
            @if (count($despatch->details))
                <table class="w-full text-left text-sm border-separate border-spacing-2">
                    <thead>
                        <tr class="uppercase text-gray-400">
                            <th>Cantidad</th>
                            <th>Código</th>
                            <th class="p-2">Descripción</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($despatch->details as $key => $item)
                            <tr wire:key="detail-{{ $key }}">
                                <td>
                                    <x-input wire:model="despatch.details.{{ $key }}.cantidad"
                                        class="w-20" type="number" min="1" />
                                </td>
                                <td>
                                    <x-input disabled value="{{ $item['codigo'] }}" class="w-28" />
                                </td>
                                <td class="w-full">
                                    <x-input disabled value="{{ $item['descripcion'] }}" class="w-full" />
                                </td>
                                <td>
                                    <div class="flex space-x-2">
                                        <x-wire-mini-button green wire:click="editDetail({{ $key }})"
                                            spinner="editDetail({{ $key }})" icon="pencil" />
                                        <x-wire-mini-button red wire:click="removeDetail({{ $key }})"
                                            spinner="removeDetail({{ $key }})" icon="trash" />
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
                            <span class="ml-2">Agregar un item</span>
                        </div>
                    </button>
                </div>
            @endif
        </div>

        <div class="flex justify-end mt-4">
            <x-wire-button wire:click="save" spinner="save">
                EMITIR GUIA DE REMISIÓN TRANSPORTISTA
            </x-wire-button>
        </div>
    </x-wire-card>

    {{-- Modal para agregar productos --}}
    <div x-data="dataProduct">
        <form wire:submit="addDetail">
            <x-wire-modal-card title="PRODUCTO" name="simpleModal" wire:model.live="openModal" width="2xl">
                <x-wire-errors :only="['product.cantidad', 'product.unidad', 'product.descripcion', 'product.codProducto']" class="mb-4" />

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-label>CANTIDAD</x-label>
                        <x-input x-model="product.cantidad" class="w-full text-xs" />
                    </div>

                    <div>
                        <x-label>UNIDAD</x-label>
                        <x-select x-model="product.unidad" class="w-full text-xs">
                            @foreach ($units as $unit)
                                <option value="{{ $unit['id'] }}">
                                    {{ $unit['description'] }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-label>CÓDIGO</x-label>
                        <x-input x-model="product.codProducto" class="w-full text-xs" />
                    </div>
                </div>

                <div class="mb-4">
                    <x-label>DESCRIPCIÓN</x-label>
                    <x-input x-model="product.descripcion" class="w-full text-xs" />
                </div>

                <x-slot name="footer" class="flex justify-end gap-x-4">
                    <x-wire-button flat label="Cancelar" x-on:click="close" />
                    <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
                </x-slot>
            </x-wire-modal-card>
        </form>
    </div>

    @push('js')
        <script>
            function dataProduct() {
                return {
                    product: @entangle('product'),
                }
            }
        </script>
    @endpush
</div>
