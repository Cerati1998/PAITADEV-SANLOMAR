<?php

namespace App\Livewire\Forms;

use App\Models\inspections\EirInspection;
use App\Models\inspections\EirInspectionDetail;
use App\Models\works\LaborWorker;
use App\Models\works\WorkOrder;
use App\Models\works\WorkOrderLabor;
use App\Services\Container\ContainerOperationalTraceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Form;

class WorkOrderForm extends Form
{
    public ?WorkOrder $workOrder = null;

    public $type = '';

    public $tipoDoc = '11';

    #[Validate('required|string|max:10')]
    public $serie;

    #[Validate('required|integer')]
    public $correlativo = 0;

    #[Validate('nullable|string|max:50')]
    public $estimate_number = null;

    #[Validate('nullable|date')]
    public $estimate_date = null;

    #[Validate('required')]
    public $started_at = null;

    #[Validate('nullable')]
    public $closed_at = null;

    #[Validate('nullable|exists:eir_inspections,id')]
    public $eir_inspection_id = null;

    #[Validate('required|exists:users,id')]
    public $opened_by = null;

    #[Validate('nullable|exists:containers,id')]
    public $container_id = null;

    #[Validate('nullable|exists:users,id')]
    public $closed_by = null;

    public $branch_id;
    #[Validate('nullable|exists:providers,id')]
    public $provider_id;
    #[Validate('nullable|max:255')]
    public $comments = '';
    public $status;

    // Array temporal para los labores
    public $labors = [];

    public $availableDamages = []; // Lista de daños únicos
    public $selectedDamageId = null; // Daño seleccionado en el modal

    public $editingLaborId = null; // ID del labor que se está editando (null = creando nuevo)


    // Modal detail properties
    public $detailWorkOrderModal = [
        'id' => '',
        'damage_id' => null,
        'location' => '',
        'component' => '',
        'method' => '',
        'qty' => 0,
        'length' => 0,
        'width' => 0,
        'height' => 0,
        'description' => '',
        'started_at' => '',
        'finished_at' => '',
        'hours' => '',
    ];
    //Detalle en modal para agregar trabajadores
    public $detailLaborWorker = [
        'work_order_labor_id' => null,
        'worker_id' => null,
        'task_description' => '',
        'main_worker' => false,
    ];

    public $workers;

