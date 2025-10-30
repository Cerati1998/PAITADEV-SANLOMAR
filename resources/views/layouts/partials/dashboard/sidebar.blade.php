@php
    $links = [
        [
            'name' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'header' => 'Gestor de Contenedores',
            'icon' => 'fa-solid fa-rectangle-list',
        ],
        [
            'name' => 'Descargas',
            'icon' => 'fa-solid fa-arrow-up-from-water-pump',
            'route' => route('discharges.index'),
            'active' => request()->routeIs(['discharges.*', 'discharge.*']),
        ],
        [
            'name' => 'Devoluciones',
            'icon' => 'fa-solid fa-arrow-down-short-wide',
            'route' => route('devolutions.index'),
            'active' => request()->routeIs('devolutions.*'),
        ],
        [
            'name' => 'Contenedores',
            'icon' => 'fa-solid fa-boxes-stacked',
            'active' => request()->routeIs(['containers.*', 'inspections.*']),
            'submenu' => [
                [
                    'name' => 'Lista',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('containers.traces'),
                    'active' => request()->routeIs('containers.*'),
                ],
                [
                    'name' => 'EIR Gate In',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('inspections.eir.list'),
                    'active' => request()->routeIs('inspections.eir.*'),
                ],
                [
                    'name' => 'Pre trip Inspection',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('inspections.pti.list'),
                    'active' => request()->routeIs('inspections.pti.*'),
                ],
                [
                    'name' => 'Leak Test',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('inspections.leak.list'),
                    'active' => request()->routeIs('inspections.leak.*'),
                ],
            ],
        ],
        [
            'header' => 'Gestor de Patio',
            'icon' => 'fa-solid fa-helmet-safety',
        ],
        [
            'name' => 'Operaciones',
            'icon' => 'fa-solid fa-person-digging',
            'active' => request()->routeIs(['workers.*', 'work_orders.*', 'dispatches.index']),
            'submenu' => [
                [
                    'name' => 'Ordenes de Trabajo',
                    'icon' => 'fa-solid fa-file-contract',
                    'route' => route('work_orders.box'),
                    'active' => request()->routeIs('work_orders.*'),
                ],
                [
                    'name' => 'Trabajadores',
                    'icon' => 'fa-solid fa-people-carry-box',
                    'route' => route('workers.index'),
                    'active' => request()->routeIs('workers.*'),
                ],
                [
                    'name' => 'Despachos',
                    'icon' => 'fa-solid fa-soap',
                    'route' => route('dispatches.index'),
                    'active' => request()->routeIs('dispatches.index'),
                ],
            ],
        ],

        [
            'header' => 'Panel de CPE',
            'icon' => 'fa-solid fa-file-invoice',
        ],
        /* [
            'name' => 'Ventas',
            'icon' => 'fa-solid fa-cash-register',
            'active' => request()->routeIs([
                'vouchers.*',
                'clients.*',
            ]),
            "submenu" => [
                [
                    'name' => 'Comprobantes',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('vouchers.index'),
                    'active' => request()->routeIs('vouchers.*'),
                ],
                [
                    'name' => 'Clientes',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('clients.index'),
                    'active' => request()->routeIs('clients.*'),
                ]
            ]
        ], */
        [
            'name' => 'Comprobantes',
            'icon' => 'fa-solid fa-cash-register',
            'route' => route('vouchers.index'),
            'active' => request()->routeIs('vouchers.*'),
        ],
        [
            'name' => 'Anulaciones',
            'icon' => 'fa-solid fa-ban',
            'route' => route('voideds.index'),
            'active' => request()->routeIs('voideds.*'),
        ],
        [
            'name' => 'Guías',
            'icon' => 'fa-solid fa-truck',
            'active' => request()->routeIs('despatchs*'),
            'route' => route('despatchs.index'),
            /* 'submenu' => [
                [
                    'name' => 'Lista de guías',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('despatchs.index'),
                    'active' => request()->routeIs('despatchs.index'),
                ],
                [
                    'name' => 'Nueva guía',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('despatchs.create'),
                    'active' => request()->routeIs('despatchs.create'),
                ],
            ] */
        ],
        [
            'header' => 'Inventario',
            'icon' => 'fa-solid fa-basket-shopping',
        ],
        [
            'name' => 'Productos',
            'icon' => 'fa-solid fa-pallet',
            'route' => route('products.index'),
            'active' => request()->routeIs('products.*'),
        ],
        [
            'header' => 'Maestras',
            'icon' => 'fa-solid fa-cogs',
        ],
        [
            'name' => 'Terceros',
            'icon' => 'fa-solid fa-briefcase',
            'active' => request()->routeIs(['clients.*', 'providers.*', 'brokers.*', 'transports.*', 'transport.*']),
            'submenu' => [
                [
                    'name' => 'Clientes',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('clients.index'),
                    'active' => request()->routeIs('clients.*'),
                ],
                [
                    'name' => 'Proveedores',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('providers.index'),
                    'active' => request()->routeIs('providers.*'),
                ],
                [
                    'name' => 'Agentes de Aduana',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('brokers.index'),
                    'active' => request()->routeIs('brokers.index'),
                ],
                [
                    'name' => 'Transportistas',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('transports.index'),
                    'active' => request()->routeIs(['transports.*', 'transport.*']),
                ],
            ],
        ],
        [
            'name' => 'Códigos Inspección',
            'icon' => 'fa-solid fa-magnifying-glass',
            'active' => request()->routeIs(['damages.*', 'components.*', 'methods.*', 'locations.*']),
            'submenu' => [
                [
                    'name' => 'Daños',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('damages.index'),
                    'active' => request()->routeIs('damages.*'),
                ],
                [
                    'name' => 'Componentes',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('components.index'),
                    'active' => request()->routeIs('components.*'),
                ],
                [
                    'name' => 'Métodos',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('methods.index'),
                    'active' => request()->routeIs('methods.*'),
                ],
                [
                    'name' => 'Ubicaciones',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('locations.index'),
                    'active' => request()->routeIs('locations.*'),
                ],
            ],
        ],
        [
            'name' => 'Admisión',
            'icon' => 'fa-solid fa-ship',
            'active' => request()->routeIs([
                'lines.*',
                'line.*',
                'ports.*',
                'container-types.*',
                'reefer-technologies.*',
            ]),
            'submenu' => [
                [
                    'name' => 'Líneas',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('lines.index'),
                    'active' => request()->routeIs(['lines.*', 'line.*']),
                ],
                [
                    'name' => 'Puertos',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('ports.index'),
                    'active' => request()->routeIs('ports.*'),
                ],
                [
                    'name' => 'Tipos y Tecnologias',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('container-types.index'),
                    'active' => request()->routeIs('container-types.*'),
                ],
            ],
        ],

        [
            'name' => 'Usuarios',
            'icon' => 'fa-solid fa-users-gear',
            'route' => route('users.index'),
            'active' => request()->routeIs('users.*'),
        ],
        [
            'header' => 'Panel de Configuración',
            'icon' => 'fa-solid fa-building',
        ],
        [
            'name' => 'Sucursales',
            'icon' => 'fa-solid fa-store',
            'route' => route('branches.index'),
            'active' => request()->routeIs('branches.*'),
        ],
        [
            'name' => 'Empresa',
            'icon' => 'fas fa-fw fa-building',
            'active' => request()->routeIs([
                'companies.*',
                /* 'branches.*', */
                /* 'users.*', */
            ]),
            'submenu' => [
                [
                    'name' => 'Datos de la empresa',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('companies.edit'),
                    'active' => request()->routeIs('companies.edit'),
                ],
                [
                    'name' => 'API Token',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('companies.api-token'),
                    'active' => request()->routeIs('companies.api-token'),
                ],
                /* [
                    'name' => 'Sucursales',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('branches.index'),
                    'active' => request()->routeIs('branches.*'),
                ], */
                /* [
                    'name' => 'Usuarios',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                ] */
            ],
        ],
    ];
