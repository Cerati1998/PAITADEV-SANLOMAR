# Phase 1: Ticket persistido y semántica de tres estados - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-08-04
**Phase:** 1-ticket-persistido-y-semantica-de-tres-estados
**Areas discussed:** Forma del estado de tres valores, Qué se persiste cuando queda pendiente, Alcance en la UI del listado, Doble flujo y filas históricas

---

## Todos cruzados

| Todo | Score | Decisión |
|------|-------|----------|
| `2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md` | 0.9 | Doblado completo — diagnóstico raíz del milestone |
| `2026-08-04-test-de-regresion-para-las-plantillas-sunat-del-repo.md` | 0.9 | Doblado **solo como referencia**; sigue Out of Scope |

**Notas:** El segundo todo se dobló inicialmente sin matiz. Se señaló que `REQUIREMENTS.md`
lo declara Out of Scope como milestone aparte, y el usuario confirmó que entra solo como
referencia de contexto, sin generar trabajo de plantillas en esta fase.

---

## Forma del estado de tres valores

| Option | Description | Selected |
|--------|-------------|----------|
| Columna `sunat_status` en `voideds` | Filtrable por SQL; lo que la Fase 2 necesitará para WEB-04 con rappasoft | ✓ |
| Clave `status` dentro del JSON `sunatResponse` | Solo agrega `sunat_ticket` al esquema; filtrar exige consultas JSON frágiles | |
| Accessor derivado en el modelo | Una sola fuente de verdad, imposible desincronizar, pero no filtrable en SQL | |

**User's choice:** Columna `sunat_status` en `voideds`

| Option | Description | Selected |
|--------|-------------|----------|
| Enum PHP respaldado + cast | Nativo en PHP 8.2 / Laravel 11; deja la semántica en un tipo reutilizable para SUM-01 | ✓ |
| Strings simples con constantes | Más cerca del estilo actual del repo, que no usa enums | |
| Tú decides | Delegar al planner según convenciones existentes | |

**User's choice:** Enum PHP respaldado + cast

| Option | Description | Selected |
|--------|-------------|----------|
| Servicio nuevo dedicado | `UtilService` ya mezcla firma, envío, CDR y PDF; un servicio propio lo reutiliza el resumen diario | ✓ |
| Métodos nuevos en `UtilService` | Todo el manejo de respuestas SUNAT en un archivo, engordando una clase que ya hace de todo | |
| Completar `UtilTrait::getResponseTicket()` | Arreglar el método roto en el trait; hoy lo consume sobre todo la vía API | |

**User's choice:** Servicio nuevo dedicado

| Option | Description | Selected |
|--------|-------------|----------|
| Solo como referencia | El todo de plantillas queda citado y en Deferred Ideas; sin trabajo en esta fase | ✓ |
| Entra como trabajo de la Fase 1 | Tests de regresión para las siete plantillas dentro de esta fase | |
| Solo la plantilla de la anulación | Punto medio: cubrir únicamente `voided.twig` | |

**User's choice:** Solo como referencia

---

## Qué se persiste cuando queda pendiente

| Option | Description | Selected |
|--------|-------------|----------|
| `sunatResponse` explícito de pendiente | JSON con estado y motivo (98 vs transporte); el listado distingue pendiente de nunca enviado | ✓ |
| Dejarlo null, como hace `Despatch` | Copia literal del patrón probado, pero null es indistinguible de "nunca se envió" | |

**User's choice:** `sunatResponse` explícito de pendiente

| Option | Description | Selected |
|--------|-------------|----------|
| Todo fallo de consulta con ticket = pendiente | Si hubo ticket, SUNAT ya recibió el XML; un fallo posterior nunca prueba rechazo | ✓ |
| Lista blanca de códigos y mensajes | Solo `98` y `"Bad Request"`; un mensaje no previsto volvería a marcar rechazo falso | |
| Pendiente salvo códigos de rechazo conocidos | Rechazo solo con error de negocio identificable (2000-3999) | |

**User's choice:** Todo fallo de consulta con ticket = pendiente

| Option | Description | Selected |
|--------|-------------|----------|
| Rechazada, sin ticket | Si SUNAT no dio ticket, la baja no está en proceso en ningún sitio | ✓ |
| Un cuarto estado "no enviada" | Más preciso para el listado, pero rompe los tres estados que TICKET-05 fija | |

**User's choice:** Rechazada, sin ticket

---

## Alcance en la UI del listado

