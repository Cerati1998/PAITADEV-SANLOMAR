<x-dashboard-layout title="Dashboard | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
    ],
]">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-16">

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
    </div>

</x-dashboard-layout>
