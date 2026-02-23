<?php

namespace App\Livewire\Forms;

use App\Models\inspections\EirInspection;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EirForm extends Form
{
    public $tipoDoc = 10;
    public $started_at = null;
    public $closed_at = null;
    public $voided_at = null;
    public $serie = '';
    public $correlativo = '';
    public $normal_wash = false;
    public $quimic_wash = false;
    public $oxalic_wash = false;
    public $reason_quimic_wash = '';
    public $special_wash = false;
    public $reason_special_wash = '';
    public $fast_repair = false;
    public $normal_repair = false;
    public $quoted_repair = false;
    public $container_category = null;
    public $observations = '';
    public $status = 'borrador';
    public $trace_id;
    public $inspector_id;
    public $closed_by = null;
    public $voided_by = null;
    public $branch_id;

    public function rules()
    {
        return [
            'started_at' => ['required', 'date'],
            'closed_at' => ['nullable', 'date'],
            'serie' => [
                'required',
                'string',
                Rule::exists('branch_company_document', 'serie')
                    ->where(fn($query) => $query->where('branch_id', session('branch')->id))
            ],
            'correlativo' => ['required', 'numeric'],
            'trace_id' => ['required', Rule::exists('container_operational_traces', 'id')],
            'inspector_id' => ['required', 'numeric'],
            //'inspector_id' => ['required', Rule::exists('users', 'id')],
            'branch_id' => ['required', 'numeric'],
            //'branch_id' => ['required', Rule::exists('branches', 'id')],
            'reason_quimic_wash' => ['nullable', 'string', 'max:100', 'required_if:quimic_wash,true'],
            'reason_special_wash' => ['nullable', 'string', 'max:100', 'required_if:special_wash,true'],
        ];
    }

    public function messages()
    {
        return [
            'trace_id.required' => 'Es obligatorio seleccionar un Contenedor del Patio',
            'reason_quimic_wash.required_if' => 'El campo Motivo para lavado Quimico es obligatorio cuando Lavado Quimico es seleccionado.',
            'reason_special_wash.required_if' => 'El campo Motivo para lavado Especial es obligatorio cuando Lavado Especial es seleccionado.',
        ];
    }

    public function validationAttributes()
    {
        return [
            'started_at' => 'Fecha de Inicio de Inspección',
            'closed_at' => 'Fecha de Finalización de Inspección',
            'serie' => 'Serie de documento',
            'correlativo' => 'Correlativo de documento',
            'trace_id' => 'Contenedor en seguimiento',
            'inspector_id' => 'Encargado de Inspección',
            'branch_id' => 'Sucursal de operación',
            'reason_quimic_wash' => 'Motivo para lavado Quimico',
            'reason_special_wash' => 'Motivo para lavado Especial',
        ];
    }

    public function store()
    {
        $this->validate();

        try {
            $eir = EirInspection::create($this->except('details', 'tipoDoc'));
            return $eir->id;
        } catch (\Exception $e) {
            throw new \Exception('Error al guardar EIR: ' . $e->getMessage());
        }
    }

    public function close_inspection($eirInspection, $statusBox)
    {
        try {
            //valido que si es DM especifiquen el tipo de reparacion
            if ($statusBox === 'DM' && (!$this->normal_repair && !$this->fast_repair && !$this->quoted_repair)) {
                throw new Exception("Debes escoger un tipo de reparacion si el Status de la estructura es INOPERATIVO");
            }
            $this->status = 'completado';
            $this->closed_by = auth()->id();
            $this->closed_at = Carbon::now();
            EirInspection::findOrFail($eirInspection)
                ->update($this->only('status', 'fast_repair', 'normal_repair', 'quoted_repair', 'closed_by','closed_at'));
        } catch (\Exception $e) {
            throw new \Exception('Error al guardar EIR: ' . $e->getMessage());
        }
    }
    public function void_inspection($eirInspection)
    {
        try {
            $this->status = 'borrador';
            $this->voided_at = Carbon::now();
            $this->voided_by = auth()->id();
            EirInspection::findOrFail($eirInspection)->update($this->only('status', 'voided_at', 'voided_by'));
        } catch (\Exception $e) {
            throw new \Exception('Error al guardar EIR: ' . $e->getMessage());
        }
    }
}
