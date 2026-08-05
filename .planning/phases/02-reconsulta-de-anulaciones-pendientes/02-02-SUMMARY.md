---
phase: 02-reconsulta-de-anulaciones-pendientes
plan: 02
subsystem: sunat-invoicing
tags: [laravel, livewire, rappasoft-livewire-tables, wireui, sunat, voided]

# Dependency graph
requires:
  - phase: 02-01
    provides: "VoidedTable::sendXml() con árbol de tres ramas (Consultar/Avisar-desenlace-confirmado/Avisar-no-se-puede-reenviar); response.blade.php con la rama Pendiente disparando sendXml()"
provides:
  - "response.blade.php con badge de texto x-wire-badge en las cuatro ramas de la celda Sunat, alimentado por VoidedStatus::badgeColor()/label()"
  - "Badge gris estático SIN ESTADO para la rama histórica (sunat_status NULL), resuelto en Blade sin pasar por el enum"
  - "VoidedTable::filters() con un SelectFilter 'Estado Sunat' (Todos + tres estados del enum + sin-estado -> whereNull)"
affects: [03-endpoint-api-de-anulacion, 04-autorizacion-de-acciones-livewire]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Badge de estado en celda de listado replicando el precedente de sunatResponse.blade.php (:color/:label desde el enum), tamaño sm en vez de lg"
    - "SelectFilter con opciones derivadas de ->value/->label() del enum en vez de literales sueltos, más un token de dominio (sin-estado) para el caso NULL, siguiendo el análogo de VoucherTable::filters()"

key-files:
  created: []
  modified:
    - resources/views/voideds/partials/response.blade.php
    - app/Livewire/VoidedTable.php

key-decisions:
  - "El badge se colocó dentro de un contenedor flex (items-center justify-between gap-2) que envuelve el botón/icono existente y el badge, replicado en las seis hojas de la celda (Aceptada, Pendiente, Rechazada con/sin ticket, histórica con/sin ticket)"
  - "No hizo falta añadir 'sunat_status' a setAdditionalSelects: la columna ya se selecciona implícitamente por ser la base de Column::make('Sunat', 'sunat_status'), confirmado tras la verificación de view:cache"
  - "El token elegido para el caso NULL del filtro es el literal 'sin-estado', que no colisiona con ningún ->value del enum (aceptada/pendiente/rechazada)"
  - "El badge de la rama histórica usa color=\"gray\" estático en vez de heredar cualquier color del enum, coherente con el icono fa-question-circle text-gray-400 que ya usaba esa rama"

patterns-established:
  - "Badge de estado + icono conviven en la misma celda de un listado rappasoft, sin que el badge introduzca un wire:click adicional"

requirements-completed: [WEB-04]

coverage:
  - id: D1
    description: "Cada fila del listado de anulaciones muestra el texto de su estado (ACEPTADA, PENDIENTE, RECHAZADA o SIN ESTADO) sin pasar el ratón ni abrir el modal (D-06, WEB-04)"
    requirement: "WEB-04"
    verification:
      - kind: unit
        ref: "grep -c 'x-wire-badge' response.blade.php == 6 (>=4 requerido); grep -c 'SIN ESTADO' == 2 (>=1 requerido); grep -c 'badgeColor()' == 4 (>=3 requerido)"
        status: pass
      - kind: manual_procedural
        ref: "human-check de la Tarea 1: abrir el listado con filas en los distintos estados y comprobar el texto sin interacción"
        status: unknown
    human_judgment: true
    rationale: "El recorrido visual real (legibilidad, no descuadre de fila, y en particular el color del badge Rechazada) solo lo confirma un humano viendo el listado renderizado; además el D-14 heredado exige contrastarlo contra el ambiente beta de SUNAT."
  - id: D2
    description: "El badge no introduce un segundo punto de entrada a la reconsulta: ninguna etiqueta x-wire-badge lleva wire:click, y los tres wire:click=\"sendXml\"/tres wire:click=\"showResponse\" del plan 02-01 se conservan intactos (D-05)"
    verification:
      - kind: unit
        ref: "grep -n 'x-wire-badge' response.blade.php inspeccionado línea por línea: ninguna contiene wire:click; grep -c 'wire:click=\"sendXml' == 3; grep -c 'wire:click=\"showResponse' == 3"
        status: pass
    human_judgment: false
  - id: D3
    description: "El operador puede filtrar el listado por estado (Estado Sunat) y aislar las pendientes, incluida la opción para filas sin estado vía whereNull (D-07, D-08)"
    verification:
      - kind: unit
        ref: "grep -c 'public function filters' VoidedTable.php == 1; grep -c 'Estado Sunat' == 1; grep -c \"whereNull('sunat_status')\" == 1; grep -c 'sin-estado' == 2; grep -c 'bulkActions' == 0; vendor/bin/pint --test pass; php -l pass"
        status: pass
      - kind: manual_procedural
        ref: "human-check de la Tarea 2: seleccionar PENDIENTE y SIN ESTADO en el desplegable y comprobar que el listado se reduce correctamente, sin fugas multi-tenant"
        status: unknown
    human_judgment: true
    rationale: "El comportamiento del SelectFilter en la UI real (reducción correcta del listado, ausencia de fuga entre empresas/sucursales) depende de datos reales en distintos estados; solo un humano lo confirma con el listado renderizado, según D-14 heredado."
  - id: D4
    description: "El enum VoidedStatus sigue con tres casos y ningún archivo prohibido (enum, VoidedTicketFlowService, VoidedForm, vouchers/partials) fue modificado; no hay migraciones nuevas"
    verification:
      - kind: unit
        ref: "git diff --name-only HEAD~2 HEAD == app/Livewire/VoidedTable.php, resources/views/voideds/partials/response.blade.php; git status --porcelain database/migrations/ vacío"
        status: pass
    human_judgment: false

