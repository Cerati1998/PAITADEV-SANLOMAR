# Requirements: INSPECTEC SERVICES / SANLOMAR — Anulación SUNAT

**Defined:** 2026-08-04
**Core Value:** Todo comprobante que sale hacia SUNAT — y toda respuesta que SUNAT devuelve — tiene que quedar registrado fielmente en el sistema.

## v1 Requirements

Milestone acotado a la recuperación de la respuesta final de la comunicación de
baja, en la vía web y en la API. Cada requisito mapea a una fase del roadmap.

### Ticket y estados

- [ ] **TICKET-01**: El sistema guarda el ticket que SUNAT devuelve al enviar una comunicación de baja, antes de consultar su estado
- [ ] **TICKET-02**: Una anulación cuya consulta de estado responde "en proceso" (código 98) conserva su ticket y no queda registrada como rechazada
- [ ] **TICKET-03**: Una anulación cuya consulta de estado falla por error de transporte (`SoapFault`, p. ej. `"Bad Request"`) conserva su ticket y no queda registrada como rechazada
- [ ] **TICKET-04**: Al obtenerse el CDR, el sistema guarda la respuesta de SUNAT, archiva el ZIP del CDR y limpia el ticket
- [ ] **TICKET-05**: El registro de una anulación distingue tres estados — aceptada, pendiente y rechazada — en lugar de solo éxito/error

### Anulación web

- [ ] **WEB-01**: Al enviar una anulación que SUNAT aún no termina de procesar, el usuario ve un aviso de "en proceso" y no un error
- [ ] **WEB-02**: El usuario puede reconsultar el estado de una anulación pendiente desde el listado, sin reenviar el XML
- [ ] **WEB-03**: El reenvío del XML a SUNAT solo ocurre cuando la anulación no tiene ticket guardado
- [ ] **WEB-04**: El usuario distingue en el listado de anulaciones cuáles fueron aceptadas, cuáles siguen pendientes y cuáles rechazó SUNAT
- [ ] **WEB-05**: Los comprobantes incluidos en una anulación se marcan como dados de baja únicamente cuando el CDR la acepta

### Anulación por API

- [ ] **API-01**: El endpoint de envío de anulación responde correctamente en vez de fallar con `Call to undefined method`
- [ ] **API-02**: La respuesta del endpoint de envío incluye el ticket cuando SUNAT todavía no terminó de procesar la baja
- [ ] **API-03**: Un cliente de la API puede consultar el estado de una anulación pendiente entregando su ticket, y recibe el CDR cuando ya está listo

## v2 Requirements

Reconocidos pero fuera del roadmap actual.

### Resumen diario

- **SUM-01**: El resumen diario persiste su ticket y aplica la misma semántica de estados que la anulación
- **SUM-02**: El usuario puede reconsultar un resumen diario pendiente sin reenviarlo

### Sondeo automático

- **POLL-01**: Un comando programado recupera en lote los CDR de las anulaciones que quedaron pendientes
- **POLL-02**: El operador recibe visibilidad de las anulaciones que llevan pendientes más de un umbral de tiempo

## Out of Scope

| Feature | Reason |
|---------|--------|
| Test de regresión de las plantillas SUNAT | Milestone aparte, ya capturado como todo pendiente; toca las siete plantillas y no comparte código con la anulación |
| Cifrado de credenciales SOL y del certificado en `companies` | Cambio transversal de seguridad; no bloquea la recuperación del ticket |
| Quitar credenciales de prueba hardcodeadas y `'debug' => true` en `UtilService` | Deuda técnica menor, sin relación con el ticket |
| Comando de sondeo automático de CDR pendientes | El usuario acotó el milestone a web + API; la reconsulta manual cubre el caso reportado |
| Resumen diario (`SummaryController`) | Comparte el patrón de ticket pero quedó fuera del alcance acordado |
| Revalidación de la sucursal de sesión contra empresa/usuario | Hallazgo de `CONCERNS.md`, ajeno a este milestone |
| `$fillable`/`$guarded` faltante en 18 modelos | Hallazgo de `CONCERNS.md`, ajeno a este milestone |
| Migrar la anulación de SOAP a la API REST GRE | SUNAT no expone la comunicación de baja por esa vía; el transporte SOAP es un dato del dominio, no una elección |

## Assumptions

- **API-03 asume un endpoint nuevo de consulta por ticket.** El controlador de la
  API es sin estado (construye el documento desde `request()->all()` y devuelve
  JSON, no persiste un `Voided`), así que la recuperación por API solo puede
  apoyarse en que el cliente conserve el ticket que se le devolvió. Sin este
  requisito la API no tendría ninguna vía de recuperación y el fix quedaría a
  medias ahí. Si prefieres dejar la API solo con API-01 y API-02, se descarta sin
  afectar al resto.

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| TICKET-01 | Phase 1 | Pending |
| TICKET-02 | Phase 1 | Pending |
| TICKET-03 | Phase 1 | Pending |
| TICKET-04 | Phase 1 | Pending |
| TICKET-05 | Phase 1 | Pending |
| WEB-01 | Phase 1 | Pending |
| WEB-02 | Phase 2 | Pending |
| WEB-03 | Phase 2 | Pending |
| WEB-04 | Phase 2 | Pending |
| WEB-05 | Phase 1 | Pending |
| API-01 | Phase 3 | Pending |
| API-02 | Phase 3 | Pending |
| API-03 | Phase 3 | Pending |

**Coverage:**
- v1 requirements: 13 total
- Mapped to phases: 13
- Unmapped: 0 ✓

**Por fase:**

| Phase | Requirements | Count |
|-------|--------------|-------|
| 1 — Ticket persistido y semántica de tres estados | TICKET-01, TICKET-02, TICKET-03, TICKET-04, TICKET-05, WEB-01, WEB-05 | 7 |
| 2 — Reconsulta de anulaciones pendientes | WEB-02, WEB-03, WEB-04 | 3 |
| 3 — Anulación por API con recuperación por ticket | API-01, API-02, API-03 | 3 |

---
*Requirements defined: 2026-08-04*
*Last updated: 2026-08-04 after roadmap creation (traceability mapped)*
