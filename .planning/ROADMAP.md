# Roadmap: INSPECTEC SERVICES / SANLOMAR — Anulación SUNAT

## Overview

Hoy la comunicación de baja pierde el ticket que SUNAT devuelve y guarda como
rechazo lo que en realidad fue una consulta fallida, de modo que el sistema
contradice a SUNAT. El milestone recorre el camino en tres tramos: primero el
envío deja de mentir (el ticket se persiste antes de consultar y el estado
guardado distingue aceptada / pendiente / rechazada), después el usuario obtiene
una vía real de recuperación desde el listado que reconsulta con el ticket en vez
de reenviar el XML, y por último la misma semántica llega a la API, que hoy ni
siquiera responde. El patrón no se inventa: se porta el de `Despatch`, ya probado
en producción para guías de remisión.

## Phases

**Phase Numbering:**

- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Ticket persistido y semántica de tres estados** - El envío web guarda el ticket antes de consultar y registra el estado real que devolvió SUNAT (3/6 planes ejecutados; verificación con gaps 2026-08-04; planes de cierre 01-04, 01-05 y 01-06 pendientes de ejecutar)
- [ ] **Phase 2: Reconsulta de anulaciones pendientes** - El usuario recupera el CDR desde el listado usando el ticket guardado, sin reenviar el XML
- [ ] **Phase 3: Anulación por API con recuperación por ticket** - El endpoint deja de romperse y expone el mismo comportamiento de ticket que la vía web

## Phase Details

### Phase 1: Ticket persistido y semántica de tres estados

**Goal**: Una comunicación de baja enviada desde la web conserva el ticket que SUNAT devolvió, y el estado que queda registrado refleja lo que SUNAT realmente respondió — aceptada, pendiente o rechazada
**Mode:** mvp
**Depends on**: Nothing (first phase)
**Requirements**: TICKET-01, TICKET-02, TICKET-03, TICKET-04, TICKET-05, WEB-01, WEB-05
**Success Criteria** (what must be TRUE):

  1. Tras enviar una comunicación de baja contra el ambiente beta, la fila de `voideds` tiene guardado el ticket que devolvió SUNAT, escrito antes de cualquier consulta de estado.
  2. Cuando la consulta de estado responde código `98` o falla por transporte (`SoapFault`, el literal `"Bad Request"`), el usuario ve un aviso de "en proceso", el ticket sigue guardado y `sunatResponse` no queda escrito como rechazo.
  3. Cuando SUNAT entrega el CDR en el mismo envío, la anulación queda registrada como aceptada, el ZIP del CDR queda archivado en el disco configurado y el ticket queda vacío.
  4. Los comprobantes incluidos en la anulación aparecen como dados de baja únicamente después de un CDR aceptado (código `0`); si la baja quedó pendiente, siguen sin marcar.
  5. El estado guardado de una anulación se lee como uno de tres valores explícitos — aceptada, pendiente o rechazada — en lugar del `success: true/false` actual.

**Plans**: 5/6 plans executed

Plans:
**Wave 1**

- [x] 01-01-PLAN.md — Enum `VoidedStatus`, columnas `sunat_ticket`/`sunat_status` y el servicio `VoidedTicketFlowService` que concentra la semántica de los tres estados

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 01-02-PLAN.md — `VoidedForm::send()` delega en el servicio, avisa "En proceso" y el modal de detalle muestra los tres estados

**Wave 3** *(blocked on Wave 2 completion)*

- [x] 01-03-PLAN.md — Vista propia del listado con los tres estados y `VoidedTable::sendXml()` sin reenvío cuando ya hay ticket

**Cierre de gaps — Wave 1** *(de la verificación 2026-08-04, 5/9 must-haves; ambos planes en paralelo, no comparten archivo)*

- [x] 01-04-PLAN.md — `classifyFailure()` deja de degradar una aceptación a rechazo, la aceptación se escribe de forma atómica y `markVoidedInvoices()` filtra por `production` (gaps 1 y 4)
- [x] 01-05-PLAN.md — El listado selecciona `sunat_ticket`/`sunatResponse` y la guarda de servidor de `sendXml()` refleja el árbol de decisión de la vista (gaps 2 y 3)

