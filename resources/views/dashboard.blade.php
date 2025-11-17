<x-dashboard-layout title="Dashboard | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
    ],
]">
    {{-- <div class="hidden md:grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">

        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center">
                <img class="h-8 w-8 rounded-full object-cover" src="{{ auth()->user()->profile_photo_url }}"
                    alt="{{ auth()->user()->name }}" />

                <div class="ml-4 flex-1">
                    <h2 class="text-lg font-semibold">
                        Bienvenido, {{ auth()->user()->name }}
                    </h2>

                    <form action="{{ route('logout') }}" method="POST">
                        <button class="text-sm hover:text-blue-500">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 flex justify-center items-center">
            <h2 class="text-2xl font-semibold truncate">
                Sucursal {{ Str::limit(session('branch')->name, 25) }}
            </h2>
        </div>
    </div> --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <x-wire-card>
            <p class="text-sm font-semibold text-gray-500">Total de Inspecciones Hoy:</p>
            <p class="text-3xl text-bold text-gray-900">{{ $data['total_inspections'] }}</p>
        </x-wire-card>
        <x-wire-card>
            <p class="text-sm font-semibold text-gray-500">Total de Reparaciones Hoy:</p>
            <p class="text-3xl text-bold text-gray-900">{{ $data['total_works'] }}</p>
        </x-wire-card>
        <x-wire-card>
            <p class="text-sm font-semibold text-gray-500">Total de Leak Hoy:</p>
            <p class="text-3xl text-bold text-gray-900">{{ $data['total_leaks'] }}</p>
        </x-wire-card>
    </div>

     <!-- Sección de Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Gráfico de líneas: Tendencia semanal -->
        <x-wire-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Tendencia Semanal</h3>
            </div>
            <div id="weekly-trend-chart"></div>
        </x-wire-card>

        <!-- Gráfico de dona: Distribución por tipo -->
        <x-wire-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Distribución por Tipo</h3>
            </div>
            <div id="distribution-chart"></div>
        </x-wire-card>
    </div>

    <!-- Gráfico de barras: Comparativa mensual -->
    <div class="grid grid-cols-1 mb-4">
        <x-wire-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Comparativa Mensual</h3>
            </div>
            <div id="monthly-comparison-chart"></div>
        </x-wire-card>
    </div>

    <div class="grid grid-cold-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <x-wire-card>
                <p class="text-lg font-semibold text-gray-900">Ultimas reparaciones cerradas:</p>
                <ul class="divide-y divide-gray-300">
                    @forelse ($data['recent_repairs'] as $rep)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $rep->serie . '-' . str_pad($rep->correlativo, 6, 0, STR_PAD_LEFT) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $rep->container?->code }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $rep->closed_at->diffForHumans() }}
                            </span>
                        </li>
                    @empty
                        <li class="py-3">
                            <p class="text-gray-500">
                                No tienes reparaciones completadas.
                            </p>
                        </li>
                    @endforelse
                </ul>
            </x-wire-card>
        </div>
        <div>
            <x-wire-card>
                <p class="text-lg font-semibold text-gray-500">Acciones rapidas</p>
                <div class="mt-4 space-y-2">
                    <x-wire-button class="w-full" href="{{ route('inspections.eir.list') }}" target="_blank" indigo>
                        Gestionar Inspecciones
                    </x-wire-button>
                    <x-wire-button class="w-full" href="{{ route('work_orders.box') }}" target="_blank" blue>
                        Gestionar Ordenes
                    </x-wire-button>
                    <x-wire-button class="w-full" href="{{ route('inspections.leak.list') }}" target="_blank" gray>
                        Gestionar Leak Test
                    </x-wire-button>
                </div>
            </x-wire-card>
        </div>
    </div>
    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
        // Gráfico de Tendencia Semanal (Líneas)
        const weeklyTrendOptions = {
            series: [{
                name: 'Inspecciones',
                data: [31, 40, 28, 51, 42, 109, 100]
            }, {
                name: 'Reparaciones',
                data: [11, 32, 45, 32, 34, 52, 41]
            }, {
                name: 'Leak Test',
                data: [15, 11, 22, 27, 13, 22, 37]
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false
                }
            },
            colors: ['#6366f1', '#3b82f6', '#6b7280'],
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            },
            legend: {
                position: 'top',
            },
            grid: {
                borderColor: '#f1f1f1',
            }
        };

        const weeklyTrendChart = new ApexCharts(document.querySelector("#weekly-trend-chart"), weeklyTrendOptions);
        weeklyTrendChart.render();

        // Gráfico de Distribución (Dona)
        const distributionOptions = {
            series: [{{ $data['total_inspections'] }}, {{ $data['total_works'] }}, {{ $data['total_leaks'] }}],
            chart: {
                type: 'donut',
                height: 350,
            },
            labels: ['Inspecciones', 'Reparaciones', 'Leak Test'],
            colors: ['#6366f1', '#3b82f6', '#6b7280'],
            legend: {
                position: 'bottom',
            },
            dataLabels: {
                enabled: true,
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Hoy',
                                fontSize: '16px',
                                fontWeight: 600,
                            }
                        }
                    }
                }
            }
        };

        const distributionChart = new ApexCharts(document.querySelector("#distribution-chart"), distributionOptions);
        distributionChart.render();

        // Gráfico de Comparativa Mensual (Barras)
        const monthlyComparisonOptions = {
            series: [{
                name: 'Inspecciones',
                data: [440, 505, 414, 671, 227, 413]
            }, {
                name: 'Reparaciones',
                data: [230, 421, 352, 452, 315, 298]
            }, {
                name: 'Leak Test',
                data: [180, 280, 250, 320, 290, 310]
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            colors: ['#6366f1', '#3b82f6', '#6b7280'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4,
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            },
            legend: {
                position: 'top',
            },
            fill: {
                opacity: 1
            },
            grid: {
                borderColor: '#f1f1f1',
            }
        };

        const monthlyComparisonChart = new ApexCharts(document.querySelector("#monthly-comparison-chart"), monthlyComparisonOptions);
        monthlyComparisonChart.render();
    </script>
    @endpush

</x-dashboard-layout>