@endphp

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-[100dvh] pt-20 transition-transform bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700 -translate-x-full"
    :class="{
        'transform-none': open,
        '-translate-x-full': !open
    }" aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">
            @foreach ($links as $link)
                @canany($link['can'] ?? [null])
                    <li>
                        @isset($link['header'])
                            <div class="pt-4 border-b border-gray-200 dark:border-gray-700">
                                <div
                                    class="flex items-center gap-1 px-2 py-2 text-[11px] font-semibold tracking-wide uppercase text-gray-500 dark:text-gray-400 dark:bg-gray-800 rounded">
                                    @if (!empty($link['icon']))
                                        <i class="{{ $link['icon'] }} text-gray-500 text-[11px]"></i>
                                    @endif
                                    {{ $link['header'] }}
                                </div>
                            </div>
                        @else
                            @isset($link['submenu'])
                                <div x-data="{ open: {{ $link['active'] ? 'true' : 'false' }} }">

                                    <button type="button" x-on:click="open = !open"
                                        class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg group hover:bg-blue-500 hover:text-white dark:text-white dark:hover:bg-gray-700 {{ $link['active'] ? 'bg-blue-700 text-white' : 'hover:bg-blue-500 hover:text-white' }}"
                                        aria-controls="dropdown-example" data-collapse-toggle="dropdown-example">

                                        <span class="inline-flex w-6 h-6 justify-center items-center">
                                            <i
                                                class="{{ $link['icon'] }} {{ $link['active'] ? 'text-white' : 'text-gray-500 group-hover:text-white' }}"></i>
                                        </span>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap" sidebar-toggle-item>
                                            {{ $link['name'] }}
                                        </span>

                                        <i class="fa-solid fa-angle-down"
                                            :class="{
                                                'fa-angle-down': !open,
                                                'fa-angle-up': open,
                                            }"></i>

                                    </button>

                                    <ul class="hidden py-2 space-y-2"
                                        :class="{
                                            'hidden': !open,
                                        }">
                                        @foreach ($link['submenu'] as $link)
                                            <li>

                                                <a href="{{ $link['route'] }}"
                                                    class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg pl-4 group hover:bg-blue-500 hover:text-white dark:text-white dark:hover:bg-gray-700 {{ $link['active'] ? 'bg-blue-700 text-white' : 'hover:bg-blue-500 hover:text-white' }}">

                                                    @isset($link['icon'])
                                                        <span class="inline-flex w-6 h-6 justify-center items-center mr-2">
                                                            <i
                                                                class="{{ $link['icon'] }} {{ $link['active'] ? 'text-white' : 'text-gray-500 group-hover:text-white' }}"></i>
                                                        </span>
                                                    @endisset

                                                    <span>
                                                        {{ $link['name'] }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                            @else
                                <a href="{{ $link['route'] ?? '#' }}"
                                    class="flex items-center p-2 group text-gray-900 rounded-lg dark:text-white hover:bg-blue-500 hover:text-white {{ $link['active'] ? 'bg-blue-700 text-white' : 'hover:bg-blue-500 hover:text-white' }}">

                                    <span class="inline-flex w-6 h-6 justify-center items-center">
                                        <i
                                            class="{{ $link['icon'] }} {{ $link['active'] ? 'text-white' : 'text-gray-500 group-hover:text-white' }}"></i>
                                    </span>

                                    <span class="ml-3">
                                        {{ $link['name'] }}
                                    </span>
                                </a>
                            @endisset
                        @endisset
                    </li>
                @endcanany
            @endforeach

        </ul>
    </div>
</aside>
