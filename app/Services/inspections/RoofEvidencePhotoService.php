<?php

namespace App\Services\inspections;

use App\Models\inspections\RoofEvidence;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RoofEvidencePhotoService
{
    public string $getDisk = 'do';

    /**
     * Guarda una foto en el slot disponible (photo_1 ... photo_4)
     * Retorna el array ['slot' => 'photo_1', 'url' => '...'] o lanza excepción si no hay slots.
     */
    public function savePhoto(RoofEvidence $roofEvidence, $file): array
    {
        $slot = $this->getAvailableSlot($roofEvidence);

        if (!$slot) {
            throw new \RuntimeException('No hay slots de foto disponibles (máximo 4)');
        }

        $disk  = $this->getDisk;
        $folder = "inspections/eir/{$roofEvidence->eir_inspection_id}/roof";
        $path  = $file->store($folder, $disk);
        $url   = Storage::disk($disk)->url($path);

        $roofEvidence->update([$slot => $url]);

        return [
            'slot' => $slot,
            'url'  => $url,
        ];
    }

    /**
     * Elimina la foto de un slot específico (photo_1 ... photo_4)
     */
    public function deletePhoto(RoofEvidence $roofEvidence, string $slot): bool
    {
        if (!in_array($slot, ['photo_1', 'photo_2', 'photo_3', 'photo_4'])) {
            throw new \InvalidArgumentException("Slot inválido: {$slot}");
        }

        $url = $roofEvidence->{$slot};

        if ($url) {
            $relativePath = ltrim(parse_url($url, PHP_URL_PATH), '/');
            if (Storage::disk($this->getDisk)->exists($relativePath)) {
                Storage::disk($this->getDisk)->delete($relativePath);
            }
        }

        $roofEvidence->update([$slot => null]);

        return true;
    }

    /**
     * Genera un ZIP con todas las fotos de la evidencia de techo
     */
    public function generateZip(RoofEvidence $roofEvidence): string
    {
        $photos = $this->getPhotos($roofEvidence);

        if (empty($photos)) {
            throw new \RuntimeException('No hay fotos para comprimir');
        }

        $zipFileName = "roof_evidence_{$roofEvidence->id}_photos.zip";
        $zipPath     = storage_path("app/temp/{$zipFileName}");
        $disk        = $this->getDisk;

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("No se pudo crear el ZIP");
        }

        foreach ($photos as $photo) {
            $relativePath = ltrim(parse_url($photo['url'], PHP_URL_PATH), '/');
            if (Storage::disk($disk)->exists($relativePath)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'roof_photo_');
                file_put_contents($tempFile, Storage::disk($disk)->get($relativePath));
                $zip->addFile($tempFile, "{$photo['slot']}_" . basename($relativePath));
                register_shutdown_function(fn() => file_exists($tempFile) && unlink($tempFile));
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Retorna array de fotos existentes con slot y URL
     */
    public function getPhotos(RoofEvidence $roofEvidence): array
    {
        $photos = [];
        foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4'] as $slot) {
            if (!empty($roofEvidence->{$slot})) {
                $photos[] = [
                    'slot' => $slot,
                    'url'  => $roofEvidence->{$slot},
                ];
            }
        }
        return $photos;
    }

    /**
     * Retorna el primer slot disponible o null si todos están ocupados
     */
    private function getAvailableSlot(RoofEvidence $roofEvidence): ?string
    {
        foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4'] as $slot) {
            if (empty($roofEvidence->{$slot})) {
                return $slot;
            }
        }
        return null;
    }

    /**
     * Valida que el archivo sea una imagen válida
     */
    public function validateImage($file, int $maxSizeKB = 10048): bool
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException("El archivo no es válido");
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
            throw new \InvalidArgumentException("El archivo debe ser una imagen (JPG, PNG, WEBP)");
        }

        if ($file->getSize() > ($maxSizeKB * 1024)) {
            throw new \InvalidArgumentException("La imagen excede el tamaño máximo permitido");
        }

        return true;
    }
}