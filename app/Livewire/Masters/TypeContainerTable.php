<?php

namespace App\Livewire\Masters;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ContainerType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class TypeContainerTable extends DataTableComponent
{
    protected $model = ContainerType::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.technologies.type-container']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('masters.technologies.container-actions', ['type' => $row]);
                }),
            Column::make("Código", "code")
                ->searchable()
                ->sortable(),
            Column::make("ISO", "iso_code")
                ->searchable()
                ->sortable(),
            Column::make("Descripción", "description")
                ->searchable()
                ->sortable(),
            Column::make("Largo", "length")
                ->sortable(),
            Column::make("Ancho", "width")
                ->sortable(),
            Column::make("Alto", "height")
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('typeContainerAdded')]
    public function builder(): Builder
    {
        return ContainerType::query();
    }

    public $openModal = false;
    public $containerTypeId;
    public $containerType = [
        'code' => '',
        'iso_code' => '',
        'description' => '',
        'is_reefer' => false,
        'length' => 0,
        'width' => 0,
        'height' => 0
    ];

    public function edit(ContainerType $containerType)
    {
        $this->containerTypeId = $containerType->id;
        $this->containerType = $containerType->only('code','iso_code', 'description', 'is_reefer','length', 'width', 'height');
        $this->openModal = true;
    }

    public function save()
    {
        $this->validate([
            'containerType.code' => [
                'required',
                'string',
                'min:2',
                'max:6',
                Rule::unique('container_types', 'code')->ignore($this->containerTypeId)
            ],
            'containerType.iso_code' => [
                'required',
                'string',
                'min:2',
                'max:4',
                Rule::unique('container_types', 'iso_code')->ignore($this->containerTypeId)
            ],
            'containerType.description' => 'required|string|min:4',
            'containerType.length' => 'numeric',
            'containerType.width' => 'numeric',
            'containerType.height' => 'numeric'
        ], [], [
            'containerType.code' => 'Código de Tipo de Contenedor',
            'containerType.iso_code' => 'Código ISO de Contenedor',
            'containerType.description' => 'Descripción de Tipo de Contenedor',
             'containerType.length' => 'Longitud de Contenedor',
            'containerType.width' => 'Ancho de Contenedor',
            'containerType.height' => 'Altura de Contenedor'
        ]);

        ContainerType::find($this->containerTypeId)->update($this->containerType);

        $this->reset('openModal', 'containerTypeId', 'containerType');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tipo de Contenedor actualizado correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroyType(ContainerType $containerType)
    {
        $containerType->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tipo de Contenedor eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
