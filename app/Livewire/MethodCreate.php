<?php

namespace App\Livewire;

use App\Models\Method;
use Livewire\Component;

class MethodCreate extends Component
{
    public $openModal = false;
    public $method = [
        'code' => '',
        'description' => '',
    ];


    public function save()
    {
        $this->validate(
            [
                'method.code' => 'required|string|min:3|unique:methods,code',
                'method.description' => 'required|string|min:5',
            ],
            [],
            [
                'method.code' => 'Código de Método',
                'method.description' => 'Descripción del Método',
            ]
        );

        try {
            Method::create($this->method);
            $this->reset('openModal', 'method');
            $this->dispatch('methodAdded');

            $this->dispatch('swal', [
                'title' => 'Éxito',
                'text' => 'Método creado correctamente.',
                'icon' => 'success',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.method-create');
    }
}
