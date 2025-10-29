<?php

namespace App\Livewire\Masters;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ReeferTechnology;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class TechnologyTable extends DataTableComponent
{
    protected $model = ReeferTechnology::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.technologies.technology']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('accciones')
                ->label(function ($row) {
                    return view('masters.technologies.technology-actions', ['technology' => $row]);
                }),
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Uso", "usage"),
            Column::make("Creado", "created_at")
                ->sortable(),
            Column::make("Actualizado", "updated_at")
                ->sortable(),
        ];
    }

    #[On('technologyAdded')]
    public function builder(): Builder
    {
        return Reefertechnology::query();
    }

    public $technologyId;
    public $openModal = false;
    public $technology = [
        'name' => '',
        'temperature_min' => '',
        'temperature_max' => '',
        'humidity_min' => '',
        'humidity_max' => '',
        'ventilation_min' => '',
        'ventilation_max' => '',
        'atmosphere_o2_min' => '',
        'atmosphere_o2_max' => '',
        'atmosphere_co2_min' => '',
        'atmosphere_co2_max' => '',
        'usage' => '',
    ];

    public function edit(Reefertechnology $reefertechnology)
    {
        $this->technologyId = $reefertechnology->id;
        $this->technology = $reefertechnology->only([
            'name',
            'temperature_min',
            'temperature_max',
            'humidity_min',
            'humidity_max',
            'ventilation_min',
            'ventilation_max',
            'atmosphere_o2_min',
            'atmosphere_o2_max',
            'atmosphere_co2_min',
            'atmosphere_co2_max',
            'usage',
        ]);
        $this->openModal = true;
    }


    public function save()
    {
        $this->validate([
            'technology.name' => [
                'required',
                'string',
                'min:3',
                Rule::unique('reefer_technologies', 'name')->ignore($this->technologyId)
            ],
            'technology.usage' => 'string|min:4',
            'technology.temperature_min' => 'nullable|numeric|min:-100|max:100',
            'technology.temperature_max' => 'nullable|numeric|min:-100|max:100',
            'technology.humidity_min' => 'nullable|numeric|min:0|max:100',
            'technology.humidity_max' => 'nullable|numeric|min:0|max:100',
            'technology.ventilation_min' => 'nullable|numeric|min:-100|max:100',
            'technology.ventilation_max' => 'nullable|numeric|min:-100|max:100',
            'technology.atmosphere_o2_min' => 'nullable|numeric|min:0|max:100',
            'technology.atmosphere_o2_max' => 'nullable|numeric|min:0|max:100',
            'technology.atmosphere_co2_min' => 'nullable|numeric|min:0|max:100',
            'technology.atmosphere_co2_max' => 'nullable|numeric|min:0|max:100',
        ], [], [
            'technology.name' => 'Nombre de tecnología',
            'technology.usage' => 'Descripción de Uso o aplicación de tecnología',
            'technology.temperature_min' => 'Valor minimo de Temperatura',
            'technology.temperature_max' => 'Valor maximo de Temperatura',
            'technology.humidity_min' => 'Valor minimo de Humedad',
            'technology.humidity_max' => 'Valor maximo de Humedad',
            'technology.ventilation_min' => 'Valor minimo de Ventilación',
            'technology.ventilation_max' => 'Valor maximo de Ventilación',
            'technology.atmosphere_o2_min' => 'Valor minimo de o2',
            'technology.atmosphere_o2_max' => 'Valor maximo de o2',
            'technology.atmosphere_co2_min' => 'Valor minimo de co2',
            'technology.atmosphere_co2_max' => 'Valor maximo de co2',
        ]);

        Reefertechnology::find($this->technologyId)->update($this->technology);

        $this->reset('technologyId', 'technology', 'openModal');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tecnología de Contenedor actualizada correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroyTechnology(Reefertechnology $reefertechnology)
    {
        $reefertechnology->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tecnología de Contenedor Eliminada correctamente',
            'icon' => 'success'
        ]);
    }
}
