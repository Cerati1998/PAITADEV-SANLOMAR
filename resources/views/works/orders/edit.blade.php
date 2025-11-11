<div x-data="dataEdit">
    <form wire:submit="update">

        <x-wire-modal-card title="Asignar Trabajos" width="7xl" wire:model="openEditModal" :hide-close="true">

            <x-validation-errors class="mb-4" />
            <header class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
                <div class="flex items-center">
                    <span
                        class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-600 dark:bg-red-700 w-6 h-6"
                        xl="xl">
                        1
                    </span>
                    <h1 class="ml-3 font-semibold text-sm uppercase">
                        Encabezado de Orden
                    </h1>
                </div>

                <!-- Botón mostrar/ocultar -->
                <button type="button"
                    class="text-blue-500 hover:text-gray-700 text-sm focus:outline-none transition flex items-center"
                    x-on:click="showHeader = !showHeader">
                    <span x-show="!showHeader">
                        <i class="fas fa-arrow-down"></i>
                        <span class="ml-1">Mostrar</span>
                    </span>
                    <span x-show="showHeader">
                        <i class="fas fa-arrow-up"></i>
                        <span class="ml-1">Ocultar</span>
                    </span>
                </button>
            </header>
            <div class="mb-1" x-show="showHeader" x-transition>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div class="col-span-1">
                        <x-label>Correlativo</x-label>
                        <x-input class="w-full" disabled
                            value="{{ $workOrderForm->serie . '-' . str_pad($workOrderForm->correlativo ?? '', 6, '0', STR_PAD_LEFT) }}" />
                    </div>

                    <div class="col-span-1">
                        <x-label>F. Apertura</x-label>
                        <x-wire-datetime-picker wire:model="workOrderForm.started_at" display-format="DD-MM-YYYY HH:mm"
                            parse-format="YYYY-MM-DD HH:mm" readonly />
                    </div>

                    <div class="col-span-1">
                        <x-label>Nº Estimado</x-label>
                        <x-input class="w-full" wire:model="workOrderForm.estimate_number" />
                    </div>
                    <div class="col-span-1">
                        <x-label>Fecha Estimado</x-label>
                        <x-wire-datetime-picker wire:model="workOrderForm.estimate_date" display-format="DD-MM-YYYY"
                            parse-format="YYYY-MM-DD" :max="now()" />
                    </div>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div class="col-span-1">
                        <x-label>Nº EIR</x-label>
                        <x-wire-select :options="$this->eirs" wire:model.live="workOrderForm.eir_inspection_id"
                            option-label="name" option-value="id" readonly />
                    </div>
                    <div class="col-span-1">
                        <x-label>Tipo</x-label>
                        <x-wire-select :options="$this->types" wire:model="workOrderForm.type" option-label="label"
                            option-value="value" readonly />
                    </div>
                    <div class="col-span-1">
                        <x-label>M.Repara</x-label>
                        <x-wire-input value="{{ $this->repair }}" readonly />
                    </div>
                    <div class="col-span-1">
                        <x-label>Contenedor</x-label>
                        <x-wire-input class="w-full" wire:model="container" readonly />
                    </div>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div class="col-span-2 lg:col-span-1">
                        <x-wire-select label="Proveedor" :options="$this->providers" wire:model="workOrderForm.provider_id"
                            option-label="name" option-value="id" />
                    </div>
                    <div class="col-span-2">
                        <x-wire-input label="Observaciones" class="w-full" wire:model="workOrderForm.comments" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-wire-button type="submit" class="w-[1/8] xl:mt-2.5" green spinner wire:loading.attr="disabled"
                        wire:target="update" icon="arrow-down-tray">
                        <span>Actualizar</span>
                    </x-wire-button>
                </div>

            </div>

            {{-- Detail --}}
            <x-wire-card>
                <div class="mb-2 lg:mb-3">
                    <div class="flex justify-center mb-2 lg:mb-3">
                        <!-- Botón mostrar/ocultar -->
                        <button type="button"
                            class="text-blue-500 hover:text-gray-700 text-sm focus:outline-none transition flex items-center"
                            x-on:click="showLabors = !showLabors">
                            <span x-show="!showLabors">
                                <i class="fas fa-arrow-down"></i>
                                <span class="ml-1">Mostrar</span>
                            </span>
                            <span x-show="showLabors">
                                <i class="fas fa-arrow-up"></i>
                                <span class="ml-1">Ocultar</span>
                            </span>
                        </button>
                    </div>

                    <header class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
                        <div class="flex items-center">
                            <span
                                class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-600 dark:bg-red-700 w-6 h-6"
                                xl="xl">
                                2
                            </span>
                            <h1 class="ml-3 font-semibold text-sm uppercase">
                                Detalle de Trabajos
                            </h1>
                        </div>
                        <x-wire-button x-on:click="$openModal('detailWorkOrderModal')" class="w-[1/8] xl:mt-2.5"
                            icon="plus-circle" blue>
                            <span>Agregar</span>
                        </x-wire-button>

                    </header>
                </div>
                <div class="overflow-x-auto mt-4 min-h-48" x-show="showLabors" x-transition>
                    @if (count($this->workOrderForm->labors))
                        <div class="overflow-y-auto  max-h-[300px] min-h-0">
                            <table class="w-full text-left text-sm border-separate border-spacing-2">
                                <thead>
                                    <tr class="uppercase p-2 bg-primary-600">
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-start">Acciones</th>
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Cantidad</th>
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Ubicación</th>
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Daño</th>
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Componente</th>
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Método</th>
                                        <th class="p-2 bg-primary-600 text-white rounded-lg text-center">Descripción
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->workOrderForm->labors as $key => $item)
                                        <tr wire:key="labor-{{ $item->id }}">
                                            <td>
                                                <div class="flex space-x-1 items-center">
                                                    <x-wire-mini-button green icon="pencil" sm
                                                        wire:click="editLabor({{ $item->id }})" />
                                                    <x-wire-mini-button blue icon="user-plus"
                                                        wire:click="editWorkerLabor({{ $item->id }})" sm />
                                                    @if ($item->photos_count > 0)
                                                        <x-wire-mini-button yellow icon="camera"
                                                            wire:click="loadPhotoLabor({{ $item->id }})" sm />
                                                    @else
                                                        <x-wire-mini-button primary icon="camera"
                                                            wire:click="loadPhotoLabor({{ $item->id }})" sm />
                                                    @endif
                                                    <x-wire-mini-button red
                                                        onclick="confirmAction('¡No podrás revertir esto!','destroyLabor',{{ $item->id }} )"
                                                        icon="trash" sm />
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-gray-700 font-medium text-base lg:text-xs">
                                                    {{ $item->qty }}
                                                </span>
                                            </td>

                                            <td class="relative overflow-visible text-center">
                                                <x-tooltip :text="$item?->inspectionDetail?->location->description">
                                                    <span class="text-gray-700 font-medium text-base lg:text-xs">
                                                        {{ $item?->inspectionDetail?->location->code }}
                                                    </span>
                                                </x-tooltip>
                                            </td>
                                            <td class="relative overflow-visible text-center">
                                                <x-tooltip :text="$item?->inspectionDetail?->damage->description">
                                                    <span class="text-gray-700 font-medium text-base lg:text-xs">
                                                        {{ $item?->inspectionDetail?->damage->code }}
                                                    </span>
                                                </x-tooltip>
                                            </td>
                                            <td class="relative overflow-visible text-center">
                                                <x-tooltip :text="$item?->inspectionDetail?->component->description">
                                                    <span class="text-gray-700 font-medium text-base lg:text-xs">
                                                        {{ $item?->inspectionDetail?->component->code }}
                                                    </span>
                                                </x-tooltip>
                                            </td>
                                            <td class="relative overflow-visible text-center">
                                                <x-tooltip :text="$item?->inspectionDetail?->method->description">
                                                    <span class="text-gray-700 font-medium text-base lg:text-xs">
                                                        {{ $item?->inspectionDetail?->method->code }}
                                                    </span>
                                                </x-tooltip>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-gray-700 font-medium text-base lg:text-xs">
                                                    {{ $item?->description }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex justify-center mt-8">
                            <button class="w-80" x-on:click="$openModal('workOrderForm.detailWorkOrderModal')">
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
            </x-wire-card>
            <div class="mt-4">
                <p class="text-gray-500 font-semibold text-sm">
                    <span class="text-red-500 ">Nota:</span> L: Largo W:Ancho H:Alto
                </p>
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" {{-- wire:click="resetea" --}} />
                <template x-if="statusOrder === 'borrador'">
                    <x-wire-button red label="Cerrar" icon="lock-closed"
                        onclick="confirmAction('¡La Orden no podrá volver a editarse!','closeWorkOrder')" sm />
                </template>

                <template x-if="statusOrder !== 'borrador'">
                    <x-wire-button green label="Reaperturar" icon="lock-open"
                        onclick="confirmAction('¡La Orden podrá volver a editarse!','voidWorkOrder')" sm />
                </template>
            </x-slot>

        </x-wire-modal-card>
    </form>

    {{-- Modal para ingresar detalles de orden de trabajo --}}
    <div>
        <form wire:submit="saveDetail">
            <x-wire-modal-card title="Detalle de Inspección" name="detailWorkOrderModal"
                wire:model="opendetailWorkOrderModal" width="5xl" :persistent="true">

                <x-validation-errors class="mb-4" />

                {{-- SECCIÓN: CÓDIGOS DE INSPECCIÓN --}}
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

                    <col-span-1>
                        <x-label class="mb-1">
                            Daño
                        </x-label>
                        <x-select label="Daño" wire:model.live="workOrderForm.detailWorkOrderModal.damage_id"
                            class="w-full">
                            <option value="" hidden>Seleccione un Item</option>
                            @foreach ($workOrderForm->availableDamages as $damage)
                                <option value="{{ $damage['damage_id'] }}">
                                    {{ $damage['damage_code'] }} - {{ $damage['damage_description'] }}
                                </option>
                            @endforeach
                        </x-select>
                    </col-span-1>

                    {{-- CAMPOS AUTOCOMPLETADOS (READONLY) --}}
                    <x-wire-input label="Ubicación" placeholder="Auto"
                        wire:model="workOrderForm.detailWorkOrderModal.location" readonly class="bg-gray-50" />

                    <x-wire-input label="Componente" placeholder="Auto"
                        wire:model="workOrderForm.detailWorkOrderModal.component" readonly class="bg-gray-50" />

                    <x-wire-input label="Método" placeholder="Auto"
                        wire:model="workOrderForm.detailWorkOrderModal.method" readonly class="bg-gray-50" />

                </div>

                {{-- SECCIÓN: CANTIDADES Y MEDIDAS --}}
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
                    <x-wire-number type="number" step="1" wire:model="workOrderForm.detailWorkOrderModal.qty"
                        label="Cantidad" min="0" readonly class="bg-gray-50" />

                    <x-wire-number type="number" step="1"
                        wire:model="workOrderForm.detailWorkOrderModal.length" label="Largo" min="0" readonly
                        class="bg-gray-50" />

                    <x-wire-number type="number" step="1"
                        wire:model="workOrderForm.detailWorkOrderModal.width" label="Ancho" min="0" readonly
                        class="bg-gray-50" />

                    <x-wire-number type="number" step="1"
                        wire:model="workOrderForm.detailWorkOrderModal.height" label="Altura" min="0" readonly
                        class="bg-gray-50" />
                </div>

                {{-- DESCRIPCIÓN --}}
                <div class="mb-3">
                    <x-wire-textarea label="Descripción de Labores"
                        wire:model="workOrderForm.detailWorkOrderModal.description"
                        placeholder="Detalla si es requerido" rows="3" class="bg-gray-50" />
                </div>

                {{-- FOOTER --}}
                <x-slot name="footer" class="flex justify-end gap-x-4">
                    <div class="flex gap-x-4">
                        <x-wire-button flat label="Cancelar" x-on:click="close" wire:click="reseteaLabor" />

                        <x-wire-button primary label="Guardar" type="submit" spinner="saveDetail"
                            wire:loading.attr="disabled" :disabled="!$workOrderForm->detailWorkOrderModal['damage_id']" />
                    </div>
                </x-slot>
            </x-wire-modal-card>
        </form>
    </div>

    {{-- Modal para añadir fotos a los labores --}}
    <div x-data="photoLaborUploader()">
        <x-wire-modal-card title="Subir Evidencia Fotográfica - Labor" wire:model="openModalLaborPhoto"
            width="5xl" :hide-close="true">

            <div class="w-full space-y-6">

                {{-- Zona de subida de foto (solo si está en borrador) --}}
                @if ($workOrderForm->status === 'borrador')
                    {{-- Toggle para elegir modo de captura --}}
                    <div
                        class="mb-4 flex items-center justify-center gap-2 sm:gap-4 p-2 sm:p-3 bg-gray-100 rounded-lg">
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

                    <div
                        class="relative group border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                        <input type="file" x-on:change="uploadLaborPhoto($event)"
                            :capture="captureMode === 'camera' ? 'environment' : null"
                            class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">

                        {{-- Indicador de carga --}}
                        <div x-show="uploading"
                            class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-lg">
                            <div class="text-white text-center">
                                <i class="fas fa-spinner fa-spin text-2xl sm:text-3xl"></i>
                                <p class="text-xs sm:text-sm mt-2" x-text="`${progress}%`"></p>
                            </div>
                        </div>

                        {{-- Badge indicador de modo --}}
                        <div class="absolute top-1 right-1 sm:top-2 sm:right-2 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded text-xs font-medium"
                            :class="captureMode === 'camera' ? 'bg-blue-500 text-white' : 'bg-purple-500 text-white'">
                            <i :class="captureMode === 'camera' ? 'fas fa-camera' : 'fas fa-images'"></i>
                        </div>

                        <div class="flex flex-col items-center justify-center text-gray-500">
                            <p class="text-sm font-medium">Haz clic o arrastra una imagen aquí</p>
                            <p class="text-xs text-gray-400">Formatos permitidos: JPG, PNG, WEBP</p>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <i class="fas fa-lock text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-sm text-yellow-700">
                            La orden está cerrada. No puedes agregar o eliminar fotos.
                        </p>
                    </div>
                @endif

                {{-- Lista de fotos ya subidas --}}
                @if (count($photosLabor))
                    <div class="h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach ($photosLabor as $photo)
                                <div class="relative group rounded-lg overflow-hidden shadow-md">
                                    <img src="{{ $photo->url }}" class="w-full h-40 object-cover rounded-md">
                                    <div
                                        class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
                                        <x-wire-button xs icon="eye" blue href="{{ $photo->url }}"
                                            target="_blank" />
                                        <x-wire-button xs icon="arrow-down-tray" white
                                            href="{{ route('work-order.photo.download', $photo->id) }}" />

                                        {{-- Botón eliminar solo si está en borrador --}}
                                        @if ($workOrderForm->status === 'borrador')
                                            <x-wire-button xs icon="trash" red
                                                onclick="confirmAction('¡No podrás revertir esto!','deleteLaborPhoto',{{ $photo->id }})" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center text-gray-400 text-sm">
                        Aún no se han añadido evidencias fotográficas a este labor.
                    </div>
                @endif

            </div>

            {{-- Footer --}}
            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cerrar" x-on:click="close" wire:click="closePhotoModal" />
                @if (count($photosLabor))
                    <x-wire-button sm icon="arrow-down-tray" red label="Descargar Todas"
                    href="{{ route('work-order.photos.download', $this->workOrderId) }}" />
                @endif
            </x-slot>
        </x-wire-modal-card>
    </div>

    {{-- Modal para ingresar datos para inicio y termino de trabajos, trabajadores, etc --}}
    <div>
        <form wire:submit="saveDetailWorker">
            <x-wire-modal-card title="Ingrese detalle de Trabajo" name="workerLaborModal" wire:model="workersModal"
                :hide-close="true" width="3xl">
                <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                    <span
                        class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-600 dark:bg-red-700 w-6 h-6"
                        xl="xl">
                        1
                    </span>
                    <h1 class="ml-3 font-semibold text-sm uppercase">
                        Encabezado
                    </h1>
                </header>
                <x-validation-errors class="mb-4" />
                <div class="mb-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="col-span-2 lg:col-span-1">
                        <x-wire-input type="datetime-local" label="Fecha Inicio"
                            wire:model.live="workOrderForm.detailWorkOrderModal.started_at" />
                    </div>
                    <div class="col-span-2 lg:col-span-1">
                        <x-wire-input type="datetime-local" label="Fecha Termino"
                            wire:model.live="workOrderForm.detailWorkOrderModal.finished_at" />
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs">
                            <span class=" text-red-500 font-semibold">Atención:</span>
                            Si agregas la fecha de termino se concluirá el trabajo
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="flex justify-between items-center">
                            <label for="vessel_id" class="text-sm text-gray-700">
                                Añadir Trabajador <x-required-tag />
                            </label>
                            <button type="button" x-on:click="$openModal('simpleModal')"
                                class="text-xs text-blue-500 hover:underline">
                                + Nuevo Trabajador
                            </button>
                        </div>
                        <x-wire-select :async-data="route('worker.search')" option-label="name" option-value="id"
                            wire:model="workOrderForm.detailLaborWorker.worker_id"
                            placeholder="Filtra por Nº de documento o Nombre" class="w-full" />
                    </div>


                    <x-wire-toggle label="Es el Supervisor del Trabajo?"
                        wire:model="workOrderForm.detailLaborWorker.main_worker" class="mt-4" />
                </div>

                <div class="mb-4">
                    <x-wire-textarea class="col-span-2 bg-gray-50" label="Detalle de actividades"
                        wire:model="workOrderForm.detailLaborWorker.task_description"
                        placeholder="Detalla si es requerido" rows="3" />
                </div>

                <header class="flex items-center border-b border-gray-200 pb-2 mb-3">
                    <span
                        class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-600 dark:bg-red-700 w-6 h-6"
                        xl="xl">
                        2
                    </span>
                    <h1 class="ml-3 font-semibold text-sm uppercase">
                        Resumen de Trabajadores
                    </h1>
                </header>
                <div class="mb-4 p-4 overflow-y-auto  max-h-[200px] min-h-0">
                    <ul class="space-y-4 text-left text-gray-500 dark:text-gray-400">
                        @if (!$this->workOrderForm->workers || $this->workOrderForm->workers->isEmpty())
                            <li class="flex items-center justify-center space-x-3">
                                <i class="fas fa-ban">
                                </i><span class="text-red-500 font-medium">No se ha asignado aun ningun
                                    Trabajador</span>
                            </li>
                        @else
                            @foreach ($this->workOrderForm->workers as $laborWorker)
                                <li
                                    class="flex items-center justify-between p-2 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-user text-gray-400"></i>
                                        <div>
                                            <p class="font-medium text-md text-gray-900 dark:text-white">
                                                {{ $laborWorker->worker->name }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ $laborWorker->worker->numDoc }} - {{ $laborWorker->worker->cargo }}
                                            </p>

                                            @if ($laborWorker->task_description)
                                                <p class="text-xs text-gray-400 mt-1">
                                                    {{ $laborWorker->task_description }}
                                                </p>
                                            @endif

                                            @if ($laborWorker->main_worker)
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 mt-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                                                    <i class="fas fa-star text-xs mr-1"></i>
                                                    Principal
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <x-wire-button icon="trash" red xs
                                        onclick="confirmAction('Eliminar a {{ $laborWorker->worker->name }} de este labor?',
                                        'destroyLaborWorker',{{ $laborWorker->id }})" />
                                </li>
                            @endforeach

                        @endif
                    </ul>
                </div>

                <x-slot name="footer" class="flex justify-end gap-x-4">
                    <div class="flex gap-x-4">
                        <x-wire-button flat label="Cancel" x-on:click="close" />
                        <x-wire-button primary label="Agregar" type="submit" />
                    </div>
                </x-slot>
            </x-wire-modal-card>
        </form>
    </div>