# Metrics
duration: 5min
completed: 2026-08-05
status: complete
---

# Phase 2 Plan 2: Legibilidad y filtro de estado en el listado de anulaciones Summary

**Badge de texto (`x-wire-badge` con `VoidedStatus::badgeColor()`/`label()`) en las cuatro ramas de la celda Sunat de `response.blade.php`, badge gris estático `SIN ESTADO` para filas históricas, y `SelectFilter` `Estado Sunat` en `VoidedTable` con las tres opciones del enum más `sin-estado` resuelto vía `whereNull`.**

## Performance

- **Duration:** 5 min
- **Started:** 2026-08-05T12:30:14-05:00 (aprox., justo tras el commit de metadata del plan 02-01)
- **Completed:** 2026-08-05T12:34:56-05:00
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- `response.blade.php` gana un `<x-wire-badge sm>` en cada una de las seis hojas de la celda Sunat, alimentado por `$row->sunat_status->badgeColor()`/`label()` en las cinco ramas con estado conocido, y con `color="gray" label="SIN ESTADO"` estático en las dos hojas de la rama histórica (`sunat_status` NULL).
- Los iconos preexistentes (`check-circle`, `clock`, `fa-ban`, `fa-question-circle`, `get_cdr.svg`) se conservan intactos; el badge se suma dentro de un contenedor `flex items-center justify-between gap-2` que también envuelve el botón, sin tocar ningún `wire:click` existente.
- `VoidedTable` gana un método público `filters(): array` con un único `SelectFilter::make('Estado Sunat')`: `Todos` (cadena vacía, sin condición), los tres estados leídos de `VoidedStatus::*->value`/`->label()`, y `sin-estado` que aplica `whereNull('sunat_status')`.
- El filtro compone sobre el `Builder` que ya devuelve `builder()` (acotado por `branch_id`, `company_id` y `production` de sesión), así que solo puede estrechar el listado.
- No se tocó `sendXml()`, `canResendXml()`, `cdrPathFor()` ni `configure()`: el `git diff` de `VoidedTable.php` muestra únicamente el import de `SelectFilter` y el método `filters()` añadido.

## Task Commits

Cada tarea se comprometió atómicamente:

1. **Tarea 1: El estado de cada baja se lee en la propia fila** - `1791768` (feat)
2. **Tarea 2: El operador aísla las anulaciones pendientes con un filtro por estado** - `8b49a3c` (feat)

_Nota: la metadata de esta fase (SUMMARY.md, STATE.md, ROADMAP.md) se compromete en un commit separado tras este resumen._

