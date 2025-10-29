<?php

namespace App\Livewire\Masters;

use App\Models\ContainerLineCategory;
use Livewire\Component;

class LineCategoryContainerCreate extends Component
{
    public $openModal = false;
    public $shippingLineId;
    public $lineCategory = [
        'code' => '',
        'description' => '',
        'shipping_line_id' =>  null
    ];

    public function render()
    {
        return view('livewire.masters.line-category-container-create');
    }

    public function mount($shipping_line = null)
    {

        if ($shipping_line) {
            $this->shippingLineId = $shipping_line?->id;
        }
    }

    public function save()
    {
        if (!$this->shippingLineId || $this->shippingLineId === '') {
            $this->dispatch('swal', [
                'title' => "Error!",
                'text' => 'No se ha podido encontrar la Linea, asegurese de escoger una',
                'icon' => 'error'
            ]);
            return;
        }

        $this->validate([
            'lineCategory.code' => 'required|string|max:5',
            'lineCategory.description' => 'nullable|string|max:150',
        ], [], [
            'lineCategory.code' => 'Código de Categoria',
            'lineCategory.description' => 'Descripción de Categoria',
        ]);



        ContainerLineCategory::create([
            ...$this->lineCategory,
            'shipping_line_id' => $this->shippingLineId
        ]);
        $this->reset('openModal', 'lineCategory');
        $this->dispatch('containerCategoryAdded');

        $this->dispatch('swal', [
            'title' => "Exito!",
            'text' => 'Categoria agregada correctamente',
            'icon' => 'success'
        ]);
    }
}