</div>
<x-spinner :targets="[
    'update',
    'destroy',
    'destroyLabor',
    'destroyLaborWorker',
    'saveDetail',
    'saveDetailWorker',
    'closeWorkOrder',
    'voidWorkOrder',
]" message="Procesando..." logo="{{ asset('img/logos/favicon.svg') }}" logoSize="56" />
@push('js')
    <script src="https://unpkg.com/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    <script>
        function dataEdit() {
            return {
                showHeader: false,
                showLabors: true,
                statusOrder: @entangle('workOrderForm.status')
            }
        }

        // Alpine component para subir fotos de labor
        // Alpine component para subir fotos de labor
        function photoLaborUploader() {
            return {
                laborId: @entangle('currentLaborId'),
                statusOrder: @entangle('workOrderForm.status'),
                uploading: false,
                progress: 0,
                captureMode: 'gallery', // ← AGREGAR ESTA LÍNEA

                async uploadLaborPhoto(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    // Validaciones
                    if (this.statusOrder !== 'borrador') {
                        Swal.fire('Error', 'No puedes agregar fotos, la orden ya se encuentra cerrada', 'error');
                        return;
                    }

                    if (!file.type.startsWith('image/')) {
                        Swal.fire('Error', 'Solo se permiten imágenes', 'error');
                        return;
                    }

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
                    formData.append('labor_id', this.laborId);

                    this.uploading = true;
                    this.progress = 0;

                    try {
                        const response = await axios.post('/work-orders/upload-photos', formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            },
                            onUploadProgress: (progressEvent) => {
                                this.progress = Math.round(
                                    (progressEvent.loaded * 100) / progressEvent.total
                                );
                            }
                        });

                        if (response.data.success) {
                            // Recargar fotos desde el componente Livewire
                            await @this.call('loadPhotoLabor', await @this.get('currentLaborId'));

                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Foto de evidencia subida correctamente',
                                timer: 2000,
                                showConfirmButton: false
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
                        this.uploading = false;
                        this.progress = 0;
                        event.target.value = '';
                    }
                }
            }
        }

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
        }

        function openLightbox(url) {
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('data-fslightbox', 'manual-gallery');
            link.style.display = 'none';

            document.body.appendChild(link);

            if (window.refreshFsLightbox) {
                refreshFsLightbox();
            }

            link.click();

            setTimeout(() => document.body.removeChild(link), 500);
        }
    </script>
@endpush