**Cierre de gaps — Wave 2** *(blocked on Wave 1; serializado a propósito — 01-05 verifica que no toca ninguna vista y 01-06 sí modifica una)*

- [ ] 01-06-PLAN.md — CR-06 de `01-REVIEW.md`: el mensaje de SUNAT deja de renderizarse sin escapar en el modal de detalle (XSS almacenado vía `desMotivoBaja`)

### Phase 2: Reconsulta de anulaciones pendientes

**Goal**: El usuario recupera desde el listado el resultado final de una anulación pendiente, y el listado dice la verdad sobre en qué estado está cada baja
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: WEB-02, WEB-03, WEB-04
**Success Criteria** (what must be TRUE):

  1. En el listado de anulaciones, una baja pendiente ofrece la acción "Consultar estado", y al usarla el sistema consulta el ticket guardado — el XML no se reenvía y SUNAT no responde `2109` (*la comunicación ya fue informada*).
  2. Si la anulación no tiene ticket guardado (el envío nunca llegó a SUNAT), esa misma acción sí reenvía el XML y guarda el ticket que SUNAT devuelva.
  3. Al obtenerse el CDR por reconsulta, la anulación pasa a aceptada, el CDR queda archivado, el ticket se limpia y los comprobantes quedan dados de baja aplicando la misma regla de la Fase 1.
  4. El listado distingue a simple vista las tres situaciones — aceptada, pendiente y rechazada — de modo que una baja que SUNAT aceptó nunca se pinta en rojo.

**Plans**: TBD

### Phase 3: Anulación por API con recuperación por ticket

**Goal**: Un cliente de la API puede anular comprobantes y llegar al resultado final aunque SUNAT no termine de procesar durante el envío
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: API-01, API-02, API-03
**Success Criteria** (what must be TRUE):

  1. `POST api/voided/send` con un token de empresa válido responde JSON en vez de fallar con `Call to undefined method`, y entrega el CDR cuando SUNAT lo devuelve de inmediato.
  2. Cuando SUNAT responde `98` o la consulta falla por transporte, la respuesta indica estado pendiente e incluye el ticket, no un error de rechazo.
  3. Un cliente que envía ese ticket al endpoint de consulta recibe el CDR si ya está listo, o de nuevo "pendiente" si SUNAT sigue procesando — en ningún caso se reenvía el XML.
  4. El endpoint de consulta exige el mismo token que el resto de la API y consulta con las credenciales SOL de la empresa autenticada.

**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Ticket persistido y semántica de tres estados | 5/6 | In Progress|  |
| 2. Reconsulta de anulaciones pendientes | 0/TBD | Not started | - |
| 3. Anulación por API con recuperación por ticket | 0/TBD | Not started | - |

## Notes

**Cómo se verifica.** SUNAT es un tercero real: toda verificación de fase se hace
contra el ambiente beta (`production = false` en la empresa de sesión) antes de
tocar producción. El caso pendiente se reproduce enviando la baja y consultando
el ticket de inmediato, que es lo que dispara el `98` o el `SoapFault`.

**Restricciones que atraviesan las tres fases** (de `CLAUDE.md` y `PROJECT.md`):

- Nada bajo `vendor/` se edita, y a los modelos de Greenter no se les agregan
  propiedades ni setters. Greenter queda como modelos, firma, envío y lectura de CDR.

- La anulación viaja por SOAP (`Greenter\See`), no por la API REST GRE que usan
  las guías; por eso el manejo de errores cubre el código `98` **y** el
  `SoapFault` de transporte.

- Toda consulta nueva filtra por `session('company')` y `session('branch')`: no
  hay scope global multi-tenant.

- El patrón de referencia es `Despatch` (`sunat_ticket` en la migración
  `2024_11_06_014056`, persistencia en `DespatchForm.php:292-325`, reconsulta en
  `DespatchTable.php:186-242`). Se porta, no se rediseña.

**Fuera del roadmap pero relacionado.** El resumen diario comparte el patrón de
ticket (v2: SUM-01, SUM-02) y el sondeo automático de CDRs pendientes (v2:
POLL-01, POLL-02) quedaron deliberadamente fuera. La Fase 1 debería dejar la
semántica de estados en un sitio reutilizable para que ese trabajo futuro no
tenga que reescribirla.

---
*Roadmap created: 2026-08-04*
