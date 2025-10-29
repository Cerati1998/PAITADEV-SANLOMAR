<x-dashboard-layout title="Sucursales | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'url' => route('dashboard'),
    ],
    [
        'name' => 'Sucursales',
    ],
]">

    <x-wire-card>

        <form action="{{route('branches.store')}}" method="POST">

            @csrf

            <x-validation-errors class="mb-4" />

            <div class="grid lg:grid-cols-3 gap-6 mb-4">

                <x-wire-maskable 
                    label="Código Establec. Anexo SUNAT:"
                    mask="####"
                    placeholder="Ingrese el código de la sucursal"
                    name="code"
                    value="{{old('code')}}" />

                <div class="lg:col-span-2">

                    <x-wire-input
                        label="Nombre de Sucursal/Almacén" 
                        placeholder="Ingrese el nombre de la sucursal"
                        name="name"
                        value="{{old('name')}}" />

                </div>

                <div class="lg:col-span-2">

                    <x-wire-input
                        label="Dirección" 
                        placeholder="Ingrese la dirección de la sucursal"
                        name="address"
                        value="{{old('address')}}" />

                </div>

                <x-wire-select 
                    label="Buscar ubigeo"
                    placeholder="Seleccione un ubigeo"
                    name="ubigeo"
                    value="{{old('ubigeo')}}" 
                    :async-data="[
                        'api' => route('api.ubigeos.index'),
                        'method' => 'POST',
                    ]"
                    option-label="name" 
                    option-value="id" />


                <x-wire-input
                    label="Teléfono"
                    placeholder="Ingrese el teléfono de la sucursal"
                    name="phone"
                    value="{{old('phone')}}" />

                <x-wire-input
                    label="Correo Electrónico"
                    placeholder="Ingrese el email de la sucursal"
                    name="email"
                    value="{{old('email')}}" />

                <x-wire-input 
                    label="Página Web" 
                    placeholder="Ingrese el sitio web de la sucursal"
                    name="website"
                    value="{{old('website')}}" />


            </div>

            <div class="flex justify-end">

                <x-wire-button type="submit" dark wire:target="save">
                    Agregar Sucursal
                </x-wire-button>
            </div>

        </form>

    </x-wire-card>

</x-dashboard-layout>