<?php

namespace App\Livewire\Operations\Works;

use App\Livewire\Forms\WorkOrderForm;
use App\Models\Container;
use App\Models\inspections\EirInspection;
use App\Models\Provider;
use App\Models\works\WorkOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkOrderCreate extends Component
{
    public WorkOrderForm $workOrderForm;
    public $requireDetails = false;
    public $container;
    public $repair;

    #[Computed]
    public function providers()
    {
        return Provider::query()
            ->select(['id', 'numDoc', 'rznSocial'])
            ->get()
            ->map(function ($provider) {
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

    #[Computed]
    public function eirs()
    {
        return EirInspection::with(['trace.container'])
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

    public $openModal = false;

    public function updated($property, $value)
    {
        if ($property === 'workOrderForm.eir_inspection_id') {
            // Validar si el EIR ya tiene una orden de trabajo
            $workEirExists = WorkOrder::firstWhere('eir_inspection_id', $this->workOrderForm->eir_inspection_id);

            if ($workEirExists) {
                $this->reset($property);
                $this->dispatch('swal', [
                    'title' => 'Error!',
                    'text' => "El EIR seleccionado ya cuenta con orden de trabajo {$workEirExists->serie}-{$workEirExists->correlativo}",
                    'icon' => 'warning'
                ]);
                return;
            }
            $eirData = $this->workOrderForm->loadContainerAndRepair($value);
            $this->container = $eirData['container'];
            $this->repair = $eirData['repair'];
            $this->workOrderForm->type = 'box';
        }
    }


    public function render()
    {
        return view('livewire.operations.works.work-order-create');
    }

    public function mount()
    {
        $this->workOrderForm->started_at = now()->format('Y-m-d H:i');
        $this->workOrderForm->branch_id  = session('branch')->id;
        $this->workOrderForm->opened_by  = auth()->id();
        $this->workOrderForm->getSeries();
    }

    public function save()
    {
        try {
            // Validar que el container_id exista
            if (!$this->workOrderForm->container_id && $this->workOrderForm->type !== 'others') {
                $this->dispatch('swal', [
                    'title' => 'Error!',
                    'text' => 'Debe seleccionar un contenedor',
                    'icon' => 'error'
                ]);
                return;
            }

            // Guardar la orden de trabajo
            $this->workOrderForm->loadFromEir($this->workOrderForm->eir_inspection_id,false);
            //dd($this->workOrderForm->labors);
            $workOrder = $this->workOrderForm->store();

            // Incrementar el correlativo en la tabla branch_company_document
            DB::table('branch_company_document')
                ->where('branch_id', $this->workOrderForm->branch_id)
                ->where('document_id', $this->workOrderForm->tipoDoc)
                ->increment('correlativo');

            // Cerrar el modal
            $this->openModal = false;

            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => "Orden de trabajo {$workOrder->serie}-{$workOrder->correlativo} creada correctamente",
                'icon' => 'success'
            ]);

            // Resetear el formulario para la próxima creación
            $this->workOrderForm->reset();
            $this->reset(['container', 'requireDetails', 'repair']);

            // Reinicializar valores necesarios
            $this->mount();

            // Opcional: Refrescar la lista de órdenes de trabajo si estás en un componente padre
            $this->dispatch('workOrderCreated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('swal', [
                'title' => 'Error de validación',
                'text' => 'Por favor, complete todos los campos requeridos',
                'icon' => 'error'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Ocurrió un error al guardar: ' . $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }
}
