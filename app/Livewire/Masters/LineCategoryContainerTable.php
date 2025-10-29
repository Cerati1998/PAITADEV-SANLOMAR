<?php

namespace App\Livewire\Masters;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ContainerLineCategory;
use Illuminate\Database\Eloquent\Builder;

class LineCategoryContainerTable extends DataTableComponent
{
    protected $model = ContainerLineCategory::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.lines.container-category']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('masters.lines.container-categories-actions', ['containerCategory' => $row]);
                }),
            Column::make("Código", "code")
                ->sortable(),
            Column::make("Descripción", "description")
                ->sortable(),
            Column::make("Linea", "shippingLine.name")
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('containerCategoryAdded')]
    public function builder(): Builder
    {
        return ContainerLineCategory::with(['shippingLine']);
    }

    public $openModal = false;
    public $lineCategory = [
        'id' => '',
        'code' => '',
        'description' => '',
    ];

    public function edit(ContainerLineCategory $containerLineCategory)
    {
        $this->lineCategory = $containerLineCategory->only(['id', 'code', 'description']);
        $this->openModal = true;
    }

    public function update()
    {

        $this->validate([
            'lineCategory.code' => 'required|string|max:5',
            'lineCategory.description' => 'nullable|string|max:150',
        ], [], [
            'lineCategory.code' => 'Código de Categoria',
            'lineCategory.description' => 'Descripción de Categoria',
        ]);



        ContainerLineCategory::find($this->lineCategory['id'])->update(
            $this->lineCategory
        );
        $this->reset('openModal', 'lineCategory');

        $this->dispatch('swal', [
            'title' => "Exito!",
            'text' => 'Categoria actualizada correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(ContainerLineCategory $containerLineCategory)
    {
        $containerLineCategory->delete();
        
        $this->dispatch('swal', [
            'title' => "Exito!",
            'text' => 'Categoria eliminada correctamente',
            'icon' => 'success'
        ]);
    }
}
