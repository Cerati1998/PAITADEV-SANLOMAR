# Phase 2: Reconsulta de anulaciones pendientes - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-08-05
**Phase:** 2-reconsulta-de-anulaciones-pendientes
**Areas discussed:** Forma de la acción, Legibilidad de los tres estados, Desenlace de la reconsulta, Alcance de la reconsulta

---

## Selección de áreas

| Option | Description | Selected |
|--------|-------------|----------|
| Forma de "Consultar estado" | Botón propio vs. reutilizar `sendXml()` decidiendo por dentro | ✓ |
| Legibilidad de los tres estados | Iconos actuales vs. badge de texto y filtro | ✓ |
| Desenlace de la reconsulta | Qué ve el usuario en cada rama | ✓ |
| Alcance de la reconsulta | Fila a fila, históricas, acción masiva | ✓ |

**User's choice:** las cuatro.

---

## Todos cruzados con la fase

| Option | Description | Selected |
|--------|-------------|----------|
| Anulación no recupera el CDR | Diagnóstico raíz; su punto 4 es el trabajo de esta fase | ✓ |
| Test de regresión de plantillas | Out of Scope como milestone aparte | ✓ |
| Ninguno | No doblar ninguno | |

**User's choice:** doblar ambos.
**Notes:** el segundo entra solo como referencia de contexto — no genera trabajo, igual que en la Fase 1.

---

## Forma de la acción

### ¿Cómo se ofrece la reconsulta en la fila pendiente?

| Option | Description | Selected |
|--------|-------------|----------|
| Botón propio + acción nueva `checkStatus()` | Dos botones en la fila pendiente; mantiene separado lo que la Fase 1 separó *(recomendada)* | |
| Reutilizar `sendXml()` decidiendo por dentro | Patrón literal de `DespatchTable:187`; un botón, un método | ✓ |
| El reloj pasa a consultar | Un solo botón por fila, se pierde ver el detalle sin llamar a SUNAT | |

**User's choice:** reutilizar `sendXml()`.
**Notes:** eligió contra la recomendación. Pesa el ROADMAP — el patrón de `Despatch` se porta, no se rediseña — y mantener un solo modelo mental del botón de la columna Sunat.

### ¿La reconsulta aparece en algún sitio más?

| Option | Description | Selected |
|--------|-------------|----------|
| Solo la columna Sunat | Un único punto de entrada *(recomendada)* | ✓ |
| Columna Sunat + modal de detalle | Botón "Consultar ahora" dentro del swal | |
| Tú decides | Criterio del planner | |

**User's choice:** solo la columna Sunat.

### ¿Cómo queda el árbol de decisión en el servidor?

| Option | Description | Selected |
|--------|-------------|----------|
| Tres ramas: consultar / reenviar / avisar | `canResendXml()` sobrevive como lista de permitidos *(recomendada)* | ✓ |
| Dos ramas: hay ticket consulta, si no reenvía | Patrón literal de `Despatch`; deja que una fila Aceptada reenvíe | |
| Dos ramas + guarda previa de Aceptada | Rechazada-con-ticket acabaría reconsultando | |

**User's choice:** tres ramas.
**Notes:** se le señaló que una anulación Aceptada tiene `sunat_ticket = null` por D-07, así que "sin ticket → reenvía" la llevaría al error `2109`. Aceptó la tercera rama en lugar de revertir la decisión anterior.

### ¿De dónde sale el RUC de la ruta del CDR?

| Option | Description | Selected |
|--------|-------------|----------|
| Del propio `Voided` | `$voided->company['ruc']`, misma regla que `markVoidedInvoices()` *(recomendada)* | ✓ |
| De `session('company')->ruc` | Lo que hace hoy `VoidedTable::sendXml():139` | |
| Tú decides | Criterio del planner | |

**User's choice:** del propio `Voided`.

---

## Legibilidad de los tres estados

### ¿Cómo se ve el estado en el listado (WEB-04)?

| Option | Description | Selected |
|--------|-------------|----------|
| Badge de texto junto al botón | `VoidedStatus::label()` + `badgeColor()`, ya existentes *(recomendada)* | ✓ |
| Los iconos actuales bastan | Solo `title`/tooltip | |
| Columna Estado aparte | Columna nueva sortable, más ancho de tabla | |

**User's choice:** badge de texto junto al botón.

### ¿Se añade filtro por estado?

| Option | Description | Selected |
|--------|-------------|----------|
| Sí, `SelectFilter` de estado | Para lo que D-01 eligió columna dedicada *(recomendada)* | ✓ |
| No, sin filtro | El volumen es bajo | |
| Tú decides | Criterio del planner | |

