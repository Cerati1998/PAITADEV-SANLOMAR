<?php

namespace App\Livewire\Masters;

use App\Models\Country;
use App\Models\Port;
use Livewire\Component;

class PortCreate extends Component
{

    
    public $countryOptions = [];
    public $openModal = false;
    public $port = [
        'code' => '',
        'name' => '',
        'country_code' => '',
        'location' => ''
    ];

    public function closeModal()
    {
        $this->reset('openModal', 'port');
    }

    public function save()
    {
        $this->validate([
            'port.code' => 'required|string|min:5|max:6|unique:ports,code',
            'port.name' => 'required|string|min:4',
            'port.country_code' => 'required|string|min:2|max:4'
        ], [], [
            'port.code' => 'Código de Puerto',
            'port.name' => 'Nombre de Puerto',
            'port.country_code' => 'Código de País'
        ]);

        Port::create($this->port);

        $this->reset('openModal', 'port');
        $this->dispatch('portAdded');

        $this->dispatch('swal', [
            'title' => "Exito!",
            'text' => 'Puerto agregado correctamente',
            'icon' => 'success'
        ]);
    }

    public function mount()
    {
        $this->countryOptions = Country::all()->toArray();
    }
    
    public function render()
    {
        return view('livewire.masters.port-create');
    }
}
