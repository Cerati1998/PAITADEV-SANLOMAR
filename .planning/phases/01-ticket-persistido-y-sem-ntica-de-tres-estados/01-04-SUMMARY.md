---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
plan: 04
subsystem: sunat
tags: [greenter, sunat, comunicacion-de-baja, voided, multi-tenant]

# Dependency graph
requires:
  - phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
    provides: VoidedTicketFlowService (Plan 01), ticket persistido y clasificación por excepción (Plan 02/03)
provides:
  - "classifyFailure() con invariante de no degradar un desenlace ya confirmado por CDR (Aceptada o Rechazada-con-ticket)"
  - "Rama de aceptación transaccional en resolveTicket(): escritura de estado + markVoidedInvoices() atómicas, con refresh() tras reversión"
  - "markVoidedInvoices() filtrado por production, además de branch_id y company_id"
affects: [01-05, 01-06, auditoria-de-fase-01]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Invariante de no-degradación: antes de clasificar por excepción, comprobar si el desenlace ya fue confirmado por SUNAT y devolver sin escribir"
    - "DB::transaction() + refresh() tras catch para resincronizar atributos en memoria de un modelo Eloquent tras una reversión"

key-files:
  created: []
  modified:
    - app/Services/Sunat/VoidedTicketFlowService.php

key-decisions:
  - "El caso 2 de la invariante (Rechazada con ticket) se incluyó aunque el missing del Gap 1 solo enumeraba el caso Aceptada, porque es la misma clase de defecto por el mismo disparador (showResponse() lanzando tras un rechazo-por-CDR ya persistido)"
  - "El motivo 'fallo_transporte' se reutiliza en el catch de la transacción en vez de crear un cuarto motivo, porque D-04 fija que el motivo es informativo"
  - "El filtro production no se castea ni se normaliza: se pasa tal cual llega del modelo (0/1 del driver), igual que branch_id y company_id"

requirements-completed: [TICKET-04, TICKET-05, WEB-05]

coverage:
  - id: D1
    description: "classifyFailure() no reescribe un desenlace ya confirmado por SUNAT (Aceptada, o Rechazada con ticket) cuando ocurre un fallo posterior"
    requirement: "TICKET-04"
    verification:
      - kind: manual_procedural
        ref: "storage/app/verify-01-04.php (script temporal, borrado tras ejecutar) — 4 escenarios: OK-ACEPTADA-NO-DEGRADA, OK-RECHAZO-CDR-NO-DEGRADA, OK-SIN-TICKET-SIGUE-RECHAZANDO, OK-CON-TICKET-SIGUE-PENDIENTE"
        status: pass
    human_judgment: true
    rationale: "Verificado en runtime con un script desechable dentro de una transacción revertida (no toca la BD real), pero el contrato del plan exige además una verificación manual contra el ambiente beta de SUNAT (D-14) con un envío real, que no se ha ejecutado en esta sesión."
  - id: D2
    description: "La rama de aceptación (escritura de estado + marcado de comprobantes) es atómica: si markVoidedInvoices() u otra escritura posterior falla, la fila revierte a Pendiente conservando el ticket"
    requirement: "TICKET-05"
    verification:
      - kind: manual_procedural
        ref: "Lectura de código: DB::transaction() envuelve las 4 asignaciones + save() + markVoidedInvoices(); catch(\\Throwable) llama refresh() y markPending()"
        status: pass
    human_judgment: true
    rationale: "No hay infraestructura de test con SUNAT simulado en esta fase (D-14); la atomicidad se verificó por lectura de código y por los cuatro escenarios de classifyFailure(), no con una prueba que fuerce el rollback real de la transacción."
  - id: D3
    description: "markVoidedInvoices() filtra por production, evitando que una baja de prueba en beta marque como anulada la factura real de producción"
    requirement: "WEB-05"
    verification:
      - kind: manual_procedural
        ref: "storage/app/verify-01-04-production.php (script temporal, borrado tras ejecutar) — DB::pretend() captura la consulta UPDATE con los 6 filtros (tipoDoc, serie, correlativo, branch_id, company_id, production); tokens OK-PRODUCTION-EN-WHERE, OK-SEIS-FILTROS"
        status: pass
    human_judgment: true
    rationale: "DB::pretend() confirma la forma de la consulta sin ejecutarla contra datos reales; el contrato del plan exige además verificación manual contra beta (D-14) comparando una factura de producción y una de beta con la misma serie/correlativo, no ejecutada en esta sesión."

# Metrics
duration: 12min
completed: 2026-08-05
status: complete
---

# Phase 01 Plan 04: Cierre de gaps de dominio (classifyFailure y filtro production) Summary

**`VoidedTicketFlowService` deja de poder degradar una aceptación de SUNAT a rechazo tras un fallo posterior, la escritura de aceptación + marcado de comprobantes se hizo atómica, y `markVoidedInvoices()` ya no cruza beta con producción.**

## Performance

- **Duration:** ~12 min
- **Completed:** 2026-08-05T15:42:14Z
- **Tasks:** 2
- **Files modified:** 1

