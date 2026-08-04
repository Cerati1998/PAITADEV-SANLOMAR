# INSPECTEC SERVICES / SANLOMAR

## What This Is

Mini-ERP en Laravel 11 para empresas de logística que operan en terminales de
contenedores (unidades menores/terceras) en Perú. Cubre el ciclo de vida del
contenedor (descarga, devolución, trazas), inspecciones EIR/PTI/leak-test,
órdenes de trabajo, despachos y facturación electrónica ante SUNAT. Lo usan los
operadores de patio y el área administrativa de la empresa.

## Core Value

Todo comprobante que sale hacia SUNAT — y toda respuesta que SUNAT devuelve —
tiene que quedar registrado fielmente en el sistema. Si el estado que muestra el
sistema no coincide con el que SUNAT tiene, el resto del ERP deja de ser
confiable para contabilidad.

## Requirements

### Validated

<!-- Capacidades ya en producción, inferidas del código y del historial de git. -->

- ✓ Contexto multiempresa/sucursal por sesión con revalidación en cada request — existing (`CheckCompanySelected`)
- ✓ Permisos por ruta con Spatie laravel-permission (`can:<route.name>`) — existing
- ✓ Admisión y catálogos (líneas navieras, naves, puertos, tipos de contenedor) — existing
- ✓ Ciclo de vida del contenedor: descarga y devolución por importación Excel, con traza operativa por contenedor — existing
- ✓ Inspecciones PTI, EIR (con fotos de detalle) y leak test sobre la traza operativa — existing
- ✓ Órdenes de trabajo estructurales y de máquina, con trabajadores y fotos de labor — existing
- ✓ Despachos, incluida la sincronización desde un sistema externo — existing
- ✓ Caja y gastos (arqueo, comprobantes, depósitos) — existing
- ✓ Emisión SUNAT de facturas, boletas y notas de crédito/débito — existing
- ✓ Emisión SUNAT de guía de remisión remitente y transportista, con ticket persistido y reconsulta — existing (`Despatch.sunat_ticket`)
- ✓ Emisión de comunicación de baja y resumen diario — existing (parcial: ver Active)
- ✓ Todo el XML y el PDF/RIDE que va a SUNAT o al cliente se genera con plantillas versionadas en `app/Services/Sunat/`, sin depender de `vendor/greenter` — existing (`549463f`..`a1e296c`, validado en producción 2026-08-04)
- ✓ API JWT/Sanctum para emisión externa (facturas, notas, guías, resúmenes) — existing
- ✓ Deploy automatizado al VPS con Deployer + GitHub Actions — existing

### Active

<!-- Milestone actual: recuperación de la respuesta final de la comunicación de baja. -->

- [ ] La anulación web persiste el ticket que devuelve SUNAT antes de consultar su estado
- [ ] Una consulta de estado que falla o queda pendiente no se guarda como rechazo
- [ ] El usuario puede reconsultar una anulación pendiente sin reenviar el XML
- [ ] Al obtener el CDR, la anulación queda registrada como aceptada y los comprobantes involucrados se marcan como dados de baja
- [ ] El endpoint API de anulación deja de romperse y expone el mismo comportamiento de ticket que la vía web

### Out of Scope

<!-- Fuera de este milestone. Cada exclusión lleva su razón para que no se re-agregue. -->

- Test de regresión de las plantillas SUNAT — trabajo real y ya capturado en
  `.planning/todos/pending/2026-08-04-test-de-regresion-para-las-plantillas-sunat-del-repo.md`,
  pero es un milestone aparte: toca las siete plantillas, no la anulación
- Cifrado de credenciales SOL y del certificado en `companies` — riesgo conocido,
  pero es un cambio transversal de seguridad que no bloquea la anulación
- Credenciales de prueba hardcodeadas y `'debug' => true` fijo en `UtilService` —
  deuda técnica menor, sin relación con la recuperación del ticket
- Comando de sondeo automático (schedule) que recupere CDRs pendientes en lote —
  el usuario acotó el milestone a web + API; la reconsulta manual cubre el caso
- Resumen diario (`SummaryController`) — comparte el patrón de ticket con la
  anulación, pero no está en el alcance acordado
