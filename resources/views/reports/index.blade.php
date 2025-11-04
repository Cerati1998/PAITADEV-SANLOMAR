<x-dashboard-layout title="Reportes | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Reportes',
    ],
]">
    <div x-data="dataReport">
        <div class="mx-auto max-w-screen-xl px-2 2xl:px-0">
            <div class="mx-auto max-w-5xl">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">Filtros</h2>

                <div class="mt-6 sm:mt-8 lg:flex lg:items-start lg:gap-12">
                    <form method="POST" id="reportFilters"
                        class="w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6 lg:max-w-xl lg:p-8">
                        @csrf
                        <div class="mb-6 grid grid-cols-2 gap-4">
                            <div class="col-span-2 sm:col-span-1">
                                <x-wire-input type="date" label="Desde:" name="since" x-model="since" />
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <x-wire-input type="date" label="Hasta:" name="until" x-model="until" />
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <x-wire-select :async-data="route('line.search')" option-value="id" option-label="name" label="Linea:"
                                    name="line" x-model="line" />
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <x-wire-select :async-data="route('client.search')" option-value="id" option-label="rznSocial"
                                    label="Cliente" name="client" x-model="client" />
                            </div>
                        </div>

                        <button type="reset" x-on:click="cleanSelects"
                            class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4  focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                            Limpiar</button>
                    </form>

                    <div class="mt-6 grow sm:mt-8 lg:mt-0">
                        <div
                            class="space-y-4 rounded-lg border border-gray-100 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Despachos</dt>
                                    <x-wire-button label="Exportar" blue sm icon="arrow-down-on-square"
                                        x-on:click.prevent="submitForm('{{ route('reports.dispatches') }}')" />
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Leak Test</dt>
                                    <x-wire-button label="Exportar" green sm icon="arrow-down-on-square"
                                        x-on:click.prevent="submitForm('{{ route('reports.leaktests') }}')" />
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Comprobantes</dt>
                                    <x-wire-button label="Exportar" red sm icon="arrow-down-on-square"
                                        x-on:click.prevent="submitForm('{{ route('reports.invoices') }}')" />
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Inspecciones</dt>
                                    <x-wire-button label="Exportar" yellow sm icon="arrow-down-on-square"
                                        x-on:click.prevent="submitForm('{{ route('reports.inspections') }}')" />
                                </div>
                            </div>
                        </div>

                        {{-- <div class="mt-6 flex items-center justify-center gap-8">
                            <img class="h-8 w-auto dark:hidden"
                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/brand-logos/paypal.svg"
                                alt="">
                            <img class="hidden h-8 w-auto dark:flex"
                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/brand-logos/paypal-dark.svg"
                                alt="">
                            <img class="h-8 w-auto dark:hidden"
                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/brand-logos/visa.svg"
                                alt="">
                            <img class="hidden h-8 w-auto dark:flex"
                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/brand-logos/visa-dark.svg"
                                alt="">
                            <img class="h-8 w-auto dark:hidden"
                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/brand-logos/mastercard.svg"
                                alt="">
                            <img class="hidden h-8 w-auto dark:flex"
                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/brand-logos/mastercard-dark.svg"
                                alt="">
                        </div> --}}
                    </div>
                </div>

                <p class="mt-6 text-center text-gray-500 dark:text-gray-400 sm:mt-8 lg:text-left">
                    Selecciona los filtros requeridos y presiona el boton correspondiente al reporte
                </p>
            </div>
        </div>
    </div>
    @push('js')
        <script>
            function dataReport() {
                return {
                    since: null,
                    until: null,
                    line: null,
                    client: null,
                    actionUrl: '',

                    cleanSelects() {
                        this.since = null;
                        this.until = null;
                        this.line = null;
                        this.client = null;
                    },

                    submitForm(url) {
                        this.actionUrl = url;
                        const form = document.getElementById("reportFilters");
                        form.action = url;
                        form.submit();
                    },
                };
            }
        </script>
    @endpush
</x-dashboard-layout>