## Accomplishments
- `classifyFailure()` gana una invariante de no-degradación: si `sunat_status` ya es `Aceptada`, o es `Rechazada` con `sunat_ticket` no nulo, devuelve ese mismo estado sin escribir nada, con `Log::warning()` de rastro. Cierra el Gap 1 de `01-VERIFICATION.md`.
- La rama de aceptación de `resolveTicket()` queda envuelta en `DB::transaction()`: si algo falla dentro (incluido `markVoidedInvoices()`), la fila revierte a `Pendiente` con su ticket vivo tras un `refresh()` que resincroniza el modelo en memoria.
- `markVoidedInvoices()` suma un sexto filtro `where('production', $voided->production)`, leído del modelo recibido, nunca de `session()`. Cierra el Gap 4.

## Task Commits

Cada tarea se commiteó de forma atómica:

1. **Tarea 1: classifyFailure() no degrada un desenlace confirmado + aceptación atómica** - `ef1ec8e` (fix)
2. **Tarea 2: markVoidedInvoices() filtra por production** - `790f4cd` (fix)

**Plan metadata:** (pendiente, se genera en el commit final de este plan)

## Files Created/Modified
- `app/Services/Sunat/VoidedTicketFlowService.php` - Invariante de no-degradación en `classifyFailure()`, transacción + `refresh()` en la rama de aceptación de `resolveTicket()`, y filtro `production` en `markVoidedInvoices()`

## Decisions Made
- Se incluyó el caso "Rechazada con ticket" en la invariante de `classifyFailure()`, aunque el `missing` del Gap 1 solo enumeraba literalmente el caso "Aceptada" — es la misma clase de defecto (el estado registrado deja de coincidir con lo que SUNAT confirmó) alcanzable por el mismo disparador (`showResponse()` lanzando tras un rechazo-por-CDR ya persistido con ticket). Documentado explícitamente en el plan para que la revisión lo pueda juzgar.
- El motivo `'fallo_transporte'` se reutilizó en el `catch` de la nueva transacción en vez de introducir un cuarto motivo, porque D-04 fija que el motivo es solo informativo y la clasificación no depende de él.
- El valor de `production` no se castea: se pasa tal cual llega del modelo `Voided` (que no lo declara en `$casts`), igual que ya ocurre con `branch_id` y `company_id` en el mismo método.

## Deviations from Plan

None - plan ejecutado tal como estaba escrito. Los dos scripts de verificación en runtime (`storage/app/verify-01-04.php` y `storage/app/verify-01-04-production.php`) se escribieron y ejecutaron según lo especificado en `<acceptance_criteria>`, y se borraron al terminar cada tarea; ninguno quedó listado en `git status --porcelain`.

**Nota sobre el criterio de bindings de la Tarea 2.** El plan proponía, como forma "equivalente y más simple" de comprobar los seis filtros, que el array de `bindings` de la consulta capturada por `DB::pretend()` tuviera longitud `7` (6 filtros + `voided = true`). En la ejecución real esa longitud fue `8`, porque `Invoice` mantiene timestamps automáticos y el `UPDATE` añade un binding adicional para `updated_at` que el plan no había anticipado. El criterio primario del plan — "la consulta capturada... contiene los seis nombres de columna" y "exactamente 6 marcadores de posición en su cláusula where" — sí se cumple: se verificó contando las 6 comparaciones de igualdad dentro de la cláusula `where` de la consulta interpolada que devuelve `DB::pretend()` (que no deja marcadores `?`, sustituye los valores directamente en la cadena SQL). No fue necesario ni deseable tocar `Invoice` para desactivar timestamps: es un cambio de comportamiento fuera del alcance del cierre de gaps. No es una desviación de código, solo un ajuste del script de verificación desechable frente a una asunción del propio plan que no se sostenía en runtime.

## Issues Encountered
None.

## User Setup Required
None - no se requiere configuración externa.

## Verificación manual pendiente (D-14)

Este plan **no** incluye la verificación manual contra el ambiente beta de SUNAT descrita en `<human-check>` de `01-04-PLAN.md` (envío real de una comunicación de baja, revisión de `sunat_status`/`sunat_ticket`/`cdr_path` en BD, y comparación de una factura de producción vs. una de beta con la misma serie/correlativo). Las verificaciones automatizadas (scripts desechables sobre `storage/app/`, sin tocar la BD real ni SUNAT) pasaron íntegras. La verificación contra beta queda pendiente de ejecución manual por el usuario o en una sesión posterior, tal como establece D-14 (no se construye infraestructura de test con SUNAT simulado en esta fase).

## Next Phase Readiness
- Los dos gaps del Plan 01 (Gap 1 y Gap 4 de `01-VERIFICATION.md`) quedan cerrados a nivel de código y verificación automatizada; falta la confirmación manual contra beta (D-14) antes de dar la fase por completamente cerrada.
- El Plan 01-05 (selección de columnas del listado, Gaps 2 y 3) corre en paralelo sobre un archivo distinto (`app/Livewire/VoidedTable.php`) y no tiene conflicto con este plan.
- `app/Livewire/VoidedTable.php`, `app/Livewire/Forms/VoidedForm.php` y todo lo relacionado con guías de remisión quedaron intactos, tal como exigía el plan.

---
*Phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados*
*Completed: 2026-08-05*

## Self-Check: PASSED

- FOUND: app/Services/Sunat/VoidedTicketFlowService.php
- FOUND: .planning/phases/01-ticket-persistido-y-sem-ntica-de-tres-estados/01-04-SUMMARY.md
- FOUND: ef1ec8e (Tarea 1 commit)
- FOUND: 790f4cd (Tarea 2 commit)
- FOUND: 1922cc0 (SUMMARY commit)
