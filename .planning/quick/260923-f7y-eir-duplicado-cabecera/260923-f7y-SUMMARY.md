---
type: quick
slug: eir-duplicado-cabecera
quick_id: 260923-f7y
phase: quick-260923-f7y
plan: 01
subsystem: inspections-eir
tags: [eir, duplicados, multi-tenant, concurrencia, sunat-adjacent]
dependency-graph:
  requires: []
  provides:
    - ContainerOperationalTrace::scopeForBranch()
    - EirInspection::existingForTrace()
    - EirInspection::documentLabel()
  affects:
    - InspectionController::create_eir
    - ContainerOperationalTraceController::getContainers
    - EirCreate (Livewire)
    - EirForm
tech-stack:
  added: []
  patterns:
    - "Guard anti-duplicado centralizado en el modelo (existingForTrace) e invocado desde los tres puntos de entrada reales: controlador, componente y transacción de guardado"
    - "lockForUpdate() como barrera de concurrencia (duplicado de traza y correlativo), con reintento acotado ante choque del índice único"
key-files:
  created: []
  modified:
    - app/Models/ContainerOperationalTrace.php
    - app/Models/inspections/EirInspection.php
    - app/Http/Controllers/operations/InspectionController.php
    - app/Http/Controllers/ContainerOperationalTraceController.php
    - app/Livewire/Operations/Inspections/EirCreate.php
    - app/Livewire/Forms/EirForm.php
    - resources/views/containers/actions.blade.php
    - resources/views/livewire/operations/inspections/eir-create.blade.php
decisions:
  - "El guard bloquea ante cualquier EIR de la traza, sin filtrar por status: se asume una traza = un EIR (igual que el guard anterior en updated(), que ya no corre en el camino real de producción)"
  - "En create_eir(), si ya existe un EIR y el usuario no tiene permiso inspections.eir.edit, se redirige a containers.traces en vez de forzar un 403"
  - "isDuplicateCorrelativo() compara SQLSTATE 23000 + el fragmento serie_correlativo en minúsculas dentro del mensaje, para tolerar que producción tenga el índice con otro nombre"
  - "vendor/bin/pint --dirty reformateó bloques enteros de archivos tocados que esta plan no modificó (declaraciones de propiedades, otros métodos); se revirtieron esas líneas a mano y solo se conservó el formato dentro de las zonas realmente editadas (getContainerTrace(), save(), store())"
metrics:
  duration: ~55min
  completed: 2026-09-23
status: complete
---

# Quick Task 260923-f7y: EIR duplicado (cabecera + correlativo) Summary

Guard anti-duplicado centralizado en los modelos y aplicado en los cuatro puntos donde
hoy se podía crear un EIR gemelo para la misma traza, más asignación atómica del
correlativo con `lockForUpdate()` y reintento acotado ante choque del índice único.

## Causas cerradas (FINDINGS)

**Causa A — el guard anti-duplicado se evitaba desde el camino real de producción.**
`updated()` solo corría cuando el usuario cambiaba el selector del formulario; el camino
real (`actions.blade.php` → `InspectionController::create_eir($trace)`) no validaba nada.
Cerrada en cuatro puntos:
1. `InspectionController::create_eir()` — valida el id de traza (`filter_var` +
   `FILTER_VALIDATE_INT`), la pertenencia al branch de sesión (`forBranch()`) y la
   existencia de un EIR previo (`existingForTrace()`) antes de renderizar el formulario.
2. `EirCreate::getContainerTrace()` — único camino ya sea por `mount()` o por
   `updated()`, aplica el mismo guard.
3. `ContainerOperationalTraceController::getContainers()` (endpoint `trace.search`,
   único consumidor: el selector del formulario) — filtra por `forBranch()` y
   `whereDoesntHave('EirInspection')`.
4. `actions.blade.php` — el ítem "Generar EIR" del menú de acciones queda condicionado a
   `!$trace->EirInspection`; si ya existe, se muestra un bloque no clicable con la
   etiqueta del EIR existente.

**Causa B — `save()` no era idempotente ni reentrante.**
- Guarda al inicio de `save()`: si `$this->idEir` ya tiene valor, reemite la redirección
  en vez de reabrir la transacción.
