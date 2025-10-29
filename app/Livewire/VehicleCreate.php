<?php

namespace App\Livewire;

use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Livewire\Component;

class VehicleCreate extends Component
{

    public $openModal = false;
    public $transport;
    public $isExtern = false;
    public $transportId = null;

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

    protected $listeners = ['setTransportId'];

    public function setTransportId($transportId = null)
    {
        $this->transportId = $transportId;
        $this->isExtern = true;
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
                }),
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

        Vehicle::create($this->vehicle);
        $this->reset('vehicle', 'openModal', 'isExtern');
        $this->dispatch('vehicleAdded');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Vehiculo agregado correctamente',
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
    public function render()
    {
        return view('livewire.vehicle-create');
    }
}
