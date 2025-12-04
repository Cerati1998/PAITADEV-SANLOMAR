<?php

namespace App\Livewire;

use App\Models\Devolution;
use App\Imports\Devolution\ContainerImport;
use App\Models\Vessel;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB as FacadesDB;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class DevolutionCreate extends Component
{
    use WithFileUploads;

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

    public $vessels = [];
    public $openModal = false;

    public $attach = null;

    /*  public function updated($propertyName)
    {
        if ($propertyName === 'devolution.shipping_line') {
            $this->vessels = Vessel::where('shipping_line_id', $this->devolution['shipping_line'])->get()->toArray();
        }
    } */
    #[On('vesselExternAdded')]
    public function updateddevolutionShippingLineId($value)
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

    public function updatedDevolutionReturnedDate($value)
    {   
        $this->devolution['week'] = 'WK'. Carbon::parse($value)->isoWeek;
    }

    public function save()
    {
        if (!auth()->user()->can('devolution.upload')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        FacadesDB::beginTransaction();
        try {
            $this->validate([
                'attach' => 'nullable|max:10240|mimes:xls,xlsx,csv',
                'devolution.shipping_line_id' => 'required|numeric|exists:shipping_lines,id',
                'devolution.vessel_id' => 'required|numeric|exists:vessels,id',
                'devolution.client_id' => 'required|numeric|exists:clients,id',
                'devolution.custom_broker_id' => 'required|numeric|exists:custom_brokers,id',
                'devolution.returned_date' => 'required|date',
                'devolution.bl_number' => 'nullable|string|min:5',
                'devolution.regimen' => 'required|string|min:6|in:importacion,exportacion',
            ], [], [
                'attach' => 'Archivo excel de Descarga',
                'devolution.shipping_line_id' => 'Linea Naviera',
                'devolution.vessel_id' => 'Nave',
                'devolution.client_id' => 'Importador/Cliente',
                'devolution.custom_broker_id' => 'Agente de Aduana',
                'devolution.returned_date' => 'Fecha ETA',
                'devolution.bl_number' => 'Número de BL',
            ]);

            $newDevolution = Devolution::create($this->devolution);

            //Guardar archivo en disco
            if ($this->attach) {
                $filePath = $this->attach->store('imports','public');

                //importo los contenedores
                Excel::import(new ContainerImport($newDevolution->id, $this->devolution['shipping_line_id']), $filePath);
            }

            FacadesDB::commit();

            $this->reset('attach', 'devolution', 'openModal');
            $this->dispatch('devolutionAdded');
            $this->dispatch('swal', [
                'title' => 'Exito!',
                'text' => 'Devolución subida con Exito!',
                'icon' => 'success'
            ]);
        } catch (\Exception $e) {
            FacadesDB::rollBack();
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Error durante la importación: ' . $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.devolution-create');
    }
}
