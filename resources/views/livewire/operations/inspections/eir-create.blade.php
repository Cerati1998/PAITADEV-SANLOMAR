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
        </style>
    @endpush

    {{-- Form principal: guarda cabecera EIR --}}
    <div x-data="dataCabecera" class="mb-6">
        <form @submit.prevent="save" class="space-y-4">
            <x-wire-card class="mb-4 xl:mb-8">

                <x-validation-errors class="mb-4" />
                {{-- Datos del EIR --}}
                <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                    <span
                        class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-500 dark:bg-red-700 w-6 h-6"
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

                        <x-select wire:model="eir.serie" class="w-full">
                            @forelse ($series as $serie)
                                <option value="{{ $serie->name }}">{{ $serie->name }}</option>
                            @empty
                                <option value="">No hay series disponibles</option>
                            @endforelse
                        </x-select>

                        {{-- @error('eir.serie')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
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

                        <x-wire-datetime-picker wire:model.live="eir.started_at" display-format="DD-MM-YYYY HH:mm"
                            parse-format="YYYY-MM-DD HH:mm" min="{{ date('Y-m-d H:i:s') }}"
                            max="{{ date('Y-m-d H:i:s') }}" readonly />
                        {{-- @error('eir.started_at')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                    </div>

                    {{-- Fecha cierre --}}
                    <div class="col-span-1">
                        <x-label>F. Cierre</x-label>

                        <x-wire-datetime-picker wire:model="eir.closed_at" display-format="DD-MM-YYYY HH:mm"
                            parse-format="YYYY-MM-DD HH:mm" :min="date('Y-m-d H:i', strtotime($eir->started_at ?? now()))" class="w-full" readonly />
                        {{-- @error('eir.closed_at')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                    </div>

                    {{-- Datos del Evento de Ingreso (solo lectura, vienen del trace) --}}
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
                <header class="flex items-center border-b border-gray-200 pb-2 mb-4">
                    <span
                        class="outline-none inline-flex justify-center items-center group rounded-full text-white bg-red-500 dark:bg-red-700 w-6 h-6"
                        xl="xl">
                        2
                    </span>
                    <h1 class="ml-3 font-semibold text-sm uppercase">
                        Datos del Contenedor
                    </h1>
                </header>

                <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-4">
                    <div class="col-span-2 lg:col-span-1">
                        <x-label>Contenedor</x-label>

                        <x-wire-select :async-data="route('trace.search')" option-label="code" option-value="id"
                            placeholder="Selecciona Contenedor" wire:model.live="trace" />
                        {{-- @error('eir.trace_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                    </div>

                    <div class="col-span-1">
                        <x-label>ISO</x-label>
                        {{-- ISO es info referencial del contenedor --}}
                        <x-wire-input wire:model="containerInfo.iso_code" readonly />
                    </div>

                    <div class="col-span-1 lg:col-span-2">
                        <x-label>Tipo</x-label>
                        <x-wire-select wire:key="select-type-{{ $containerInfo['container_type'] ?? 'null' }}"
                            :options="$this->container_types" option-label="description" option-value="id" placeholder="Selecciona Tipo"
                            wire:model.live="containerInfo.container_type" />
                    </div>
                    <div x-show="typeContainer == 11 || typeContainer == 12" class="contents">
                        <div class="col-span-1">
                            <x-label>Tecnologia</x-label>
                            <x-wire-select
                                wire:key="select-technology-{{ $containerInfo['container_technology'] ?? 'null' }}"
                                :options="$this->reefer_technologies" option-label="name" option-value="id"
                                placeholder="Selecciona Tecnologia" wire:model="containerInfo.container_technology" />
                        </div>
                        <div class="col-span-1">
                            <x-label>Maquina</x-label>
                            <x-wire-select wire:key="select-machine-{{ $containerInfo['container_machine'] ?? 'null' }}"
                                :options="$this->reefer_machines" option-label="code" option-value="id" placeholder="Selecciona Maquina"
                                wire:model="containerInfo.container_machine" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-4">
                    {{-- Tara (actualizable en Container) --}}
                    <div class="col-span-1">
                        <x-label>Tara</x-label>
                        <x-wire-input type="number" wire:model="containerData.tare" min="0" max="9999" />
                    </div>

                    {{-- Payload (actualizable en Container) --}}
                    <div class="col-span-1">
                        <x-label>Payload</x-label>
                        <x-wire-input type="number" wire:model="containerData.payload" min="0" max="99999" />
                    </div>
                    {{-- Gross (actualizable en Container) --}}
                    <div class="col-span-1">
                        <x-label>Max Gross</x-label>
                        <x-wire-input type="number" wire:model="containerData.max_gross" min="0" max="99999" readonly/>
                    </div>
                    {{-- Height (actualizable en Container) --}}
                    <div class="col-span-1">
                        <x-label>Height</x-label>
                        <x-wire-input type="number" wire:model="containerData.max_height" min="0" max="99999" />
                    </div>
                    {{-- Width (actualizable en Container) --}}
                    <div class="col-span-1">
                        <x-label>Width</x-label>
                        <x-wire-input type="number" wire:model="containerData.max_width" min="0" max="99999" />
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
                        <div class="flex justify-start gap-2 lg:gap-4">
                            <x-wire-toggle left-label="Normal" wire:model="eir.normal_wash" sm />
                            <x-wire-toggle left-label="Quimico" x-on:click="lavado_quimico = !lavado_quimico;"
                                wire:model="eir.quimic_wash" sm />
                            <x-wire-toggle left-label="Especial" x-on:click="lavado_especial = !lavado_especial;"
                                wire:model="eir.special_wash" sm />
                            <x-wire-toggle left-label="Oxalico" wire:model="eir.oxalic_wash" sm />
                        </div>
                    </div>

                    {{-- Motivos (estos son campos del form, se guardan si corresponde) --}}
                    <div class="col-span-2 xl:col-span-3" x-show="lavado_quimico">
                        <x-label>Motivo Lavado Químico</x-label>
                        <x-wire-input placeholder="Ejem: Piso con Manchas" wire:model="eir.reason_quimic_wash" />
                        {{-- @error('eir.reason_quimic_wash')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                    </div>

                    <div class="col-span-2 xl:col-span-3" x-show="lavado_especial">
                        <x-label>Motivo Lavado Especial</x-label>
                        <x-wire-input placeholder="Ejem: Pupas o Larvas" wire:model="eir.reason_special_wash" />
                        {{--  @error('eir.reason_special_wash')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror --}}
                    </div>
                </div>
                <div class="mb-8">
                    <x-label>Observaciones</x-label>
                    <x-wire-input wire:model="eir.observations" />
                </div>

            </x-wire-card>

            {{-- Botones (Guardar - Cerrar) --}}
            <div class="mt-6 flex justify-center gap-4">
                <x-wire-button green label="Guardar" interaction="positive" icon="arrow-up-on-square"
                    type="submit" />
            </div>
        </form>
    </div>

    <x-spinner :targets="['save']" logo="{{ asset('img/logos/favicon.svg') }}" logoSize="56"
        message="Guardando información..." />
    @push('js')
        <script>
            function dataCabecera() {
                // aqui mostrar mensaje de confirmacion y luego enviar evento a Livewire
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

                        this.$watch('containerData.tare', () => {
                            this.containerData.max_gross = this.maxGross;
                        });

                        this.$watch('containerData.payload', () => {
                            this.containerData.max_gross = this.maxGross;
                        });
                    },
                    save() {
                        Swal.fire({
                            title: "¿Estas seguro de Iniciar?",
                            text: "¡No podrás revertir esto!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '¡Sí, Continua!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                @this.call('save');
                            }
                        })
                    }
                }

            }
            Livewire.on('eirCreated', data => {
                Swal.fire({
                    title: data[0].title,
                    text: data[0].text,
                    icon: data[0].icon,
                });

                if (data[0].redirect) {
                    setTimeout(() => {
                        window.location.href = data[0].redirect;
                    }, 2000);
                }
            });
        </script>
    @endpush
</div>
