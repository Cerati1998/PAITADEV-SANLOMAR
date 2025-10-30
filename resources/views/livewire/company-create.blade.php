<div x-data="{
    active: false,
}">
    <x-container class="py-8">

        <a href="{{route('dashboard')}}">
            <img class="h-8" src="{{asset('img/logos/paitadev.png')}}" alt="Coders Free">
        </a>

        <div class="max-w-2xl mx-auto mt-6">

            <form wire:submit="save">

                <h1 class="text-center text-3xl uppercase text-blue-500 mb-6">
                    Registrar <br/> nueva empresa
                </h1>

                <x-validation-errors class="mb-4" />

                <div class="bg-white rounded-lg shadow-lg p-8">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-wire-input label="RUC" wire:model="ruc" placeholder="Ingrese su RUC">
                                <x-slot name="append">
                                    <x-wire-mini-button
                                        wire:click="search"
                                        class="h-full"
                                        rounded="rounded-r-md"
                                        icon="magnifying-glass"
                                        spinner="search"
                                        primary
                                    />
                                </x-slot>
                            </x-wire-input>
                        </div>

                        <div>
                            <x-wire-input 
                                label="Razón social"
                                wire:model="razonSocial" />
                        </div>

                        <div>
                            <x-wire-input 
                                label="Nombre comercial"
                                wire:model="nombreComercial" />
                        </div>

                        <div>
                            <x-wire-select label="Buscar su ubigeo" 
                                placeholder="Seleccione su ubigeo"
                                wire:model="ubigeo"
                                :async-data="[
                                    'api' => route('api.ubigeos.index'),
                                    'method' => 'POST',
                                ]" option-label="name" option-value="id" />
                        </div>

                        <div class="col-span-2">
                            <x-wire-input 
                                label="Dirección"
                                wire:model="direccion" />
                        </div>
                    </div>

                    <label class="flex my-4">
                        <div class="mt-1">
                            <x-wire-checkbox 
                            x-model="active" />
                        </div>

                        <span class="ml-2 text-sm">
                            Declara bajo juramento que la información proporcionada es veraz y se compromete a cumplir con las normas y políticas de la empresa.
                        </span>
                    </label>

                    <div class="flex justify-center">
                        <x-wire-button
                            x-bind:disabled="!active"
                            type="submit"
                            blue
                            class="mt-6"
                            label="Registrar empresa" />
                    </div>

                </div>
            </form>

        </div>

    </x-container>
</div>