- Re-chequeo dentro de la transacción: primera instrucción del closure,
  `EirInspection::where('trace_id', ...)->lockForUpdate()->first()`; si hay duplicado, la
  transacción confirma vacía y no se emite `eirCreated`.
- Lado cliente: bandera Alpine `submitting` que deshabilita el botón "Guardar" desde el
  clic de confirmación hasta que `idEir` sigue nulo (error de validación) o hasta el
  redirect (guardado exitoso).

**Causa C — correlativo sin bloqueo.**
- La consulta del máximo correlativo (en `getSeries()` y dentro de la transacción de
  `save()`) ahora filtra `branch_id` de forma explícita (el `whereHas('branch', ...)`
  anterior funcionaba por accidente porque `branches` no tiene columna `branch_id`) y usa
  `lockForUpdate()`.
- `EirForm::store()` reenvía `QueryException` sin envolverla, para que `save()` pueda
  distinguir un choque del índice único de cualquier otro fallo.
- Reintento acotado a 3 intentos: si `isDuplicateCorrelativo()` detecta SQLSTATE `23000`
  con el fragmento `serie_correlativo`, reintenta (el `max()` se recalcula porque está
  dentro del closure); si se agota o es otro tipo de error, se registra con `Log::error`
  y se muestra un mensaje en español, nunca el SQL crudo.

## Verificación automática (ejecutada)

- `php -l` limpio en los seis archivos PHP tocados.
- `php artisan view:clear` y `php artisan route:list --name=inspections.eir` sin error
  (4 rutas registradas: `list`, `edit`, `pdf`, `create`).
- Tinker (lectura, sin mutar datos):
  - `EirInspection::existingForTrace(318)?->documentLabel()` → `EIR-SLEI-000149`.
  - `existingForTrace(318, 150)?->id` → `384` (excluye correctamente el propio id).
  - `existingForTrace(999999)` → `null`.
  - `ContainerOperationalTrace::whereKey(318)->forBranch(1)->exists()` → `true`;
    `forBranch(99999)` → `false`.
  - Selector: `whereDoesntHave('EirInspection')->count()` = 166 total y 166 en branch 1
    (único branch local); traza 318 (con EIR) queda fuera del selector.
  - `lockForUpdate()` aceptado por MySQL tanto sobre el agregado (`max_con_lock` = 1121)
    como sobre la consulta por `trace_id` (`lock_traza_318` = 150), dentro de una
    transacción con `rollBack()`.
  - Pares `serie`+`correlativo` repetidos: `[]` (baseline limpio en local, consistente
    con FINDINGS).
- `vendor/bin/pint --dirty` aplicado tras cada tarea; el diff se revisó y se revirtieron
  a mano las zonas no relacionadas que Pint reformateó (ver Decisiones). La pasada final
  con el árbol ya commiteado devuelve `PASS 0 files`.

## Verificación manual (navegador) — PENDIENTE DE UAT DEL USUARIO

No pude ejecutarla yo (requiere sesión de navegador). Sigue el checklist del PLAN,
`<verification>` → "Verificación manual (navegador, BD local)". Antes de empezar, anota
el conteo de partida con
`php artisan tinker --execute="echo App\Models\inspections\EirInspection::count();"`.

1. **Camino feliz.** En `containers-traces`, elige una traza sin EIR y usa "Generar EIR".
   El formulario debe abrirse con cabecera autocompletada; al guardar, aviso de éxito y
   redirect a los 2s. El conteo sube exactamente en 1.
2. **Duplicado por URL.** Reabre la misma URL `…/{trace}/create-eir` de la traza recién
   inspeccionada: no debe renderizar el formulario, debe avisar nombrando el EIR
   existente y redirigir. El conteo no cambia.
3. **Menú de acciones.** Recarga `containers-traces`: esa traza ya no ofrece "Generar
   EIR", muestra el texto de EIR ya generado. Una traza sin EIR sigue ofreciendo la
   opción.
4. **Selector.** Entra a `…/create-eir` sin parámetro y busca el código del contenedor
   del paso 1 en el selector: no debe aparecer. Uno sin EIR sí aparece y autocompleta.
