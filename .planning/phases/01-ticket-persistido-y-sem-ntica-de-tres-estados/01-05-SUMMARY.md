---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
plan: 05
subsystem: sunat
tags: [livewire, laravel-livewire-tables, sunat, comunicacion-de-baja, voided, multi-tenant]

# Dependency graph
requires:
  - phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
    provides: ticket persistido y clasificación por excepción (Plan 01/02/03), classifyFailure() sin degradación (Plan 04)
provides:
  - "VoidedTable::configure() selecciona sunat_ticket y sunatResponse vía setAdditionalSelects(), alimentando con datos reales el partial de la columna Sunat"
  - "VoidedTable::canResendXml(Voided): bool — predicado privado, lista de permitidos, que replica hoja por hoja el árbol de voideds/partials/response.blade.php"
  - "sendXml() delega su guarda inicial en canResendXml(), neutralizando también el botón de la columna CDR compartida"
affects: [01-06, auditoria-de-fase-01]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "setAdditionalSelects() para columnas que un partial necesita leer pero que no son Column::make() visibles (rappasoft/laravel-livewire-tables), siguiendo el patrón ya probado en DespatchTable"
    - "Predicado privado como lista de permitidos (no de bloqueos) para guardas de servidor que deben tener paridad exacta con un árbol de decisión de vista, evitando el error de enumerar bloqueos y dejar fuera un caso legítimo (CR-02)"

key-files:
  created: []
  modified:
    - app/Livewire/VoidedTable.php

key-decisions:
  - "canResendXml() es una lista de permitidos (dos casos true) y no una lista de bloqueos, precisamente para no repetir el error de CR-02, cuyo fix propuesto (bloquear si sunatResponse no está vacío) hubiera bloqueado también el caso legítimo rechazada-sin-ticket de D-06"
  - "No se añadió una comprobación sobre cdr_path al predicado aunque endurecería marginalmente el caso histórico: la vista no la tiene, y un servidor más estricto que la vista produciría un botón visible que al pulsarse es rechazado — divergencia confusa. La combinación que quedaría fuera no es alcanzable por ningún camino del código actual"
  - "El botón de la columna CDR (vouchers.partials.cdr, compartido por cuatro listados) se neutraliza desde el servidor sin tocar el partial, porque editarlo afectaría facturas, boletas, notas y las dos guías de remisión — fuera del alcance de esta fase"

requirements-completed: [TICKET-01, TICKET-05, WEB-01]

coverage:
  - id: D1
    description: "El listado selecciona sunat_ticket y sunatResponse en la consulta, y el partial de la columna Sunat decide con los valores reales de BD en vez de con dos null permanentes (cierra Gap 2, D-09)"
    requirement: "WEB-01"
    verification:
      - kind: manual_procedural
        ref: "storage/app/verify-01-05.php (script temporal, borrado tras ejecutar) — getAdditionalSelects() contiene id/sunat_ticket/sunatResponse (OK-SELECTS-COMPLETOS); columns() conserva la columna Sunat sobre sunat_status (OK-COLUMNA-SUNAT-INTACTA)"
        status: pass
    human_judgment: true
    rationale: "Verificado en runtime con un script desechable que no toca la BD real (D-14 descarta infraestructura de test con SUNAT simulado), pero el contrato del plan exige además la verificación manual contra el ambiente beta de SUNAT descrita en <human-check>, que no se ha ejecutado en esta sesión."
  - id: D2
    description: "canResendXml() permite el reenvío exactamente en los dos casos legítimos (rechazada sin ticket; histórica sin ticket y sin respuesta) y lo rechaza en los otros cuatro escenarios verificados, con paridad exacta frente al render del partial de la vista (cierra el residuo del Gap 3, D-09/D-13)"
    requirement: "TICKET-01"
    verification:
      - kind: manual_procedural
        ref: "storage/app/verify-01-05.php (script temporal, borrado tras ejecutar) — 6 escenarios por reflexión sobre canResendXml() (OK-GUARDA-6-ESCENARIOS) y paridad hoja por hoja contra el render de voideds.partials.response (OK-PARIDAD-VISTA-SERVIDOR)"
        status: pass
    human_judgment: true
    rationale: "Los 6 escenarios cubren las hojas del árbol de decisión con modelos en memoria (sin BD ni red), pero el contrato del plan exige también la comprobación manual contra beta con filas reales (ítems 1-4 del <human-check>), no ejecutada en esta sesión."
  - id: D3
    description: "builder() conserva sus tres filtros multi-tenant (branch_id, company_id, production) sin ampliar el conjunto de filas leídas por añadir columnas al SELECT"
    requirement: "TICKET-05"
    verification:
      - kind: other
        ref: "grep -c \"session('branch')->id|session('company')->id|session('company')->production\" app/Livewire/VoidedTable.php — cada patrón devuelve 1"
        status: pass
    human_judgment: false

