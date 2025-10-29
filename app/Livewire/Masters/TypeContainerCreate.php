<?php

namespace App\Livewire\Masters;

use App\Models\ContainerType;
use Livewire\Component;

class TypeContainerCreate extends Component
{

    public $openModal = false;

    public  $containerType = [
        'code' => '',
        'iso_code' => '',
        'description' => '',
        'is_reefer' => false,
        'length' => 0,
        'width' => 0,
        'height' => 0
    ];

    public function save()
    {
        $this->validate([
            'containerType.code' => [
                'required',
                'string',
                'min:2',
                'max:6',
                'unique:container_types,code'
            ],
            'containerType.iso_code' => [
                'required',
                'string',
                'min:2',
                'max:4',
                'unique:container_types,iso_code'
            ],
            'containerType.description' => 'required|string|min:4',
            'containerType.length' => 'numeric',
            'containerType.width' => 'numeric',
            'containerType.height' => 'numeric'
        ], [], [
            'containerType.iso_code' => 'Código de Tipo de Contenedor',
            'containerType.description' => 'Descripción de Tipo de Contenedor',
            'containerType.length' => 'Longitud de Contenedor',
            'containerType.width' => 'Ancho de Contenedor',
            'containerType.height' => 'Altura de Contenedor'
        ]);

        ContainerType::create($this->containerType);

        $this->reset('openModal', 'containerType');
        $this->dispatch('typeContainerAdded');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tipo de Contenedor creado correctamente',
            'icon' => 'success'
        ]);
    }

    public function render()
    {
        return view('livewire.masters.type-container-create');
    }
}