5. **Doble envío.** En una traza nueva, confirma el guardado y haz clic repetido en
   "Guardar": el botón queda inhabilitado, el conteo sube en 1 (no 2). Repite con Enter.
6. **Traza inválida.** Abre `…/999999/create-eir` y `…/abc/create-eir`: ambos redirigen
   con aviso, sin pantalla 500.
7. **Correlativo.** Tras los pasos anteriores, repite la consulta de pares
   `serie`+`correlativo` repetidos: debe seguir devolviendo `[]`.

## Fuera de alcance (según el PLAN, decidido con motivo)

- Limpieza de los 5 casos históricos con dos EIR en local (trazas 318, 337, 386, 424,
  720) y de los duplicados de producción — requieren confirmación del usuario sobre
  cuáles son gemelos reales; borrar un EIR arrastra detalles, fotos en Spaces y órdenes
  de trabajo.
- Índice único y migraciones — no se tocó `unique(serie, correlativo)` ni se agregó un
  único sobre `trace_id`.
- `app/Http/Controllers/Api/*` — la API REST no se usa hoy, no se tocó.
- Módulos PTI y Leak Test — comparten el patrón de `actions.blade.php` y probablemente
  el mismo defecto, pero no se diagnosticaron; candidato a un quick task aparte.

## Deviations from Plan

None - plan ejecutado tal como estaba escrito. El único ajuste fue de forma, no de
alcance: revertir a mano el reformateo no relacionado que `pint --dirty` aplicó sobre
zonas de los archivos que este plan no modificó (documentado en `decisions` del
frontmatter).

## Self-Check: PASSED

Archivos modificados verificados en disco:
- FOUND: app/Models/ContainerOperationalTrace.php
- FOUND: app/Models/inspections/EirInspection.php
- FOUND: app/Http/Controllers/operations/InspectionController.php
- FOUND: app/Http/Controllers/ContainerOperationalTraceController.php
- FOUND: app/Livewire/Operations/Inspections/EirCreate.php
- FOUND: app/Livewire/Forms/EirForm.php
- FOUND: resources/views/containers/actions.blade.php
- FOUND: resources/views/livewire/operations/inspections/eir-create.blade.php

Commits verificados en `git log --oneline`:
- FOUND: e1ab6ef
- FOUND: 71a96fa
- FOUND: 4522ab1

## Revisión del orquestador (post-ejecución)

Al revisar el `save()` resultante apareció un defecto introducido por la propia Tarea 3: si
la transacción revienta **después** de `$this->eir->store()` (el `update` de `containers`
del paso 4), el rollback deshace el insert pero `$this->idEir` queda asignado en memoria.
Un `save()` posterior tomaría entonces la guarda de idempotencia del inicio del método y
despacharía `eirCreated` con `route('inspections.eir.edit', $idEir)` para un id que ya no
existe → 404 en lugar de reintentar el guardado.

Corregido en `5e7fd35`: `$this->idEir = null` al entrar al `catch (QueryException)`
(antes del `continue` del reintento, para que un intento fallido no contamine el siguiente)
y en la rama `if ($duplicate)`. Invariante resultante: `$this->idEir` solo tiene valor
cuando la transacción confirmó.

Ese commit se dejó deliberadamente en 7 líneas: `pint --dirty` sobre `EirCreate.php`
volvía a reformatear zonas no relacionadas (líneas en blanco entre las declaraciones de
propiedades, `NULL`→`null`, `fn(`→`fn (`, `if (!$lineId) return;` a bloque, y la
eliminación del import sin usar `Livewire\Attributes\Computed`), así que se reconstruyó el
archivo desde `132b4c4` y se reaplicó solo el cambio intencionado.

Verificación de cierre repetida por el orquestador: `php -l` limpio en los 6 PHP,
`php artisan view:clear` sin error, `route:list --name=inspections.eir` con 4 rutas,
árbol de trabajo limpio, y el `tinker` de solo lectura devolviendo
`guard_traza_318 = "EIR-SLEI-000149"`, `excluyendo_150 = 384`, `traza_inexistente = null`,
`scope_branch_1 = true`, `scope_branch_99999 = false` y `pares_repetidos = []`.