# Metrics
duration: 8min
completed: 2026-08-05
status: complete
---

# Phase 01 Plan 05: Cierre de gaps 2 y 3 del listado de anulaciones (VoidedTable) Summary

**`VoidedTable::configure()` deja de restringir el SELECT a `id`, y `sendXml()` delega su guarda en un predicado privado que replica exactamente el árbol de decisión de la vista, neutralizando también el botón de reenvío de la columna CDR compartida.**

## Performance

- **Duration:** ~8 min
- **Completed:** 2026-08-05T15:48:01Z
- **Tasks:** 2
- **Files modified:** 1

## Accomplishments
- `configure()` amplía `setAdditionalSelects()` de `['id']` a `['id', 'sunat_ticket', 'sunatResponse']`: el partial de la columna Sunat deja de recibir `null` permanente en esas dos variables y decide con el valor real de cada fila. Cierra el Gap 2 de `01-VERIFICATION.md`.
- Nuevo método privado `canResendXml(Voided $voided): bool` en `VoidedTable`: lista de permitidos que devuelve `true` únicamente en los dos casos legítimos (rechazada sin ticket, D-06; histórica sin ticket y sin respuesta, D-13) y `false` en cualquier otra combinación.
- `sendXml()` consulta `canResendXml()` como primera sentencia condicional, antes de calcular `$directory`, construir `$name` o tocar `Storage`/SUNAT. Con esto neutraliza también el botón de la columna CDR (`vouchers.partials.cdr`, compartida por cuatro listados), que ofrecía la acción a toda fila sin `cdr_path` sin mirar el estado — sin necesidad de editar ese partial. Cierra el residuo del Gap 3.
- El aviso de guarda distingue dos textos: "En proceso" cuando hay ticket guardado (igual que antes), y el nuevo "No se puede reenviar" cuando no hay ticket pero sí una respuesta de SUNAT ya registrada (aceptada, o histórica con respuesta).

## Task Commits

Cada tarea se commiteó de forma atómica:

1. **Tarea 1: el listado selecciona sunat_ticket y sunatResponse** - `e8fc321` (fix)
2. **Tarea 2: la guarda de servidor de sendXml() refleja el árbol de decisión de la vista** - `736b028` (fix)

**Plan metadata:** (pendiente, se genera en el commit final de este plan)

## Files Created/Modified
- `app/Livewire/VoidedTable.php` - `setAdditionalSelects()` amplía a `sunat_ticket`/`sunatResponse`; nuevo predicado privado `canResendXml()`; guarda de `sendXml()` delegada en el predicado con dos textos de aviso

