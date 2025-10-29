<?php

namespace App\Livewire\Operations\Inspections;

use App\Models\Container;
use App\Models\ContainerOperationalTrace;
use App\Models\inspections\LeakTest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LeakTestCreate extends Component
{
    public $tipoDoc = '16';
    public $openModal = false;
    public $branch_id;
    public $series;

    #[Computed]
    public function traces()
    {
        $traces = ContainerOperationalTrace::with([
            'gateInDetail.originable.branch',
            'container.container_type'
        ])
            ->whereHas('gateInDetail.originable.branch', function ($query) {
                $query->where('branch_id', $this->branch_id);
            })
            ->whereHas('container.container_type', function ($typeQuery) {
                $typeQuery->whereIn('iso_code', ['45R1', '22R1']);
            })
            ->whereBetween('container_operational_traces.status', [1, 4])
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

    public $leaktest = [
        'serie' => '',
        'correlativo' => 0,
        'started_at' => null,
        'finished_at' => null,
        'hours' => null,
        'status' => 'pending',
        'branch_id' => null,
        'trace_id' => null,
        'inspector_id' => null,
    ];

    public $containerData = [
        'machine' => '',
        'technology' => '',
        'year' => '',
    ];

    /**
     * Obtiene la serie y correlativo del branch actual
     */
    public function getSeries()
    {
        // Asignar valor al branch
        $this->branch_id = session('branch')->id;

        $this->series = DB::table('branch_company_document')
            ->select('serie as name', 'correlativo')
            ->where('branch_id', $this->branch_id)
            ->where('document_id', $this->tipoDoc)
            ->get();

        $serieData = $this->series->first();

        if ($serieData) {
            $this->leaktest['serie'] = $serieData->name;
            $this->leaktest['correlativo'] = $serieData->correlativo;
        }

        // Obtener el último correlativo usado
        $lastCorrelativo = leaktest::where('serie', $this->leaktest['serie'])
            ->where('branch_id', $this->branch_id)
            ->max('correlativo');

        if ($lastCorrelativo) {
            $this->leaktest['correlativo'] = $lastCorrelativo + 1;
        }
    }

    public function updatedLeaktestTraceId($value)
    {
        if (!$value) {
            $this->reset('containerData');
            return;
        }
        try {

            //  Verificar que no tenga un leak test existente
            $leakTestExists = LeakTest::where('trace_id', $value)->exists();

            if ($leakTestExists) {
                throw new Exception('El contenedor seleccionado ya cuenta con Leak Test, escoge otro');
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

            //  Asignar las horas de trabajo por tecnología/máquina
            $machineCode = strtolower($this->containerData['machine']);

            $this->leaktest['hours'] = match ($machineCode) {
                'daikin' => 3,
                'starcool' => 12,
                default => 5,
            };
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
        return view('livewire.operations.inspections.leak-test-create');
    }

    public function mount()
    {
        $this->getSeries();
        $this->leaktest['inspector_id'] = auth()->id();
        $this->leaktest['branch_id'] = $this->branch_id;
    }

    public function save()
    {
        DB::transaction(function () {
            $this->validate([
                'leaktest.serie' => 'required|string|max:4',
                'leaktest.correlativo' => 'numeric|required',
                'leaktest.started_at' => 'date|required',
                'leaktest.finished_at' => 'required|date',
                'leaktest.hours' => 'required|numeric|max:24',
                'leaktest.status' => 'required|in:ok,fail',
                'leaktest.branch_id' => 'required|exists:branches,id',
                'leaktest.trace_id' => [
                    'required',
                    'exists:container_operational_traces,id',
                    Rule::unique('leak_tests', 'trace_id')
                ],
                'leaktest.inspector_id' => 'required|exists:users,id'

            ], [
                'leaktest.trace_id.unique' => 'El contenedor seleccionado ya cuenta con Leak Test'
            ], [
                'leaktest.serie' => 'Serie de documento',
                'leaktest.correlativo' => 'Correlativo de documento',
                'leaktest.started_at' => 'Fecha de inicio',
                'leaktest.finished_at' => 'Fecha de termino',
                'leaktest.hours' => 'Horas de test',
                'leaktest.status' => 'Estado final de Test',
                'leaktest.branch_id' => 'Sucursal',
                'leaktest.trace_id' => 'Contenedor',
                'leaktest.inspector_id' => 'Inspector'
            ]);

            // 1. Actualizar correlativo si es necesario
            $correlativo = LeakTest::query()
                ->where('serie', $this->leaktest['serie'])
                ->whereHas('branch', fn($query) => $query->where('branch_id', session('branch')->id))
                ->max('correlativo');

            if ($correlativo) {
                //$this->eir->correlativo = $correlativo + 1;
            }

            LeakTest::create($this->leaktest);
        });

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Leak Test registrado correctamente',
            'icon' => 'success'
        ]);
        $this->dispatch('leakTestCreated');
        $this->reset('openModal', 'leaktest', 'containerData');
    }
}
