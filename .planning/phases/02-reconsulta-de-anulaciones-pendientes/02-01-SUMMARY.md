---
phase: 02-reconsulta-de-anulaciones-pendientes
plan: 01
subsystem: sunat-invoicing
tags: [laravel, livewire, sunat, voided, greenter, jetstream]

# Dependency graph
requires:
  - phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
    provides: "sunat_ticket/sunat_status persistidos, VoidedTicketFlowService::resolveTicket()/classifyFailure(), VoidedStatus de tres casos"
provides:
  - "VoidedTable::sendXml() con árbol de tres ramas: Consultar / Avisar-desenlace-confirmado / Avisar-no-se-puede-reenviar"
  - "VoidedTable::cdrPathFor() como único punto de cálculo de la ruta del CDR, leyendo el RUC de $voided->company"
  - "response.blade.php rama Pendiente disparando sendXml() en vez de showResponse()"
affects: [03-endpoint-api-de-anulacion, 04-autorizacion-de-acciones-livewire]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Guarda de servidor de tres ramas con early-return, evaluada antes de cualquier llamada a SUNAT (D-02)"
    - "Cálculo de ruta de almacenamiento (CDR) centralizado en un método privado que lee el ámbito multi-tenant del modelo recibido, no de session()"

key-files:
  created: []
  modified:
    - app/Livewire/VoidedTable.php
    - resources/views/voideds/partials/response.blade.php

key-decisions:
  - "D-04 aplicado en las dos ramas que archivan CDR (reconsulta y reenvío) vía un único método cdrPathFor(), eliminando la lectura de session('company')->ruc"
  - "El catch de la rama Consultar, cuando classifyFailure() no devuelve Pendiente, muestra showResponse() y además un swal de error con el mensaje de la excepción — mismo criterio que la rama de reenvío"
  - "canResendXml() no se reescribe (D-03): sigue siendo lista de permitidos; solo se amplía su comentario para documentar por qué Pendiente queda deliberadamente fuera"

patterns-established:
  - "Un solo método de acción por columna (sendXml) decide internamente si consulta, avisa o reenvía — ver D-01/D-02 en 02-CONTEXT.md"

requirements-completed: [WEB-02, WEB-03]

coverage:
  - id: D1
    description: "Una anulación Pendiente con sunat_ticket guardado reconsulta ese ticket vía resolveTicket() en vez de reenviar el XML (WEB-02)"
    requirement: "WEB-02"
    verification:
      - kind: manual_procedural
        ref: "grep -c 'resolveTicket(' app/Livewire/VoidedTable.php (>=1, sin tercer parámetro $see) + human-check contra SUNAT beta (D-14)"
        status: unknown
    human_judgment: true
    rationale: "El recorrido real depende de la respuesta de SUNAT beta (código 98 o CDR); no hay infraestructura de test con SUNAT simulado (D-14), así que solo un humano puede confirmar el resultado contra el ambiente real."
  - id: D2
    description: "Si SUNAT sigue procesando, swal 'Aún en proceso' y el ticket permanece guardado (D-09)"
    verification:
      - kind: unit
        ref: "grep -c 'Aún en proceso' app/Livewire/VoidedTable.php == 2 (rama try y rama catch)"
        status: pass
    human_judgment: false
  - id: D3
    description: "Si SUNAT ya entregó el CDR, showResponse() muestra el detalle y la fila queda Aceptada o Rechazada según el código del propio CDR (D-09)"
    verification:
      - kind: unit
        ref: "sendXml() no contiene llamadas a sendXmlFile( ni markVoidedInvoices( — el estado y el marcado los aplica VoidedTicketFlowService::resolveTicket(), no el componente"
        status: pass
    human_judgment: true
    rationale: "El código delega correctamente en el servicio ya probado en la Fase 1, pero el desenlace final (Aceptada/Rechazada) solo se confirma contra una respuesta real de SUNAT beta (D-14)."
  - id: D4
    description: "Una anulación sin ticket sigue reenviando el XML y guardando el ticket que SUNAT devuelva (WEB-03)"
    requirement: "WEB-03"
    verification:
      - kind: unit
        ref: "grep -c 'sendAndResolve' app/Livewire/VoidedTable.php == 1; canResendXml() conserva sus dos casos true sin incluir Pendiente"
        status: pass
    human_judgment: false
  - id: D5
    description: "Una anulación Aceptada, o Rechazada que conserva ticket, no llama a SUNAT y recibe un swal explicativo (D-02 fila 3, D-12)"
    verification:
      - kind: unit
        ref: "grep -c 'Desenlace confirmado' app/Livewire/VoidedTable.php >=1; grep -c '2109' conserva el literal de la rama 'No se puede reenviar'"
        status: pass
    human_judgment: false
  - id: D6
    description: "La ruta del ZIP del CDR se arma con el RUC de la empresa dueña de la fila, no con el de la empresa de la sesión (D-04)"
    verification:
      - kind: unit
        ref: "grep -vE '^\\s*(//|\\*|/\\*)' app/Livewire/VoidedTable.php | grep -c \"session('company')->ruc\" == 0; cdrPathFor() lee \\$voided->company['ruc']"
        status: pass
    human_judgment: false
  - id: D7
    description: "Verificación manual contra el ambiente beta de SUNAT con production = false (D-14): CDR se recupera sin que SUNAT responda 2109"
    verification: []
    human_judgment: true
    rationale: "SUNAT es un tercero real; el recorrido de código 98 / SoapFault de transporte / CDR recuperado solo se puede verificar manualmente en beta. Queda pendiente como ítem de UAT de la fase, igual que hizo la Fase 1 en 01-UAT.md."

