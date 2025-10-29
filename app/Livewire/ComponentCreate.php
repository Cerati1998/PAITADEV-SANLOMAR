<?php

namespace App\Livewire;

use App\Models\Component as ModelsComponent;
use Exception;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ComponentCreate extends Component
{

    public $openModal = false;
    public $component = [
        'code' => '',
        'description' => '',
    ];

    public function save()
    {
        $this->validate(
            [
                'component.code' => [
                    'required',
                    'string',
                    'min:3',
                    Rule::unique('components', 'code')
                ],
                'component.description' => 'required|string|min:5',
            ],
            [],
            [
                'component.code' => 'Código de Componente',
                'component.description' => 'Descripción del Componente',
            ]
        );

        try {
            if (!$component = ModelsComponent::create($this->component)) {
                throw new Exception('No se pudo crear el componente');
            }

            $this->reset('openModal', 'component');
            $this->dispatch('componentAdded', $component->id);
            $this->dispatch('swal', [
                'title' => 'Éxito',
                'text' => 'Componente creado correctamente.',
                'icon' => 'success',
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error',
            ]);
        }
    }
    public function render()
    {
        return view('livewire.component-create');
    }
}
