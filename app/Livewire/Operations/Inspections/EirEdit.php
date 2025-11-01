<?php

namespace App\Livewire\Operations\Inspections;

use App\Livewire\Forms\EirForm;
use App\Livewire\Forms\WorkOrderForm;
use App\Models\Container;
use App\Models\ContainerLineCategory;
use App\Models\ContainerOperationalTrace;
use App\Models\Damage;
use App\Models\inspections\EirInspection;
use App\Models\inspections\EirInspectionDetail;
use App\Models\inspections\EirInspectionDetailPhoto;
use App\Models\Location;
use App\Models\Method;
use App\Models\Component as ReeferComponent;
use App\Models\ReeferMachine;
use App\Models\ReeferTechnology;
use App\Models\works\WorkOrder;
use App\Services\Container\ContainerOperationalTraceService;
use App\Services\Container\ContainerStatusService;
use Carbon\Carbon;
use App\Services\inspections\EirPhotoService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class EirEdit extends Component
{
    use WithFileUploads;

    public $container_types;
    public $reefer_technologies;
    public $reefer_machines;
    public $trace = NULL;
    public EirForm $eir;
    public WorkOrderForm $workOrderForm;

    #[Locked]
    public $idEir = NULL;

    #[Locked]
    public $eirStatus = 'borrador';

    public $months = [];
    public $years = [];

    public $statusBox = 'pending';
    public $statusMachine = 'pending';
    public $statusContainerTrace = 1;
    public $isEditing = false;
    public $openEditModal = false;
    public $openCloseModal = false;
    public $eirDetailId = NULL;

    public $trace_origin = [
        'vessel' => '',
        'shipping_line' => '',
        'event' => ''
    ];

    public $damages = null;
    public $locations = null;
    public $components = null;
    public $methods = null;
    public $container_line_categories;

    public $details = [];

    public $detailModal = [
        'location_id' => '',
        'damage_id' => '',
        'component_id' => '',
        'method_id' => '',
        'qty' => '',
        'length' => '',
        'width' => '',
        'height' => '',
        'comments' => '',
    ];

    public $containerInfo = [
        'code' => '',
        'iso_code' => '',
        'container_type' => '',
        'container_technology' => '',
        'container_machine' => '',
    ];

    public $containerData = [
        'tare' => '',
        'payload' => '',
        'max_gross' => '',
        'max_height' => '',
        'max_width' => '',
        'build_month' => '',
        'build_year' => '',
    ];

    public $photos = [
        1 => null,
        2 => null,
        3 => null,
        4 => null,
    ];

    public $urls = [
        1 => null,
        2 => null,
        3 => null,
        4 => null,
    ];

    #[Computed]
    public function damages()
    {
        return Damage::all()->map(function ($damage) {
            return [
                'id' => $damage->id,
                'name' => $damage->code,
                'description' => $damage->description,
            ];
        });
    }

    #[Computed]
    public function locations()
    {
        return Location::all()->map(function ($location) {
            return [
                'id' => $location->id,
                'name' => $location->code,
                'description' => $location->description,
            ];
        });
    }

    #[Computed]
    public function components()
    {
        return ReeferComponent::all()->map(function ($component) {
            return [
                'id' => $component->id,
                'name' => $component->code,
                'description' => $component->description,
            ];
        });
    }

    #[Computed]
    public function methods()
    {
        return Method::all()->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->code,
                'description' => $method->description,
            ];
        });
    }

    public $openModalDetailPhoto = false;
    public $newPhotoDetail;
    public $currentDetailId;
    public $photosDetail = [];
    protected EirPhotoService $photoService;
    protected $rules = [
        'photos.*' => 'image|max:10048',
    ];

    public $rejectedDetails = [];

    public function render()
    {
        return view('livewire.operations.inspections.eir-edit');
    }

    public function getContainerTrace()
    {
        if (!$this->trace) return;

        $trace = ContainerOperationalTrace::with('container.container_type', 'container.reefer_technology', 'container.machine')
            ->find($this->trace);

        if ($trace?->container) {
            $container = $trace->container;

            $this->containerInfo['id'] = $container->id;
            $this->containerInfo['code'] = $container->code;
            $this->containerInfo['iso_code'] = $container->iso_code;
            $this->containerInfo['container_type'] = (int) $container->container_type->id ?? '';
            $this->containerInfo['container_technology'] = (int) $container?->reefer_technology->id ?? '';
            $this->containerInfo['container_machine'] = (int) $container?->reefer_machine_id ?? '';
            $this->containerData['tare'] = $container->tare ?? '';
            $this->containerData['payload'] = $container->payload ?? '';
            $this->containerData['max_gross'] = $container->max_gross ?? '';
            $this->containerData['max_height'] = $container->max_height ?? '';
            $this->containerData['max_width'] = $container->max_width ?? '';
            $this->containerData['build_month'] = str_pad($container->build_month, 2, '0', STR_PAD_LEFT);
            $this->containerData['build_year'] = $container->build_year;
        }

        $this->trace_origin['vessel'] = $trace->nave;
        $this->trace_origin['shipping_line'] = $trace->linea;
        $this->setContainerCategories($trace->lineaId);
        $this->trace_origin['event'] = $trace->origen;

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
            $this->eir->trace_id = $value;
        }
    }

    /*  public function updatedPhotos($value, $key)
    {
        if ($value) {
            $this->validateOnly("photos.$key");

            if ($this->eirStatus !== 'borrador') {
                $this->photos[$key] = null;
                $this->dispatch('swal', [
                    'title' => 'Error!',
                    'text' => 'No puedes actualizar el registro, el EIR ya se encuentra cerrado',
                    'icon' => 'error'
                ]);
                return;
            }

            try {
                $eir = EirInspection::findOrFail($this->idEir);

                $oldPhotoField = "path_photo_{$key}";
                $oldPhotoPath = $eir->$oldPhotoField;

                // Eliminar foto anterior de DO Spaces
                if ($oldPhotoPath && Storage::disk(config('filesystems.default'))->exists($oldPhotoPath)) {
                    Storage::disk(config('filesystems.default'))->delete($oldPhotoPath);
                }

                // Guardar la nueva foto (ya usa el disco correcto internamente)
                $path = $this->photoService->saveHeaderPhoto($eir, $this->photos[$key], $key);

                // Obtener URL de DO Spaces
                $this->urls[$key] = $this->photoService->getPhotoUrl($path);
                $this->photos[$key] = null;
            } catch (\Exception $e) {
                $this->dispatch('swal', [
                    'title' => 'Error!',
                    'text' => $e->getMessage(),
                    'icon' => 'error'
                ]);
            }
        }
    } */

    public function loadDetails()
    {
        $this->details = EirInspectionDetail::with([
            'location:id,description,code',
            'damage:id,description,code',
            'component:id,description,code',
            'method:id,description,code'
        ])
            ->where('eir_inspection_id', $this->idEir)
            ->get()
            ->toArray();

        $this->rejectedDetails = collect($this->details)
            ->where('approved', false)
            ->pluck('id')
            ->toArray();
    }

    public function boot(EirPhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    public function mount($catalogs, $eir_id)
    {
        $eirModel = EirInspection::with('trace')->findOrFail($eir_id);

        $this->idEir = $eir_id;
        $this->eirStatus = $eirModel->status;

        $this->eir->fill($eirModel->only([
            'started_at',
            'closed_at',
            'serie',
            'correlativo',
            'normal_wash',
            'quimic_wash',
            'reason_quimic_wash',
            'special_wash',
            'reason_special_wash',
            'normal_repair',
            'fast_repair',
            'quoted_repair',
            'container_category',
            'observations',
            'status',
            'trace_id',
            'inspector_id',
            'branch_id',
        ]));

        $this->urls[1] = $eirModel->getPhoto1Url();
        $this->urls[2] = $eirModel->getPhoto2Url();
        $this->urls[3] = $eirModel->getPhoto3Url();
        $this->urls[4] = $eirModel->getPhoto4Url();

        $this->details = $eirModel->details;
        $this->container_types = $catalogs['container_types'];
        $this->damages = $this->damages();
        $this->locations = $this->locations();
        $this->components = $this->components();
        $this->methods = $this->methods();
        $this->reefer_technologies = ReeferTechnology::all();
        $this->reefer_machines = ReeferMachine::all();

        $this->trace = $eirModel->trace_id;
        $this->statusBox = $eirModel->trace->status_box;
        $this->statusMachine = $eirModel->trace->status_machine;
        $this->statusContainerTrace = $eirModel->trace->status;

        $this->getContainerTrace();
        $this->setMonthYearOptions();
        $this->loadDetails();
    }

    public function save()
    {
        if (!auth()->user()->can('inspections.eir.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if (!$this->ensureDraft()) return;

        DB::transaction(function () {
            $this->validate([
                'containerData.tare' => 'required|numeric|between:1000,9999',
                'containerData.payload' => 'required|numeric|between:10000,99999',
                'containerData.max_gross' => 'required|numeric|between:20000,99999',
                'containerData.build_month' => 'numeric|min:1|max:12',
                'containerInfo.container_type' => 'required|numeric',
                'containerInfo.container_technology' => 'required|required_if:containerInfo.container_type,11,12',
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

            $this->eir->validate();

            $eir = EirInspection::findOrFail($this->idEir);
            $eir->update($this->eir->except('details', 'tipoDoc'));

            if (!empty($this->containerInfo['id'])) {
                Container::findOrFail($this->containerInfo['id'])->update([
                    'tare' => $this->containerData['tare'],
                    'payload' => $this->containerData['payload'],
                    'max_gross' => ($this->containerData['max_gross'] !== '') ? $this->containerData['max_gross'] : 0,
                    'max_height' => ($this->containerData['max_height'] !== '') ? $this->containerData['max_height'] : 0,
                    'max_width' => ($this->containerData['max_width'] !== '') ? $this->containerData['max_width']  : 0,
                    'build_month' => $this->containerData['build_month'],
                    'build_year' => $this->containerData['build_year'],
                    'container_type_id' => $this->containerInfo['container_type'],
                    'reefer_technology_id' => $this->containerInfo['container_technology'],
                    'reefer_machine_id' => $this->containerInfo['container_machine'],
                ]);
            }
        });

        $this->dispatch('swal', [
            'title' => '¡Éxito!',
            'text' => 'EIR actualizado con éxito',
            'icon' => 'success'
        ]);
    }

    public function saveDetail()
    {
        if (!auth()->user()->can('inspections.eir.create-detail')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if ($this->eirStatus !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes añadir detalles al registro, el EIR ya se encuentra cerrado',
                'icon' => 'error'
            ]);
            return;
        }

        $msg = '';

        $this->validate([
            'detailModal.location_id' => 'required|numeric|exists:locations,id',
            'detailModal.damage_id' => 'required|numeric|exists:damages,id',
            'detailModal.component_id' => 'required|numeric|exists:components,id',
            'detailModal.method_id' => 'required|numeric|exists:methods,id',
            'detailModal.qty' => "required|min:1|integer",
            'detailModal.length' => "nullable|min:0|integer",
            'detailModal.width' => "nullable|min:0|integer",
            'detailModal.height' => "nullable|min:0|integer",
            'detailModal.comments' => 'nullable|between:5,255',
        ], [], [
            'detailModal.location_id' => 'Código de Ubicación',
            'detailModal.damage_id' => 'Código de Daño',
            'detailModal.component_id' => 'Código de Componente',
            'detailModal.method_id' => 'Código de método de reparación',
            'detailModal.qty' => "Cantidad",
            'detailModal.length' => "Longitud/largo en Cm",
            'detailModal.width' => "Ancho en Cm",
            'detailModal.height' => "Altura en Cm",
            'detailModal.comments' => 'Comentarios u Observaciones adicionales',
        ]);

        $this->detailModal['eir_inspection_id'] = $this->idEir;

        //asigno 0 a los vacios
        $data = collect($this->detailModal)
            ->map(function ($value, $key) {
                // Solo aplicar el reemplazo a las claves específicas
                if (in_array($key, ['width', 'height', 'length']) && $value === '') {
                    return 0;
                }
                return $value;
            })
            ->toArray();


        if (!$this->isEditing) {
            EirInspectionDetail::create($data);
            $msg = 'Detalle agregado correctamente';
        } else {
            $eirDetail = EirInspectionDetail::find($this->eirDetailId);
            $eirDetail->update($data);
            $msg = 'Detalle actualizado correctamente';
        }

        $this->cancelDetailModal();
        $this->loadDetails();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => $msg,
            'icon' => 'success'
        ]);
    }

    public function editDetail(EirInspectionDetail $eirInspectionDetail)
    {
        $this->detailModal = [
            'eir_inspection_id' => (int) $eirInspectionDetail->eir_inspection_id,
            'location_id' => (int) $eirInspectionDetail->location_id,
            'damage_id' => (int) $eirInspectionDetail->damage_id,
            'component_id' => (int) $eirInspectionDetail->component_id,
            'method_id' => (int) $eirInspectionDetail->method_id,
            'qty' => $eirInspectionDetail->qty ?? '',
            'length' => $eirInspectionDetail->length ?? 0,
            'width' => $eirInspectionDetail->width ?? 0,
            'height' => $eirInspectionDetail->height ?? 0,
            'comments' => $eirInspectionDetail->comments ?? '',
        ];

        $this->isEditing = true;
        $this->openEditModal = true;
        $this->eirDetailId = (int) $eirInspectionDetail->id;
    }

    public function removeDetail(EirInspectionDetail $eirDetail)
    {
        if (!auth()->user()->can('inspections.eir.delete-detail')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if (!$this->ensureDraft()) return;

        $eirDetail->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Detalle de inspección eliminado con exito',
            'icon' => 'success'
        ]);

        $this->loadDetails();
    }

    public function cancelDetailModal()
    {
        $this->reset('openEditModal', 'isEditing', 'eirDetailId', 'detailModal');
    }

    public function download_photos()
    {
        try {
            $inspection = EirInspection::findOrFail($this->idEir);
            $zipPath = $this->photoService->generateHeaderPhotosZip($inspection);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function loadPhotoDetail(EirInspectionDetail $eirInspectionDetail)
    {
        $this->dispatch('photosUpdated');
        $this->photosDetail = $eirInspectionDetail->photos;
        $this->currentDetailId = (int) $eirInspectionDetail->id;
        $this->openModalDetailPhoto = true;
    }

    /* public function updatedNewPhotoDetail()
    {
        if (!$this->ensureDraft()) return;

        $this->attachDetailPhoto();
    } */

    /**
     * Método para validar que la inspección se encuentra o no en borrador
     * @return bool
     */
    public function ensureDraft(): bool
    {
        if ($this->eirStatus !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar un EIR cerrado',
                'icon' => 'error'
            ]);
            return false;
        }
        return true;
    }

    public function refreshHeaderPhotos()
    {
        $eir = EirInspection::find($this->idEir);

        if ($eir) {
            $this->urls = [
                1 => $eir->path_photo_1,
                2 => $eir->path_photo_2,
                3 => $eir->path_photo_3,
                4 => $eir->path_photo_4,
            ];
        }
    }
    /* public function attachDetailPhoto()
    {

        try {
            $this->photoService->validateImage($this->newPhotoDetail);

            $detail = EirInspectionDetail::findOrFail($this->currentDetailId);
            $this->photoService->saveDetailPhoto($detail, $this->newPhotoDetail);

            $this->photosDetail = $detail->fresh()->photos;
            $this->newPhotoDetail = null;

            $this->dispatch('swal', [
                'title' => 'Éxito!',
                'text' => 'Foto de evidencia subida correctamente',
                'icon' => 'success'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    } */

    /*  public function downloadPhoto($photoId)
    {
        $photo = EirInspectionDetailPhoto::findOrFail($photoId);
        return Storage::disk('public')->download($photo->path);
    } */

    public function downloadPhoto($photoId)
    {
        $photo = EirInspectionDetailPhoto::findOrFail($photoId);

        $tmp = tempnam(sys_get_temp_dir(), 'photo_');
        file_put_contents($tmp, file_get_contents($photo->path));

        return response()->download($tmp, basename($photo->path))
            ->deleteFileAfterSend(true);
    }


    public function deletePhoto($photoId)
    {
        if (!auth()->user()->can('inspections.eir.delete-photos')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if (!$this->ensureDraft()) return;

        $this->photoService->deleteDetailPhoto($photoId);

        $this->photosDetail = EirInspectionDetail::find($this->currentDetailId)->photos;

        $this->dispatch('swal', [
            'title' => 'Eliminado!',
            'text' => 'Foto eliminada correctamente',
            'icon' => 'success'
        ]);
    }

    public function download_detail_photos()
    {
        $photos = EirInspectionDetail::find($this->currentDetailId)->photos;

        try {
            $detail = EirInspectionDetail::findOrFail($this->currentDetailId);
            $zipPath = $this->photoService->generateDetailPhotosZip($detail);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Cierra el EIR y crea/actualiza WorkOrder si es necesario 
     */
    public function close()
    {
        if (!auth()->user()->can('inspections.eir.close')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        try {
            // Validaciones previas
            if ($this->statusBox === 'pending') {
                throw new Exception('Seleccione un estado Final de Estructura para Cerrar el EIR');
            }

            if ($this->statusBox === 'OS' && !empty($this->details)) {
                throw new Exception('No puedes cerrar el EIR con Estructura operativa, tienes códigos de Daño registrados');
            }

            $this->statusContainerTrace = ContainerStatusService::calculateStatus(
                $this->statusBox,
                $this->statusMachine
            );

            // Evaluar si requiere WorkOrder:
            // - statusBox = DM
            // - NO es cotización (quoted_repair = false) → Pasan TODOS los detalles
            $requireWorkOrder = $this->statusBox === 'DM' && !$this->eir->quoted_repair;
            $workOrderMessage = '';

            DB::transaction(function () use ($requireWorkOrder, &$workOrderMessage) {
                // Actualizar trace del contenedor
                $traceService = app(ContainerOperationalTraceService::class);
                //Log::info($this->statusMachine);
                $updatedTrace = $traceService->updateWithCalculatedStatus(
                    traceId: $this->trace,
                    statusBox: $this->statusBox,
                    statusMachine: $this->statusMachine
                );
                //Log::info($this->statusMachine);
                // Actualizar la propiedad local con el status calculado
                $this->statusContainerTrace = $updatedTrace->status;

                // Cerrar la inspección
                $this->eir->close_inspection($this->idEir, $this->statusBox);
                $this->eirStatus = 'completado';

                // Si NO es cotización y status = DM, crear/actualizar WorkOrder con TODOS los detalles
                if ($requireWorkOrder) {
                    $workOrderMessage = $this->handleWorkOrderOnClose();
                }
            });

            // Construir mensaje de éxito
            $message = 'EIR cerrado con éxito.';
            if ($workOrderMessage) {
                $message .= ' ' . $workOrderMessage;
            }

            //cierro modal
            $this->reset('openCloseModal');

            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => $message,
                'icon' => 'success'
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Gestiona WorkOrder al cerrar EIR (con TODOS los detalles, sin filtrar por approved)
     */
    private function handleWorkOrderOnClose(): string
    {
        $existingWorkOrder = WorkOrder::where('eir_inspection_id', $this->idEir)
            ->where('type', 'box')
            ->first();

        if ($existingWorkOrder) {
            // Actualizar orden existente
            $this->workOrderForm->setWorkOrder($existingWorkOrder);
            $this->workOrderForm->loadFromEirAllDetails($this->idEir, 'box'); // Todos los detalles
            $this->workOrderForm->update();

            return "Orden {$existingWorkOrder->serie}-" . str_pad($existingWorkOrder->correlativo, 6, '0', STR_PAD_LEFT) . " actualizada.";
        } else {
            // Crear nueva orden
            $this->workOrderForm->getSeries();
            $this->workOrderForm->loadFromEirAllDetails($this->idEir, 'box'); // Todos los detalles
            $workOrder = $this->workOrderForm->store();

            return "Orden {$workOrder->serie}-" . str_pad($workOrder->correlativo, 6, '0', STR_PAD_LEFT) . " creada.";
        }
    }

    /**
     * Guarda cambios de aprobación (solo para cotizaciones)
     * Actualiza WorkOrder con detalles APROBADOS únicamente
     */
    public function saveApprovalChanges()
    {
        if (!auth()->user()->can('manage_work.eir')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        try {
            // Validar que sea una cotización
            if (!$this->eir->quoted_repair) {
                throw new Exception('Esta función solo está disponible para reparaciones cotizadas.');
            }

            $allDetailIds = collect($this->details)->pluck('id')->toArray();
            $approvedIds = array_diff($allDetailIds, $this->rejectedDetails);
            $hasApprovedDetails = !empty($approvedIds);

            DB::transaction(function () use ($approvedIds, $hasApprovedDetails) {
                // Actualizar detalles aprobados
                if (!empty($approvedIds)) {
                    EirInspectionDetail::whereIn('id', $approvedIds)
                        ->where('eir_inspection_id', $this->idEir)
                        ->update(['approved' => true]);
                }

                // Actualizar detalles rechazados
                if (!empty($this->rejectedDetails)) {
                    EirInspectionDetail::whereIn('id', $this->rejectedDetails)
                        ->where('eir_inspection_id', $this->idEir)
                        ->update(['approved' => false]);
                }

                //actualizo el stimated_by
                EirInspection::find($this->idEir)->update([
                    'stimated_by' => auth()->id()
                ]);


                // SIEMPRE gestionar WorkOrder (sin importar si EIR está abierto o cerrado)
                $this->syncWorkOrderWithApprovals($hasApprovedDetails);
            });

            // Recargar detalles
            $this->loadDetails();

            $approvedCount = count($approvedIds);
            $rejectedCount = count($this->rejectedDetails);

            $message = "Detalles actualizados: {$approvedCount} aprobados, {$rejectedCount} rechazados.";

            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => $message,
                'icon' => 'success'
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Sincroniza WorkOrder con detalles aprobados (para cotizaciones)
     * Se ejecuta siempre que se cargan aprobados
     */
    private function syncWorkOrderWithApprovals(bool $hasApprovedDetails): void
    {
        $existingWorkOrder = WorkOrder::where('eir_inspection_id', $this->idEir)
            ->where('type', 'box')
            ->first();

        if ($existingWorkOrder) {
            // Validar que la orden esté en estado editable
            if ($existingWorkOrder->status && $existingWorkOrder->status !== 'borrador') {
                throw new Exception('No se puede actualizar. La orden de trabajo ya está cerrada o en proceso.');
            }

            //evaluo que no tenga trabajadores asignados:
            if ($existingWorkOrder->hasWorkers()) {
                throw new Exception('No se puede volver a cargar aprobaciones, la orden de trabajo ya cuenta con trabajadores');
            }

            if ($hasApprovedDetails) {
                // Actualizar con detalles aprobados únicamente
                $this->workOrderForm->setWorkOrder($existingWorkOrder);
                $this->workOrderForm->loadFromEir($this->idEir, 'box'); // Solo aprobados
                $this->workOrderForm->update();
            } else {
                // Sin aprobados, eliminar la orden
                $existingWorkOrder->labors()->delete();
                $existingWorkOrder->delete();
            }
        } else {
            if ($hasApprovedDetails) {
                // Crear nueva orden con aprobados
                $this->workOrderForm->getSeries();
                $this->workOrderForm->loadFromEir($this->idEir, 'box'); // Solo aprobados
                $this->workOrderForm->store();
            }
        }
    }

    /**
     * Reabre el EIR (mantiene la WorkOrder intacta)
     */
    public function void()
    {
        if (!auth()->user()->can('inspections.eir.void')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        try {
            $this->eir->void_inspection($this->idEir);
            $this->eirStatus = 'borrador';

            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => 'EIR reaperturado con éxito',
                'icon' => 'success'
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Resetear la selección
     */
    public function resetSelection()
    {
        $this->rejectedDetails = [];
    }
}
