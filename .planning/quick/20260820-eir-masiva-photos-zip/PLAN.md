---
type: quick
slug: eir-masiva-photos-zip
created: 2026-08-20
status: planned
---

# Quick: Botón "Masiva" en la cabecera de EIR Edit (descarga ZIP de todas las fotos)

Agregar un botón naranja **Masiva** al costado de *Galeria* en la barra de acciones de
`eir-edit.blade.php` que descarga en un solo ZIP todas las fotos de la inspección
(cabecera + fotos de cada detalle), mostrando el spinner global de la aplicación
mientras se genera. Además, los 4 botones de esa barra pierden el texto en pantallas
pequeñas y quedan solo con su icono.

## Alcance

- Fotos de cabecera (`path_photo_1..4`) y fotos de cada `EirInspectionDetail`.
- **Fuera de alcance:** fotos de `RoofEvidence` (el pedido acotó "eir details y cabecera").

## Tareas

### 1. `app/Services/inspections/EirPhotoService.php`

- Extraer `createZipFromEntries(array $entries, string $zipFileName)`: misma lógica que
  `createZipFromPaths()` pero cada entrada define su ruta *dentro* del ZIP
  (`['path' => <ruta remota>, 'entry' => <ruta en el zip>]`).
- `createZipFromPaths()` pasa a delegar en el nuevo método (mapea a `basename`), sin
  cambio de comportamiento para `generateHeaderPhotosZip()` / `generateDetailPhotosZip()`.
- Nuevo `generateAllPhotosZip(EirInspection $inspection): string` que arma el ZIP con
  estructura `cabecera/NN_archivo.jpg` y `detalles/NN_<code ubicación>/NN_archivo.jpg`,
  con nombres saneados y prefijo numérico para evitar colisiones.
- Nombre del archivo: `eir_{serie}_{correlativo}_fotos.zip`.

### 2. `app/Livewire/Operations/Inspections/EirEdit.php`

- Nuevo método `download_all_photos()` siguiendo el patrón exacto de
  `download_photos()` / `download_detail_photos()`: `findOrFail($this->idEir)`,
  delega en el servicio, `response()->download(...)->deleteFileAfterSend(true)`,
  y `catch (\RuntimeException)` → `dispatch('swal', ... icon: error)`.

### 3. `resources/views/livewire/operations/inspections/eir-edit.blade.php`

- Barra de botones (~línea 256): los labels pasan de atributo `label` a slot
  `<span class="hidden sm:inline">…</span>` para que en móvil quede solo el icono.
- Botón nuevo `orange` **Masiva**, icono `archive-box-arrow-down`, justo después de
  *Galeria*, con `wire:click="download_all_photos"` y `wire:loading.attr="disabled"`.
- Agregar `download_all_photos` a los `:targets` del `<x-spinner>` (~línea 690) para
  que se muestre el spinner global de la aplicación.

## Verificación

- `vendor/bin/pint --test` sobre los archivos tocados.
- Revisión visual: 4 botones sin texto en <640px, con texto desde `sm`.
- Descarga con inspección sin fotos → swal de error, sin excepción sin capturar.