**Nota de proceso:** el usuario detuvo esta ronda de preguntas para objetar que la
consulta es solo sobre anulación de comprobantes de pago, no sobre guías de remisión.
La verificación posterior le dio la razón sobre el riesgo real de alcance:
`vouchers/partials/response.blade.php` resultó estar compartida por cuatro tablas
(`VoucherTable`, `DespatchTable`, `DespatchTransportistaTable`, `VoidedTable`), de modo
que la recomendación original — migrar las vistas al estado nuevo — habría tocado los
listados de facturas y de guías. El área se reformuló con ese hallazgo.

| Option | Description | Selected |
|--------|-------------|----------|
| Seguir escribiendo `success` | Conservar la clave para que la vista compartida siga funcionando | |
| Vista propia solo para anulaciones | `VoidedTable` pasa a su propio partial; la compartida no se toca | ✓ |
| Tú decides | Delegar, con la restricción de no modificar la vista compartida | |

**User's choice:** Vista propia solo para anulaciones
**Notes:** Al tener vista propia, el JSON de la anulación queda libre de arrastrar `success`.

| Option | Description | Selected |
|--------|-------------|----------|
| Sí, cortar la vía de reenvío | Una baja con ticket deja de ofrecer reenviar XML; la reconsulta sigue siendo Fase 2 | ✓ |
| No, dejarlo para la Fase 2 | Fase más acotada, pero el reenvío que devuelve `2109` sigue disponible entre fases | |

**User's choice:** Sí, cortarla

| Option | Description | Selected |
|--------|-------------|----------|
| Swal `info` "En proceso" | Aviso breve al volver del envío; mismo mecanismo que ya usa el formulario | ✓ |
| Modal de detalle con el ticket | Extender la vista exclusiva de anulación con un bloque de pendiente | |

**User's choice:** Swal `info` "En proceso"

---

## Doble flujo y filas históricas

| Option | Description | Selected |
|--------|-------------|----------|
| Extraer al servicio nuevo y que ambos lo usen | El servicio concentra el flujo; la Fase 2 encuentra la pieza hecha | ✓ |
| Arreglar solo `VoidedForm::send()` | Se limita a lo que TICKET-01..05 y WEB-01 piden literalmente | |
| Arreglar ambos, cada uno por su lado | Más rápido, mantiene la duplicación que causó el bug doble | |

**User's choice:** Extraer al servicio nuevo y que ambos lo usen

| Option | Description | Selected |
|--------|-------------|----------|
| Nada, quedan como rechazadas | Sin ticket no hay recuperación programática | |
| Marcarlas como pendientes de revisión | Las de error de transporte pasan a pendiente | |
| Tú decides | Delegar al planner | |

**User's choice:** *(respuesta libre)* "yo lo corrijo por database"
**Notes:** El usuario corrige el histórico manualmente. La fase no reclasifica ni deduce
estados. Esto derivó en la pregunta de seguimiento sobre el default de la columna.

| Option | Description | Selected |
|--------|-------------|----------|
| `sunat_status` nullable, sin default | Las filas históricas quedan en NULL; solo las nuevas nacen con estado | ✓ |
| Default `rejected` para las existentes | Refleja la pantalla actual, pero escribe como rechazo lo que SUNAT aceptó | |
| Clasificarlas según su `sunatResponse` | La migración deduce el estado del JSON guardado | |

**User's choice:** `sunat_status` nullable, sin default

| Option | Description | Selected |
|--------|-------------|----------|
| Prueba manual contra beta | Recorrer las tres rutas contra `production = false`; como se reprodujo el bug | ✓ |
| Tests automatizados con SUNAT simulado | Más garantía de regresión; añade test doubles que el módulo no tiene hoy | |
| Ambas, con los tests solo del servicio | Test unitario de la clasificación de estados más la prueba manual | |

**User's choice:** Prueba manual contra beta

---

## Claude's Discretion

- Nombre exacto del servicio, del enum y de sus casos
- Firma de los métodos del servicio y cómo se inyecta la company de sesión
- Estructura interna concreta del JSON `sunatResponse` en estado pendiente
- Representación visual del estado pendiente en la vista nueva del listado

## Deferred Ideas

- Acción "Consultar estado" (WEB-02) y badge de tres estados (WEB-04) — Fase 2
- Reenvío condicionado a ausencia de ticket (WEB-03) — Fase 2
- Endpoint API de anulación y el `use UtilTrait;` faltante en `VoidedService` — Fase 3
- Resumen diario con la misma semántica (SUM-01, SUM-02) — v2
- Sondeo automático de CDRs pendientes (POLL-01, POLL-02) — v2
- Tests automatizados con SUNAT simulado — descartado para esta fase
- Test de regresión de las siete plantillas SUNAT — milestone aparte
