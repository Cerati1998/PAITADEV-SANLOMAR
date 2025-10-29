<?php

namespace App\Observers;

use App\Models\inspections\EirInspectionDetail;
use Illuminate\Support\Facades\Storage;

class EirInspectionDetailObserver
{
     /**
     * Handle the EirInspectionDetail "deleting" event.
     */
    public function deleting(EirInspectionDetail $detail): void
    {
        // Eliminar todas las fotos del storage
        foreach ($detail->photos as $photo) {
            if (Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        }
    }
}
