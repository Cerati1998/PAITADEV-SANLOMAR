<?php

namespace App\Livewire\Masters;

use App\Models\ReeferMachine;
use Livewire\Component;

class MachineCreate extends Component
{
    public $openModal = false;
     public $machine = [
        'code' => '',
        'name' => ''
    ];

     public function save()
    {
        $this->validate([
            'machine.code' => [
                'required',
                'string',
                'min:2',
                 'unique:reefer_machines,code'
            ],
            'machine.name' => 'required|string|min:4'
        ], [], [
            'machine.code' => 'Código de Tecnologia Reefer',
            'machine.name' => 'Nombre de Tecnologia Reefer'
        ]);

        ReeferMachine::create($this->machine);

        $this->reset('openModal', 'machine');
        $this->dispatch('machineAdded');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tecnología Reefer creada correctamente',
            'icon' => 'success'
        ]);
    }
    public function render()
    {
        return view('livewire.masters.machine-create');
    }
}
