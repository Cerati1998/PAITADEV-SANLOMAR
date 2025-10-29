<div>
    <div>
        <x-wire-card>
    
            <form wire:submit="save">
    
                <div class="grid lg:grid-cols-3 gap-6 mb-4">
    
                    <x-wire-input placeholder="Ingrese el código de la sucursal" label="Código Establec. Anexo SUNAT:"
                        wire:model="code" />
    
                    <div class="lg:col-span-2">
    
                        <x-wire-input placeholder="Ingrese el nombre de la sucursal" label="Nombre de Sucursal/Almacén"
                            wire:model="name" />
    
                    </div>
    
                    <div class="lg:col-span-2">
    
                        <x-wire-input placeholder="Ingrese la dirección de la sucursal" label="Dirección"
                            wire:model="address" />
    
                    </div>
    
                    <x-wire-select label="Buscar su ubigeo" placeholder="Seleccione su ubigeo"
                        wire:model="ubigeo" :async-data="[
                            'api' => route('api.ubigeos.index'),
                            'method' => 'POST',
                        ]" option-label="name" option-value="id" />
    
                    <x-wire-input label="Teléfono" placeholder="Ingrese el teléfono de la sucursal"
                        wire:model="phone" />
    
                    <x-wire-input label="Correo Electrónico" placeholder="Ingrese el email de la sucursal"
                        wire:model="email" />
    
                    <x-wire-input label="Página Web" placeholder="Ingrese el sitio web de la sucursal"
                        wire:model="website" />
    
    
                </div>
    
                <div class="flex justify-end">
    
                    <x-wire-button type="submit" dark>
                        Actualizar
                    </x-wire-button>
                </div>
    
            </form>
    
        </x-wire-card>
    </div>
</div>
