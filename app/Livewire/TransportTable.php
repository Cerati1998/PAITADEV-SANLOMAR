<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Transport;
use App\Services\SunatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class TransportTable extends DataTableComponent
{
    protected $model = Transport::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => ['transports.transport']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('transports.actions', ['transport' => $row]);
                }),
            Column::make("razón Social", "razonSocial")
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

            Column::make("Direccion", "direccion")
                ->sortable(),
            Column::make("Email", "email")
                ->sortable(),
            Column::make("Phone", "phone")
                ->sortable(),
            Column::make("Observations", "observations")
                ->sortable(),
        ];
    }

    #[On('transportAdded')]
    public function builder(): Builder
    {
        return Transport::query()
            ->where('branch_id', session('branch')->id);
    }

    public $openModal = false;

    public $transportId;
    public $identities;
    public $transport = [
        'razonSocial' => '',
        'tipoDoc' => '-',
        'numDoc' => '',
        'direccion' => '',
        'email' => '',
        'phone' => '',
        'observations' => '',
    ];

    public function edit(Transport $transport)
    {
        $this->transport = $transport->only(['razonSocial', 'tipoDoc', 'numDoc', 'direccion', 'email', 'phone', 'observations']);
        $this->transportId = $transport->id;
        $this->openModal = true;
    }

    public function save()
    {
        $this->validate([
            'transport.tipoDoc' => 'required|exists:identities,id',
            'transport.numDoc' => [
                Rule::requiredIf($this->transport['tipoDoc'] != '-'),
                Rule::when($this->transport['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->transport['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('transports', 'numDoc')->where(function ($query) {
                    return $query->where('branch_id', session('branch')->id)
                        ->where('tipoDoc', $this->transport['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                })->ignore($this->transportId),
            ],
            'transport.razonSocial' => 'required',
            'transport.direccion' => Rule::requiredIf($this->transport['tipoDoc'] == 6),
            'transport.phone' => 'nullable',
            'transport.email' => 'nullable',
            'transport.observations' => 'nullable'
        ],[], [
            'transport.tipoDoc' => 'Tipo de Documento',
            'transport.numDoc' => 'Número de Documento',
            'transport.razonSocial' => 'Razón Social',
            'transport.direccion' => 'Dirección',
            'transport.phone' => 'Telefono',
            'transport.email' => 'Correo electronico',
            'transport.observations' => 'Observaciones'
        ]);
        
        Transport::find($this->transportId)->update($this->transport);

        $this->reset('transport', 'openModal', 'transportId');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Transportista actualizado correctamente',
            'icon' => 'success'
        ]);
    }
    public function searchDocument()
    {
        $this->validate([
            'transport.tipoDoc' => 'required|in:1,6',
            'transport.numDoc' => [
                Rule::when($this->transport['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->transport['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->transport['tipoDoc'];
        $numero  = (string) $this->transport['numDoc'];

        try {
            $response = match ($docType) {
                '6' => $sunat->consultarRUC($numero),
                '1' => $sunat->consultarDNI($numero),
                default => ['success' => false, 'message' => 'Tipo de documento no válido']
            };

            if (!($response['success'] ?? false)) {
                throw new \Exception($response['message'] ?? 'No se encontró información');
            }

            if ($docType === '6') {
                $this->transport['razonSocial'] = $response['data']['razon_social'] ?? null;
                $this->transport['direccion'] = $response['data']['direccion'] ?? null;
            } elseif ($docType === '1') {
                $this->transport['razonSocial'] = $response['data']['nombre'] ?? null;
                $this->transport['direccion'] = '-';
            }
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text'  => $e->getMessage(),
            ]);
        }
    }

    public function destroy(Transport $transport)
    {
        $transport->delete();
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Transportista eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
