<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Component;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;


class ComponentTable extends DataTableComponent
{
    protected $model = Component::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => ['codes.components.modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('codes.components.actions', ['componente' => $row]);
                }),
            Column::make("Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Descripción", "description")
                ->searchable()
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('componentAdded')]
    public function builder(): Builder
    {
        return Component::query();
    }

    public $openModal = false;
    public $component_id;

    public $component = [
        'code' => '',
        'description' => '',
    ];

    public function edit(Component $component)
    {
        $this->component_id = $component->id;
        $this->component = $component->only(['code', 'description']);
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('openModal', 'component_id', 'component');
    }

    public function save()
    {
        $this->validate(
            [
                'component.code' => [
                    'required',
                    'string',
                    'min:3',
                    Rule::unique('components', 'code')->ignore($this->component_id),
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
            if (!Component::find($this->component_id)->update($this->component)) {
                throw new Exception('Error al actualizar el componente. Verifique los datos ingresados.');
            }

            $this->dispatch('swal', [
                'title' => 'Éxito',
                'text' => 'Componente actualizado correctamente.',
                'icon' => 'success',
            ]);

            $this->reset('openModal', 'component_id', 'component');
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error',
            ]);
        }
    }
    public function destroy(Component $component)
    {
        $component->delete();
    }
}