- Revalidación de la sucursal de sesión contra empresa/usuario, y `$fillable`
  faltante en 18 modelos — hallazgos de `CONCERNS.md`, ajenos a este milestone

## Context

**De dónde sale este milestone.** Al anular comprobantes, el sistema guardaba
`sunatResponse.error.message = "Bad Request"` y pintaba la comunicación de baja
en rojo, pero la consulta web de SUNAT mostraba el comprobante efectivamente
dado de baja. La baja sí se registró; lo que falló fue la consulta posterior del
ticket, y esa respuesta de error quedó persistida como si fuera un rechazo.

**Causa raíz** (diagnóstico completo en
`.planning/todos/pending/2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md`):

1. El ticket nunca se persiste — no hay columna `sunat_ticket` en `voideds`, así
   que vive solo en una variable local de `VoidedForm::send()`.
2. Se consulta el estado del ticket de inmediato, sin espera ni reintento. SUNAT
   responde `98` (en proceso) o un `SoapFault` HTTP 400, que
   `BaseSunat::getErrorFromFault()` traduce al literal `"Bad Request"`.
3. `getResponse()` no distingue pendiente de rechazado: todo lo que no es éxito
   se guarda como `success: false`.
4. La única recuperación de la UI (`VoidedTable::sendXml()`) reenvía el XML, lo
   que en SUNAT devuelve `2109` (*ya fue informada*) y nunca recupera el CDR.

**El patrón correcto ya existe en este repo.** Las guías de remisión resolvieron
exactamente este problema: `Despatch` tiene columna `sunat_ticket`, la persiste
antes de consultar, trata el código `98` como pendiente conservando el ticket, y
`DespatchTable::sendXml()` reconsulta con el ticket guardado en vez de reenviar.
Este milestone lleva ese patrón a la anulación.

**Estado del módulo SUNAT.** La migración de plantillas XML y de reporte fuera de
`vendor/greenter` está cerrada y validada en producción. Greenter quedó reducido
a modelos, firma, envío y lectura de CDR. Ninguna salida a SUNAT depende ya de un
archivo de `vendor/`.

**Ojo con `.planning/codebase/`.** Los documentos de mapeo son del 2026-07-24 y
están desactualizados: reportan como abiertos varios hallazgos ya corregidos
(por ejemplo la precedencia de operadores en `SunatService`, resuelta en
`3f51e50`). Verificar contra el código antes de tomarlos como fuente.

## Constraints

- **Tech stack**: Laravel 11 + Livewire 3 + `rappasoft/laravel-livewire-tables`, Jetstream, WireUI, Tailwind — el fix debe encajar en el patrón Controller (shell) + Livewire (interacción) + Form object ya establecido
- **Dependencias**: Greenter se usa solo para modelos, firma (`SignedXml`), envío y lectura de CDR — no se le agregan propiedades ni setters a sus modelos, ni se edita nada bajo `vendor/`
- **Compatibilidad**: la comunicación de baja viaja por SOAP (`See`), no por la API REST GRE que usan las guías — el manejo de errores debe cubrir tanto el código `98` como el `SoapFault` de transporte
- **Multi-tenant**: todo acceso a datos se ancla en `session('company')` y `session('branch')`, no hay scope global — cualquier consulta nueva debe filtrar por ambos
- **Producción**: SUNAT es un tercero real; la verificación se hace contra el ambiente beta antes de tocar producción

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Replicar el patrón `sunat_ticket` de `Despatch` en vez de diseñar uno nuevo | Ya está probado en producción para guías de remisión y mantiene un solo modelo mental para tickets SUNAT | — Pending |
| Acotar el milestone a anulación web + API, sin comando de sondeo | El usuario priorizó cerrar el bug reportado; el sondeo automático amplía superficie y pruebas sin resolver nada que la reconsulta manual no cubra | — Pending |
| Tratar el `SoapFault` de transporte como pendiente, no como rechazo | El caso observado en producción prueba que un fallo de consulta no implica que SUNAT haya rechazado la baja | — Pending |
| Dejar el test de regresión de plantillas como milestone aparte | Toca las siete plantillas SUNAT y no comparte código con la anulación; mezclarlos difumina el criterio de "hecho" | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-08-04 after initialization*
