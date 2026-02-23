<?php

namespace App\Livewire\Operations\Inspections;

use App\Models\inspections\EirInspection;
use App\Models\inspections\RoofEvidence;
use App\Services\inspections\RoofEvidencePhotoService;
use Exception;
use Livewire\Attributes\On;
use Livewire\Component;

class RoofEvidenceModal extends Component
{
    public int $eirId;
    public bool $openModal = false;
    public $eirStatus;

    public array $form = [
        'qty_welded_patch' => 0,
        'qty_riveted_patch' => 0,
        'qty_door_rail_patch' => 0,
        'qty_front_rail_patch' => 0,
        'qty_silicon_patch' => 0,
        'qty_welded_cut' => 0,
        'qty_sealed_cut' => 0,
        'has_additional_damages' => 0,
    ];
    /** Fotos actuales para mostrar en el modal: [['slot'=>'photo_1','url'=>'...'], ...] */
    public array $photos = [];
    protected array $rules = [
        'form.qty_welded_patch' => 'required|integer|min:0',
        'form.qty_riveted_patch' => 'required|integer|min:0',
        'form.qty_door_rail_patch' => 'required|integer|min:0',
        'form.qty_front_rail_patch' => 'required|integer|min:0',
        'form.qty_silicon_patch' => 'required|integer|min:0',
        'form.qty_welded_cut' => 'required|integer|min:0',
        'form.qty_sealed_cut' => 'required|integer|min:0',
        'form.has_additional_damages' => 'required|boolean',
    ];

    // Escucha el evento que dispara EirEdit
    protected $listeners = ['openRoofEvidenceModal' => 'openModalRoof'];

    #[On('openRoofEvidenceModal')]
    public function openModalRoof(?int $eirId = null): void
    {
        // Si no viene (desde EirEdit donde ya está montado con el prop), usa el que tiene
        if ($eirId !== null) {
            $this->eirId = $eirId;
        }

        $eir = EirInspection::with('roofEvidence')->find($this->eirId);
        $this->eirStatus = $eir->status;
        $roof = $eir->roofEvidence;

        if ($roof) {
            $this->form = $roof->only(array_keys($this->form));
            $this->loadPhotos($roof);
        } else {
            $this->resetForm();
            $this->photos = [];
        }

        $this->openModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->eirStatus === 'completado') {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acción no permitida',
                'text' => 'No se pueden modificar evidencias de techo de una inspección completada.',
            ]);
            return;
        }

        if (!auth()->user()->can('roofEvidence.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        RoofEvidence::updateOrCreate(
            ['eir_inspection_id' => $this->eirId],
            $this->form
        );

        //$this->openModal = false;
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Exito!',
            'text' => 'Evidencias de techo registrados correctamente',
        ]);
    }

    /**
     * Elimina una foto por slot. Llamado desde Alpine via @this.call('deletePhoto', slot)
     */
    public function deletePhoto(string $slot): bool
    {
        try {
            if (!auth()->user()->can('roofEvidence.photo.delete')) {
                throw new Exception("No tienes el permiso para ejecutar esta acción");
            }

            if ($this->eirStatus === 'completado') {
                throw new Exception("No se pueden modificar evidencias de techo de una inspección completada.");
            }

            $roof = RoofEvidence::where('eir_inspection_id', $this->eirId)->first();
            if (!$roof)
                return false;

            $service = app(RoofEvidencePhotoService::class);
            $service->deletePhoto($roof, $slot);
            $roof->refresh();
            $this->loadPhotos($roof);
            return true;
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al eliminar',
                'text' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Descarga una foto individual por slot.
     */
    public function downloadPhoto(string $slot): mixed
    {
        $roof = RoofEvidence::where('eir_inspection_id', $this->eirId)->first();

        if (!$roof || empty($roof->{$slot}))
            return null;

        $url = $roof->{$slot};
        $contents = file_get_contents($url);
        $filename = "roof_{$slot}_" . now()->format('Ymd_His') . '.jpg';

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, $filename);
    }

    /**
     * Descarga todas las fotos como ZIP.
     */
    public function downloadAllPhotos(): mixed
    {
        $roof = RoofEvidence::where('eir_inspection_id', $this->eirId)->first();

        if (!$roof)
            return null;

        $service = app(RoofEvidencePhotoService::class);

        try {
            $zipPath = $service->generateZip($roof);
            $filename = "roof_evidence_{$roof->id}_" . now()->format('Ymd_His') . '.zip';

            return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => $e->getMessage(),
            ]);
            return null;
        }
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

    private function loadPhotos(RoofEvidence $roof): void
    {
        $service = app(RoofEvidencePhotoService::class);
        $this->photos = $service->getPhotos($roof);
    }

    public function render()
    {
        return view('livewire.operations.inspections.roof-evidence-modal');
    }
}
