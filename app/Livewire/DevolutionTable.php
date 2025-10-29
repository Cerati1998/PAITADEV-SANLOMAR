<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ContainerOperationalTrace;
use App\Models\CustomBroker;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Devolution;
use App\Models\ShippingLine;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class DevolutionTable extends DataTableComponent
{
    protected $model = Devolution::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['devolutions.devolution']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('devolutions.actions', ['devolution' => $row]);
                }),
            Column::make("Fecha Retorno", "returned_date")
                ->sortable(),
            Column::make("Cliente", "client.rznSocial")
                ->searchable(),
            Column::make("Agente", "broker.rznSocial")
                ->searchable(),
            Column::make("BL", "bl_number")
                ->searchable(),
            Column::make("Linea", "shippingLine.name")
                ->searchable(),
            Column::make("Nave", "vessel.name")
                ->searchable(),
            Column::make("Creado Por", "creator.name")
                ->sortable()
        ];
    }

    #[On('devolutionAdded')]
    public function builder(): Builder
    {
        return Devolution::with(['client', 'shippingLine', 'vessel', 'creator', 'branch', 'broker'])
            ->where('devolutions.branch_id', session('branch')->id);
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Fecha Return')
                ->config([
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => 'Introduzca el rango de fechas', // A placeholder value
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder
                        ->whereDate('returned_at', '>=', $dateRange['minDate'])
                        ->whereDate('returned_at', '<=', $dateRange['maxDate']);
                }),
            SelectFilter::make('Cliente')
                ->options(
                    ['' => 'Todos'] + Client::query()
                        ->whereHas('devolutions', function ($query) {
                            $query->where('devolutions.branch_id', session('branch')->id);
                        })
                        ->pluck('rznSocial', 'id')
                        ->toArray()
                )
                ->filter(function ($query, $value) {
                    if ($value) {
                        $query->whereHas('client', function ($q) use ($value) {
                            $q->where('id', $value);
                        });
                    }
                }),
            SelectFilter::make('Agente')
                ->options(
                    ['' => 'Todos'] + CustomBroker::query()
                        ->whereHas('devolutions', function ($query) {
                            $query->where('devolutions.branch_id', session('branch')->id);
                        })
                        ->pluck('rznSocial', 'id')
                        ->toArray()
                )
                ->filter(function ($query, $value) {
                    if ($value) {
                        $query->whereHas('broker', function ($q) use ($value) {
                            $q->where('id', $value);
                        });
                    }
                }),
            SelectFilter::make('Linea')
                ->options(
                    ['' => 'Todas'] + ShippingLine::query()
                        ->whereHas('devolutions', function ($query) {
                            $query->where('devolutions.branch_id', session('branch')->id);
                        })
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->filter(function ($query, $value) {
                    if ($value) {
                        $query->whereHas('shippingLine', function ($q) use ($value) {
                            $q->where('id', $value);
                        });
                    }
                })
        ];
    }

    public $devolutionId;

    public $vessels = [];


    public $openModal = false;
    public $selectedDevolutionId = null;
    public $openModalAnulate = false;
    public $anulateReason = '';

    public $devolution = [
        'shipping_line_id' => '',
        'vessel_id' => '',
        'client_id' => '',
        'custom_broker_id' => '',
        'bl_number' => '',
        'memo_number' => '',
        'returned_date' => '',
        'week' => '',
        'voyage' => '',
        'regimen' => ''
    ];

    public function setSelectedDevolutionId($id)
    {
        $this->selectedDevolutionId = $id;
        $this->openModalAnulate = true;
    }

    public function edit(Devolution $devolution)
    {
        $this->devolutionId = $devolution->id;
        $this->devolution = $devolution->only([
            'shipping_line_id',
            'vessel_id',
            'client_id',
            'custom_broker_id',
            'bl_number',
            'returned_date',
            'memo_number',
            'week',
            'voyage',
            'regimen'
        ]);

        $this->devolution['returned_date'] = Carbon::createFromFormat('d/m/Y', $this->devolution['returned_date'])->format('Y-m-d');
        $this->vessels = Vessel::where('shipping_line_id', $this->devolution['shipping_line_id'])
            ->get()
            ->toArray();

        $this->openModal = true;
    }

    #[On('vesselExternAdded')]
    public function updatedDevolutionShippingLineId($value)
    {
        // Resetear el vessel seleccionado
        $this->devolution['vessel'] = '';

        // Cargar las naves de la nueva línea naviera
        if (!empty($value)) {
            $this->vessels = Vessel::where('shipping_line_id', $value)
                ->get()
                ->toArray();
        } else {
            $this->vessels = [];
        }
    }

    public function save()
    {

        $this->validate([
            'devolution.shipping_line_id' => 'required|numeric|exists:shipping_lines,id',
            'devolution.vessel_id' => 'required|numeric|exists:vessels,id',
            'devolution.client_id' => 'required|numeric|exists:clients,id',
            'devolution.custom_broker_id' => 'required|numeric|exists:custom_brokers,id',
            'devolution.returned_date' => 'required|date',
            'devolution.bl_number' => 'nullable|string|min:5',
            'devolution.regimen' => 'required|string|min:6|in:importacion,exportacion',
        ], [], [
            'devolution.shipping_line_id' => 'Linea Naviera',
            'devolution.vessel_id' => 'Nave',
            'devolution.client_id' => 'Importador/Cliente',
            'devolution.custom_broker_id' => 'Agente de Aduana',
            'devolution.returned_date' => 'Fecha ETA',
            'devolution.bl_number' => 'Número de BL',
        ]);


        $devolution = Devolution::find($this->devolutionId);
        $devolution->update($this->devolution);


        $this->reset('devolutionId', 'devolution', 'openModal');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Devolución actualizada con Exito!',
            'icon' => 'success'
        ]);
    }

    public function destroy()
    {
        //valido que tengo el id
        if (!$this->selectedDevolutionId) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se ha seleccionado ninguna descarga.',
                'icon' => 'error'
            ]);
            return;
        }

        if ($this->anulateReason === '' || strlen($this->anulateReason) < 5) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'El motivo de anulación es requerido y debe tener al menos 5 caracteres',
                'icon' => 'error'
            ]);
            return;
        }

        $devolution = Devolution::find($this->selectedDevolutionId);

        //primero verifico que ningun contenedor ingresado se encuentre con status != ANUNCIADO
        $containersIn = ContainerOperationalTrace::query()
            ->whereHas(
                'gateInDetail.originable',
                fn($query) =>
                $query->where('originable_type', Devolution::class)
                    ->where('originable_id', $devolution->id)
            )
            ->where('status', '>', 1)
            ->count();

        if ($containersIn > 0) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se puede anular la Descarga, hay contenedores de esta que ya estan en patio',
                'icon' => 'error'
            ]);
            $this->reset('selectedDevolutionId', 'openModalAnulate', 'anulateReason');
            return;
        }

        //actualizo a estado 0 todos los contenedores
        ContainerOperationalTrace::query()
            ->whereHas('gateInDetail.originable', fn($query) =>
            $query->where('originable_id', $devolution->id)
                ->where('originable_type', "App\Models\devolution"))->update([
                'status' => 0
            ]);

        //actualizo la descarga añadiendo razon de anulacion y anulated by
        $devolution->anulated_reason = $this->anulateReason;
        $devolution->anulated_by = auth()->user()->id;
        $devolution->save();
        $devolution->delete();

        $this->reset('selectedDevolutionId', 'openModalAnulate', 'anulateReason');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Anuncio de Descarga anulado',
            'icon' => 'success'
        ]);
    }
}
