<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Location;
use Exception;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class LocationTable extends DataTableComponent
{
    protected $model = Location::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['codes.locations.modal']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('codes.locations.actions', ['location' => $row]);
                }),
            Column::make("Código", "code")
                ->searchable()
                ->sortable(),
            Column::make("Descripción", "description")
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),

        ];
    }

    #[On('locationAdded')]
    public function builder(): Builder
    {
        return Location::query();
    }

    public $location_id;
    public $openModal = false;
    public $location = [
        'code' => '',
        'description' => ''
    ];

    public function edit(Location $location)
    {
        $this->location_id = $location->id;
        $this->location = $location->only('code', 'description');
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('location_id', 'openModal', 'location');
    }

    public function save()
    {
        $this->validate(
            [
                'location.code' => [
                    'required',
                    'string',
                    'min:4',
                    Rule::unique('locations', 'code')->ignore($this->location_id)
                ],
                'location.description' => 'required|min:5|string'
            ],
            [],
            [
                'location.code' => 'Código de Ubicación',
                'location.description' => 'Descripción de Ubicación'
            ]
        );

        try {
            if (!Location::find($this->location_id)->update($this->location)) {
                throw new Exception("No se ha podido actualizar la Ubicación. Verifique que exista");
            }

            $this->reset('location_id', 'openModal', 'location');
            $this->dispatch('swal', [
                'title' => 'Exito',
                'text' => 'Ubicación actualizada correctamente',
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

    public function destroy(Location $location)
    {
        try {
            if (!$location->delete()) {
                throw new Exception("Ocurrió un problema al intentar eliminar la ubicación");
            }

            $this->dispatch('swal', [
                'title' => 'Exito',
                'text' => 'Ubicación eliminada correctamente',
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
}
