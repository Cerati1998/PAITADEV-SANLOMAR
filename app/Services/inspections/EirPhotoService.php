<?php

namespace App\Services\inspections;

use App\Models\inspections\EirInspection;
use App\Models\inspections\EirInspectionDetail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EirPhotoService
{
    /**
     * Disco de almacenamiento (DigitalOcean Spaces)
     */
    public string $getDisk = 'do';

    /**
     * Genera un archivo ZIP con las fotos de cabecera de una inspección
     */
    public function generateHeaderPhotosZip(EirInspection $inspection): string
    {
        $photos = collect([
            $inspection->path_photo_1,
            $inspection->path_photo_2,
            $inspection->path_photo_3,
            $inspection->path_photo_4,
        ])->filter()->toArray();

        $zipFileName = "eir_{$inspection->serie}_{$inspection->correlativo}_header.zip";

        return $this->createZipFromPaths($photos, $zipFileName);
    }

    /**
     * Genera un archivo ZIP con las fotos de un detalle de inspección
     */
    public function generateDetailPhotosZip(EirInspectionDetail $detail): string
    {
        $photos = $detail->photos->pluck('path')->toArray();
        $zipFileName = "eir_detail_{$detail->id}_photos.zip";

        return $this->createZipFromPaths($photos, $zipFileName);
    }

    /**
     * Genera un archivo ZIP con todas las fotos de la inspección:
     * las de cabecera y las de cada detalle, organizadas en carpetas
     */
    public function generateAllPhotosZip(EirInspection $inspection): string
    {
        $inspection->loadMissing('details.photos', 'details.location');

        $entries = [];

        $headerPhotos = collect([
            $inspection->path_photo_1,
            $inspection->path_photo_2,
            $inspection->path_photo_3,
            $inspection->path_photo_4,
        ])->filter()->values();

        foreach ($headerPhotos as $index => $photoPath) {
            $entries[] = [
                'path' => $photoPath,
                'entry' => 'cabecera/' . $this->numberedFileName($photoPath, $index + 1),
            ];
        }

        foreach ($inspection->details as $detailIndex => $detail) {
            $folder = 'detalles/' . $this->detailFolderName($detail, $detailIndex + 1);

            foreach ($detail->photos as $photoIndex => $photo) {
                if (empty($photo->path)) continue;

                $entries[] = [
                    'path' => $photo->path,
                    'entry' => $folder . '/' . $this->numberedFileName($photo->path, $photoIndex + 1),
                ];
            }
        }

        $zipFileName = "eir_{$inspection->serie}_{$inspection->correlativo}_fotos.zip";

        return $this->createZipFromEntries($entries, $zipFileName);
    }

    /**
     * Nombre de la carpeta de un detalle dentro del ZIP
     */
    private function detailFolderName(EirInspectionDetail $detail, int $position): string
    {
        $code = $detail->location->code ?? 'detalle';

        return str_pad((string) $position, 2, '0', STR_PAD_LEFT) . '_' . $this->sanitizeName($code);
    }

    /**
     * Nombre de archivo dentro del ZIP, prefijado para evitar colisiones
     */
    private function numberedFileName(string $photoPath, int $position): string
    {
        $relativePath = ltrim((string) parse_url($photoPath, PHP_URL_PATH), '/');

        return str_pad((string) $position, 2, '0', STR_PAD_LEFT) . '_' . $this->sanitizeName(basename($relativePath));
    }

    /**
     * Limpia un nombre para que sea una entrada válida dentro del ZIP
     */
    private function sanitizeName(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);

        return trim((string) $clean, '_') ?: 'archivo';
    }

    /**
     * Crea un archivo ZIP a partir de un array de rutas de fotos
     */
    private function createZipFromPaths(array $photoPaths, string $zipFileName): string
    {
        $entries = [];

        foreach ($photoPaths as $photoPath) {
            if (empty($photoPath)) continue;

            $relativePath = ltrim((string) parse_url($photoPath, PHP_URL_PATH), '/');

            $entries[] = [
                'path' => $photoPath,
                'entry' => basename($relativePath),
            ];
        }

        return $this->createZipFromEntries($entries, $zipFileName);
    }

    /**
     * Crea un archivo ZIP a partir de entradas
     * ['path' => ruta remota de la foto, 'entry' => ruta dentro del ZIP]
     */
    private function createZipFromEntries(array $entries, string $zipFileName): string
    {
        $zipPath = storage_path("app/temp/{$zipFileName}");
        $disk = $this->getDisk;

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("No se pudo crear el archivo ZIP: {$zipFileName}");
        }

        $addedFiles = 0;

        foreach ($entries as $entry) {
            $photoPath = $entry['path'] ?? null;

            if (empty($photoPath)) continue;

            // Si se guardó una URL completa, obtener solo el path relativo
            $relativePath = parse_url($photoPath, PHP_URL_PATH);
            $relativePath = ltrim($relativePath, '/');

            if (Storage::disk($disk)->exists($relativePath)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'eir_photo_');
                file_put_contents($tempFile, Storage::disk($disk)->get($relativePath));

                $zip->addFile($tempFile, $entry['entry'] ?? basename($relativePath));
                $addedFiles++;

                register_shutdown_function(function () use ($tempFile) {
                    if (file_exists($tempFile)) @unlink($tempFile);
                });
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            @unlink($zipPath);
            throw new \RuntimeException("No se encontraron fotos válidas para comprimir");
        }

        return $zipPath;
    }

    /**
     * Guarda una foto de cabecera de inspección (y almacena URL completa)
     */
    public function saveHeaderPhoto(EirInspection $inspection, $file, int $position): string
    {
        if ($position < 1 || $position > 4) {
            throw new \InvalidArgumentException("La posición debe estar entre 1 y 4");
        }

        $disk = $this->getDisk;
        $folder = str_pad($inspection->correlativo, 6, '0', STR_PAD_LEFT);
        $path = $file->store("eir/{$folder}", $disk);

        // Generar URL pública completa
        $url = Storage::disk($disk)->url($path);

        // Guardar en el campo de la inspección
        $field = "path_photo_{$position}";
        $inspection->$field = $url;
        $inspection->save();

        return $url;
    }

    /**
     * Guarda una foto de detalle de inspección (y almacena URL completa)
     */
    public function saveDetailPhoto(EirInspectionDetail $detail, $file)
    {
        $disk = $this->getDisk;
        $path = $file->store("eir/details/{$detail->id}", $disk);
        $url = Storage::disk($disk)->url($path);

        // Retorna el modelo creado
        $photo = $detail->photos()->create([
            'path' => $url,
        ]);

        return $photo;
    }

    /**
     * Elimina una foto de detalle (toma en cuenta URL completa)
     */
    public function deleteDetailPhoto(int $photoId): bool
    {
        $disk = $this->getDisk;
        $photo = \App\Models\inspections\EirInspectionDetailPhoto::findOrFail($photoId);

        // Si el path es una URL, obtener el path relativo
        $relativePath = parse_url($photo->path, PHP_URL_PATH);
        $relativePath = ltrim($relativePath, '/');

        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }

        return $photo->delete();
    }

    /**
     * Devuelve la URL pública de una foto
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
     * Valida que un archivo sea una imagen válida
     */
    public function validateImage($file, int $maxSizeKB = 10048): bool
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException("El archivo no es válido");
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
            throw new \InvalidArgumentException("El archivo debe ser una imagen (JPG, PNG)");
        }

        if ($file->getSize() > ($maxSizeKB * 1024)) {
            throw new \InvalidArgumentException("La imagen excede el tamaño máximo de {$maxSizeKB}KB");
        }

        return true;
    }

    /**
     * Limpia archivos ZIP temporales antiguos
     */
    public function cleanOldZipFiles(int $hoursOld = 24): int
    {
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) return 0;

        $files = glob("{$tempPath}/*_photos*.zip");
        $deleted = 0;
        $cutoffTime = now()->subHours($hoursOld)->timestamp;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
