<?php

namespace App\Services\WorkOrders;

use App\Models\works\WorkOrder;
use App\Models\works\WorkOrderLabor;
use App\Models\works\WorkOrderLaborImage;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class WorkLaborPhotoService
{
    /**
     * Disco de almacenamiento (DigitalOcean Spaces)
     */
    public string $getDisk = 'do';

    /**
     * Genera un archivo ZIP con todas las fotos de una orden de trabajo
     *
     * @param WorkOrder $workOrder
     * @return string Path del archivo ZIP generado
     * @throws \RuntimeException
     */
    public function generateWorkOrderPhotosZip(WorkOrder $workOrder): string
    {
        $photos = [];

        foreach ($workOrder->labors as $labor) {
            $laborPhotos = $labor->photos->pluck('path')->toArray();
            $photos = array_merge($photos, $laborPhotos);
        }

        $zipFileName = "work_order_{$workOrder->serie}_{$workOrder->correlativo}_photos.zip";

        return $this->createZipFromPaths($photos, $zipFileName);
    }

    /**
     * Genera un archivo ZIP con las fotos de un labor específico
     *
     * @param WorkOrderLabor $labor
     * @return string Path del archivo ZIP generado
     * @throws \RuntimeException
     */
    public function generateLaborPhotosZip(WorkOrderLabor $labor): string
    {
        $photos = $labor->photos->pluck('path')->toArray();
        $zipFileName = "labor_{$labor->id}_photos.zip";

        return $this->createZipFromPaths($photos, $zipFileName);
    }

    /**
     * Crea un archivo ZIP a partir de un array de rutas de fotos
     *
     * @param array $photoPaths Array de rutas de archivos
     * @param string $zipFileName Nombre del archivo ZIP a generar
     * @return string Path completo del ZIP generado
     * @throws \RuntimeException
     */
    public function createZipFromPaths(array $photoPaths, string $zipFileName): string
    {
        $zipPath = storage_path("app/temp/{$zipFileName}");
        $disk = $this->getDisk;

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("No se pudo crear el archivo ZIP: {$zipFileName}");
        }

        $addedFiles = 0;

        foreach ($photoPaths as $photoPath) {
            if (empty($photoPath)) continue;

            // Si se guardó una URL completa, obtener solo el path relativo
            $relativePath = parse_url($photoPath, PHP_URL_PATH);
            $relativePath = ltrim($relativePath, '/');

            if (Storage::disk($disk)->exists($relativePath)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'work_photo_');
                file_put_contents($tempFile, Storage::disk($disk)->get($relativePath));

                $zip->addFile($tempFile, basename($relativePath));
                $addedFiles++;

                register_shutdown_function(function () use ($tempFile) {
                    if (file_exists($tempFile)) unlink($tempFile);
                });
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            unlink($zipPath);
            throw new \RuntimeException("No se encontraron fotos válidas para comprimir");
        }

        return $zipPath;
    }

    /**
     * Guarda una foto de labor (y almacena URL completa)
     *
     * @param WorkOrderLabor $labor
     * @param \Illuminate\Http\UploadedFile $file
     * @return string URL de la foto guardada
     */
    public function saveLaborPhoto(WorkOrderLabor $labor, $file): string
    {
        $disk = $this->getDisk;
        $workOrder = $labor->workOrder;
        $folder = "work_orders/{$workOrder->id}/labors/{$labor->id}";
        $path = $file->store($folder, $disk);

        // Generar URL pública completa
        $url = Storage::disk($disk)->url($path);

        $labor->photos()->create([
            'path' => $url,
        ]);

        return $url;
    }

    /**
     * Guarda múltiples fotos de labor
     *
     * @param WorkOrderLabor $labor
     * @param array $files Array de UploadedFile
     * @return array Array de URLs donde se guardaron las fotos
     */
    public function saveLaborPhotos(WorkOrderLabor $labor, array $files): array
    {
        $urls = [];

        foreach ($files as $file) {
            $urls[] = $this->saveLaborPhoto($labor, $file);
        }

        return $urls;
    }

    /**
     * Elimina una foto de labor (toma en cuenta URL completa)
     *
     * @param int $photoId
     * @return bool
     */
    public function deleteLaborPhoto(int $photoId): bool
    {
        $disk = $this->getDisk;
        $photo = WorkOrderLaborImage::findOrFail($photoId);

        // Si el path es una URL, obtener el path relativo
        $relativePath = parse_url($photo->path, PHP_URL_PATH);
        $relativePath = ltrim($relativePath, '/');

        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }

        return $photo->delete();
    }

    /**
     * Elimina todas las fotos de un labor
     *
     * @param WorkOrderLabor $labor
     * @return int Cantidad de fotos eliminadas
     */
    public function deleteAllLaborPhotos(WorkOrderLabor $labor): int
    {
        $disk = $this->getDisk;
        $deleted = 0;

        foreach ($labor->photos as $photo) {
            // Si el path es una URL, obtener el path relativo
            $relativePath = parse_url($photo->path, PHP_URL_PATH);
            $relativePath = ltrim($relativePath, '/');

            if (Storage::disk($disk)->exists($relativePath)) {
                Storage::disk($disk)->delete($relativePath);
            }
            $photo->delete();
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Obtiene la URL pública de una foto
     *
     * @param string|null $path
     * @return string|null
     */
    public function getPhotoUrl(?string $path): ?string
    {
        if (!$path) return null;

        // Si ya es una URL completa, la devolvemos
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $disk = $this->getDisk;
        return Storage::disk($disk)->url($path);
    }

    /**
     * Obtiene todas las URLs de fotos de un labor
     *
     * @param WorkOrderLabor $labor
     * @return array
     */
    public function getLaborPhotoUrls(WorkOrderLabor $labor): array
    {
        return $labor->photos->map(function ($photo) {
            return [
                'id' => $photo->id,
                'url' => $this->getPhotoUrl($photo->path),
                'path' => $photo->path,
            ];
        })->toArray();
    }

    /**
     * Valida que un archivo sea una imagen válida
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $maxSizeKB Tamaño máximo en KB (default: 10MB)
     * @return bool
     * @throws \InvalidArgumentException
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
            throw new \InvalidArgumentException("La imagen excede el tamaño máximo de {$maxSizeKB}KB");
        }

        return true;
    }

    /**
     * Valida múltiples imágenes
     *
     * @param array $files
     * @param int $maxSizeKB
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateImages(array $files, int $maxSizeKB = 10048): bool
    {
        foreach ($files as $file) {
            $this->validateImage($file, $maxSizeKB);
        }

        return true;
    }

    /**
     * Limpia archivos ZIP temporales antiguos
     *
     * @param int $hoursOld Antigüedad en horas (default: 24)
     * @return int Cantidad de archivos eliminados
     */
    public function cleanOldZipFiles(int $hoursOld = 24): int
    {
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) return 0;

        $patterns = [
            "{$tempPath}/work_order_*_photos.zip",
            "{$tempPath}/labor_*_photos.zip"
        ];

        $deleted = 0;
        $cutoffTime = now()->subHours($hoursOld)->timestamp;

        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Cuenta el total de fotos en una orden de trabajo
     *
     * @param WorkOrder $workOrder
     * @return int
     */
    public function countWorkOrderPhotos(WorkOrder $workOrder): int
    {
        return $workOrder->labors->sum(function ($labor) {
            return $labor->photos->count();
        });
    }

    /**
     * Obtiene estadísticas de fotos por labor
     *
     * @param WorkOrder $workOrder
     * @return array
     */
    public function getPhotoStatistics(WorkOrder $workOrder): array
    {
        $stats = [
            'total_photos' => 0,
            'labors_with_photos' => 0,
            'labors_without_photos' => 0,
            'average_photos_per_labor' => 0,
        ];

        $laborsCount = $workOrder->labors->count();

        foreach ($workOrder->labors as $labor) {
            $photoCount = $labor->photos->count();
            $stats['total_photos'] += $photoCount;

            if ($photoCount > 0) {
                $stats['labors_with_photos']++;
            } else {
                $stats['labors_without_photos']++;
            }
        }

        if ($laborsCount > 0) {
            $stats['average_photos_per_labor'] = round($stats['total_photos'] / $laborsCount, 2);
        }

        return $stats;
    }
}
