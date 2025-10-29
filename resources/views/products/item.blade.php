<div x-data="data">

    <form wire:submit="save">

        <x-wire-modal-card title="PRODUCTO" name="simpleModal" wire:model.live="openModal" width="2xl">

            {{-- Si hay errores, se mostrarán aquí --}}
            <x-validation-errors class="mb-6" />

            {{-- Afectacion IGV --}}
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <x-label>
                        CATEGORIA
                    </x-label>

                    <x-select x-model="product.category_id" class="w-full text-xs">
                        @foreach ($categories as $category)
                            <option value="{{ $category['id'] }}">
                                {{ $category['name'] }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label>
                        AFECTACION IGV
                    </x-label>

                    <x-select x-model="product.tipAfeIgv" class="w-full text-xs">
                        <template x-for="affectation in affectations" :key="affectation.id">
                            <option x-text="affectation.description" :value="affectation.id"></option>
                        </template>
                    </x-select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
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

                {{-- Codigo de barras --}}
                <div>
                    <x-label>
                        CÓDIGO BARRAS
                    </x-label>

                    <x-input x-model="product.codBarras" class="w-full text-xs" />
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

                    <x-input x-model="product.mtoValor" type="number" step="0.01" class="w-48 text-xs"
                        x-on:change="product.mtoValor = roundToTwoDecimals(product.mtoValor)"
                        x-on:input="calculateTotal('mtoValor')" />
                </div>
                {{-- Valor compra unitario --}}
                <div class="flex items-center space-x-2">
                    <span class="whitespace-nowrap text-sm">
                        Valor Compra
                    </span>

                    <x-input x-model="product.mtoValorCompra" type="number" step="0.01" class="w-48 text-xs"
                        x-on:change="product.mtoValorCompra = roundToTwoDecimals(product.mtoValorCompra)" />
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

                    <x-input x-model="product.precioUnitario" type="number" step="0.01" class="w-48 text-xs"
                        x-on:change="product.precioUnitario = roundToTwoDecimals(product.precioUnitario)"
                        x-on:input="calculateTotal('precioUnitario')" />
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

                <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>

@push('js')
    <script>
        function data() {
            return {
                errors: [],

                product: @entangle('product'),

                affectations: @json($affectations),

                calculateTotal(name) {

                    if (name == 'mtoValor') {
                        let mtoValor = this.roundToTwoDecimals(this.product.mtoValor);
                        this.product.precioUnitario = this.roundToTwoDecimals(mtoValor * (1 + this.product.porcentajeIgv /
                            100));
                    } else {
                        let precioUnitario = this.roundToTwoDecimals(this.product.precioUnitario);
                        this.product.mtoValor = this.roundToTwoDecimals(precioUnitario / (1 + this.product.porcentajeIgv /
                            100));
                    }

                },

                roundToTwoDecimals(value) {
                    return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
                },

                init() {

                    this.$watch('product.tipAfeIgv', value => {
                        affectation = this.affectations.find(affectation => affectation.id == value);

                        if (affectation.igv) {
                            this.product.porcentajeIgv = affectation.id == 17 ? 4 : 18;
                        } else {
                            this.product.porcentajeIgv = 0;
                        }
                    });

                    this.$watch('product.porcentajeIgv', value => {
                        this.calculateTotal('mtoValor');
                    });
                }
            }
        }

        function confirmDelete(productId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '¡Sí, bórralo!'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('destroy', productId);
                }
            });
        }
    </script>
@endpush
