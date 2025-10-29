<?php

namespace App\Livewire\Masters;

use App\Models\ShippingLine;
use Livewire\Component;

class LineCreate extends Component
{

    public $openModal = false;
    public $line = [
        'code' => '',
        'name' => ''
    ];

    public function closeModal()
    {
        $this->reset('opemModal', 'line');
    }

    public function save()
    {
        $this->validate(
            [
                'line.code' => 'required|string|min:2|unique:shipping_lines,code',
                'line.name' => 'required|string|min:3'
            ],
            [],
            [
                'line.code' => 'Código de Linea',
                'line.name' => 'Nombre de Linea'
            ]
        );

        ShippingLine::create($this->line);

        $this->dispatch('lineAdded');
        $this->reset('openModal', 'line');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Linea agregada correctamente',
            'icon' => 'success'
        ]);
    }

    public function render()
    {
        return view('livewire.masters.line-create');
    }
}
