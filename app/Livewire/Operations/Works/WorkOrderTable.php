<?php

namespace App\Livewire\Operations\Works;

use App\Livewire\Forms\WorkOrderForm;
use App\Models\inspections\EirInspection;
use App\Models\Provider;
use App\Models\works\LaborWorker;
use Exception;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\works\WorkOrder;
use App\Models\works\WorkOrderLabor;
use App\Models\works\WorkOrderLaborImage;
use App\Services\inspections\EirPhotoService;
use App\Services\WorkOrders\WorkLaborPhotoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class WorkOrderTable extends DataTableComponent
{

    use WithFileUploads;

    public $type;
    public WorkOrderForm $workOrderForm;
    public $container;
    public $repair;
    public $openEditModal = false;
    public $opendetailWorkOrderModal = false;

    public $workersModal = false;
    public $workOrderId;

    /**
     * Obtiene el servicio de fotos (propiedad computada)
     */
    protected function getPhotoService(): WorkLaborPhotoService
    {
        return app(WorkLaborPhotoService::class);
    }


    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['works.orders.edit']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('works.orders.actions', ['workorder' => $row]);
                }),
            Column::make("Inicio")
                ->label(function ($row) {
                    return $row->started_at_date . ' ' . $row->started_at_time;
                })
                ->sortable(),
            Column::make("Termino")
                ->label(function ($row) {
                    return "<div class='text-center'> 
                    {$row->closed_at_date}  {$row->closed_at_time}
                    </div>";
                })
                ->html(),
            Column::make("Estado", "work_orders.status")
                ->label(function ($row) {
                    $icon = match ($row->status) {
                        'completado' => '<i class="text-xl fas fa-circle-check text-green-500" title="Culminado"></i>',
                        'borrador' => '<i class="text-xl fas fa-spinner text-red-500" title="Borrador"></i>',
                        default => ''
                    };
                    return '<div class="flex justify-center">' . $icon . '</div>';
                })->html(),

            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlativo", "correlativo")
                ->format(function ($value) {
                    return str_pad($value, 6, '0', STR_PAD_LEFT);
                })
                ->sortable(),
            Column::make('Tipo', 'type')
                ->label(function ($row) {
                    return "<img class='h-8' src='$row->type_formatted'/>";
                })->html(),
            Column::make("Estimado", "estimate_number")
                ->searchable(),
            Column::make("Nº EIR", "eir_inspection_id")
                ->sortable()
                ->format(function ($value, $row) {
                    if ($row->eirInspection) {
                        $correlativo_eir = str_pad($row->eirInspection->correlativo, 6, 0, STR_PAD_LEFT);
                        $url = route('inspections.eir.pdf', $row->eirInspection->id);
                        return '
                            <a href="' . $url . '" target="_blank">
                            <span class="text-sm font-semibold text-blue-500">
                            ' . $row->eirInspection->serie . '-' . $correlativo_eir . '
                            </span>
                            </a>';
                    }
                    return '<span class="text-gray-400">-</span>';
                })
                ->html(),
            Column::make("Contenedor", "container.code")
                ->searchable()
                ->sortable(),
            Column::make("Supervisor", "openedBy.name")
                ->sortable(),
        ];
    }

    #[On('workOrderCreated')]
    public function builder(): Builder
    {
        return WorkOrder::query()
            ->with([
                'container:id,code',
                'openedBy:id,name',
                'closedBy:id,name',
                'eirInspection:id,serie,correlativo'
            ])
            ->addSelect(['work_orders.*'])
            //->where('work_orders.type', $this->type)
            ->where('branch_id', session('branch')->id);
    }
    public function filters(): array
    {
        $branchId = session('branch')->id;

        return [
            DateRangeFilter::make('Fecha Creado')
                ->config([
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => 'Introduzca el rango de fechas', // A placeholder value
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder
                        ->whereDate('work_orders.started_at', '>=', $dateRange['minDate']);
                    //->orWhereDate('work_orders.started_at', '<=', $dateRange['maxDate']);
                }),
            SelectFilter::make('Tipo')
                ->options(
                    [
                        '' => 'Todos',
                        'box' => 'Estructura',
                        'machine' => 'Equipo de Frío',
                        'others' => 'Otros'

                    ]
                )
                ->filter(function (Builder $builder, $status) {
                    if (!empty($status)) {
                        $builder->where('work_orders.type', $status);
                    }
                }),
            SelectFilter::make('Estado')
                ->options(
                    ['' => 'Todos'] + WorkOrder::query()
                        ->where('branch_id', $branchId)
                        ->distinct()
                        ->pluck('work_orders.status', 'status')
                        ->filter() // Elimina valores null/empty
                        ->toArray()
                )
                ->filter(function (Builder $builder, $status) {
                    if (!empty($status)) {
                        $builder->where('work_orders.status', $status);
                    }
                })

            /* SelectFilter::make('Contenedor')
                ->options(
                    ['Todos' => ''] + Container::query()
                        ->whereHas('gateInDetails.containerOperationalTrace.EirInspection')
                        ->whereHas('gateInDetails', function ($gateQuery) use ($branchId) {
                            $gateQuery->where(function ($originQuery) use ($branchId) {
                                // Para Descarga (Dischargue)
                                $originQuery->whereHasMorph('originable', ['App\Models\Dischargue'], function ($dischargueQuery) use ($branchId) {
                                    $dischargueQuery->where('branch_id', $branchId);
                                })
                                    // Para Devolución (Devolution)
                                    ->orWhereHasMorph('originable', ['App\Models\Devolution'], function ($devolutionQuery) use ($branchId) {
                                        $devolutionQuery->where('branch_id', $branchId);
                                    });
                            });
                        })
                        ->pluck('code', 'id')
                        ->toArray()
                )
                ->filter(function (Builder $builder, $containerId) {
                    if (!empty($containerId)) {
                        $builder->whereHas('trace', function ($traceQuery) use ($containerId) {
                            $traceQuery->where('container_id', $containerId);
                        });
                    }
                }) */
        ];
    }

    #[Computed]
    public function providers()
    {
        return Provider::all()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->numDoc,
                'description' => $provider->rznSocial,
            ];
        });
    }

    #[Computed]
    public function types()
    {
        return [
            ['value' => 'box', 'label' => 'Estructura'],
            ['value' => 'machine', 'label' => 'Equipo de Frío'],
            ['value' => 'others', 'label' => 'Otros'],
        ];
    }

    #[Computed()]
    public function eirs()
    {
        return EirInspection::with(['trace'])
            ->where('eir_inspections.status', 'completado')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($eir_inspection) {
                return [
                    'id' => $eir_inspection->id,
                    'name' => $eir_inspection->serie . '-' . str_pad($eir_inspection->correlativo, 6, 0, STR_PAD_LEFT),
                    'description' => $eir_inspection->trace->container->code,
                ];
            });
    }



    public function edit(WorkOrder $workOrder)
    {
        if (!auth()->user()->can('manage_work.edit')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        // Cargar todos los datos de la orden
        $this->workOrderForm->setWorkOrder($workOrder);
        $this->repair = $this->workOrderForm->loadRepair($workOrder->eir_inspection_id);
        $this->container = $this->workOrderForm->loadContainer($workOrder->eir_inspection_id);
        $this->workOrderId = $workOrder->id;
        $this->openEditModal = true;
    }

    public function editLabor(WorkOrderLabor $workOrderLabor)
    {
        try {
            $this->workOrderForm->loadLaborForEdit($workOrderLabor->id);
            $this->opendetailWorkOrderModal = true;
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function updatedWorkOrderFormDetailWorkOrderModalDamageId($value)
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        $this->workOrderForm->fillDetailFromDamage($value);
    }

    public function resetea()
    {
        // Limpiar el formulario completamente
        $this->workOrderForm->reset();          // Limpia TODO
        $this->workOrderForm->workOrder = null; // Resetea la orden
        $this->workOrderForm->labors = [];      // Limpia labores

        $this->reset(['container', 'repair']);

        $this->openEditModal = false;
    }

    public function reseteaLabor()
    {
        // Limpiar el formulario de edicion de trabajo completamente
        //$this->workOrderForm->opendetailWorkOrderModal = false;
        $this->reset('workOrderForm.detailWorkOrderModal', 'workOrderForm.editingLaborId');
    }

    public function destroy(WorkOrder $workOrder)
    {

        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        $workOrder->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Orden eliminada correctamente',
            'icon' => 'success'
        ]);
    }
    public function destroyLabor(WorkOrderLabor $workOrderLabor)
    {
        if (!auth()->user()->can('manage_work.labors')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        $workOrderLabor->delete();
        // Refrescar labores en el formulario
        $this->workOrderForm->refreshLabors();
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Labor eliminada correctamente',
            'icon' => 'success'
        ]);
    }

    /**
     * Guardar el detalle seleccionado
     * Sirve para crear y actualizar
     */
    public function saveDetail()
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        // Validar campos básicos
        $this->validate([
            'workOrderForm.detailWorkOrderModal.damage_id' => 'required',
            'workOrderForm.detailWorkOrderModal.description' => 'nullable|string|max:500',
            'workOrderForm.detailWorkOrderModal.hours' => 'nullable|numeric|min:0',
        ], [
            'workOrderForm.detailWorkOrderModal.damage_id.required' => 'Debes seleccionar un daño',
        ]);

        try {
            // Verificar si estamos EDITANDO o CREANDO
            if ($this->workOrderForm->editingLaborId) {
                //  MODO EDICIÓN: Actualizar labor existente
                $labor = WorkOrderLabor::find($this->workOrderForm->editingLaborId);

                if (!$labor) {
                    throw new \Exception('Labor no encontrado');
                }

                $labor->update([
                    'description' => $this->workOrderForm->detailWorkOrderModal['description'],
                    'started_at' => $this->workOrderForm->detailWorkOrderModal['started_at'],
                    'finished_at' => $this->workOrderForm->detailWorkOrderModal['finished_at'],
                    'hours' => $this->workOrderForm->detailWorkOrderModal['hours'] ?? 0,
                    // NO actualizamos qty ni eir_inspection_detail_id (son del EIR)
                ]);

                $message = 'Labor actualizado correctamente';
            } else {
                //  MODO CREACIÓN: Crear nuevo labor
                WorkOrderLabor::create([
                    'work_order_id' => $this->workOrderForm->workOrder->id,
                    'eir_inspection_detail_id' => $this->workOrderForm->detailWorkOrderModal['eir_inspection_detail_id'],
                    'qty' => $this->workOrderForm->detailWorkOrderModal['qty'],
                    'description' => $this->workOrderForm->detailWorkOrderModal['description'],
                    'started_at' => $this->workOrderForm->detailWorkOrderModal['started_at'],
                    'finished_at' => $this->workOrderForm->detailWorkOrderModal['finished_at'],
                    'hours' => $this->workOrderForm->detailWorkOrderModal['hours'] ?? 0,
                ]);

                $message = 'Labor agregado correctamente';
            }

            // Refrescar labores en el formulario
            $this->workOrderForm->refreshLabors();

            $this->dispatch('swal', [
                'title' => 'Éxito!',
                'text' => $message,
                'icon' => 'success'
            ]);

            // Cerrar modal y limpiar
            $this->opendetailWorkOrderModal = false;
            $this->workOrderForm->resetDetailModal();
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function update()
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        $this->workOrderForm->updateHeader();
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Encabezado actualizado correctamente',
            'icon' => 'success'
        ]);
    }

    public function editWorkerLabor(WorkOrderLabor $workOrderLabor)
    {
        $this->workOrderForm->detailWorkOrderModal['id'] = $workOrderLabor->id;
        $this->workOrderForm->detailLaborWorker['work_order_labor_id'] = $workOrderLabor->id;
        $this->workOrderForm->loadLaborForEdit($workOrderLabor->id);
        $this->workOrderForm->loadWorkersFromDetail($workOrderLabor->id);
        $this->workersModal = true;

        //dd($this->workOrderForm->workers);
    }

    /**
     * Guardar el trabajador asignado
     */
    public function saveDetailWorker()
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        // Validar campos básicos
        $this->validate([
            'workOrderForm.detailLaborWorker.work_order_labor_id' => 'required|numeric|exists:work_order_labors,id',
            'workOrderForm.detailLaborWorker.worker_id' => 'required|numeric|exists:workers,id',
            'workOrderForm.detailLaborWorker.task_description' => 'nullable|string|max:500',
            'workOrderForm.detailLaborWorker.main_worker' => 'nullable|boolean',
        ], [
            'workOrderForm.detailLaborWorker.worker_id.required' => 'Debes seleccionar un Trabajador',
        ]);

        try {
            // Verificar si estamos EDITANDO o CREANDO
            //  MODO EDICIÓN: Actualizar labor existente
            $labor = WorkOrderLabor::find($this->workOrderForm->detailLaborWorker['work_order_labor_id']);

            if (!$labor) {
                throw new \Exception('Labor no encontrado');
            }
            //verifico que el labor no tenga fecha de cierre
            if ($labor->finished_at) {
                throw new \Exception('El trabajo ya cuenta con fecha de cierre');
            }

            //verifico si el trabajador ya ha sido asignado
            $workerExists = LaborWorker::query()
                ->where('worker_id', $this->workOrderForm->detailLaborWorker['worker_id'])
                ->where('work_order_labor_id', $this->workOrderForm->detailLaborWorker['work_order_labor_id'])->count();

            if ($workerExists > 0) {
                throw new \Exception("El trabajador ya ha sido asignado a este trabajo");
            }

            //verifico si main_worker ha sido marcado para verificar que no haya ningun trabajador ya con esa asignacion
            if ($this->workOrderForm->detailLaborWorker['main_worker']) {

                $workerExists = LaborWorker::query()
                    ->where('work_order_labor_id', $this->workOrderForm->detailLaborWorker['work_order_labor_id'])
                    ->where('main_worker', true)->count();

                if ($workerExists > 0) {
                    throw new \Exception("El trabajo ya cuenta con un trabajador como encargado Principal");
                }
            }

            LaborWorker::create($this->workOrderForm->detailLaborWorker);
            //dd($this->workOrderForm->detailWorkOrderModal);
            //recargo los trabajos
            $this->workOrderForm->loadWorkersFromDetail($this->workOrderForm->detailWorkOrderModal['id']);

            // Refrescar labores en el formulario
            //$this->workOrderForm->reset('detailLaborWorker');

            $this->dispatch('swal', [
                'title' => 'Éxito!',
                'text' => "Trabajador asignado correctamente a trabajo {$labor->description}",
                'icon' => 'success'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function updatedWorkOrderFormDetailWorkOrderModalStartedAt($value)
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        if ($value) {
            WorkOrderLabor::findOrFail($this->workOrderForm->detailWorkOrderModal['id'])->update([
                'started_at' => $value
            ]);

            $this->dispatch('swal', [
                'title' => 'Exito!',
                'text' => 'Hora de inicio actualizada!',
                'icon' => 'success'
            ]);
        }
    }

    public function updatedWorkOrderFormDetailWorkOrderModalFinishedAt($value)
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        if ($value) {
            try {
                $workOrderLaborId = $this->workOrderForm->detailWorkOrderModal['id'] ?? null;

                if (!$workOrderLaborId) {
                    throw new Exception("No se ha encontrado la labor actual.");
                }

                $workOrderLabor = WorkOrderLabor::findOrFail($workOrderLaborId);

                // Validar existencia de hora de inicio
                if (!$workOrderLabor->started_at) {
                    throw new Exception("Debes registrar la hora de inicio antes de la de cierre.");
                }

                $startedAt = Carbon::parse($workOrderLabor->started_at);
                $finishedAt = Carbon::parse($value);

                // Validar que la hora de cierre sea posterior a la de inicio
                if ($finishedAt->lessThanOrEqualTo($startedAt)) {
                    throw new Exception("La hora de cierre debe ser mayor que la hora de inicio.");
                }

                // Calcular diferencia en horas con decimales
                //$hours = round($finishedAt->diffInMinutes($startedAt) / 60, 2);
                $hours = $startedAt->diffInHours($finishedAt);


                // Guardar cambios
                $workOrderLabor->update([
                    'finished_at' => $finishedAt,
                    'hours' => round($hours, 2),
                ]);

                // Actualizar en el formulario Livewire (si deseas reflejarlo visualmente)
                $this->workOrderForm->detailWorkOrderModal['hours'] = round($hours, 2);

                // Notificación
                $this->dispatch('swal', [
                    'title' => 'Éxito!',
                    'text' => "Fecha de cierre registrada correctamente. Total: {$hours} horas.",
                    'icon' => 'success',
                ]);
            } catch (\Exception $e) {
                // Limpiar valor si hubo error
                $this->workOrderForm->detailWorkOrderModal['finished_at'] = null;

                $this->dispatch('swal', [
                    'title' => 'Error!',
                    'text' => $e->getMessage(),
                    'icon' => 'warning',
                ]);
            }
        }
    }


    public function destroyLaborWorker(LaborWorker $laborWorker)
    {
        if (!auth()->user()->can('manage_work.workers')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar/eliminar la orden, ya se encuentra cerrada',
                'icon' => 'error'
            ]);
            return;
        }

        $laborWorker->delete();
        // Refrescar labores en el formulario
        $this->workOrderForm->loadWorkersFromDetail($laborWorker->work_order_labor_id);
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Trabajador removido correctamente',
            'icon' => 'success'
        ]);
    }

    /**
     * Cierra la orden de trabajo desde el componente
     */
    public function closeWorkOrder()
    {
        if (!auth()->user()->can('manage_work.status')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        try {
            // Validar permisos si es necesario
            // $this->authorize('close', $this->workOrderForm->workOrder);

            // Llamar al método del form
            $result = $this->workOrderForm->close_order();

            // Emitir evento de éxito
            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => $result['message'],
                'icon' => 'success'
            ]);

            // Refrescar tabla o redirigir
            $this->dispatch('workOrderClosed');
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }
    /**
     * Reapertura la orden
     * @return void
     */
    public function voidWorkOrder()
    {
        try {
            // Validar permisos si es necesario
            // $this->authorize('close', $this->workOrderForm->workOrder);

            // Llamar al método del form
            WorkOrder::findOrFail($this->workOrderId)->update([
                'status' => 'borrador'
            ]);

            $this->workOrderForm->status = 'borrador';

            // Emitir evento de éxito
            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => 'Orden de trabajo reaperturada',
                'icon' => 'success'
            ]);

            // Refrescar tabla o redirigir
            $this->dispatch('workOrderClosed');
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }




    // Propiedades para manejo de fotos
    public $openModalLaborPhoto = false;
    public $photosLabor = [];
    public $currentLaborId = null;
    public $newPhotoLabor = null;

    /**
     * Descarga todas las fotos de la orden de trabajo en ZIP
     */
    public function downloadWorkOrderPhotos(WorkOrder $workOrder)
    {
        try {
            $zipPath = $this->getPhotoService()->generateWorkOrderPhotosZip($workOrder);
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
     * Descarga fotos de un labor específico en ZIP
     */
    public function downloadLaborPhotos()
    {
        try {
            $labor = WorkOrderLabor::findOrFail($this->currentLaborId);
            $zipPath = $this->getPhotoService()->generateLaborPhotosZip($labor);
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
     * Abre el modal de fotos para un labor específico
     */
    public function loadPhotoLabor(WorkOrderLabor $workOrderLabor)
    {
        $this->dispatch('photosUpdated');
        $this->photosLabor = $workOrderLabor->photos;
        $this->currentLaborId = $workOrderLabor->id;
        $this->openModalLaborPhoto = true;
    }

    /**
     * Se ejecuta automáticamente cuando se sube una foto
     */
    public function updatedNewPhotoLabor()
    {
        if (!$this->ensureDraft()) return;

        $this->attachLaborPhoto();
    }

    /**
     * Valida que la orden esté en borrador
     */
    public function ensureDraft(): bool
    {
        if ($this->workOrderForm->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No puedes modificar una orden cerrada',
                'icon' => 'error'
            ]);
            return false;
        }
        return true;
    }

    /**
     * Adjunta una foto al labor actual
     */
    /*  public function attachLaborPhoto()
    {
        try {
            // Validar la imagen
            $this->getPhotoService()->validateImage($this->newPhotoLabor);

            // Obtener el labor
            $labor = WorkOrderLabor::findOrFail($this->currentLaborId);

            // Guardar la foto
            $this->getPhotoService()->saveLaborPhoto($labor, $this->newPhotoLabor);

            // Recargar las fotos
            $this->photosLabor = $labor->fresh()->photos;
            $this->newPhotoLabor = null;

            $this->dispatch('swal', [
                'title' => 'Éxito!',
                'text' => 'Foto subida correctamente',
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

    /**
     * Descarga una foto individual
     */
    public function downloadLaborPhoto($photoId)
    {
        try {
            $photo = WorkOrderLaborImage::findOrFail($photoId);

            // Si el path es una URL completa, obtener solo el path relativo
            $relativePath = parse_url($photo->path, PHP_URL_PATH);
            $relativePath = ltrim($relativePath, '/');

            // Verificar que el archivo existe
            if (!Storage::disk($this->getPhotoService()->getDisk)->exists($relativePath)) {
                $this->dispatch('swal', [
                    'title' => 'Error!',
                    'text' => 'La foto no existe en el almacenamiento',
                    'icon' => 'error'
                ]);
                return;
            }

            // Descargar el archivo
            $file = Storage::disk($this->getPhotoService()->getDisk)->get($relativePath);
            $fileName = basename($relativePath);

            return response()->streamDownload(function () use ($file) {
                echo $file;
            }, $fileName);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se pudo descargar la foto: ' . $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Elimina una foto del labor
     */
    public function deleteLaborPhoto($photoId)
    {
        if (!auth()->user()->can('manage_work.photos.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if (!$this->ensureDraft()) return;

        try {
            $this->getPhotoService()->deleteLaborPhoto($photoId);

            // Recargar las fotos
            $this->photosLabor = WorkOrderLabor::find($this->currentLaborId)->photos;

            $this->dispatch('swal', [
                'title' => 'Eliminado!',
                'text' => 'Foto eliminada correctamente',
                'icon' => 'success'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se pudo eliminar la foto',
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Cierra el modal de fotos
     */
    public function closePhotoModal()
    {
        $this->openModalLaborPhoto = false;
        $this->photosLabor = [];
        $this->currentLaborId = null;
        $this->newPhotoLabor = null;
    }

    /**
     * Obtiene estadísticas de fotos de una orden
     */
    public function getPhotoStats(WorkOrder $workOrder)
    {
        return $this->getPhotoService()->getPhotoStatistics($workOrder);
    }
}
