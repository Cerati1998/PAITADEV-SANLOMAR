<?php

namespace App\Livewire;

use App\Models\Driver;
use App\Models\Transport;
use App\Services\SunatService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class DriverCreate extends Component
{
    public $identities;
    public $isExtern = false;
    public $transportId = null;

    public $openModal = false;
    public $driver = [
        'nombres'   => '',
        'apellidos' => '',
        'tipoDoc'   => '',
        'numDoc'    => '',
        'brevete'   => '',
        'direccion' => '',
        'telefono'  => '',
        'transport_id' => null,
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
            'driver.tipoDoc' => 'required|exists:identities,id',
            'driver.numDoc' => [
                Rule::requiredIf($this->driver['tipoDoc'] != '-'),
                Rule::when($this->driver['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::unique('drivers', 'numDoc')->where(function ($query) {
                    return $query->where('tipoDoc', $this->driver['tipoDoc'])
                    ->where('transport_id',$this->transportId)
                        ->where('tipoDoc', '!=', '-');
                }),
            ],
            'driver.nombres' => 'required',
            'driver.apellidos' => 'required',
            'driver.brevete' => 'required|string|size:9',
            'driver.direccion' => 'nullable',
            'driver.telefono' => 'nullable',
        ], [], [
            'driver.tipoDoc' => 'Tipo de Documento',
            'driver.numDoc' => 'Número de Documento',
            'driver.nombres' => 'Razón Social',
            'driver.apellidos' => 'Dirección',
            'driver.brevete' => 'Brevete de Conductor',
            'driver.direccion' => 'Dirección de Conductor',
            'driver.telefono' => 'Telefono de Conductor',
        ]);

        $this->driver['brevete'] = strtoupper($this->driver['brevete']);
        $this->driver['transport_id'] = $this->transportId;

        Driver::create($this->driver);

        $this->reset('driver', 'openModal', 'isExtern');
        $this->dispatch('driverAdded');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Conductor agregado correctamente',
            'icon' => 'success'
        ]);
    }

    public function searchDocument()
    {
        $this->validate([
            'driver.tipoDoc' => 'required|in:1,6',
            'driver.numDoc' => [
                Rule::when($this->driver['tipoDoc'] == 1, 'numeric|digits:8'),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->driver['tipoDoc'];
        $numero  = (string) $this->driver['numDoc'];

        try {
            $response = match ($docType) {
                '1' => $sunat->consultarDNI($numero),
                default => ['success' => false, 'message' => 'Tipo de documento no válido']
            };

            if (!($response['success'] ?? false)) {
                throw new \Exception($response['message'] ?? 'No se encontró información');
            }

            if ($docType === '1') {
                $this->driver['nombres'] = $response['data']['first_name'] ?? null;
                $this->driver['apellidos'] = $response['data']['first_last_name'] . ' ' . $response['data']['second_last_name'] ?? null;
                $this->driver['direccion'] = '-';
                $this->driver['brevete'] = $response['data']['numeroDocumento'] ?? '-';
            }
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text'  => $e->getMessage(),
            ]);
        }
    }


    public function mount($transport)
    {
        if ($transport) {
            $this->transportId = $transport->id;
        }
    }

    public function render()
    {
        return view('livewire.driver-create');
    }
}
