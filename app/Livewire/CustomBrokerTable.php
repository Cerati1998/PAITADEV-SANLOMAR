<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\CustomBroker;
use App\Services\SunatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class CustomBrokerTable extends DataTableComponent
{
    protected $model = CustomBroker::class;

     public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => ['brokers.modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),

            Column::make("Tipo Doc", "identity.description")
                ->sortable(),

            Column::make("Num. Doc.", "numDoc")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return $value ? $value : 'S/N';
                }),

            Column::make("Razon Social", "rznSocial")
                ->searchable()
                ->sortable(),
            Column::make("Direccion", "direccion")
            ->format(function($value){
                return $value ? $value : 'S/N';
            }),
            Column::make('actions')
                ->label(function ($row) {
                    return view('brokers.actions', ['broker' => $row]);
                }),

        ];
    }

    #[On('brokerAdded')]
    public function builder(): Builder
    {
        return CustomBroker::query();
    }

    //Configuracion adicional
    public $identities;

    public $openModal = false;
    public $broker_id;

    public $broker = [
        'tipoDoc' => '-',
        'numDoc' => null,
        'rznSocial' => null,
        'direccion' => null,
        'email' => null,
        'telephone' => null,
    ];

    public function searchDocument()
    {
        $this->validate([
            'broker.tipoDoc' => 'required|in:1,6',
            'broker.numDoc' => [
                Rule::when($this->broker['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->broker['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->broker['tipoDoc'];
        $numero  = (string) $this->broker['numDoc'];

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
                $this->broker['rznSocial'] = $response['data']['razon_social'] ?? null;
                $this->broker['direccion'] = $response['data']['direccion'] ?? null;
            } elseif ($docType === '1') {
                $this->broker['rznSocial'] = $response['data']['nombre'] ?? null;
                $this->broker['direccion'] = '-';
            }
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text'  => $e->getMessage(),
            ]);
        }
    }

    public function edit(CustomBroker $broker)
    {
        $this->broker_id = $broker->id;
        $this->openModal = true;

        $this->broker = [
            'tipoDoc' => $broker->tipoDoc,
            'numDoc' => $broker->numDoc,
            'rznSocial' => $broker->rznSocial,
            'direccion' => $broker->direccion,
            'email' => $broker->email,
            'telephone' => $broker->telephone,
        ];
    }

    public function save()
    {
        if (!auth()->user()->can('brokers.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'broker.tipoDoc' => 'required|exists:identities,id',
            'broker.numDoc' => [
                Rule::requiredIf($this->broker['tipoDoc'] != '-'),
                Rule::when($this->broker['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->broker['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('custom_brokers', 'numDoc')->where(function ($query) {
                    return $query->where('tipoDoc', $this->broker['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                })->ignore($this->broker_id),
            ],
            'broker.rznSocial' => 'required',
            'broker.direccion' => Rule::requiredIf($this->broker['tipoDoc'] == 6),
            'broker.telephone' => 'nullable',
            'broker.email' => 'nullable',
        ]);

        CustomBroker::find($this->broker_id)->update($this->broker);

        $this->reset('broker', 'broker_id', 'openModal');
    }

    public function destroy(CustomBroker $broker)
    {
        if (!auth()->user()->can('brokers.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $broker->delete();
        $this->dispatch('swal',[
            'title' => 'Exito!',
            'text' => 'Agencia eliminada con Exito',
            'icon' => 'success'
        ]);
    }
}
