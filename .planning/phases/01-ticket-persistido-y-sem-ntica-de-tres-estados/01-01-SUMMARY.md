---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
plan: 01
subsystem: sunat-invoicing
tags: [laravel-11, greenter, sunat, php-enums, voided, comunicacion-de-baja]

# Dependency graph
requires: []
provides:
  - "App\\Enums\\VoidedStatus — enum string-backed con tres casos (Aceptada, Pendiente, Rechazada) y label()/badgeColor()"
  - "voideds.sunat_ticket y voideds.sunat_status — columnas nullable sin default ni backfill"
  - "Voided::$fillable/$casts actualizados para exponer y castear ambas columnas"
  - "App\\Services\\Sunat\\VoidedTicketFlowService — sendAndResolve(), resolveTicket() y classifyFailure() públicos"
affects: [01-02-PLAN, 01-03-PLAN]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Enum PHP string-backed casteado en Eloquent (primer enum del proyecto, precedente para futuros estados cerrados)"
    - "Persistir el ticket asíncrono SUNAT con un save() propio antes de cualquier consulta posterior (portado de Despatch)"
    - "Clasificación de fallo por presencia de ticket persistido, no por contenido del error (evita whitelist de códigos SOAP)"

key-files:
  created:
    - app/Enums/VoidedStatus.php
    - database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php
    - app/Services/Sunat/VoidedTicketFlowService.php
  modified:
    - app/Models/Voided.php

key-decisions:
  - "sendAndResolve() y resolveTicket() reciben un `See` opcional en el segundo caso para no reabrir la conexión SOAP dos veces en el flujo de envío"
  - "El desenlace aceptado se escribe inline en resolveTicket() (no hay markAccepted privado): el plan solo pidió un único punto de escritura por desenlace, y la rama de aceptación ya es única"
  - "Se añadió una rama defensiva para 'CDR ausente tras zip poblado' (caso no documentado en ExtService::processResponse, que siempre rellena ambos juntos) que clasifica pendiente en vez de lanzar TypeError"

requirements-completed: [TICKET-01, TICKET-02, TICKET-03, TICKET-04, TICKET-05, WEB-05]

coverage:
  - id: D1
    description: "El enum VoidedStatus existe con tres casos string-backed y los métodos label()/badgeColor()"
    requirement: TICKET-05
    verification:
      - kind: manual_procedural
        ref: "php artisan tinker --execute=\"echo App\\Enums\\VoidedStatus::from('aceptada')->label().'|'.App\\Enums\\VoidedStatus::from('pendiente')->badgeColor().'|'.App\\Enums\\VoidedStatus::from('rechazada')->value;\" -> ACEPTADA|warning|rechazada"
        status: pass
    human_judgment: false
  - id: D2
    description: "Las columnas voideds.sunat_ticket y voideds.sunat_status existen, nullable, sin default, sin backfill, y se leen como null en filas históricas"
    requirement: TICKET-01
    verification:
      - kind: manual_procedural
        ref: "php artisan migrate --force && php artisan migrate:rollback --step=1 --force (rollback limpio verificado); inserción de fila histórica sin las columnas nuevas -> Voided::find()->sunat_status === null"
        status: pass
    human_judgment: false
  - id: D3
    description: "VoidedTicketFlowService concentra sendAndResolve/resolveTicket/classifyFailure, persiste el ticket antes de consultar, clasifica pendiente ante cualquier \\Throwable (no solo SoapFault), decide aceptación por el código del CDR igual a '0', y marca comprobantes filtrando por branch_id/company_id del propio Voided"
    requirement: TICKET-02
    verification:
      - kind: unit
        ref: "No hay suite de tests SUNAT en el repo (nyquist_validation: false); verificado por lectura de código, php -l, Reflection sobre métodos públicos, y greps del plan sobre catch(\\Throwable), session(), UtilTrait/getResponseVoided, sunat_ticket = null"
        status: pass
    human_judgment: true
    rationale: "El flujo real solo se puede confirmar contra el SOAP de SUNAT beta (D-14, verificación manual pendiente en el plan 02, que es el primer consumidor); esta fase entrega la pieza de dominio sin conectarla todavía a ningún llamador Livewire"

# Metrics
duration: 8min
completed: 2026-08-04
status: complete
---

# Phase 1 Plan 1: Vocabulario de estado y servicio de flujo del ticket Summary

**Enum `VoidedStatus` (aceptada/pendiente/rechazada), columnas `sunat_ticket`/`sunat_status` en `voideds`, y `VoidedTicketFlowService` que persiste el ticket antes de consultar y clasifica el desenlace sin lista blanca de códigos SOAP.**

## Performance

- **Duration:** 8 min
- **Started:** 2026-08-04T23:29:31Z
- **Completed:** 2026-08-04T23:36:33Z
- **Tasks:** 2
- **Files modified:** 4 (3 nuevos, 1 modificado)

## Accomplishments
- Primer enum PHP del proyecto (`App\Enums\VoidedStatus`), string-backed, con `label()`/`badgeColor()` sin caso `default` para que un cuarto estado futuro rompa en compilación
- Migración aditiva (`sunat_ticket`, `sunat_status` en `voideds`), nullable y sin backfill, con rollback probado en local
- `Voided` expone y castea ambas columnas sin tocar accessors ni relaciones existentes
- `VoidedTicketFlowService`: único punto que envía la comunicación de baja y consulta su estado, con `try/catch(\Throwable)` alrededor de `getStatus()` (no solo `SoapFault`), decisión de aceptación por `cdr->getCode() === '0'`, archivado del CDR siempre que llegue, y marcado de comprobantes filtrando por `branch_id`/`company_id` del propio `Voided`

