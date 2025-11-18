<x-dashboard-layout title="Dashboard | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
    ],
]">
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
                <h3 class="text-lg font-semibold text-gray-900">Tendencia Semanal (Últimos 7 días)</h3>
            </div>
            <div id="weekly-trend-chart" class="min-h-[350px] flex items-center justify-center">
                <div class="text-gray-500">Cargando datos...</div>
            </div>
        </x-wire-card>

        <!-- Gráfico de dona: Distribución por tipo de reparación -->
        <x-wire-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Tipos de Reparación (Último mes)</h3>
            </div>
            <div id="distribution-chart" class="min-h-[350px] flex items-center justify-center">
                <div class="text-gray-500">Cargando datos...</div>
            </div>
        </x-wire-card>
    </div>

    <!-- Gráfico de barras: Comparativa mensual -->
    <div class="grid grid-cols-1 mb-4">
        <x-wire-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Comparativa Mensual (Últimos 6 meses)</h3>
            </div>
            <div id="monthly-comparison-chart" class="min-h-[350px] flex items-center justify-center">
                <div class="text-gray-500">Cargando datos...</div>
            </div>
        </x-wire-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
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

    <!-- Scripts al final del contenido -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        //console.log('=== INICIO DEL SCRIPT ===');
        //console.log('Axios disponible:', typeof axios !== 'undefined');
        //console.log('ApexCharts disponible:', typeof ApexCharts !== 'undefined');

        // Variables para almacenar las instancias de los gráficos
        let weeklyTrendChart = null;
        let distributionChart = null;
        let monthlyComparisonChart = null;

        // Función para cargar datos
        async function loadChartData() {
            try {
                //console.log('Iniciando carga de datos...');
                //console.log('URL de la petición:', '{{ route('dashboard.chart-data') }}');

                const response = await axios.get('{{ route('dashboard.chart-data') }}');

                //console.log('Respuesta recibida:', response.data);

                if (response.data.success) {
                    const data = response.data.data;

                    //console.log('Datos extraídos:', data);

                    // Renderizar los gráficos con los datos
                    renderWeeklyTrendChart(data.weekly_data);
                    renderDistributionChart(data.repair_types);
                    renderMonthlyComparisonChart(data.monthly_data);

                    //console.log('Gráficos renderizados exitosamente');
                } else {
                    console.error('Error en la respuesta:', response.data);
                    showError('Error al cargar los datos');
                }
            } catch (error) {
                console.error('Error completo:', error);
                console.error('Respuesta del servidor:', error.response);
                showError('Error: ' + (error.response?.data?.message || error.message));
            }
        }

        function renderWeeklyTrendChart(data) {
            //console.log('Renderizando gráfico semanal:', data);

            // Limpiar el contenido de "Cargando..."
            const container = document.querySelector("#weekly-trend-chart");
            container.innerHTML = '';

            const options = {
                series: [{
                    name: 'Inspecciones',
                    data: data.inspections
                }, {
                    name: 'Reparaciones',
                    data: data.works
                }, {
                    name: 'Leak Test',
                    data: data.leaks
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                colors: ['#6366f1', '#3b82f6', '#6b7280'],
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: data.days,
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                },
                grid: {
                    borderColor: '#f1f1f1',
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                }
            };

            if (weeklyTrendChart) {
                weeklyTrendChart.destroy();
            }

            weeklyTrendChart = new ApexCharts(document.querySelector("#weekly-trend-chart"), options);
            weeklyTrendChart.render();
        }

        function renderDistributionChart(data) {
            //console.log('Renderizando gráfico de distribución:', data);
            // Limpiar el contenido de "Cargando..."
            const container = document.querySelector("#distribution-chart");
            container.innerHTML = '';

            const options = {
                series: data.values,
                chart: {
                    type: 'donut',
                    height: 350,
                },
                labels: data.labels,
                colors: ['#10b981', '#3b82f6', '#f59e0b'],
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
                                    label: 'Total',
                                    fontSize: '16px',
                                    fontWeight: 600,
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            if (distributionChart) {
                distributionChart.destroy();
            }

            distributionChart = new ApexCharts(document.querySelector("#distribution-chart"), options);
            distributionChart.render();
        }

        function renderMonthlyComparisonChart(data) {
            //console.log('Renderizando gráfico mensual:', data);
            const container = document.querySelector("#monthly-comparison-chart");
            container.innerHTML = '';

            const options = {
                series: [{
                    name: 'Inspecciones',
                    data: data.inspections
                }, {
                    name: 'Reparaciones',
                    data: data.works
                }, {
                    name: 'Leak Test',
                    data: data.leaks
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
                    categories: data.months,
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                },
                fill: {
                    opacity: 1
                },
                grid: {
                    borderColor: '#f1f1f1',
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " registros"
                        }
                    }
                }
            };

            if (monthlyComparisonChart) {
                monthlyComparisonChart.destroy();
            }

            monthlyComparisonChart = new ApexCharts(document.querySelector("#monthly-comparison-chart"), options);
            monthlyComparisonChart.render();
        }

        function showError(message) {
            console.error('Mostrando error:', message);
            ['#weekly-trend-chart', '#distribution-chart', '#monthly-comparison-chart'].forEach(selector => {
                const element = document.querySelector(selector);
                if (element) {
                    element.innerHTML = `<div class="text-red-500 text-center py-8">${message}</div>`;
                }
            });
        }

        // Verificar si el DOM ya está listo
        function initCharts() {
            if (typeof ApexCharts === 'undefined') {
                //console.log('ApexCharts aún no está disponible, reintentando en 100ms...');
                setTimeout(initCharts, 100);
                return;
            }

            //console.log('ApexCharts cargado correctamente, iniciando...');

            if (document.readyState === 'loading') {
                //console.log('DOM aún cargando, esperando DOMContentLoaded...');
                document.addEventListener('DOMContentLoaded', function() {
                    //console.log('DOM cargado, iniciando carga de datos...');
                    loadChartData();
                });
            } else {
                //console.log('DOM ya cargado, iniciando carga de datos inmediatamente...');
                loadChartData();
            }
        }

        // Iniciar cuando el script se ejecute
        initCharts();
    </script>

</x-dashboard-layout>