    /**
     * Carga los daños únicos de los labores de la orden de trabajo
     */
    /**
     * Carga los daños únicos disponibles desde los labores existentes
     */
    public function loadUniqueDamages()
    {
        if (!$this->workOrder) {
            $this->availableDamages = [];
            return;
        }

        /*  // Obtener los inspection_detail_ids únicos de los labores
        $detailIds = WorkOrderLabor::where('work_order_id', $this->workOrder->id)
            ->pluck('eir_inspection_detail_id')
            ->unique(); */

        // Cargar los detalles completos con sus relaciones
        $this->availableDamages = EirInspectionDetail::with([
            'damage',
            'location',
            'component',
            'method'
        ])
            ->where('approved', true)
            //->whereIn('id', $detailIds)
            ->get()
            ->filter(fn($detail) => $detail->damage) // Solo los que tienen daño
            ->unique('damage_id') // Únicos por damage_id
            ->map(fn($detail) => [
                'eir_inspection_detail_id' => $detail->id,
                'damage_id' => $detail->damage->id,
                'damage_code' => $detail->damage->code,
                'damage_description' => $detail->damage->description,
                'location_code' => $detail->location->code ?? '-',
                'component_code' => $detail->component->code ?? '-',
                'method_code' => $detail->method->code ?? '-',
                'qty' => $detail->qty ?? 0,
                'length' => $detail->length ?? 0,
                'width' => $detail->width ?? 0,
                'height' => $detail->height ?? 0,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Autocompletado cuando se selecciona un daño
     */
    public function fillDetailFromDamage($damageId)
    {
        if (!$damageId) {
            $this->resetDetailModal();
            return;
        }

        // Buscar el daño en el array de availableDamages
        $selectedDamage = collect($this->availableDamages)
            ->firstWhere('damage_id', $damageId);

        if (!$selectedDamage) {
            $this->resetDetailModal();
            return;
        }

        // Buscar el labor correspondiente para obtener la descripción
        $labor = WorkOrderLabor::where('work_order_id', $this->workOrder->id)
            ->where('eir_inspection_detail_id', $selectedDamage['eir_inspection_detail_id'])
            ->first();

        // Autocompletar todos los campos
        $this->detailWorkOrderModal = [
            'id' => null,
            'eir_inspection_detail_id' => $selectedDamage['eir_inspection_detail_id'],
            'damage_id' => $selectedDamage['damage_id'],
            'location' => $selectedDamage['location_code'],
            'component' => $selectedDamage['component_code'],
            'method' => $selectedDamage['method_code'],
            'qty' => $selectedDamage['qty'],
            'length' => $selectedDamage['length'],
            'width' => $selectedDamage['width'],
            'height' => $selectedDamage['height'],
            'started_at' => NULL,
            'finished_at' => NULL,
            'hours' => 0,
            //'description' => $labor?->description ?? '',
        ];
    }

    /**
     * Carga un labor existente para edición
     */
    public function loadLaborForEdit($laborId)
    {
        $labor = WorkOrderLabor::with([
            'inspectionDetail.damage',
            'inspectionDetail.location',
            'inspectionDetail.component',
            'inspectionDetail.method'
        ])->find($laborId);

        if (!$labor || !$labor->inspectionDetail) {
            throw new \Exception('Labor no encontrado');
        }

        $detail = $labor->inspectionDetail;

        // Guardar el ID del labor que estamos editando
        $this->editingLaborId = $laborId;

        // Cargar todos los datos del labor Y del inspection detail
        $this->detailWorkOrderModal = [
            'id' => $labor->id,
            'eir_inspection_detail_id' => $detail->id,
            'damage_id' => $detail->damage->id,
            'location' => $detail->location->code ?? '-',
            'component' => $detail->component->code ?? '-',
            'method' => $detail->method->code ?? '-',
            'qty' => $detail->qty ?? 0,
            'length' => $detail->length ?? 0,
            'width' => $detail->width ?? 0,
            'height' => $detail->height ?? 0,
            'description' => $labor->description ?? '',
            'started_at' => $labor->started_at
                ? Carbon::parse($labor->started_at)->format('Y-m-d\TH:i')
                : null,
            'finished_at' => $labor->finished_at
                ? Carbon::parse($labor->finished_at)->format('Y-m-d\TH:i')
                : null,
            'hours' => $labor->hours ?? 0,
        ];
    }

    /**
     * Resetear el modal de detalle
     */
    public function resetDetailModal()
    {
        $this->detailWorkOrderModal = [
            'id' => null,
            'damage_id' => null,
            'location' => '',
            'component' => '',
            'method' => '',
            'qty' => 0,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'description' => '',
            'started_at' => null,
            'finished_at' => null,
            'hours' => 0,
        ];
    }


    /**
     * Agregar al método reset() para limpiar también estos campos
     */
    public function resetForm()
    {
        $this->reset([
            'workOrder',
            'type',
            'serie',
            'correlativo',
            'estimate_number',
            'estimate_date',
            'started_at',
            'closed_at',
            'eir_inspection_id',
            'opened_by',
            'container_id',
            'closed_by',
            'branch_id',
            'provider_id',
            'comments',
            'status',
            'labors',
            'availableDamages',
            'selectedDamageId',
            'detailWorkOrderModal'
        ]);
    }
    /**
     * Obtiene la serie y correlativo del branch actual
     */
    public function getSeries()
    {
        // Asignar valor al branch
        $this->branch_id = session('branch')->id;

        $serieData = DB::table('branch_company_document')
            ->select('serie as name', 'correlativo')
            ->where('branch_id', $this->branch_id)
            ->where('document_id', $this->tipoDoc)
            ->first();

        if ($serieData) {
            $this->serie = $serieData->name;
            $this->correlativo = $serieData->correlativo;
        }

        // Obtener el último correlativo usado
        $lastCorrelativo = WorkOrder::where('serie', $this->serie)
            ->where('branch_id', $this->branch_id)
            ->max('correlativo');

        if ($lastCorrelativo) {
            $this->correlativo = $lastCorrelativo + 1;
        }
    }

    /**
     * Carga desde EIR solo detalles APROBADOS (para cotizaciones)
     */
    public function loadFromEir(int $eirInspectionId, bool $baseData = true, string $type = 'box')
    {
        $eirInspection = EirInspection::with([
            'details' => function ($query) {
                $query->where('approved', true)
                    ->with(['location', 'damage', 'component', 'method']);
            },
            'trace.container'
        ])->findOrFail($eirInspectionId);

        // Validar que existan detalles aprobados
        if ($eirInspection->details->isEmpty()) {
            throw new \Exception('No hay detalles aprobados para crear la orden de trabajo.');
        }
        if ($baseData) {
            $this->loadBaseData($eirInspection, $type);
        }
        $this->loadLaborsFromDetails($eirInspection->details);
    }

    /**
     * Carga desde EIR TODOS los detalles (para reparaciones normales)
     */
    public function loadFromEirAllDetails(int $eirInspectionId, string $type = 'box')
    {
        $eirInspection = EirInspection::with([
            'details' => function ($query) {
                $query->with(['location', 'damage', 'component', 'method']);
            },
            'trace.container'
        ])->findOrFail($eirInspectionId);

        // Validar que existan detalles
        if ($eirInspection->details->isEmpty()) {
            throw new \Exception('No hay detalles en el EIR para crear la orden de trabajo.');
        }

        $this->loadBaseData($eirInspection, $type);
        $this->loadLaborsFromDetails($eirInspection->details);
    }

    /**
     * Carga los datos base del EIR (método reutilizable)
     */
    public function loadBaseData($eirInspection, $type)
    {
        // Solo cargar datos iniciales si NO estamos actualizando una orden existente
        if (!$this->workOrder) {
            $this->type = $type;
            $this->estimate_number = null;
            $this->estimate_date = null;
            $this->started_at = Carbon::now()->format('Y-m-d H:i:s');
            $this->closed_at = null;
            $this->opened_by = auth()->id();
            $this->closed_by = null;
            $this->branch_id = session('branch')->id;
        }

        // Siempre actualizar estos campos
        $this->eir_inspection_id = $eirInspection->id;
        $this->container_id = $eirInspection->trace->container_id;
    }

    /**
     * Carga labores desde una colección de detalles
     */
    private function loadLaborsFromDetails($details)
    {
        $this->labors = $details->map(function ($detail) {
            return [
                'eir_inspection_detail_id' => $detail->id,
                'qty' => $detail->qty,
                'description' => $this->buildLaborDescription($detail),
                'started_at' => null,
                'finished_at' => null,
                'hours' => 0,
            ];
        })->toArray();

        $this->labors = $this->sanitizeArray($this->labors);

    }

    /**
     * Sanitiza recursivamente un array
     */
    private function sanitizeArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->forceUtf8($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function forceUtf8($text): string
    {
        if (!is_string($text))
            return '';

        // Si no es UTF-8 válido, convertir desde ISO-8859-1
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        // Eliminar caracteres de control inválidos (0x00-0x1F excepto tab, newline, return)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Eliminar caracteres de reemplazo
        $text = str_replace("\xEF\xBF\xBD", '', $text);

        return $text;
    }

    public function loadContainer($eirId)
    {
        $eir = EirInspection::with(['trace.container'])->find($eirId);
        $this->container_id = $eir?->trace?->container?->id;
        return $eir?->trace?->container?->code ?? null;
    }

    public function loadRepair($eirId)
    {
        $eir = EirInspection::find($eirId);

        if (!$eir) {
            return 'Sin Reparación';
        }

        return match (true) {
            $eir->fast_repair => 'Rápida',
            $eir->normal_repair => 'Normal',
            $eir->quoted_repair => 'Mto Alto',
            default => 'Sin Reparación'
        };
    }

    /**
     * Carga una orden de trabajo existente para editar
     */
    public function setWorkOrder(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
        $this->type = $workOrder->type;
        $this->serie = $workOrder->serie;
        $this->correlativo = $workOrder->correlativo;
        $this->estimate_number = $workOrder->estimate_number;
        $this->estimate_date = $workOrder->estimate_date?->format('Y-m-d');
        $this->started_at = $workOrder->started_at?->format('Y-m-d H:i:s');
        $this->closed_at = $workOrder->closed_at?->format('Y-m-d H:i:s');
        $this->eir_inspection_id = $workOrder->eir_inspection_id;
        $this->opened_by = $workOrder->opened_by;
        $this->container_id = $workOrder->container_id;
        $this->closed_by = $workOrder->closed_by;
        $this->status = $workOrder->status;
        $this->branch_id = $workOrder->branch_id;
        $this->provider_id = $workOrder->provider_id;
        $this->comments = $workOrder->comments;
        $this->labors = WorkOrderLabor::with([
            'inspectionDetail.location',
            'inspectionDetail.damage',
            'inspectionDetail.component',
            'inspectionDetail.method'
        ])->withCount('photos')
            ->where('work_order_id', $this->workOrder->id)->get();
        //  CARGAR DAÑOS ÚNICOS AUTOMÁTICAMENTE
        $this->loadUniqueDamages();
    }

    /**
     * Guarda la orden de trabajo con sus labores
     */
    public function store()
    {
        $this->validate();

        return DB::transaction(function () {
            // Crear la orden de trabajo
            $workOrder = WorkOrder::create($this->only([
                'type',
                'serie',
                'correlativo',
                'estimate_number',
                'estimate_date',
                'started_at',
                'closed_at',
                'eir_inspection_id',
                'opened_by',
                'container_id',
                'closed_by',
                'branch_id',
                'provider_id',
                'comments'
            ]));

            // Crear los labores asociados
            if (!empty($this->labors)) {
                foreach ($this->labors as $labor) {
                    WorkOrderLabor::create([
                        'work_order_id' => $workOrder->id,
                        'eir_inspection_detail_id' => $labor['eir_inspection_detail_id'],
                        'qty' => $labor['qty'],
                        'description' => $labor['description'],
                        'started_at' => $labor['started_at'] ?? null,
                        'finished_at' => $labor['finished_at'] ?? null,
                        'hours' => $labor['hours'],
                    ]);
                }
            }

            return $workOrder->fresh()->load('labors');
        });
    }

    /**
     * Actualiza una orden de trabajo existente
     */
    public function updateHeader()
    {
        if (!$this->workOrder) {
            throw new \Exception('No hay orden de trabajo cargada para actualizar.');
        }

        // Validar que la orden esté en estado editable
        if ($this->status && $this->status !== 'borrador') {
            throw new \Exception('No se puede actualizar. La orden de trabajo no está en estado borrador.');
        }

        $this->validate();

        return DB::transaction(function () {
            // Actualizar la orden (solo campos editables)
            $this->workOrder->update($this->only([
                'estimate_number',
                'estimate_date',
                'provider_id',
                'comments'
            ]));
        });
    }

    /**
     * Actualiza una orden de trabajo existente
     */
    public function update()
    {
        if (!$this->workOrder) {
            throw new \Exception('No hay orden de trabajo cargada para actualizar.');
        }

        // Validar que la orden esté en estado editable
        if ($this->status && $this->status !== 'borrador') {
            throw new \Exception('No se puede actualizar. La orden de trabajo no está en estado borrador.');
        }

        $this->validate();

        return DB::transaction(function () {
            // Actualizar la orden (solo campos editables)
            $this->workOrder->update($this->only([
                'estimate_number',
                'estimate_date',
                'provider_id',
                'comments'
            ]));

            // Eliminar labores antiguos y crear nuevos
            if (!empty($this->labors)) {
                $this->workOrder->labors()->delete();

                foreach ($this->labors as $labor) {
                    WorkOrderLabor::create([
                        'work_order_id' => $this->workOrder->id,
                        'eir_inspection_detail_id' => $labor['eir_inspection_detail_id'],
                        'description' => $labor['description'],
                        'qty' => $labor['qty'],
                        'started_at' => $labor['started_at'] ?? null,
                        'finished_at' => $labor['finished_at'] ?? null,
                        'hours' => $labor['hours'] ?? 0,
                    ]);
                }
            }
            return $this->workOrder->fresh()->load('labors');
        });
    }

    /**
     * Construye la descripción del labor basado en el detalle del EIR
     */
    private function buildLaborDescription($detail): string
    {
        $parts = [];

        /*         if ($detail->component) {
            $parts[] = "Comp: {$detail->component->code}";
        } */

        if ($detail->damage) {
            $parts[] = "Daño: {$detail->damage->code}";
        }

        if ($detail->location) {
            $parts[] = "Ubic: {$detail->location->code}";
        }

        /*         if ($detail->method) {
            $parts[] = "Mét: {$detail->method->code}";
        } */

        $dimensions = [];
        if ($detail->length)
            $dimensions[] = "L:{$detail->length}";
        if ($detail->width)
            $dimensions[] = "W:{$detail->width}";
        if ($detail->height)
            $dimensions[] = "H:{$detail->height}";
        if (!empty($dimensions)) {
            $parts[] = implode('x', $dimensions);
        }

        if ($detail->qty > 1) {
            $parts[] = "Cant: {$detail->qty}";
        }

        if ($detail->comments) {
            $shortComment = strlen($detail->comments) > 40
                ? substr($detail->comments, 0, 37) . '...'
                : $detail->comments;
            $parts[] = $shortComment;
        }

        return implode(' | ', $parts);
    }

    /*
     * Recarga los labores desde la base de datos
     */
    public function refreshLabors()
    {
        if (!$this->workOrder) {
            $this->labors = [];
            return;
        }

        // Recargar labores desde la BD con todas las relaciones
        $this->labors = WorkOrderLabor::with([
            'inspectionDetail.location',
            'inspectionDetail.damage',
            'inspectionDetail.component',
            'inspectionDetail.method'
        ])->withCount('photos')
            ->where('work_order_id', $this->workOrder->id)
            ->get();

        // Opcional: También recargar los daños únicos si cambiaron
        $this->loadUniqueDamages();
    }

    public function loadWorkersFromDetail($workOrderDetailId)
    {
        $this->workers = LaborWorker::with(['worker'])
            ->where('labor_worker.work_order_labor_id', $workOrderDetailId)->get();
    }

    /**
     * Cierra la orden de trabajo y actualiza el trace del contenedor
     * 
     * @throws Exception
     */
    public function close_order()
    {
        try {
            // Validar que la orden tenga una inspección EIR asociada
            if (!$this->eir_inspection_id) {
                throw new Exception('La orden de trabajo no tiene una inspección EIR asociada');
            }

            // Validar que tenga labores registradas
            if (empty($this->labors)) {
                throw new Exception('No se puede cerrar una orden sin labores registradas');
            }

            DB::transaction(function () {
                // 1. Obtener el trace_id desde la inspección EIR
                $eirInspection = EirInspection::findOrFail($this->eir_inspection_id);
                $traceId = $eirInspection->trace_id;

                if (!$traceId) {
                    throw new Exception('No se encontró el trace del contenedor');
                }

                // 2. Determinar el nuevo estado según el tipo de trabajo
                $statusBox = null;
                $statusMachine = null;

                if ($this->type === 'box') {
                    //  Al cerrar WorkOrder de BOX → pasa a OS (Operativo Sucio)
                    // Box puede ser: pending, dm, os, op
                    $statusBox = 'OS';
                } elseif ($this->type === 'machine') {
                    //  Al cerrar WorkOrder de MACHINE → pasa a OP (Operativo)
                    // Machine puede ser: pending, dm, op (NO tiene os)
                    $statusMachine = 'OP';
                }

                // 3. El service consulta el valor faltante de la BD automáticamente
                $traceService = app(ContainerOperationalTraceService::class);
                $traceService->updateWithCalculatedStatus(
                    traceId: $traceId,
                    statusBox: $statusBox,
                    statusMachine: $statusMachine
                );

                // 4. Cerrar la orden de trabajo
                $this->workOrder->update([
                    'status' => 'completado',
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                ]);

                // Actualizar las propiedades del form
                $this->status = 'completado';
                $this->closed_at = now()->format('Y-m-d H:i');
                $this->closed_by = auth()->id();
            });

            return [
                'success' => true,
                'message' => 'Orden de trabajo cerrada exitosamente'
            ];
        } catch (Exception $e) {
            throw new Exception('Error al cerrar la orden: ' . $e->getMessage());
        }
    }
}
