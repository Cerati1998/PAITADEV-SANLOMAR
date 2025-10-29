<?php

namespace App\Livewire;

use App\Models\Location;
use Exception;
use Livewire\Component;

class LocationCreate extends Component
{

    public $openModal = false;
    public $location = [
        'code' => '',
        'description' => ''
    ];

    public function save()
    {
        $this->validate([
            'location.code' => 'required|string|min:4|unique:locations,code',
            'location.description' => 'required|string|min:5'
        ], [], [
            'location.code' => 'Código de Ubicación',
            'location.description' => 'Descripción de Ubicación'
        ]);

        try {
            Location::create($this->location);

            $this->reset('openModal', 'location');
            $this->dispatch('locationAdded');
            $this->dispatch('swal', [
                'title' => 'Éxito',
                'text' => 'Ubicación creada correctamente',
                'icon' => 'success'
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }
    public function render()
    {
        return view('livewire.location-create');
    }
}
