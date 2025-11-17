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

</x-dashboard-layout>
