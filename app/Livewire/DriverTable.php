<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Driver;
use App\Services\SunatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class DriverTable extends DataTableComponent
{
    protected $model = Driver::class;
    public $identities;
    public $transport;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['transports.driver']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('transports.driver-actions', ['driver' => $row]);
                }),
            Column::make("Nombres", "nombres")
                ->searchable()
                ->sortable(),
            Column::make("Apellidos", "apellidos")
                ->searchable()
                ->sortable(),
            Column::make("Tipo Doc", "identity.description")
                ->sortable(),
            Column::make("Documento", "numDoc")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return $value ? $value : 'S/N';
                }),
            Column::make("Brevete", "brevete")
                ->sortable(),
            Column::make("Telefono", "telefono")
                ->format(function ($value) {
                    return $value ?? '-';
                })
                ->sortable(),
            Column::make("Transportista", "transport.razonSocial")
                ->sortable(),
        ];
    }

    #[On('driverAdded')]
    public function builder(): Builder
    {
        return Driver::with(['transport'])
            ->where('transport_id', $this->transport->id);
    }

    public $openModal = false;
    public $driverId;
    public $driver = [
        'nombres'   => '',
        'apellidos' => '',
        'tipoDoc'   => '',
        'numDoc'    => '',
        'brevete'   => '',
        'direccion' => '',
        'telefono'  => '',
    ];

    public function edit(Driver $driver)
    {
        $this->driver = $driver->only(
            'nombres',
            'apellidos',
            'tipoDoc',
            'numDoc',
            'brevete',
            'direccion',
            'telefono'
        );
        $this->driverId = $driver->id;
        $this->openModal = true;
    }


    public function save()
    {
        if (!auth()->user()->can('transport.drivers.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'driver.tipoDoc' => 'required|exists:identities,id',
            'driver.numDoc' => [
                Rule::requiredIf($this->driver['tipoDoc'] != '-'),
                Rule::when($this->driver['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::unique('drivers', 'numDoc')->where(function ($query) {
                    return $query->where('tipoDoc', $this->driver['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                })->ignore($this->driverId),
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

        Driver::find($this->driverId)->update($this->driver);

        $this->reset('driver', 'openModal', 'driverId');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Conductor actualizado correctamente',
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
                $this->driver['nombres'] = $response['data']['nombres'] ?? null;
                $this->driver['apellidos'] = $response['data']['apellidoPaterno'] . ' ' . $response['data']['apellidoMaterno'] ?? null;
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

    public function destroy(Driver $driver)
    {
        if (!auth()->user()->can('transport.drivers.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $driver->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Conductor eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