# Metrics
duration: 8min
completed: 2026-08-05
status: complete
---

# Phase 2 Plan 1: Reconsulta de extremo a extremo Summary

**`VoidedTable::sendXml()` reescrito con un árbol de tres ramas (Consultar / Avisar-desenlace-confirmado / Avisar-no-se-puede-reenviar) que reconsulta el `sunat_ticket` pendiente vía `VoidedTicketFlowService::resolveTicket()` en vez de reenviar el XML, y un nuevo método `cdrPathFor()` que archiva el CDR con el RUC del modelo, no de la sesión.**

## Performance

- **Duration:** 8 min
- **Started:** 2026-08-05T17:22:15Z (aprox., heredado del inicio de sesión de ejecución de la fase)
- **Completed:** 2026-08-05T17:27:56Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- `sendXml()` implementa el árbol de tres ramas de D-02: **Consultar** (Pendiente con ticket, vía `resolveTicket()`), **Avisar — desenlace confirmado** (Rechazada con ticket, D-12) y **Avisar — no se puede reenviar** (el resto, mensaje `2109` conservado).
- Nuevo método privado `cdrPathFor(Voided $voided): string`, único punto del fichero que compone la ruta `cdr/R-{ruc}-RC-{Ymd}-{correlativo}.zip`, leyendo el RUC de `$voided->company['ruc']` (D-04) en vez de `session('company')->ruc`.
- La rama de reenvío (WEB-03) consume el mismo `cdrPathFor()`, quedando un único cálculo de esa ruta en todo el componente.
- La rama Pendiente de `response.blade.php` dispara `sendXml({{ $row->id }})` en vez de `showResponse({{ $row->id }})`, conservando el icono `clock` ámbar — cubre WEB-02 sin abrir un segundo punto de entrada.

## Task Commits

Cada tarea se comprometió atómicamente:

1. **Tarea 1: La reconsulta de extremo a extremo — ruteo de tres ramas y botón de la fila pendiente** - `a046e37` (feat)
2. **Tarea 2: El reenvío archiva el CDR bajo el RUC de la empresa dueña de la fila** - `2af0364` (fix)

_Nota: la metadata de esta fase (SUMMARY.md, STATE.md, ROADMAP.md) se compromete en un commit separado tras este resumen._

## Files Created/Modified
- `app/Livewire/VoidedTable.php` - `sendXml()` reescrito con árbol de tres ramas; nuevo método privado `cdrPathFor()`; rama de reenvío consume `cdrPathFor()`; comentario de `canResendXml()` ampliado
- `resources/views/voideds/partials/response.blade.php` - rama Pendiente dispara `sendXml()` en vez de `showResponse()`; comentario actualizado

## Decisions Made
- El catch de la rama Consultar, cuando `classifyFailure()` devuelve un estado distinto de `Pendiente`, muestra `showResponse($voided)` **y además** un swal de error con `$e->getMessage()` (título "Error al consultar el comprobante") — el plan pedía "resolverlo como ya lo resuelve la rama de reenvío" sin fijar el título exacto para este caso; se usó un título distinto al de la rama de reenvío ("Error al enviar el comprobante") porque describe con más precisión la acción que falló (consultar, no enviar). Ningún criterio de aceptación fija ese literal.
- `canResendXml()` no se tocó en su lógica (D-03): solo se amplió el comentario que la encabeza para dejar constancia de que `Pendiente` queda deliberadamente fuera de la lista de permitidos.

## Deviations from Plan

None - plan ejecutado tal como fue escrito. Los dos cambios de Tarea 1 (ruteo de servidor + botón de vista) se entregaron juntos en el mismo commit, como pedía el plan por ser la misma capacidad.

## Issues Encountered
None.

## User Setup Required

None - no se requiere configuración externa.

## Next Phase Readiness

- El listado de anulaciones ya reconsulta correctamente el ticket pendiente sin reenviar el XML; WEB-02 y WEB-03 quedan cubiertos a nivel de código.
- **Pendiente para el UAT de la fase (D-14):** verificación manual contra el ambiente beta de SUNAT (`production = false`), forzando el código `98` o el `SoapFault` de transporte en una comunicación de baja Pendiente, y comprobando que:
  (a) SUNAT no responde `2109` al pulsar la acción de la columna Sunat,
  (b) si ya hay CDR, la fila pasa a Aceptada, el ZIP queda descargable desde la columna CDR y los comprobantes de la baja quedan marcados,
  (c) si SUNAT sigue procesando, aparece el swal "Aún en proceso" y el ticket permanece.
  Adicionalmente, verificar que el reenvío normal (empresa de sesión = empresa de la fila) sigue archivando el CDR en la misma ruta que antes de este plan (`sunat/beta/cdr/R-{ruc}-RC-{Ymd}-{correlativo}.zip`), confirmando que mover la procedencia del RUC (D-04) no rompió el caso normal.
- Ninguno de estos ítems es automatizable sin infraestructura de test con SUNAT simulado, decisión ya tomada y heredada de la Fase 1.
- El plan 02-02 de esta fase (badge de texto D-06 y `SelectFilter` D-07) puede empezar sin bloqueos: no depende de ningún símbolo introducido por este plan más allá de los ya existentes (`VoidedStatus::label()`/`badgeColor()`).

---
*Phase: 02-reconsulta-de-anulaciones-pendientes*
*Completed: 2026-08-05*

## Self-Check: PASSED
- app/Livewire/VoidedTable.php: FOUND
- resources/views/voideds/partials/response.blade.php: FOUND
- Commit a046e37 (Tarea 1): FOUND
- Commit 2af0364 (Tarea 2): FOUND