## Decisions Made
- `canResendXml()` se escribió como lista de permitidos (dos `true` explícitos, `false` por defecto) y no como lista de bloqueos, para no repetir el error de CR-02: su fix propuesto (bloquear si `sunatResponse` no está vacío) habría bloqueado también el caso legítimo rechazada-sin-ticket, cuyo `sunatResponse` sí lo escribe `markRejected()`.
- No se añadió una comprobación de `cdr_path` al predicado, aunque endurecería marginalmente el caso histórico: la vista no la tiene, y divergir haría que el servidor rechazara un botón que la vista sigue mostrando como disponible. La combinación que quedaría fuera no es alcanzable con el código actual (archivado del ZIP y escritura de `sunatResponse` ocurren juntos en la rama de aceptación).
- El botón de la columna CDR se neutraliza desde el servidor sin tocar `vouchers/partials/cdr.blade.php`, porque ese partial lo comparten `VoucherTable`, `DespatchTable` y `DespatchTransportistaTable` y editarlo está fuera del alcance de esta fase.

## Deviations from Plan

None - plan ejecutado tal como estaba escrito. El script de verificación en runtime `storage/app/verify-01-05.php` se escribió en dos pasos (Tarea 1 primero, ampliado en la Tarea 2 con los seis escenarios de `canResendXml()` y la comprobación de paridad vista/servidor), según lo especificado en `<acceptance_criteria>`, y se borró al terminar la Tarea 2; no quedó listado en `git status --porcelain`.

## Issues Encountered
None.

## User Setup Required
None - no se requiere configuración externa.

## Verificación manual pendiente (D-14)

Este plan **no** incluye la verificación manual contra el ambiente beta de SUNAT descrita en `<human-check>` de `01-05-PLAN.md` (comprobar en el listado real que las filas históricas y las rechazadas-con-ticket dejan de ofrecer reenvío, que el caso legítimo sigue disponible, que el botón de la columna CDR responde con el aviso "No se puede reenviar" sin generar tráfico SUNAT, y que los listados de facturas/guías no sufren regresión). Las verificaciones automatizadas (script desechable sobre `storage/app/`, sin tocar la BD real ni SUNAT) pasaron íntegras: `OK-SELECTS-COMPLETOS`, `OK-COLUMNA-SUNAT-INTACTA`, `OK-GUARDA-6-ESCENARIOS`, `OK-PARIDAD-VISTA-SERVIDOR`. La verificación contra beta queda pendiente de ejecución manual por el usuario o en una sesión posterior, tal como establece D-14.

**Nota sobre la suite de tests.** Este plan no ejecutó `vendor/bin/phpunit`: la suite del repo corre contra la base de datos configurada (no hay override sqlite habilitado) y es destructiva sin esa configuración, tal como documenta `CLAUDE.md`. La verificación se hizo con el script desechable descrito arriba, más `php -l` y `vendor/bin/pint --test`, ambos en verde para `app/Livewire/VoidedTable.php`.

## Next Phase Readiness
- Los cuatro gaps de `01-VERIFICATION.md` quedan cerrados a nivel de código y verificación automatizada (Gap 1 y Gap 4 por el Plan 01-04; Gap 2 y Gap 3 por este plan); falta la confirmación manual contra beta (D-14) en ambos planes antes de dar la Fase 1 por completamente cerrada.
- El Plan 01-06, serializado a la wave 2, corre después de este plan y modifica `resources/views/voideds/partials/sunatResponse.blade.php` — ningún archivo de vista fue tocado por este plan, así que no hay conflicto.
- `app/Services/Sunat/VoidedTicketFlowService.php` (Plan 01-04) y todo lo relacionado con guías de remisión quedaron intactos, tal como exigía el plan.
- El diff completo del plan lista exactamente un archivo: `app/Livewire/VoidedTable.php`. Nada bajo `vendor/`, nada bajo `resources/views/`, ningún archivo con `Despatch` en el nombre.

---
*Phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados*
*Completed: 2026-08-05*

## Self-Check: PASSED

- FOUND: app/Livewire/VoidedTable.php
- FOUND: .planning/phases/01-ticket-persistido-y-sem-ntica-de-tres-estados/01-05-SUMMARY.md
- FOUND: e8fc321 (Tarea 1 commit)
- FOUND: 736b028 (Tarea 2 commit)
