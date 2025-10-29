<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class VehicleTable extends DataTableComponent
{
    protected $model = Vehicle::class;

    public $transport;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['transports.vehicle']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('transports.vehicle-actions', ['vehicle' => $row]);
                }),
            Column::make("Placa", "code")
                ->searchable()
                ->sortable(),
            Column::make("Marca", "brand")
                ->searchable()
                ->sortable(),
            Column::make("Modelo", "model")
                ->searchable()
                ->sortable(),
            Column::make("Categoria", "category")
                ->searchable()
                ->sortable(),
            Column::make("Tipo", "type")
                ->searchable()
                ->sortable(),
            Column::make("Color", "color")
                ->sortable(),
            Column::make("Transportista", "transport.razonSocial")
                ->sortable(),
        ];
    }

    #[On('vehicleAdded')]
    public function builder(): Builder
    {
        return Vehicle::with(['transport'])
            ->where('transport_id', $this->transport->id);
    }

    public $openModal = false;
    public $vehicleId = null;
    public $categories = [
        "N1",
        "N2",
        "N3",
        "O1",
        "O2",
        "O3",
        "O4",
        "OTRO"
    ];
    public $types = [
        "Remolcador",
        "Plataforma 40'",
        "Plataforma 20'",
        "Containera 40'",
        "Containera 20'",
        "Cama baja",
        "Cabina Litera",
        "Otro"
    ];
    public $vehicle =  [
        'code' => '',
        'brand' => '',
        'model' => '',
        'category' => '',
        'type' => '',
        'color' => '',
        'transport_id' => '',
    ];

    public function edit(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle->only(['code', 'brand', 'model', 'category', 'type', 'color', 'transport_id']);
        $this->vehicleId = $vehicle->id;
        $this->openModal = true;
    }

    public function save()
    {
        $this->validate([
            'vehicle.code' => [
                'required',
                'string',
                'min:5',
                Rule::unique('vehicles', 'code')->where(function ($query) {
                    return $query->where('transport_id', $this->transport->id);
                })->ignore($this->vehicleId),
            ],
            'vehicle.brand' => 'nullable|string',
            'vehicle.model' => 'nullable|string',
            'vehicle.category' => 'required|string',
            'vehicle.type' => 'required|string',
            'vehicle.color' => 'required|string|min:4',
        ], [], [
            'vehicle.code' => 'Placa de Vehiculo',
            'vehicle.brand' => 'Marca de Vehiculo',
            'vehicle.model' => 'Modelo de Vehiculo',
            'vehicle.category' => 'Categoria de Vehiculo',
            'vehicle.type' => 'Tipo de Vehiculo',
            'vehicle.color' => 'Color de Carroceria',
        ]);

        $this->vehicle['transport_id'] = $this->transport->id;

        Vehicle::find($this->vehicleId)->update($this->vehicle);
        $this->reset('vehicle', 'openModal');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Vehiculo actualizado correctamente',
            'icon' => 'success'
        ]);
    }

    public function searchPlaca()
    {
        $this->dispatch('swal', [
            'title' => 'Ups!',
            'text' => 'Servicio de busqueda por Placa SUNARP no disponible. Ingrese la placa manualmente',
            'icon' => 'error'
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Vehiculo eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