## Files Created/Modified
- `resources/views/voideds/partials/response.blade.php` - badge de texto en las seis hojas de la celda Sunat (cuatro ramas de estado, dos con sub-hojas); contenedor flex para alinear botón/icono y badge; badge gris estático para el caso NULL
- `app/Livewire/VoidedTable.php` - nuevo import `SelectFilter`; nuevo método público `filters()` con el `SelectFilter` `Estado Sunat`; el resto del fichero (incluidos `sendXml()`, `canResendXml()`, `cdrPathFor()`, `configure()`) queda idéntico al que dejó el plan 02-01

## Decisions Made
- No hizo falta añadir `'sunat_status'` a `setAdditionalSelects`: la columna ya se selecciona implícitamente por ser la base de `Column::make('Sunat', 'sunat_status')` (línea 61 de `VoidedTable.php`); `view:cache`/`view:clear` terminaron en 0 sin necesidad del ajuste, así que `configure()` quedó exactamente igual que en el plan 02-01, sin anotación adicional requerida por el `acceptance_criteria`.
- El token para el caso NULL del filtro es el literal `sin-estado` (D-07 "Claude's Discretion"), elegido porque no colisiona con ningún `->value` del enum (`aceptada`/`pendiente`/`rechazada`).
- La colocación del badge dentro de la celda es *después* del botón/icono, en un `flex items-center justify-between gap-2` (mismo recurso de `sunatResponse.blade.php` adaptado a alineación centrada en vez de la fila completa del modal).
- **Punto de atención señalado por el plan, pendiente de UAT:** `VoidedStatus::badgeColor()` devuelve `danger` para Rechazada, y `danger` no está en el pack de colores de WireUI (`vendor/wireui/wireui/src/Enum/Packs/Color.php` no lo declara — se confirmó en esta ejecución que los colores válidos incluyen `gray`, `warning`, `primary`, etc., pero no `danger`/`invalidated` bajo ese nombre exacto). El badge de Rechazada puede renderizarse sin fondo de color mientras Aceptada sale azul (`primary`) y Pendiente ámbar (`warning`). El texto RECHAZADA y el icono `fa-ban` rojo siguen distinguiendo la fila, así que el criterio 4 del ROADMAP se cumple igualmente; corregir el enum queda fuera de alcance de esta fase (D-08 lo declara intocable) y es candidato a un ajuste posterior. Este punto debe quedar anotado explícitamente en el UAT de la fase.

## Deviations from Plan

None - plan ejecutado tal como fue escrito. Las dos tareas se entregaron cada una en su propio commit, como pedía el plan.

## Issues Encountered
None.

## User Setup Required

None - no se requiere configuración externa.

## Next Phase Readiness

- El listado de anulaciones ya dice la verdad a simple vista sobre el estado de cada comunicación de baja (WEB-04 cubierto a nivel de código) y el operador cuenta con el filtro `Estado Sunat` para aislar las pendientes que debe reconsultar con la acción del plan 02-01.
- **Pendiente para el UAT de la fase (D-14, heredado):**
  1. Verificación visual: abrir el listado con filas en los cuatro estados y confirmar que cada badge se lee sin pasar el ratón ni abrir el modal, que los iconos siguen presentes y que la fila no se descuadra — con atención especial al color del badge Rechazada (ver "Decisions Made" arriba).
  2. Verificación del filtro: seleccionar `PENDIENTE` y confirmar que el listado se reduce a las filas con badge PENDIENTE (las que ofrecen el botón de reconsulta); seleccionar `SIN ESTADO` y confirmar que solo aparecen las filas históricas con badge gris; volver a `Todos` y confirmar que el listado se restablece completo; confirmar que el filtro no saca filas de otra empresa o sucursal.
  3. Ambos puntos se suman a los pendientes de UAT ya heredados del plan 02-01 (reconsulta contra SUNAT beta con `production = false`).
- Ninguno de estos ítems es automatizable sin infraestructura de test con SUNAT simulado o sin datos reales renderizados en el listado — decisión ya heredada de la Fase 1.
- Con este plan se cierra la Fase 2 completa (WEB-02, WEB-03, WEB-04). La Fase 3 (endpoint API de anulación) puede empezar sin bloqueos de esta fase.

---
*Phase: 02-reconsulta-de-anulaciones-pendientes*
*Completed: 2026-08-05*

## Self-Check: PASSED
- resources/views/voideds/partials/response.blade.php: FOUND
- app/Livewire/VoidedTable.php: FOUND
- Commit 1791768 (Tarea 1): FOUND
- Commit 8b49a3c (Tarea 2): FOUND
