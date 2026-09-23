# Quick Task 260923-f7y: EIR duplicados (correlativo + cabecera) — Hallazgos

**Fecha:** 2026-09-23
**Reporte del usuario:** "en el módulo de EIR inspections están existiendo casos donde se repite
correlativo y todo el contenido de cabecera, lo cual no debería pasar."

## Evidencia recogida

- `database/migrations/2025_09_18_164302_create_eir_inspections_table.php:19` define
  `$table->unique(['serie', 'correlativo'])`, y el índice **existe** en la BD local
  (`eir_inspections_serie_correlativo_unique`, no único = 0). No hay migración posterior
  que lo elimine (solo `create_work_orders_table` y `create_roof_evidence_table` tocan la tabla).
- BD local (1118 EIR, única serie `SLEI`, único branch, rango 1-1121, huecos 12/120/458):
  **0 correlativos repetidos**. Los datos locales llegan a `2026-02-18`, así que los casos
  reportados están en producción.
- Existen **5 trazas con 2 EIR cada una**: trace 318 (SLEI-149 / SLEI-380), 337 (150/372),
  386 (186/375), 424 (228/410), 720 (357/952). Separadas por semanas → esas concretas
  parecen reinspecciones, pero demuestran que **nada impide dos EIR para el mismo contenedor**.
- Único punto de creación de EIR en toda la app: `EirCreate::save()`
  (`app/Livewire/Operations/Inspections/EirCreate.php:201`) → `EirForm::store()`.

## Causas raíz

### A. El guard anti-duplicado se evita cuando el EIR se genera desde la traza (principal)

La única comprobación de "este contenedor ya tiene un EIR" vive en
`EirCreate::updated()` (`EirCreate.php:149-178`), y **solo corre cuando el usuario cambia
el selector `trace` dentro del formulario**.

El camino real de producción es otro: en `resources/views/containers/actions.blade.php:10`
el ítem "Generar EIR" redirige a `inspections.eir.create/{trace}`. Ese `trace` llega como
parámetro de ruta → `InspectionController::create_eir($trace)`
(`app/Http/Controllers/operations/InspectionController.php:37-48`, sin ninguna validación:
no comprueba que la traza exista, que pertenezca al branch de sesión, ni que ya tenga EIR)
→ `@livewire(... ['trace' => $trace])` (`resources/views/inspections/eir/create.blade.php:14`)
→ `EirCreate::mount()` asigna `$this->trace` sin pasar por `updated()`.

Resultado: `updated('trace')` **nunca se dispara** en el flujo habitual y `save()` tampoco
repite la comprobación. Abrir "Generar EIR" dos veces sobre el mismo contenedor (dos
pestañas, o volver a abrir el menú porque no se vio el primero) crea dos EIR con
cabecera idéntica — todo se autocompleta desde trace + container — y correlativos consecutivos.
Además, el ítem "Generar EIR" sigue visible para una traza que ya tiene EIR.

### B. `save()` no es idempotente ni reentrante

- No hay guardia por `$this->idEir`: si `save()` se vuelve a invocar, crea otro EIR.
- El overlay de carga usa `wire:loading.delay.longer` (`resources/views/components/spinner.blade.php:10`),
  así que durante los primeros ~500 ms de la petición el botón "Guardar" sigue activo.
- Tras el éxito la vista espera **2 s** antes de redirigir
  (`setTimeout(... 2000)` en `resources/views/livewire/operations/inspections/eir-create.blade.php`),
  dejando el formulario vivo y reenviable.
- Un reintento del operador (tablet, red del patio, petición que expira en el proxy después de
  que PHP ya insertó) reejecuta `save()` y produce un EIR gemelo.

### C. Asignación del correlativo sin bloqueo

`EirCreate::save()` (líneas 205-212) hace `max('correlativo') + 1` sin `lockForUpdate`.
Dos inspectores guardando a la vez leen el mismo máximo. Con el índice único presente
esto no duplica: revienta con `SQLSTATE[23000] Duplicate entry`, que `EirForm::store()`
reenvía crudo al usuario como `Error al guardar EIR: ...`. Si producción no tuviera el
índice único (esquema aplicado a mano), aquí sí se duplicaría el correlativo exacto.

Detalle adicional: la consulta del máximo usa
`whereHas('branch', fn($q) => $q->where('branch_id', ...))`. `branches` no tiene columna
`branch_id`, así que MySQL resuelve el identificador no cualificado contra la tabla externa
y el filtro acaba siendo `eir_inspections.branch_id = ?`. Funciona por accidente; conviene
escribirlo explícito.

## Supuestos asumidos (el usuario no respondió la consulta)

- El fix va sobre el flujo web/Livewire; la API REST (`app/Http/Controllers/Api/*`) no se toca.
- Producción es MySQL con el mismo esquema de la migración (índice único presente), así que
  el objetivo es cerrar los caminos de la aplicación, no reparar datos.
- Un contenedor (traza) debe tener **un solo** EIR; una reinspección no se hace creando
  un segundo EIR desde el mismo trace.
