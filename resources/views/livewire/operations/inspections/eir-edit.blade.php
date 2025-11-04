<div>
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



            table tbody td input,
            table thead th {
                font-size: 0.85rem !important;
                line-height: 1rem !important;
            }
        </style>
    @endpush

    {{-- Form principal: actualiza cabecera EIR --}}
    <div x-data="dataCabecera" class="mb-6">
        <form @submit.prevent="save" class="space-y-4">
            <x-wire-card class="mb-4 xl:mb-8">

                {{-- Datos del EIR --}}
                <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                    <span
                        class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-600 dark:bg-red-700 w-6 h-6"
                        xl="xl">
                        1
                    </span>
                    <h1 class="ml-3 font-semibold text-sm uppercase">
                        Datos del Documento
                    </h1>
                </header>

                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                    {{-- Serie --}}
                    <div class="col-span-1">
                        <x-label>Serie</x-label>
                        <x-input class="w-full" disabled value="{{ $eir->serie }}" />
                    </div>

                    {{-- Correlativo --}}
                    <div class="col-span-1">
                        <x-label>Correlativo</x-label>
                        <x-input class="w-full" disabled
                            value="{{ str_pad($eir->correlativo ?? '', 6, '0', STR_PAD_LEFT) }}" />
                    </div>

                    {{-- Fecha apertura --}}
                    <div class="col-span-1">
                        <x-label>F. Apertura</x-label>
                        <x-wire-datetime-picker wire:model="eir.started_at" display-format="DD-MM-YYYY HH:mm"
                            parse-format="YYYY-MM-DD HH:mm" readonly />
                    </div>

                    {{-- Fecha cierre --}}
                    <div class="col-span-1">
                        <x-label>F. Cierre</x-label>
                        <x-wire-datetime-picker wire:model="eir.closed_at" display-format="DD-MM-YYYY HH:mm"
                            parse-format="YYYY-MM-DD HH:mm" readonly />
                    </div>

                    {{-- Datos del Evento de Ingreso --}}
                    <div class="col-span-2">
                        <x-label>Linea</x-label>
                        <x-wire-input wire:model="trace_origin.shipping_line" readonly />
                    </div>
                    <div class="col-span-1">
                        <x-label>Nave</x-label>
                        <x-wire-input wire:model="trace_origin.vessel" readonly />
                    </div>
                    <div class="col-span-1">
                        <x-label>Anuncio</x-label>
                        <x-wire-input wire:model="trace_origin.event" readonly />
                    </div>
                </div>

                {{-- Sección Contenedor --}}
                <div class="mb-6">
                    <header class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
                        <div class="flex items-center">
                            <span
                                class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-600 dark:bg-red-700 w-6 h-6"
                                xl="xl">
                                2
                            </span>
                            <h1 class="ml-3 font-semibold text-sm uppercase">
                                Datos del Contenedor
                            </h1>
                        </div>

                        <!-- Botón mostrar/ocultar -->
                        <button type="button"
                            class="text-blue-500 hover:text-gray-700 text-sm focus:outline-none transition flex items-center"
                            x-on:click="showContainer = !showContainer">
                            <span x-show="!showContainer">
                                <i class="fas fa-arrow-down"></i>
                                <span class="ml-1">Mostrar</span>
                            </span>
                            <span x-show="showContainer">
                                <i class="fas fa-arrow-up"></i>
                                <span class="ml-1">Ocultar</span>
                            </span>
                        </button>
                    </header>

                    <!-- Contenido -->
                    <div x-show="showContainer" x-transition>
                        <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-4">
                            <div class="col-span-2 lg:col-span-1">
                                <x-label>Contenedor</x-label>
                                <x-wire-input class="w-full" value="{{ $containerInfo['code'] ?? '' }}" readonly />
                            </div>

                            <div class="col-span-1">
                                <x-label>ISO</x-label>
                                <x-wire-input wire:model="containerInfo.iso_code" readonly />
                            </div>

                            <div class="col-span-1 lg:col-span-2">
                                <x-label>Tipo</x-label>
                                <x-wire-select
                                    wirex-bind:key="select-type-{{ $containerInfo['container_type'] ?? 'null' }}"
                                    :options="$this->container_types" option-label="description" option-value="id"
                                    wire:model="containerInfo.container_type" />
                            </div>

                            <div x-show="typeContainer == 11 || typeContainer == 12" class="contents">
                                <div class="col-span-1">
                                    <x-label>Tecnología</x-label>
                                    <x-wire-select
                                        wirex-bind:key="select-technology-{{ $containerInfo['container_technology'] ?? 'null' }}"
                                        :options="$this->reefer_technologies" option-label="name" option-value="id"
                                        wire:model="containerInfo.container_technology" />
                                </div>
                                <div class="col-span-1">
                                    <x-label>Máquina</x-label>
                                    <x-wire-select
                                        wirex-bind:key="select-machine-{{ $containerInfo['container_machine'] ?? 'null' }}"
                                        :options="$this->reefer_machines" option-label="code" option-value="id"
                                        wire:model="containerInfo.container_machine" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-4">
                            {{-- Tara (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Tara</x-label>
                                <x-wire-input type="number" wire:model="containerData.tare" min="0"
                                    max="9999" />
                            </div>

                            {{-- Payload (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Payload</x-label>
                                <x-wire-input type="number" wire:model="containerData.payload" min="0"
                                    max="99999" />
                            </div>
                            {{-- Gross (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Max Gross</x-label>
                                <x-wire-input type="number" wire:model="containerData.max_gross" min="0"
                                    max="99999" readonly />
                            </div>
                            {{-- Height (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Height</x-label>
                                <x-wire-input type="number" wire:model="containerData.max_height" min="0"
                                    max="99999" />
                            </div>
                            {{-- Width (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Width</x-label>
                                <x-wire-input type="number" wire:model="containerData.max_width" min="0"
                                    max="99999" />
                            </div>
                            {{-- Categoria (para eir) --}}
                            <div class="col-span-1">
                                <x-label>Categoria Contenedor</x-label>
                                <x-wire-select :options="$this->container_line_categories" option-label="name" option-value="name"
                                    placeholder="Categoria para Linea" wire:model="eir.container_category" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-4">
                            {{-- Mes de Fabricación (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Mes de Fabricación</x-label>
                                <x-wire-select :options="$this->months" placeholder="Selecciona Mes"
                                    wire:model="containerData.build_month" />
                            </div>

                            {{-- Año de Fabricación (actualizable en Container) --}}
                            <div class="col-span-1">
                                <x-label>Año de Fabricación</x-label>
                                <x-wire-select :options="$this->years" placeholder="Selecciona Año"
                                    wire:model="containerData.build_year" />
                            </div>

                            {{-- TIPOS DE LAVADO (estos sí van al EIR -> form) --}}
                            <div class="col-span-2 mt-4 xl:mt-0 self-end">
                                <x-label class="block mb-4 font-bold text-blue-700">TIPOS DE LAVADO</x-label>
                                <div class="flex justify-start gap-4">
                                    <x-wire-toggle left-label="Normal" wire:model="eir.normal_wash" sm />
                                    <x-wire-toggle left-label="Quimico" x-on:click="lavado_quimico = !lavado_quimico;"
                                        wire:model="eir.quimic_wash" sm />
                                    <x-wire-toggle left-label="Especial"
                                        x-on:click="lavado_especial = !lavado_especial;" wire:model="eir.special_wash"
                                        sm />
                                </div>
                            </div>

                            {{-- Motivos (estos son campos del form, se guardan si corresponde) --}}
                            <div class="col-span-2 xl:col-span-3" x-show="lavado_quimico">
                                <x-label>Motivo Lavado Químico</x-label>
                                <x-wire-input placeholder="Ejem: Piso con Manchas"
                                    wire:model="eir.reason_quimic_wash" />
                                {{-- @error('eir.reason_quimic_wash')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                            </div>

                            <div class="col-span-2 xl:col-span-3" x-show="lavado_especial">
                                <x-label>Motivo Lavado Especial</x-label>
                                <x-wire-input placeholder="Ejem: Pupas o Larvas"
                                    wire:model="eir.reason_special_wash" />
                                {{--  @error('eir.reason_special_wash')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <x-label>Observaciones</x-label>
                    <x-wire-input wire:model="eir.observations" />
                </div>
            </x-wire-card>

            {{-- Botones --}}
            <div class="mt-6 flex justify-center gap-1 lg:gap-2">
                <x-wire-button blue label="Actualizar" interaction="positive" icon="arrow-up-on-square"
                    type="submit" wire:target="save" spinner="save" wire:loading.attr="disabled" sm />
                <x-wire-button positive label="Galeria" interaction="blue" icon="cloud-arrow-up"
                    x-on:click="$openModal('galleryModal')" sm />
                <template x-if="status_eir === 'borrador'">
                    <x-wire-button black label="Cerrar" icon="lock-closed" x-on:click="$openModal('closeEirModal')"
                        sm />
                </template>

                <template x-if="status_eir !== 'borrador'">
                    <x-wire-button black label="Reaperturar" icon="lock-open"
                        onclick="confirmAction('¡La Inspección podrá volver a editarse!','void')" sm />
                </template>
            </div>
        </form>
    </div>

    {{-- Detalle --}}
    @if ($idEir)
        <x-wire-card>
            <div class="mb-4">
                <div class="flex justify-end mb-4">
                    <x-wire-button x-on:click="$openModal('detailEirModal')" class="w-[1/8] xl:mt-2.5"
                        icon="plus-circle" blue>
                        Agregar
                    </x-wire-button>
                </div>

                <header class="flex items-center space-x-4 mb-4">
                    <hr class="flex-1">

                    <p class="flex items-center">
                        <span
                            class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-500 dark:bg-red-700 w-6 h-6"
                            xl="xl">
                            3
                        </span>

                    <h1 class="ml-3 font-semibold text-sm uppercase">Detalle de Inspección</h1>
                    <i class="fa-solid fa-boxes text-gray-600 ml-2"></i>
                    </p>

                    <hr class="flex-1">
                </header>
            </div>

            {{-- Detail --}}
            <div class="overflow-x-auto mt-4 min-h-48">
                @if (count($details))
                    <div class="overflow-y-auto max-h-[400px]">
                        <table class="w-full text-left text-sm border-separate border-spacing-2">
                            <thead>
                                <tr class="uppercase p-2 bg-primary-600">
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Acciones</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Ubicación</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Daño</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Componente</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Método</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Cantidad</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Largo</th>
                                    <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Ancho</th>
                                </tr>
                            </thead>
                            <tbody class="h-450">
                                @foreach ($details as $key => $item)
                                    <tr wirex-bind:key="detail-{{ $item['id'] }}">
                                        <td>
                                            <div
                                                class="flex space-x-1 items-center {{ !$item['approved'] ? 'opacity-60' : '' }}">
                                                <x-wire-checkbox id="item-{{ $item['id'] }}"
                                                    value="{{ $item['id'] }}" wire:model.live="rejectedDetails"
                                                    class="me-2" />
                                                <x-wire-mini-button green wire:click="editDetail({{ $item['id'] }})"
                                                    icon="pencil" :disabled="!$item['approved']" sm />
                                                <x-wire-mini-button blue
                                                    wire:click="loadPhotoDetail({{ $item['id'] }})" icon="camera"
                                                    wire:loading.attr="loadPhotoDetail" wire:target="loadPhotoDetail"
                                                    spinner :disabled="!$item['approved']" sm />
                                                <x-wire-mini-button red
                                                    onclick="confirmAction('¡No podrás revertir esto!','removeDetail', {{ $item['id'] }} )"
                                                    icon="trash" :disabled="!$item['approved']" sm />
                                            </div>
                                        </td>

                                        <td>
                                            <x-input value="{{ $item['location']['code'] ?? '' }}" readonly
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                        <td>
                                            <x-input value="{{ $item['damage']['code'] ?? '' }}" readonly
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                        <td>
                                            <x-input value="{{ $item['component']['code'] ?? '' }}" readonly
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                        <td>
                                            <x-input readonly value="{{ $item['method']['code'] ?? '' }}" readonly
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                        <td>
                                            <x-input readonly value="{{ $item['qty'] }}"
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                        <td>
                                            <x-input readonly
                                                value="{{ $item['length'] > 0 ? $item['length'] . ' Cm' : '' }}"
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                        <td>
                                            <x-input readonly
                                                value="{{ $item['width'] > 0 ? $item['width'] . ' Cm' : '' }}"
                                                class="w-full {{ !$item['approved'] ? 'bg-red-200 text-gray-500 line-through' : '' }}" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex justify-center mt-8">
                        <button class="w-80" x-on:click="$openModal('detailEirModal')">
                            <i class="fa-solid fa-inbox text-6xl text-gray-400"></i>
                            <div
                                class="border border-dashed border-gray-400 p-2 mt-4 flex justify-center items-center">
                                <span class="pt-0.5"><i class="fa-solid fa-plus"></i></span>
                                <span class="ml-2">Agregar un item</span>
                            </div>
                        </button>
                    </div>
                @endif
            </div>
            <div class="flex justify-center items-center lg:justify-between mt-4">
                <div class="hidden lg:block">
                    <p class="text-gray-500 font-semibold text-sm">
                        <span class="text-red-500 ">Nota:</span> Marca solo los items que no fueron aprobados.
                    </p>
                </div>
                <div class="gap-2">
                    @if ($eir->quoted_repair && $eir->status !== 'borrador')
                        <x-wire-button slate sm
                            onclick="confirmAction('Se iniciará la orden de Trabajo para Taller','saveApprovalChanges')">
                            <i class="fas fa-hand-pointer"></i> Cargar Aprobados
                        </x-wire-button>
                    @endif
                    <x-wire-button href="{{ route('inspections.eir.pdf', $this->idEir) }}" target="_blank" red sm>
                        <i class="fas fa-file-pdf"></i> Generar Formato
                    </x-wire-button>
                </div>
            </div>
        </x-wire-card>
    @endif

    {{-- Modal para añadir las 4 fotos de cabecera --}}
    <div x-data="photoHeaderUploader()">
        <x-wire-modal-card title="Fotografías de Cabecera" name="galleryModal" width="5xl" :hide-close="true">
            {{-- Toggle para elegir modo de captura --}}
            <div class="mb-4 flex items-center justify-center gap-2 sm:gap-4 p-2 sm:p-3 bg-gray-100 rounded-lg">
                <span class="text-xs sm:text-sm font-medium text-gray-700">Modo:</span>
                <button @click="captureMode = 'camera'"
                    :class="captureMode === 'camera' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                    class="px-2 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm rounded-lg border transition-colors duration-200 flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-camera text-xs sm:text-base"></i>
                    <span>Cámara</span>
                </button>
                <button @click="captureMode = 'gallery'"
                    :class="captureMode === 'gallery' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                    class="px-2 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm rounded-lg border transition-colors duration-200 flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-images text-xs sm:text-base"></i>
                    <span>Galería</span>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2">
                @foreach ([1, 2, 3, 4] as $i)
                    <div class="relative">
                        <img src="{{ $urls[$i] ?? asset('img/gallery/empty2.png') }}" loading="lazy"
                            class="{{ $urls[$i] ? 'w-full h-48 lg:h-96 rounded-lg shadow-md transition-transform duration-200 hover:scale-105' : 'w-full h-auto' }} object-cover">
                        {{-- Input file con capture dinámico --}}
                        <input type="file" x-on:change="uploadPhoto($event, {{ $i }})"
                            :capture="captureMode === 'camera' ? 'environment' : null"
                            class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                        {{-- Indicador de carga --}}
                        <div x-show="uploading[{{ $i }}]"
                            class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-lg">
                            <div class="text-white text-center">
                                <i class="fas fa-spinner fa-spin text-2xl sm:text-3xl"></i>
                                <p class="text-xs sm:text-sm mt-2" x-text="`${progress[{{ $i }}]}%`"></p>
                            </div>
                        </div>
                        {{-- Badge indicador de modo --}}
                        <div class="absolute top-1 right-1 sm:top-2 sm:right-2 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded text-xs font-medium"
                            :class="captureMode === 'camera' ? 'bg-blue-500 text-white' : 'bg-purple-500 text-white'">
                            <i :class="captureMode === 'camera' ? 'fas fa-camera' : 'fas fa-images'"></i>
                        </div>
                    </div>
                @endforeach
            </div>
            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cerrar" x-on:click="close" />
                <x-wire-button red icon="arrow-down-tray" label="Descargar" wire:click="download_photos" />
            </x-slot>
        </x-wire-modal-card>
    </div>
    {{-- Modal para añadir fotos a los detalles --}}
    <div x-data="photoDetailUploader()">
        <x-wire-modal-card title="Subir Evidencia Fotográfica" wire:model="openModalDetailPhoto" width="5xl"
            :hide-close="true">

            <div class="w-full space-y-6">

                {{-- Toggle para elegir modo de captura --}}
                <div class="flex items-center justify-center gap-2 sm:gap-3 p-2 sm:p-3 bg-gray-100 rounded-lg">
                    <div class="flex items-center gap-1 sm:gap-2">
                        <i class="fas fa-images text-xs sm:text-base"
                            :class="captureMode === 'gallery' ? 'text-blue-500' : 'text-gray-400'"></i>
                        <span class="text-xs sm:text-sm font-medium"
                            :class="captureMode === 'gallery' ? 'text-blue-500' : 'text-gray-500'">
                            Galería
                        </span>
                    </div>

                    {{-- Switch --}}
                    <button @click="toggleCaptureMode()"
                        class="relative w-12 h-7 sm:w-14 sm:h-8 rounded-full transition-colors duration-300"
                        :class="captureMode === 'camera' ? 'bg-blue-500' : 'bg-gray-300'">
                        <span
                            class="absolute top-0.5 left-0.5 sm:top-1 sm:left-1 w-6 h-6 bg-white rounded-full shadow-md transition-all duration-300"
                            :style="{
                                transform: captureMode === 'camera' ?
                                    (window.innerWidth >= 640 ? 'translateX(1.5rem)' : 'translateX(1.25rem)') :
                                    'translateX(0)'
                            }">
                        </span>
                    </button>

                    <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-xs sm:text-sm font-medium"
                            :class="captureMode === 'camera' ? 'text-blue-500' : 'text-gray-500'">
                            Cámara
                        </span>
                        <i class="fas fa-camera text-xs sm:text-base"
                            :class="captureMode === 'camera' ? 'text-blue-500' : 'text-gray-400'"></i>
                    </div>
                </div>

                {{-- Zona de subida de foto --}}
                <div
                    class="relative group border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                    <input type="file" x-on:change="uploadDetailPhoto($event)"
                        :capture="captureMode === 'camera' ? 'environment' : null"
                        class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">

                    {{-- Indicador de carga --}}
                    <div x-show="uploading"
                        class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-lg">
                        <div class="text-white text-center">
                            <i class="fas fa-spinner fa-spin text-3xl"></i>
                            <p class="text-sm mt-2" x-text="`${progress}%`"></p>
                        </div>
                    </div>

                    {{-- Badge indicador de modo --}}
                    <div class="absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium"
                        :class="captureMode === 'camera' ? 'bg-blue-500 text-white' : 'bg-purple-500 text-white'">
                        <i :class="captureMode === 'camera' ? 'fas fa-camera' : 'fas fa-images'"></i>
                    </div>

                    <div class="flex flex-col items-center justify-center text-gray-500">
                        <p class="text-sm font-medium">Haz clic o arrastra una imagen aquí</p>
                        <p class="text-xs text-gray-400">Formatos permitidos: JPG, PNG</p>
                    </div>
                </div>

                <!-- Lista de fotos ya subidas (versión Alpine) -->
                <div x-show="photosDetail.length"
                    class="h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <template x-for="photo in photosDetail" :key="photo.id">
                            <div class="relative group rounded-lg overflow-hidden shadow-md">
                                <img :src="photo.path" loading="lazy" decoding="async"
                                    class="w-full h-40 object-cover rounded-md">

                                <div
                                    class="absolute inset-0 bg-black bg-opacity-40 opacity-0 
                            group-hover:opacity-100 transition flex items-center justify-center gap-3">

                                    <a x-bind:href="photo.path"
                                        class="inline-flex justify-center items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md px-2.5 py-2 text-xs font-medium transition-all duration-200 ease-in-out shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        target="_blank">
                                        <i class="fas fa-eye text-[10px]"></i>
                                    </a>

                                    <x-wire-button xs icon="arrow-down-tray" white
                                        x-on:click="downloadPhoto(photo.id)" />

                                    <x-wire-button xs icon="trash" red x-on:click="deleteDetailPhoto(photo.id)" />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Mensaje cuando no hay fotos -->
                <div x-show="!photosDetail.length" class="text-center text-gray-400 text-sm">
                    Aún no se han añadido evidencias fotográficas a este detalle.
                </div>


            </div>

            {{-- Footer --}}
            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cerrar" x-on:click="close" />
                @if (count($photosDetail))
                    <x-wire-button red icon="arrow-down-tray" label="Descargar todas"
                        wire:click="download_detail_photos" />
                @endif
            </x-slot>
        </x-wire-modal-card>
    </div>

    {{-- Modal para ingresar datos de conclusión de EIR --}}
    <div>
        <x-wire-modal-card title="Ingrese datos finales" name="closeEirModal" wire:model="openCloseModal"
            :hide-close="true" width="3xl">

            <x-validation-errors class="mb-4" />
            <div class="mb-3">
                <x-label>Estado Estructura</x-label>
                <x-select class="w-full" wire:model="statusBox">
                    <option value="pending" hidden>Seleccione...</option>
                    <option value="DM">Dañado/Inoperativo</option>
                    <option value="OS">Operativo/Sucio</option>
                    {{--                     <option value="OP">Operativo</option>
 --}}
                </x-select>
            </div>
            <div class="mb-3 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <x-wire-toggle left-label="Reperación Rápida" wire:model="eir.fast_repair" sm />
                <x-wire-toggle left-label="Reperación Normal" wire:model="eir.normal_repair" sm />
                <x-wire-toggle left-label="Reperación M.Alto" wire:model="eir.quoted_repair" sm />
            </div>


            <x-slot name="footer" class="flex justify-end gap-x-4">
                <div class="flex gap-x-4">
                    <x-wire-button flat label="Cancel" x-on:click="close" />

                    <x-wire-button red label="Finalizar" spinner="close" wire:loading.attr="disabled"
                        onclick="confirmAction('¡La Inspección no podra Editarse!','close')" />
                </div>
            </x-slot>
        </x-wire-modal-card>
    </div>
    {{-- Modal para ingresar detalles de daño en contenedor --}}
    <div>

        <form wire:submit="saveDetail">
            <x-wire-modal-card title="Detalle de Inspección" name="detailEirModal" wire:model="openEditModal"
                width="5xl" :persistent="true">

                <x-validation-errors class="mb-4" />
                <header class="flex items-center space-x-4 mb-3">
                    <hr class="flex-1">
                    <p class="flex items-center">
                        <i class="fa-solid fa-magnifying-glass text-gray-600"></i>
                        <span class="ml-2 text-sm">
                            Códigos de Inspección
                        </span>
                    </p>
                    <hr class="flex-1">
                </header>
                <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">

                    <x-wire-select label="Ubicación" placeholder="Selecciona la Ubicación" :options="$this->locations"
                        {{-- :async-data="route('location.search')" --}} wire:model="detailModal.location_id" option-label="name"
                        option-value="id" />

                    <x-wire-select label="Daño" placeholder="Selecciona el Daño" :options="$this->damages"
                        {{-- :async-data="route('damage.search')" --}} wire:model="detailModal.damage_id" option-label="name"
                        option-value="id" />

                    <x-wire-select label="Componente" placeholder="Selecciona el Componente" :options="$this->components"
                        {{-- :async-data="route('component.search')" --}} wire:model="detailModal.component_id" option-label="name"
                        option-value="id" />

                    <x-wire-select label="Método" placeholder="Selecciona Método de Reparación" :options="$this->methods"
                        {{-- :async-data="route('method.search')" --}} wire:model="detailModal.method_id" option-label="name"
                        option-value="id" />

                </div>
                <header class="flex items-center space-x-4 mb-3">
                    <hr class="flex-1">
                    <p class="flex items-center">
                        <i class="fa-solid fa-ruler-horizontal text-gray-600"></i>
                        <span class="ml-2 text-sm">
                            Cantidades y medidas (CM)
                        </span>
                    </p>
                    <hr class="flex-1">
                </header>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <x-wire-number type="number" step="1" wire:model="detailModal.qty" label="Cantidad"
                        min="0" />
                    <x-wire-number type="number" step="1" wire:model="detailModal.length" label="Largo"
                        min="0" />
                    <x-wire-number type="number" step="1" wire:model="detailModal.width" label="Ancho"
                        min="0" />
                    <x-wire-number type="number" step="1" wire:model="detailModal.height" label="Altura"
                        min="0" />
                </div>
                <div class="mb-3">
                    <x-wire-textarea label="Observaciones" wire:model="detailModal.comments"
                        placeholder="Detalla si es requerido" />
                </div>


                <x-slot name="footer" class="flex justify-end gap-x-4">
                    <div class="flex gap-x-4">
                        <x-wire-button flat label="Cancel" x-on:click="close" wire:click="cancelDetailModal" />

                        <x-wire-button label="Guardar" type="submit" spinner="addDetail"
                            wire:loading.attr="disabled" />
                    </div>
                </x-slot>
            </x-wire-modal-card>
        </form>

    </div>

    <x-spinner :targets="['save', 'close', 'void', 'saveDetail', 'removeDetail', 'deletePhoto', 'saveApprovalChanges']" logo="{{ asset('img/logos/favicon.svg') }}" logoSize="56"
        message="Procesando..." />

    @push('js')
        <script src="https://unpkg.com/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
        <script>
            // Alpine component para fotos de cabecera
            function photoHeaderUploader() {
                return {
                    eirId: @entangle('idEir'),
                    status: @entangle('eir.status'),
                    urls: @entangle('urls'), // Sincronizar con Livewire
                    uploading: {},
                    progress: {},
                    captureMode: 'gallery',

                    async uploadPhoto(event, position) {
                        const file = event.target.files[0];
                        if (!file) return;

                        // Validaciones
                        if (this.status !== 'borrador') {
                            Swal.fire('Error', 'No puedes agregar fotos, el EIR ya se encuentra cerrado', 'error');
                            return;
                        }

                        if (!file.type.startsWith('image/')) {
                            Swal.fire('Error', 'Solo se permiten imágenes', 'error');
                            return;
                        }

                        /* if (file.size > 10 * 1024 * 1024) {
                                                    Swal.fire('Error', 'La imagen no debe superar 10MB', 'error');
                                                    return;
                                                }
                         */
                        // Comprimir antes de enviar
                        const options = {
                            maxSizeMB: 0.7,
                            maxWidthOrHeight: 1920,
                            initialQuality: 0.8,
                            useWebWorker: true
                        };

                        let compressedFile = file;
                        try {
                            compressedFile = await imageCompression(file, options);
                            console.log(`Original: ${(file.size / 1024 / 1024).toFixed(2)} MB`);
                            console.log(`Comprimido: ${(compressedFile.size / 1024 / 1024).toFixed(2)} MB`);
                        } catch (e) {
                            console.error('Error al comprimir:', e);
                        }

                        const formData = new FormData();
                        formData.append('photo', compressedFile);
                        formData.append('position', position);
                        formData.append('eir_id', this.eirId);

                        this.uploading[position] = true;
                        this.progress[position] = 0;

                        try {
                            const response = await axios.post('/eir/upload-header-photo', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                },
                                onUploadProgress: (progressEvent) => {
                                    this.progress[position] = Math.round(
                                        (progressEvent.loaded * 100) / progressEvent.total
                                    );
                                }
                            });

                            if (response.data.success) {
                                // Llamar al método del componente Livewire para refrescar las URLs
                                await @this.call('refreshHeaderPhotos');

                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                                    text: `Foto ${position} subida correctamente`,
                                });
                            }

                        } catch (error) {
                            console.error(error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.response?.data?.message || 'Error al subir la imagen'
                            });
                        } finally {
                            this.uploading[position] = false;
                            this.progress[position] = 0;
                            event.target.value = '';
                        }
                    }, // Método auxiliar para cambiar el modo (opcional)
                    toggleCaptureMode() {
                        this.captureMode = this.captureMode === 'camera' ? 'gallery' : 'camera';
                    },
                }
            }

            // Alpine component para fotos de detalle
            function photoDetailUploader() {
                return {
                    detailId: @entangle('currentDetailId'),
                    photosDetail: @entangle('photosDetail'),
                    uploading: false,
                    progress: 0,
                    captureMode: 'gallery',

                    init() {
                        if (!this.photosDetail) {
                            this.photosDetail = [];
                        }
                        console.log(' Inicializado con:', this.photosDetail);

                        // Escuchar el evento cuando se cargan nuevas fotos desde el backend
                        this.$watch('detailId', (newId) => {
                            if (newId) {
                                console.log(' detailId cambiado a:', newId);
                            }
                        });

                        // Escuchar el evento dispatch para forzar actualización
                        @this.on('photosUpdated', () => {
                            console.log(' Evento photosUpdated recibido');
                            // Forzar re-render del array
                            this.photosDetail = [...this.photosDetail];
                        });
                    },

                    async uploadDetailPhoto(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        if (!file.type.startsWith('image/')) {
                            Swal.fire('Error', 'Solo se permiten imágenes', 'error');
                            return;
                        }

                        const options = {
                            maxSizeMB: 0.7,
                            maxWidthOrHeight: 1920,
                            initialQuality: 0.8,
                            useWebWorker: true
                        };

                        this.uploading = true;
                        this.progress = 0;

                        try {
                            const compressed = await imageCompression(file, options);
                            console.log(`Original: ${(file.size / 1024 / 1024).toFixed(2)} MB`);
                            console.log(`Comprimido: ${(compressed.size / 1024 / 1024).toFixed(2)} MB`);

                            const formData = new FormData();
                            formData.append('photo', compressed);
                            formData.append('detail_id', this.detailId);

                            const response = await axios.post('/eir/upload-detail-photo', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                },
                                onUploadProgress: (progressEvent) => {
                                    this.progress = Math.round((progressEvent.loaded * 100) / progressEvent
                                        .total);
                                }
                            });

                            if (response.data.success) {
                                console.log(' ANTES:', this.photosDetail);
                                console.log(' Nueva foto:', response.data.photo);

                                // Asegurar que sea array
                                if (!Array.isArray(this.photosDetail)) {
                                    this.photosDetail = [];
                                }

                                const newPhoto = {
                                    id: response.data.photo.id,
                                    path: response.data.photo.path,
                                    eir_inspection_detail_id: this.detailId
                                };

                                //  Usar spread operator (fuerza reactividad)
                                this.photosDetail = [...this.photosDetail, newPhoto];

                                console.log(' DESPUÉS:', this.photosDetail);

                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                                    text: 'Foto subida correctamente',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }

                        } catch (error) {
                            console.error('Upload error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.response?.data?.message || 'Error al subir la imagen'
                            });
                        } finally {
                            this.uploading = false;
                            this.progress = 0;
                            event.target.value = '';
                        }
                    },
                    downloadPhoto(photoId) {
                        if (!photoId) {
                            return;
                        }
                        @this.call('downloadPhoto', photoId);
                    },
                    async deleteDetailPhoto(photoId) {
                        const result = await Swal.fire({
                            title: '¿Estás seguro?',
                            text: "¡No podrás revertir esto!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar!',
                            cancelButtonText: 'Cancelar'
                        });

                        if (result.isConfirmed) {
                            try {
                                // Llamar al método de Livewire - USANDO @this.call como antes
                                const response = await @this.call('deletePhoto', photoId);

                                // Si la eliminación fue exitosa, remover del array local
                                if (response) {
                                    // Filtrar el array para quitar la foto eliminada
                                    this.photosDetail = this.photosDetail.filter(photo => photo.id !== photoId);

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Eliminado!',
                                        text: 'La foto ha sido eliminada.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                }
                            } catch (error) {
                                console.error('Error al eliminar:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'No se pudo eliminar la foto'
                                });
                            }
                        }
                    },


                    toggleCaptureMode() {
                        this.captureMode = this.captureMode === 'camera' ? 'gallery' : 'camera';
                    }
                }
            }

            function dataCabecera() {
                return {
                    typeContainer: @entangle('containerInfo.container_type'),
                    lavado_normal: @entangle('eir.normal_wash'),
                    lavado_quimico: @entangle('eir.quimic_wash'),
                    lavado_especial: @entangle('eir.special_wash'),
                    containerData: @entangle('containerData'),
                    get maxGross() {
                        const tare = parseInt(this.containerData.tare) || 0;
                        const payload = parseInt(this.containerData.payload) || 0;
                        return tare + payload;
                    },
                    status_eir: @entangle('eir.status'),
                    statusBox: @entangle('statusBox'),
                    fast_repair: @entangle('eir.fast_repair'),
                    normal_repair: @entangle('eir.normal_repair'),
                    quoted_repair: @entangle('eir.quoted_repair'),
                    showContainer: true,
                    init() {
                        // Watchers para la lógica de exclusividad
                        this.$watch('lavado_normal', (value) => {
                            if (value === true) {
                                this.lavado_quimico = false;
                                this.lavado_especial = false;
                            }
                        });

                        this.$watch('lavado_quimico', (value) => {
                            if (value === true) {
                                this.lavado_normal = false;
                            }
                        });

                        this.$watch('lavado_especial', (value) => {
                            if (value === true) {
                                this.lavado_normal = false;
                            }
                        });

                        //chequeo status finales box con reparaciones

                        this.$watch('statusBox', (value) => {
                            this.handleRepairChange('status', value);
                        });

                        this.$watch('fast_repair', (value) => {
                            this.handleRepairChange('fast', value);
                        });

                        this.$watch('normal_repair', (value) => {
                            this.handleRepairChange('normal', value);
                        });

                        this.$watch('quoted_repair', (value) => {
                            this.handleRepairChange('quoted', value);
                        });

                        this.$watch('containerData.tare', () => {
                            this.containerData.max_gross = this.maxGross;
                        });

                        this.$watch('containerData.payload', () => {
                            this.containerData.max_gross = this.maxGross;
                        });
                    },
                    handleRepairChange(changeType, value) {
                        if (this.statusBox === 'OS') {
                            // Si es OS, resetear todos
                            this.fast_repair = false;
                            this.normal_repair = false;
                            this.quoted_repair = false;
                            return;
                        }

                        if (value === true) {
                            // Solo permitir un tipo de repair seleccionado
                            this.fast_repair = changeType === 'fast';
                            this.normal_repair = changeType === 'normal';
                            this.quoted_repair = changeType === 'quoted';
                        }
                    },
                    save() {
                        Swal.fire({
                            title: "¿Actualizar EIR?",
                            text: "Se guardarán los cambios realizados",
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, actualizar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                @this.call('save');
                            }
                        })
                    }
                }
            }

            /**
             * Método genérico para confirmar acciones Livewire
             * @param {string} message - Mensaje a mostrar en el confirm
             * @param {string} methodName - Nombre del método Livewire a ejecutar
             * @param {any} [param] - Parámetro opcional a pasar al método
             */
            function confirmAction(message, methodName, param = null) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '¡Sí, Seguro!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (param !== null) {
                            @this.call(methodName, param)
                        } else {
                            @this.call(methodName)
                        }
                    }
                });
                // Si hay parámetro, llamamos con él

            }
        </script>
    @endpush
</div>