## Task Commits

Each task was committed atomically:

1. **Task 1: Vocabulario de estado persistible — enum, columnas y cast** - `674d700` (feat)
2. **Task 2: Servicio de flujo del ticket — persistir, clasificar, archivar y marcar** - `23f6107` (feat)

_Note: Ningún task de este plan usaba `tdd="true"`; no hay commits `test(...)` separados._

## Files Created/Modified
- `app/Enums/VoidedStatus.php` - Enum string-backed con tres casos y `label()`/`badgeColor()`
- `database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php` - Añade `sunat_ticket`/`sunat_status` a `voideds`, nullable, sin default
- `app/Models/Voided.php` - `$fillable` + `$casts` actualizados para las dos columnas nuevas
- `app/Services/Sunat/VoidedTicketFlowService.php` - Servicio de dominio con `sendAndResolve()`, `resolveTicket()`, `classifyFailure()` públicos y `markPending()`/`markRejected()`/`markVoidedInvoices()` privados

## Decisions Made
- El `See` construido en `sendAndResolve()` se reutiliza como parámetro opcional en `resolveTicket()` para no reabrir una segunda conexión SOAP en el mismo flujo de envío; cuando `resolveTicket()` se invoca solo (futura reconsulta de la Fase 2), construye su propio `See` vía `UtilService::getSee()`.
- No existe un método privado `markAccepted()`: el plan solo exige un único punto de escritura por desenlace, y la rama de aceptación en `resolveTicket()` ya es ese único punto (a diferencia de pendiente/rechazada, que cada uno tiene dos o tres puntos de invocación).
- Se agregó una rama defensiva no pedida explícitamente por el plan: si `getCdrZip()` viene poblado pero `getCdrResponse()` es `null` (combinación que `ExtService::processResponse()` de Greenter no debería producir nunca, porque rellena ambos juntos), el servicio clasifica pendiente en vez de lanzar un error fatal al llamar `$cdr->getCode()` sobre `null`. Es un `[Rule 2 - Missing Critical]` de defensa contra un `TypeError`, no un cambio de la semántica de negocio descrita en el plan.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Guardia defensiva para CDR ausente con ZIP presente**
- **Found during:** Task 2 (escritura de `resolveTicket()`)
- **Issue:** El plan asume que "si hay ZIP, archivarlo" y "si el código del CDR es X" son pasos consecutivos, pero no contempla explícitamente el caso en que `getCdrZip()` devuelva contenido y `getCdrResponse()` devuelva `null` — llamar `$cdr->getCode()` sobre `null` sería un error fatal de PHP (`Call to a member function getCode() on null`)
- **Fix:** Se añadió una comprobación `if (!$cdr) { return $this->markPending(...); }` inmediatamente después de archivar el ZIP y antes de leer el código del CDR
- **Files modified:** app/Services/Sunat/VoidedTicketFlowService.php
- **Verification:** Lectura de `ExtService::processResponse()` (`vendor/greenter/ws/src/Ws/Services/ExtService.php:65-103`) confirma que en la práctica `cdrZip` y `cdrResponse` siempre se rellenan juntos desde `$status->content`, por lo que esta rama es puramente defensiva y no debería ejecutarse contra SUNAT real; no altera ningún criterio de aceptación del plan
- **Committed in:** `23f6107` (commit del Task 2)

**2. [Verificación] Ajuste del comando de verificación de nullable/sin-default por driver de base de datos**
- **Found during:** Task 1 (verificación automatizada)
- **Issue:** El comando de verificación literal del plan (`$x['default'] === null`) falla en este entorno porque `Schema::getColumns()` sobre MySQL/MariaDB devuelve el string literal `"NULL"` (no `null` de PHP) para columnas sin default explícito — se confirmó comparando contra la columna preexistente `hash`, que tiene exactamente el mismo comportamiento y nunca tuvo default
- **Fix:** Ninguno en el código; se verificó manualmente con `var_dump()` que `nullable === true` (bool) y que el string de `default` es el mismo `"NULL"` que ya produce `hash`, confirmando que la migración no introduce ningún default explícito, que es la intención real de D-01/D-13
- **Files modified:** ninguno
- **Verification:** `var_dump()` sobre `sunat_ticket`, `sunat_status` y `hash` en `voideds` muestra el mismo patrón para las tres columnas
- **Committed in:** N/A (hallazgo de verificación, no cambio de código)

---

**Total deviations:** 2 (1 auto-fijado por Regla 2, 1 nota de verificación sin cambio de código)
**Impact on plan:** Ninguno de los dos deviations cambia el comportamiento descrito en `must_haves` ni en `acceptance_criteria`. El primero es una guardia de seguridad adicional; el segundo es una discrepancia entre el comando de verificación literal del plan y el driver de base de datos real del proyecto, sin impacto en el resultado (D-01/D-13 se cumplen).

## Issues Encountered
Ninguno que bloqueara el plan. El único punto de fricción fue el comando de verificación de nullable/default documentado arriba, resuelto por inspección directa en vez de por el comando literal.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- `App\Enums\VoidedStatus` y `App\Services\Sunat\VoidedTicketFlowService` quedan listos para que el Plan 02 (`VoidedForm::send()`) y el Plan 03 (`VoidedTable::sendXml()`) deleguen en ellos, cumpliendo D-11 (eliminar la duplicación del flujo defectuoso)
- La verificación real contra SUNAT beta (D-14) sigue pendiente: este plan no invoca el servicio desde ningún llamador Livewire todavía, así que no hay tráfico SOAP real que probar aquí — corresponde al Plan 02
- Sin bloqueos para continuar con el Plan 02
