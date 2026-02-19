<?php

namespace App\Livewire\Operations\Inspections;

use App\Models\inspections\RoofEvidence;
use Livewire\Attributes\On;
use Livewire\Component;

class RoofEvidenceModal extends Component
{
    public int $eirId;
    public bool $openModal = false;

    public array $form = [
        'qty_welded_patch' => 0,
        'qty_riveted_patch' => 0,
        'qty_rail_patch' => 0,
        'qty_welded_cut' => 0,
        'qty_sealed_cut' => 0,
        'has_additional_damages' => 0,
    ];

    protected array $rules = [
        'form.qty_welded_patch' => 'required|integer|min:0',
        'form.qty_riveted_patch' => 'required|integer|min:0',
        'form.qty_rail_patch' => 'required|integer|min:0',
        'form.qty_welded_cut' => 'required|integer|min:0',
        'form.qty_sealed_cut' => 'required|integer|min:0',
        'form.has_additional_damages' => 'required|integer|min:0|max:1',
    ];

    // Escucha el evento que dispara EirEdit
    protected $listeners = ['openRoofEvidenceModal' => 'openModalRoof'];

    #[On('openRoofEvidenceModal')]
    public function openModalRoof(): void
    {
        $roof = RoofEvidence::where('eir_inspection_id', $this->eirId)->first();
        if ($roof) {
            $this->form = $roof->only(array_keys($this->form));
        } else {
            $this->resetForm();
        }
        //dd($this->form);

        $this->openModal = true;
    }

    public function save(): void
    {
        $this->validate();

        RoofEvidence::updateOrCreate(
            ['eir_inspection_id' => $this->eirId],
            $this->form
        );

        $this->openModal = false;
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Exito!',
            'text' => 'Evidencias de techo registrados correctamente',
        ]);
    }

    public function cancel(): void
    {
        $this->openModal = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->form = array_map(fn() => 0, $this->form);
    }

    public function render()
    {
        return view('livewire.operations.inspections.roof-evidence-modal');
    }
}
