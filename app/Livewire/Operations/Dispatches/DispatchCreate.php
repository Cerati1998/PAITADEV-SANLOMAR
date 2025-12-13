<?php

namespace App\Livewire\Operations\Dispatches;

use App\Models\Client;
use App\Models\ContainerOperationalTrace;
use App\Models\Dispatches\Dispatch;
use App\Models\User;
use App\Models\Vessel;
use App\Models\works\Worker;
use App\Services\Container\ContainerOperationalTraceService;
use App\Services\Container\ContainerStatusService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DispatchCreate extends Component
{
    #[Computed]
    public function shippers()
    {
        return Client::all()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->rznSocial,
                'description' => $client->numDoc,
            ];
        });
    }

    #[Computed]
    public function users()
    {
        return User::whereHas('branches', function ($query) {
            $query->where('branches.id', session('branch')->id)
                ->where('branch_company_user.company_id', session('company')->id);
        })
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Super');
            })
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'description' => $user->numDoc,
            ]);
    }


    #[Computed]
    public function vessels()
    {
        return Vessel::with('shippingLine')->get()->map(function ($vessel) {
            return [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'description' => $vessel->shippingLine->name,
            ];
        });
    }
    #[Computed]
    public function traces()
    {
        $traces = ContainerOperationalTrace::with([
            'gateInDetail.originable.branch',
            'container.container_type'
        ])
            ->whereHas('gateInDetail', function ($query) {
                $query->whereHasMorph('originable', [
                    \App\Models\Dischargue::class,
                    \App\Models\Devolution::class
                ], function ($morphQuery) {
                    $morphQuery->where('branch_id', session('branch')->id);
                });
            })
            ->whereHas('container.container_type', function ($typeQuery) {
                $typeQuery->whereIn('iso_code', ['45R1', '22R1']);
            })
            ->whereBetween('container_operational_traces.status', [3, 4])
            ->orderBy('id', 'desc')
            ->get();

        return $traces->map(function ($trace) {
            return [
                'id' => $trace->id,
                'name' => $trace->container->code ?? 'N/A',
                'description' => strtoupper($trace->nave ?? '') . ' - ' . strtoupper($trace->linea ?? ''),
            ];
        })->values()->toArray();
    }

    public $dispatchData = [
        'wash_date' => '',
        'dispatch_date' => null,
        'reserva_booking' => '',
        'oxalic_wash' => false,
        'eir_gate_out' => '',
        'vessel_id' => null,
        'trace_id' => null,
        'washer_id' => '',
        'dispatcher_id' => null,
        'shipper_id' => null,
        'branch_id' => null,
    ];

    public $containerData = [
        'machine' => '',
        'technology' => '',
        'year' => '',
    ];

    public $openModal = false;

    public function mount()
    {
        $this->dispatchData['washer_id'] = auth()->id();
        $this->dispatchData['branch_id'] = session('branch')->id;
        $this->dispatchData['wash_date'] = Carbon::now()->format('Y-m-d H:i');
    }

    public function updatedDispatchDataTraceId($value)
    {
        if (!$value) {
            $this->reset('containerData');
            return;
        }
        try {

            //  Verificar que no tenga un leak test existente
            $dispatchExists = Dispatch::where('trace_id', $value)->exists();

            if ($dispatchExists) {
                throw new Exception('El contenedor seleccionado ya cuenta con Lavado o Despacho, escoge otro');
            }

            //  buscar el trace
            $trace = ContainerOperationalTrace::with([
                'container.reefer_technology',
                'container.machine'
            ])->find($value);

            if (!$trace) {
                throw new Exception('No se encontró el trace seleccionado');
            }


            // Acceder al contenedor a través del trace
            $container = $trace?->container;

            if (!$container) {
                throw new Exception('No se encontró el contenedor asociado al trace');
            }

            // Asignar los datos del contenedor
            $this->containerData['technology'] = $container->reefer_technology?->name ?? 'N/A';
            $this->containerData['machine'] = $container->machine?->code ?? 'N/A';
            $this->containerData['year'] = $container->build_year ?? 'N/A';
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.operations.dispatches.dispatch-create');
    }

    public function save()
    {
        if (!auth()->user()->can('dispatches.create')) {
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
            'dispatchData.trace_id' => 'nullable|exists:container_operational_traces,id',
            'dispatchData.washer_id' => 'required|exists:users,id',
            'dispatchData.dispatcher_id' => 'required|exists:users,id',
            'dispatchData.shipper_id' => 'nullable|exists:clients,id',
        ], [], [
            'dispatchData.wash_date' => 'Fecha de Lavado',
            'dispatchData.dispatch_date' => 'Fecha de Despacho',
            'dispatchData.reserva_booking' => 'N° de Reserva Booking',
            'dispatchData.eir_gate_out' => 'EIR gate out',
            'dispatchData.vessel_id' => 'Nave',
            'dispatchData.trace_id' => 'Contenedor',
            'dispatchData.washer_id' => 'Supervisor de Lavado',
            'dispatchData.dispatcher_id' => 'Supervisor de Despacho',
            'dispatchData.shipper_id' => 'Nombre de Embarcador/Shipper',
        ]);

        $message = 'Lavado';

        DB::transaction(function () use ($message) {
            // Creo el despacho
            //$this->dispatchData['dispatcher_id'] = $this->dispatchData['dispatch_date'] ? auth()->user()->id : null;
            $dispatch = Dispatch::create($this->dispatchData);

            // Si hay fecha de despacho => actualizar trace como DESPACHADO
            if (!empty($this->dispatchData['dispatch_date'])) {
                $traceService = app(ContainerOperationalTraceService::class);

                $traceService->updateStatus(
                    (int) $this->dispatchData['trace_id'],
                    'OP', // puedes ajustar si lo deseas
                    null,
                    ContainerStatusService::TRACE_DESPACHADO,
                    auth()->id()
                );

                $message .= ' y Despacho';
            }
        });

        // Limpiar estado del componente
        $this->reset('openModal', 'dispatchData', 'containerData');
        $this->dispatchData['washer_id'] = auth()->id();
        $this->dispatchData['branch_id'] = session('branch')->id;
        $this->dispatch('dispatchAdded');
        $this->dispatch('swal', [
            'title' => 'Éxito!',
            'text' => "{$message} registrado correctamente",
            'icon' => 'success'
        ]);
    }
}
