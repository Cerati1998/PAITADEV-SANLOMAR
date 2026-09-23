<?php

namespace App\Livewire\Operations\Inspections;

use App\Livewire\Forms\EirForm;
use App\Models\Container;
use App\Models\ContainerLineCategory;
use App\Models\ContainerOperationalTrace;
use App\Models\inspections\EirInspection;
use App\Models\ReeferMachine;
use App\Models\ReeferTechnology;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EirCreate extends Component
{
    public $locations;
    public $damages;
    public $components;
    public $methods;
    public $container_line_categories;
    public $container_types;
    public $reefer_technologies;
    public $reefer_machines;
    public $trace = NULL; // En caso se genere desde el OperationalTrace
    public $series;
    public EirForm $eir;
    public $idEir = NULL; // Se da uso cuando se genere el EIR
    public $months = [];
    public $years = [];

    public $trace_origin = [
        'vessel' => '',
        'shipping_line' => '',
        'event' => ''
    ];

    // Datos referenciales del contenedor (no van al fillable de EIR)
    public $containerInfo = [
        'code' => '',
        'iso_code' => '',
        'container_type' => '',
        'container_technology' => null,
        'container_machine' => null,
    ];

    // Datos que se usarán para actualizar el modelo Container
    public $containerData = [
        'tare' => '',
        'payload' => '',
        'max_gross' => '',
        'max_height' => '',
        'max_width' => '',
        'build_month' => '',
        'build_year' => '',
    ];

    public function render()
    {
        return view('livewire.operations.inspections.eir-create');
    }

    public function getSeries()
    {
        $this->reset(['eir.serie', 'eir.correlativo']);

        $this->series = DB::table('branch_company_document')
            ->select('serie as name', 'correlativo')
            ->where('branch_id', session('branch')->id)
            ->where('document_id', $this->eir->tipoDoc)
            ->get();

        $serie = $this->series->first();
        if ($serie) {
            $this->eir->serie = $serie->name;
            $this->eir->correlativo = $serie->correlativo;
        }

        $correlativo = EirInspection::query()
            ->where('serie', $this->eir->serie)
            ->where('branch_id', session('branch')->id)
            ->max('correlativo');

        if ($correlativo) {
            $this->eir->correlativo = $correlativo + 1;
        }
    }

    private function rejectTrace(string $text): void
    {
        $this->dispatch('swal', [
            'icon' => 'error',
            'title' => 'Contenedor no disponible',
            'text' => $text,
        ]);

        $this->eir->trace_id = null;

        $this->reset(['trace', 'containerInfo', 'containerData', 'trace_origin']);
    }

    public function getContainerTrace()
    {
        if (! $this->trace) {
            return;
        }

        $trace = ContainerOperationalTrace::with('container.container_type', 'container.reefer_technology', 'container.machine')
            ->forBranch()
            ->whereKey($this->trace)
            ->first();

        if (! $trace) {
            $this->rejectTrace('No se encontró el contenedor en tu sucursal.');

            return;
        }

        $existingEir = EirInspection::existingForTrace($this->trace, $this->idEir);

        if ($existingEir) {
            $this->rejectTrace("Este contenedor ya tiene la inspección {$existingEir->documentLabel()}.");

            return;
        }

        if ($trace?->container) {
            $container = $trace->container;

            // Info referencial
            $this->containerInfo['id'] = $container->id;
            $this->containerInfo['code'] = $container->code;
            $this->containerInfo['iso_code'] = $container->iso_code;
            $this->containerInfo['container_type'] = $container->container_type->id ?? '';
            $this->containerInfo['container_technology'] = $container?->reefer_technology->id ?? '';
            $this->containerInfo['container_machine'] = $container?->machine->id ?? '';

            // Data editable que actualizará el modelo Container
            $this->containerData['tare'] = $container->tare ?? '';
            $this->containerData['payload'] = $container->payload ?? '';
            $this->containerData['max_gross'] = $container->max_gross ?? '';
            $this->containerData['max_height'] = $container->max_height ?? '';
            $this->containerData['max_width'] = $container->max_width ?? '';
            $this->containerData['build_month'] = $container->build_month;
            $this->containerData['build_year'] = $container->build_year;
        }

        // Origen del evento
        $this->trace_origin['vessel'] = $trace->nave;
        $this->trace_origin['shipping_line'] = $trace->linea;
        $this->setContainerCategories($trace->lineaId);
        $this->trace_origin['event'] = $trace->origen;

        //  Paso el id del trace a la inspeccion
        $this->eir->trace_id = $this->trace;
    }

    public function setMonthYearOptions()
    {
        $this->months = collect(range(1, 12))->mapWithKeys(fn($mes) => [$mes => str_pad($mes, 2, '0', STR_PAD_LEFT)]);
        $currentYear = Carbon::now()->year;
        $this->years = collect(range(2000, $currentYear))->reverse()->toArray();
    }

    public function setContainerCategories($lineId)
    {
        if (!$lineId) return;

        return $this->container_line_categories = ContainerLineCategory::where('shipping_line_id', $lineId)->get()->map(function ($category) {
            return [
                'name' => $category->code,
            ];
        });
    }

    public function updated($property, $value)
    {
        if ($property === 'trace') {
            $this->trace = $value;
            $this->getContainerTrace();
        }
    }

    public function mount($catalogs)
    {
        $this->eir->started_at = now()->format('Y-m-d H:i');
        $this->eir->branch_id = session('branch')->id;
        $this->eir->inspector_id = auth()->id();

        // Catálogos
        $this->locations = $catalogs['locations'];
        $this->damages = $catalogs['damages'];
        $this->components = $catalogs['components'];
        $this->methods = $catalogs['methods'];
        $this->container_types = $catalogs['container_types'];

        $this->reefer_technologies = ReeferTechnology::all();
        $this->reefer_machines = ReeferMachine::all();

        $this->getSeries();
        $this->getContainerTrace();
        $this->setMonthYearOptions();
    }

    private function dispatchEirCreated(): void
    {
        $this->dispatch('eirCreated', [
            'title' => 'Exito!',
            'text' => 'EIR Registrado con Exito',
            'icon' => 'success',
            'redirect' => route('inspections.eir.edit', $this->idEir),
        ]);
    }

    /**
     * Detecta el choque del índice único (serie, correlativo). Compara el código
     * SQLSTATE y busca el fragmento del nombre del índice en minúsculas, para tolerar
     * que producción tenga el índice con otro nombre que contenga las dos columnas.
     */
    private function isDuplicateCorrelativo(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000'
            && str_contains(strtolower($e->getMessage()), 'serie_correlativo');
    }

    public function save()
    {
        // Guarda de idempotencia: un save() reentrante (doble clic, reintento) reemite
        // la redirección en lugar de crear un EIR gemelo.
        if ($this->idEir) {
            $this->dispatchEirCreated();

            return;
        }

        $duplicate = null;
        $attempts = 0;
        $maxAttempts = 3;

        while (true) {
            $attempts++;

            try {
                DB::transaction(function () use (&$duplicate) {
                    // 0. Re-chequeo de duplicado dentro de la transacción: primera
                    // instrucción, antes de cualquier insert.
                    $duplicate = EirInspection::where('trace_id', $this->eir->trace_id)
                        ->lockForUpdate()
                        ->first();

                    if ($duplicate) {
                        return;
                    }

                    // 1. Actualizar correlativo si es necesario (bloqueado)
                    $correlativo = EirInspection::query()
                        ->where('serie', $this->eir->serie)
                        ->where('branch_id', session('branch')->id)
                        ->lockForUpdate()
                        ->max('correlativo');

                    if ($correlativo) {
                        $this->eir->correlativo = $correlativo + 1;
                    }

                    // 2. Validar datos del contenedor ANTES de crear el EIR
                    $this->validate([
                        'containerData.tare' => 'required|numeric|between:1000,9999',
                        'containerData.payload' => 'required|numeric|between:10000,99999',
                        'containerData.max_gross' => 'required|numeric|between:20000,99999',
                        'containerData.build_month' => 'required|numeric|min:1|max:12',
                        'containerInfo.container_type' => 'required|numeric',
                        'containerInfo.container_technology' => 'nullable|required_if:containerInfo.container_type,11,12',
                        'containerInfo.container_machine' => 'nullable|required_if:containerInfo.container_type,11,12',
                        'containerData.build_year' => [
                            'required',
                            'numeric',
                            Rule::in($this->years)
                        ],
                    ], [
                        'containerInfo.container_technology.required_if' => 'La Marca de Máquina de Contenedor es obligatorio cuando Tipo de Contenedor es Reefer',
                        'containerInfo.container_machine.required_if' => 'La Marca de Máquina de Contenedor es obligatorio cuando Tipo de Contenedor es Reefer',
                    ], [
                        'containerData.tare' => 'Tara de Contenedor',
                        'containerData.payload' => 'Payload de Contenedor',
                        'containerData.max_gross' => 'Maximo peso cargado de Contenedor',
                        'containerData.build_month' => 'Mes de Fabricación',
                        'containerData.build_year' => 'Año de Fabricación',
                        'containerInfo.container_type' => 'Tipo de Contenedor',
                        'containerInfo.container_technology' => 'Tecnología de Contenedor',
                        'containerInfo.container_machine' => 'Marca de Máquina de Contenedor',
                    ]);

                    // 3. Crear el EIR (dentro de la transacción)
                    $this->idEir = $this->eir->store();


                    // 4. Actualizar el contenedor (dentro de la misma transacción)
                    if (!empty($this->containerInfo['id'])) {
                        Container::find($this->containerInfo['id'])->update([
                            'tare' => $this->containerData['tare'],
                            'payload' => $this->containerData['payload'],
                            'max_gross' => ($this->containerData['max_gross'] !== '') ? $this->containerData['max_gross'] : 0,
                            'max_height' => ($this->containerData['max_height'] !== '') ? $this->containerData['max_height'] : 0,
                            'max_width' => ($this->containerData['max_width'] !== '') ? $this->containerData['max_width']  : 0,
                            'build_month' => $this->containerData['build_month'],
                            'build_year' => $this->containerData['build_year'],
                            'container_type_id' => $this->containerInfo['container_type'],
                            'reefer_technology_id' => ($this->containerInfo['container_technology'] !== '') ? $this->containerInfo['container_technology'] : null,
                            'reefer_machine_id' => ($this->containerInfo['container_machine'] !== '') ? $this->containerInfo['container_machine'] : null,
                        ]);
                    }
                });

                break;
            } catch (QueryException $e) {
                // La transacción hizo rollback: el id asignado en el paso 3 ya no existe en BD.
                // Dejarlo puesto haría que un save() posterior tomara la guarda de
                // idempotencia y redirigiera a un EIR inexistente.
                $this->idEir = null;

                if ($attempts < $maxAttempts && $this->isDuplicateCorrelativo($e)) {
                    continue;
                }

                Log::error('Error al guardar EIR', [
                    'trace_id' => $this->eir->trace_id,
                    'attempts' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error!',
                    'text' => $this->isDuplicateCorrelativo($e)
                        ? 'Otro inspector guardó un EIR al mismo tiempo, vuelve a intentarlo.'
                        : 'No se pudo guardar el EIR, intenta nuevamente.',
                ]);

                return;
            }
        }

        if ($duplicate) {
            $this->idEir = null;

            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Contenedor no disponible',
                'text' => "Este contenedor ya tiene la inspección {$duplicate->documentLabel()}.",
            ]);

            return;
        }

        $this->dispatchEirCreated();
    }
}
