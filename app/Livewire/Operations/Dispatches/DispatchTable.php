<?php

namespace App\Livewire\Operations\Dispatches;

use App\Models\Client;
use App\Models\ContainerOperationalTrace;
use App\Models\Vessel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Dispatches\Dispatch;
use App\Services\Container\ContainerOperationalTraceService;
use App\Services\Container\ContainerStatusService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DispatchTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['dispatches.edit']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('dispatches.actions', ['dispatch' => $row]);
                }),
            Column::make("Fecha Wash", "wash_date")
                ->sortable(),
            Column::make("Fecha Out", "dispatch_date")
                ->sortable(),
            Column::make("Reserva BK", "reserva_booking")
                ->sortable(),
            Column::make("Oxalic", "oxalic_wash")
                ->format(function ($value) {
                    return $value ? 'Lavado con Oxalico' : 'Sin Oxalico';
                }),
            Column::make("Eir", "eir_gate_out")
                ->sortable(),
            Column::make("Nave", "vessel.name")
                ->sortable(),
            Column::make("Contenedor")
                ->label(function ($row) {
                    return $row->trace?->container?->code ?? '-';
                })
                ->sortable(),
            Column::make("Shipper", "shipper.rznSocial")
                ->sortable(),
            Column::make("Lavador", "washer.name")
                ->sortable(),
            Column::make("Despachador", "dispatcher.name")
                ->sortable(),
        ];
    }

    #[On('dispatchAdded')]
    public function builder(): Builder
    {
        return Dispatch::query()
            ->with([
                'trace.container:id,code',
                'shipper:id',
                'vessel:id',
                'washer:id,name',
                'dispatcher:id,name',
            ])
            ->addSelect(['dispatches.*'])
            ->whereHas('trace', function ($traceQuery) {
                $traceQuery->whereBetween('status', [2, 5]);
            })
            ->where('dispatches.branch_id', session('branch')->id);
    }

    #[Computed]
    public function shippers()
    {
        return Client::all()->map(function ($client) {
            return [
                'id' => (int) $client->id,
                'name' => $client->rznSocial,
                'description' => $client->numDoc,
            ];
        });
    }
    #[Computed()]
    public function vessels()
    {
        return Vessel::with('shippingLine')->get()->map(function ($vessel) {
            return [
                'id' => (int) $vessel->id,
                'name' => $vessel->name,
                'description' => $vessel->shippingLine->name,
            ];
        });
    }

    public $dispatchData = [
        'wash_date' => '',
        'dispatch_date' => null,
        'reserva_booking' => '',
        'oxalic_wash' => false,
        'eir_gate_out' => '',
        'vessel_id' => null,
        'trace_id' => null,
        'dispatcher_id' => null,
        'shipper_id' => null,
        'branch_id' => null,
    ];

    public $containerData = [
        'machine' => '',
        'technology' => '',
        'year' => '',
    ];

    public $dispatchId;
    public $openModal = false;

    public function edit(Dispatch $dispatch)
    {
        try {
            $this->dispatchData = $dispatch->only([
                'wash_date',
                'dispatch_date',
                'reserva_booking',
                'oxalic_wash',
                'eir_gate_out',
                'vessel_id',
                'trace_id',
                'dispatcher_id',
                'shipper_id',
            ]);

            $trace = ContainerOperationalTrace::with([
                'container.reefer_technology',
                'container.machine'
            ])->find($this->dispatchData['trace_id']);

            if (!$trace) {
                throw new Exception('No se encontró el trace seleccionado');
            }

            // Acceder al contenedor a través del trace
            $container = $trace?->container;

            if (!$container) {
                throw new Exception('No se encontró el contenedor asociado al trace');
            }

            // Asignar los datos del contenedor
            $this->containerData['trace_container_id'] = $container->code ?? 'N/A';
            $this->containerData['technology'] = $container->reefer_technology?->name ?? 'N/A';
            $this->containerData['machine'] = $container->machine?->code ?? 'N/A';
            $this->containerData['year'] = $container->build_year ?? 'N/A';

            $this->dispatchData['wash_date'] = Carbon::createFromFormat('d/m/Y H:i', $this->dispatchData['wash_date'])->format('Y-m-d\TH:i');
            $this->dispatchData['dispatch_date'] =
                (!empty($this->dispatchData['dispatch_date']) && preg_match('/\d{2}\/\d{2}\/\d{4}/', $this->dispatchData['dispatch_date']))
                ? Carbon::createFromFormat('d/m/Y H:i', $this->dispatchData['dispatch_date'])->format('Y-m-d\TH:i')
                : null;

            $this->dispatchId = (int) $dispatch->id;
            $this->openModal = true;
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Exito!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function save()
    {
        if (!auth()->user()->can('dispatches.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'dispatchData.wash_date' => 'required|date',
            'dispatchData.dispatch_date' => 'nullable|date',
            'dispatchData.reserva_booking' => 'nullable|string|max:50',
            'dispatchData.eir_gate_out' => 'nullable|string|max:50',
            'dispatchData.vessel_id' => 'nullable|exists:vessels,id',
            'dispatchData.dispatcher_id' => 'nullable|exists:users,id',
            'dispatchData.shipper_id' => 'nullable|exists:clients,id',
        ], [], [
            'dispatchData.wash_date' => 'Fecha de Lavado',
            'dispatchData.dispatch_date' => 'Fecha de Despacho',
            'dispatchData.reserva_booking' => 'N° de Reserva Booking',
            'dispatchData.eir_gate_out' => 'EIR gate out',
            'dispatchData.vessel_id' => 'Nave',
            'dispatchData.dispatcher_id' => 'Supervisor de Despacho',
            'dispatchData.shipper_id' => 'Nombre de Embarcador/Shipper',
        ]);

        $message = 'Lavado';

        DB::transaction(function () use ($message) {
            // Creo el despacho
            Dispatch::findOrFail($this->dispatchId)->update($this->dispatchData);

            // Si hay fecha de despacho => actualizar trace como DESPACHADO
            if (!empty($this->dispatchData['dispatch_date'])) {
                $traceService = app(ContainerOperationalTraceService::class);

                $traceService->updateStatus(
                    (int) $this->dispatchData['trace_id'],
                    'OP', // puedes ajustar si lo deseas
                    ContainerStatusService::TRACE_DESPACHADO,
                    auth()->id()
                );

                $message .= ' y Despacho';
            }
        });

        // Limpiar estado del componente
        $this->reset('openModal', 'dispatchData', 'containerData', 'dispatchId');
        $this->dispatch('swal', [
            'title' => 'Éxito!',
            'text' => "{$message} actualizado correctamente",
            'icon' => 'success'
        ]);
    }
}
