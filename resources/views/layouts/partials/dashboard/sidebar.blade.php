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
            'can' => [
                'discharges.index',
                'devolution.index',
                'inspections.eir.list',
                'inspections.leak.list',
                'containers.traces',
            ],
        ],
        [
            'name' => 'Descargas',
            'icon' => 'fa-solid fa-arrow-up-from-water-pump',
            'route' => route('discharges.index'),
            'active' => request()->routeIs(['discharges.*', 'discharge.*']),
            'can' => 'discharges.index',
        ],
        [
            'name' => 'Devoluciones',
            'icon' => 'fa-solid fa-arrow-down-short-wide',
            'route' => route('devolutions.index'),
            'active' => request()->routeIs('devolutions.*'),
            'can' => 'devolution.index',
        ],
        [
            'name' => 'Contenedores',
            'icon' => 'fa-solid fa-boxes-stacked',
            'active' => request()->routeIs(['containers.*', 'inspections.*']),
            'can' => ['inspections.eir.list', 'containers.traces', 'inspections.leak.list'],
            'submenu' => [
                [
                    'name' => 'Lista',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('containers.traces'),
                    'active' => request()->routeIs('containers.*'),
                    'can' => 'containers.traces',
                ],
                [
                    'name' => 'EIR Gate In',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('inspections.eir.list'),
                    'active' => request()->routeIs('inspections.eir.*'),
                    'can' => 'inspections.eir.list',
                ],
                /*  [
                    'name' => 'Pre trip Inspection',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('inspections.pti.list'),
                    'active' => request()->routeIs('inspections.pti.*'),
                    'can' => 'inspections.eir.list',
                ], */
                [
                    'name' => 'Leak Test',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('inspections.leak.list'),
                    'active' => request()->routeIs('inspections.leak.*'),
                    'can' => 'inspections.leak.list',
                ],
            ],
        ],
        [
            'header' => 'Gestor de Patio',
            'icon' => 'fa-solid fa-helmet-safety',
            'can' => ['manage_work.index', 'workers.index', 'dispatches.index'],
        ],
        [
            'name' => 'Operaciones',
            'icon' => 'fa-solid fa-person-digging',
            'active' => request()->routeIs(['workers.*', 'work_orders.*', 'dispatches.index']),
            'can' => ['manage_work.index', 'workers.index', 'dispatches.index'],

            'submenu' => [
                [
                    'name' => 'Ordenes de Trabajo',
                    'icon' => 'fa-solid fa-file-contract',
                    'route' => route('work_orders.box'),
                    'active' => request()->routeIs('work_orders.*'),
                    'can' => 'manage_work.index',
                ],
                [
                    'name' => 'Trabajadores',
                    'icon' => 'fa-solid fa-people-carry-box',
                    'route' => route('workers.index'),
                    'active' => request()->routeIs('workers.*'),
                    'can' => 'workers.index',
                ],
                [
                    'name' => 'Despachos',
                    'icon' => 'fa-solid fa-soap',
                    'route' => route('dispatches.index'),
                    'active' => request()->routeIs('dispatches.index'),
                    'can' => 'dispatches.index',
                ],
            ],
        ],

        [
            'header' => 'Panel de CPE',
            'icon' => 'fa-solid fa-file-invoice',
            'can' => ['vouchers.index', 'voideds', 'despatches.index'],
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
            'can' => 'vouchers.index',
        ],
        [
            'name' => 'Anulaciones',
            'icon' => 'fa-solid fa-ban',
            'route' => route('voideds.index'),
            'active' => request()->routeIs('voideds.*'),
            'can' => 'voideds',
        ],
        [
            'name' => 'Guías',
            'icon' => 'fa-solid fa-truck',
            'active' => request()->routeIs(['despatchs*', 'despatch-transportistas*']),
            'can' => ['despatches.index', 'despatch-transportistas.index'],

            'submenu' => [
                [
                    'name' => 'Guía Remisión',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('despatchs.index'),
                    'active' => request()->routeIs('despatchs*'),
                    'can' => 'despatches.index',
                ],
                [
                    'name' => 'Guía Transportista',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('despatch-transportistas.index'),
                    'active' => request()->routeIs('despatch-transportistas*'),
                    'can' => 'despatch-transportistas.index',
                ],
            ],
        ],
        [
            'header' => 'Gestor de Costos',
            'icon' => 'fa-solid fa-helmet-safety',
            'can' => ['cashes.index', 'bills.index'],
        ],

        [
            'name' => 'Gestionar Cajas',
            'icon' => 'fa-solid fa-vault',
            'route' => route('cashes.index'),
            'active' => request()->routeIs('cashes.*'),
            'can' => 'cashes.index',
        ],
        [
            'name' => 'Compras',
            'icon' => 'fa-solid fa-hand-holding-dollar',
            'active' => request()->routeIs(['bills.*']),
            'can' => ['bills.index'],

            'submenu' => [
                [
                    'name' => 'Gastos',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('bills.index'),
                    'active' => request()->routeIs('bills.*'),
                    'can' => 'bills.index',
                ],
            ],
        ],

        [
            'header' => 'Inventario',
            'icon' => 'fa-solid fa-basket-shopping',
            'can' => ['products'],
        ],
        [
            'name' => 'Productos',
            'icon' => 'fa-solid fa-pallet',
            'route' => route('products.index'),
            'active' => request()->routeIs('products.*'),
            'can' => 'products',
        ],
        [
            'header' => 'Maestras',
            'icon' => 'fa-solid fa-cogs',
            'can' => ['clients', 'providers', 'brokers', 'transports'],
        ],
        [
            'name' => 'Terceros',
            'icon' => 'fa-solid fa-briefcase',
            'active' => request()->routeIs(['clients.*', 'providers.*', 'brokers.*', 'transports.*', 'transport.*']),
            'can' => ['clients', 'providers', 'brokers', 'transports'],

            'submenu' => [
                [
                    'name' => 'Clientes',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('clients.index'),
                    'active' => request()->routeIs('clients.*'),
                    'can' => 'clients',
                ],
                [
                    'name' => 'Proveedores',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('providers.index'),
                    'active' => request()->routeIs('providers.*'),
                    'can' => 'providers',
                ],
                [
                    'name' => 'Agentes de Aduana',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('brokers.index'),
                    'active' => request()->routeIs('brokers.index'),
                    'can' => 'brokers',
                ],
                [
                    'name' => 'Transportistas',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('transports.index'),
                    'active' => request()->routeIs(['transports.*', 'transport.*']),
                    'can' => 'transports',
                ],
            ],
        ],
        [
            'name' => 'Códigos Inspección',
            'icon' => 'fa-solid fa-magnifying-glass',
            'active' => request()->routeIs(['damages.*', 'components.*', 'methods.*', 'locations.*']),
            'can' => ['damages', 'components', 'methods', 'locations'],

            'submenu' => [
                [
                    'name' => 'Daños',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('damages.index'),
                    'active' => request()->routeIs('damages.*'),
                    'can' => 'damages',
                ],
                [
                    'name' => 'Componentes',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('components.index'),
                    'active' => request()->routeIs('components.*'),
                    'can' => 'components',
                ],
                [
                    'name' => 'Métodos',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('methods.index'),
                    'active' => request()->routeIs('methods.*'),
                    'can' => 'methods',
                ],
                [
                    'name' => 'Ubicaciones',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('locations.index'),
                    'active' => request()->routeIs('locations.*'),
                    'can' => 'locations',
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
            'can' => ['lines', 'ports', 'container-types.index'],

            'submenu' => [
                [
                    'name' => 'Líneas',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('lines.index'),
                    'active' => request()->routeIs(['lines.*', 'line.*']),
                    'can' => 'lines',
                ],
                [
                    'name' => 'Puertos',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('ports.index'),
                    'active' => request()->routeIs('ports.*'),
                    'can' => 'ports',
                ],
                [
                    'name' => 'Tipos y Tecnologias',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('container-types.index'),
                    'active' => request()->routeIs('container-types.*'),
                    'can' => 'container-types.index',
                ],
            ],
        ],

        [
            'name' => 'Usuarios',
            'icon' => 'fa-solid fa-users-gear',
            'route' => route('users.index'),
            'active' => request()->routeIs('users.*'),
            'can' => 'users',
        ],
        [
            'name' => 'Roles y Permisos',
            'icon' => 'fa-solid fa-user-shield',
            'route' => route('roles.index'),
            'active' => request()->routeIs('roles.*'),
            'can' => 'roles',
        ],
        [
            'name' => 'Reportes',
            'icon' => 'fa-solid fa-file-export',
            'route' => route('reports.index'),
            'active' => request()->routeIs('reports.index'),
            'can' => 'reports',
        ],
        [
            'header' => 'Panel de Configuración',
            'icon' => 'fa-solid fa-building',
            'can' => ['branches', 'companies'],
        ],
        [
            'name' => 'Sucursales',
            'icon' => 'fa-solid fa-store',
            'route' => route('branches.index'),
            'active' => request()->routeIs('branches.*'),
            'can' => 'branches',
        ],
        [
            'name' => 'Empresa',
            'icon' => 'fas fa-fw fa-building',
            'active' => request()->routeIs([
                'companies.*',
                /* 'branches.*', */
                /* 'users.*', */
            ]),
            'can' => 'companies',

            'submenu' => [
                [
                    'name' => 'Datos de la empresa',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('companies.edit'),
                    'active' => request()->routeIs('companies.edit'),
                    'can' => 'companies.edit',
                ],

                [
                    'name' => 'API Token',
                    'icon' => 'fa-regular fa-circle',
                    'route' => route('companies.api-token'),
                    'active' => request()->routeIs('companies.api-token'),
                    'can' => 'companies.api-token',
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
                @php
                    $canShow = true;
                    if (isset($link['can'])) {
                        $canShow = is_array($link['can'])
                            ? auth()->user()->hasAnyPermission($link['can'])
                            : auth()->user()->can($link['can']);
                    }
                @endphp

                @if ($canShow)
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
                                        @foreach ($link['submenu'] as $sublink)
                                            @php
                                                $canShowSub = true;
                                                if (isset($sublink['can'])) {
                                                    $canShowSub = is_array($sublink['can'])
                                                        ? auth()->user()->hasAnyPermission($sublink['can'])
                                                        : auth()->user()->can($sublink['can']);
                                                }
                                            @endphp

                                            @if ($canShowSub)
                                                <li>
                                                    <a href="{{ $sublink['route'] }}"
                                                        class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg pl-4 group hover:bg-blue-500 hover:text-white dark:text-white dark:hover:bg-gray-700 {{ $sublink['active'] ? 'bg-blue-700 text-white' : 'hover:bg-blue-500 hover:text-white' }}">

                                                        @isset($sublink['icon'])
                                                            <span class="inline-flex w-6 h-6 justify-center items-center mr-2">
                                                                <i
                                                                    class="{{ $sublink['icon'] }} {{ $sublink['active'] ? 'text-white' : 'text-gray-500 group-hover:text-white' }}"></i>
                                                            </span>
                                                        @endisset

                                                        <span>
                                                            {{ $sublink['name'] }}
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
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
                @endif
            @endforeach
        </ul>
    </div>
</aside>
