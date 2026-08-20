---
type: quick-summary
slug: eir-masiva-photos-zip
completed: 2026-08-20
status: complete
---

# Summary: Botón "Masiva" en la cabecera de EIR Edit

## Qué se hizo

- **`app/Services/inspections/EirPhotoService.php`**
  - `createZipFromPaths()` se refactorizó para delegar en un nuevo
    `createZipFromEntries()`, que permite definir la ruta de cada foto *dentro*
    del ZIP. Los ZIP existentes (cabecera y detalle) mantienen el mismo
    comportamiento (`basename`).
  - Nuevo `generateAllPhotosZip(EirInspection $inspection)`: arma un único ZIP
    con `cabecera/NN_archivo.jpg` y `detalles/NN_<code ubicación>/NN_archivo.jpg`.
    Nombre del archivo: `eir_{serie}_{correlativo}_fotos.zip`.
  - Helpers privados `detailFolderName()`, `numberedFileName()` y
    `sanitizeName()` para nombres válidos y sin colisiones.

- **`app/Livewire/Operations/Inspections/EirEdit.php`**
  - Nuevo `download_all_photos()` siguiendo el patrón de `download_photos()`:
    `response()->download(...)->deleteFileAfterSend(true)` y `swal` de error
    cuando el servicio lanza `RuntimeException` (inspección sin fotos válidas).

- **`resources/views/livewire/operations/inspections/eir-edit.blade.php`**
  - Botón `orange` **Masiva** (icono `archive-box-arrow-down`) al costado de
    *Galeria*, con `wire:click="download_all_photos"`.
  - Los 4 botones de la barra pasaron de atributo `label` a slot
    `<span class="hidden sm:inline">…</span>`: en <640px quedan solo con icono.
  - `download_all_photos` agregado a los `:targets` del `<x-spinner>` global.

## Verificación

- `php -l` OK en ambos archivos PHP.
- `Blade::compileString()` de la vista compila a PHP válido.
- `vendor/bin/pint --test`: los mismos hallazgos de estilo existen en HEAD
  (preexistentes); no se reformatearon los archivos para no ensuciar el diff.

## Fuera de alcance

- Fotos de `RoofEvidence`: el pedido acotó el alcance a cabecera + detalles.