**User's choice:** sí, `SelectFilter`.

### ¿Cómo se etiquetan las filas históricas?

| Option | Description | Selected |
|--------|-------------|----------|
| Badge gris "SIN ESTADO" | Explicita *desconocido*, no *sin enviar* *(recomendada)* | ✓ |
| Celda sin badge | El icono de interrogación ya lo comunica | |
| Tú decides | Criterio del planner | |

**User's choice:** badge gris "SIN ESTADO".
**Notes:** se resuelve en vista y filtro; el enum se queda con tres casos porque TICKET-05 fija tres estados.

---

## Desenlace de la reconsulta

### ¿Qué ve el usuario en cada desenlace?

| Option | Description | Selected |
|--------|-------------|----------|
| Swal por rama, como hoy | Pendiente → info; aceptada/rechazada → `showResponse()` *(recomendada)* | ✓ |
| Siempre el modal de detalle | También en pendiente, ahora que `markPending()` escribe motivo | |
| Toast discreto y ya | Más rápido, esconde el mensaje de SUNAT | |

**User's choice:** swal por rama.

### ¿Se registra el último intento de consulta?

| Option | Description | Selected |
|--------|-------------|----------|
| No, con lo que hay basta | `updated_at` ya lo fecha; sin migración *(recomendada)* | ✓ |
| Sí, columna `last_checked_at` | Útil el día del sondeo automático (v2) | |
| Tú decides | Criterio del planner | |

**User's choice:** no. **La fase no lleva migración.**

---

## Alcance de la reconsulta

### ¿Fila a fila o forma masiva?

| Option | Description | Selected |
|--------|-------------|----------|
| Solo fila a fila | Lo masivo es POLL-01 disparado a mano *(recomendada)* | ✓ |
| Bulk action sobre filas marcadas | Un timeout deja el lote a medias | |
| Botón único "consultar pendientes" | Recorre todas las pendientes de la sucursal | |

**User's choice:** solo fila a fila.

### ¿Se reconsulta una Rechazada con ticket?

| Option | Description | Selected |
|--------|-------------|----------|
| No, es desenlace final | El ticket se conserva solo para bloquear el reenvío *(recomendada)* | ✓ |
| Sí, consultar es idempotente | Permitiría recuperar el ZIP si el archivado falló | |
| Tú decides | Criterio del planner | |

**User's choice:** no.

### ¿Se toca `VoidedForm`?

| Option | Description | Selected |
|--------|-------------|----------|
| Solo el listado | `VoidedTable.php` + su vista, nada más *(recomendada)* | ✓ |
| También el formulario | Consultar desde el swal de "En proceso" | |

**User's choice:** solo el listado.

---

## Claude's Discretion

- Icono y forma del botón de la rama "consultar".
- Colocación del badge dentro de la celda y su composición con el icono existente.
- Etiquetas literales del `SelectFilter` y representación del NULL en su consulta.
- Textos exactos de los swal de la rama "avisar".
- Si el cálculo de `$directory`/`$name` se extrae a un método privado o se deja inline.

## Deferred Ideas

- Método `checkStatus()` separado — descartado en D-01, camino de vuelta si el árbol de tres ramas resulta ilegible.
- Reconsulta desde el modal de detalle — descartada en D-05.
- Reconsulta masiva (bulk action o botón de cabecera) — descartada en D-11, sigue siendo POLL-01 (v2).
- Columna `last_checked_at` — descartada en D-10, tendría sentido con el sondeo automático (v2).
- Reconsultar una Rechazada con ticket para recuperar un ZIP mal archivado — descartada en D-12.
- Visibilidad de pendientes que superan un umbral (POLL-02) — v2.
- Autorización de las acciones de `VoidedTable` (CR-02) y `vouchers.anulate` en el punto de la anulación (CR-04) — Fase 4.
- Endpoint API de anulación (API-01/02/03) — Fase 3.
- Resumen diario con la misma semántica (SUM-01, SUM-02) — v2.
- Tests con SUNAT simulado — descartado, se hereda D-14 de la Fase 1.

## Hallazgos de la discusión

- **`sunat_ticket` es columna nueva de la Fase 1**, así que ninguna fila histórica puede tenerlo poblado. El caso "histórica con ticket" no existe y quedó descartado como pregunta.
- **Una anulación Aceptada tiene `sunat_ticket = null`** (D-07 lo limpia). Es lo que obliga a la tercera rama y lo que hace que el patrón de dos ramas de `DespatchTable` no se pueda portar literal.
