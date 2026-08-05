# Phase 2: Reconsulta de anulaciones pendientes - Context

**Gathered:** 2026-08-05
**Status:** Ready for planning

<domain>
## Phase Boundary

Desde el listado de anulaciones, una comunicación de baja que quedó **pendiente**
se resuelve consultando el `sunat_ticket` ya guardado — **sin reenviar el XML** —
y el listado pasa a decir a simple vista en qué estado está cada baja: aceptada,
pendiente o rechazada.

Requisitos: **WEB-02** (reconsulta desde el listado), **WEB-03** (reenvío solo sin
ticket), **WEB-04** (los tres estados distinguibles a simple vista).

**Qué ya existe de la Fase 1 y esta fase solo consume, no rediseña:**

- `App\Enums\VoidedStatus` con `label()` y `badgeColor()`
- columnas `sunat_ticket` y `sunat_status` en `voideds`
- `VoidedTicketFlowService::resolveTicket()` — **público a propósito para esta
  fase** (D-11 de la Fase 1): consulta el ticket, archiva el CDR, clasifica el
  desenlace, limpia el ticket y marca los comprobantes
- `VoidedTicketFlowService::classifyFailure()` para el catch exterior
- `VoidedTable::canResendXml()` — lista de permitidos del reenvío
- `resources/views/voideds/partials/response.blade.php` — vista propia del listado

**Archivos en alcance (solo dos):**
- `app/Livewire/VoidedTable.php`
- `resources/views/voideds/partials/response.blade.php`

**Explícitamente fuera:**
- `app/Livewire/Forms/VoidedForm.php` — el flujo de envío quedó correcto en la Fase 1
- `app/Services/Sunat/VoidedTicketFlowService.php` — se consume tal cual; si hiciera
  falta tocarlo, es señal de que la fase se está saliendo del alcance
- `resources/views/vouchers/partials/cdr.blade.php` y
  `resources/views/vouchers/partials/response.blade.php` — compartidas por
  `VoucherTable`, `DespatchTable` y `DespatchTransportistaTable`. **No se modifican**;
  la columna CDR se sigue neutralizando desde el servidor (decisión 01-05)
- Endpoint API de anulación — Fase 3
- Comprobación de propiedad multi-tenant y de permiso en las acciones — Fase 4
  (CR-02/CR-04). Esta fase **no** cierra ese hueco, aunque D-04 lo roza

</domain>

<decisions>
## Implementation Decisions

### Forma de la acción de reconsulta

- **D-01:** La reconsulta **no estrena un método propio**: se reutiliza
  `VoidedTable::sendXml()`, que decide por dentro qué hacer con la fila. Es el
  patrón literal de `DespatchTable::sendXml():187` (*"si ya tiene ticket guardado,
  solo consultar el estado"*), y el ROADMAP fija que el patrón de `Despatch`
  **se porta, no se rediseña**. Un botón, un método, un punto de entrada.

- **D-02:** `sendXml()` queda con **tres ramas explícitas**, no dos. El patrón de
  `Despatch` es de dos (hay ticket → consulta; no hay → reenvía), pero aquí eso no
  basta: D-07 de la Fase 1 limpia `sunat_ticket` al aceptar, así que una anulación
  **Aceptada** tiene ticket `null` y la rama "no hay ticket" la mandaría a reenviar
  el XML — exactamente el error `2109` que este milestone existe para eliminar.
  El árbol es:

  | Situación | Rama |
  |---|---|
  | `sunat_ticket` poblado (Pendiente) | **Consultar** — `resolveTicket()` |
  | `canResendXml()` devuelve true (Rechazada sin ticket, o histórica virgen) | **Reenviar** el XML y guardar el ticket |
  | Cualquier otra (Aceptada, Rechazada con ticket) | **Avisar** con swal, sin llamar a SUNAT |

- **D-03:** `canResendXml()` **sobrevive** como lista de permitidos del reenvío —
  no se sustituye por una lista de bloqueos. La razón está escrita en la Fase 1
  (decisión 01-05): la lista de bloqueos que proponía CR-02 habría bloqueado
  también el caso legítimo `Rechazada sin ticket` (D-06), cuyo `sunatResponse` sí
  está poblado porque lo escribió `markRejected()`. La guarda vive en el servidor
  porque una acción Livewire pública es invocable directamente desde el cliente:
  esconder el botón en la vista no protege nada.

- **D-04:** El RUC con que se arma la ruta del CDR
  (`{directory}cdr/R-{ruc}-RC-{Ymd}-{correlativo}.zip`) se lee de
  **`$voided->company['ruc']`**, el JSON que `VoidedObserver` persiste al crear la
  fila, **no de `session('company')->ruc`** como hace hoy `VoidedTable::sendXml():139`.
  Es la misma regla que la Fase 1 fijó para `markVoidedInvoices()`: dentro del flujo,
  el ámbito multi-tenant se lee del modelo recibido. Hoy la garantía de que sesión y
  fila coinciden vive en `builder()`, no en la acción — que es justo el hueco CR-02
  que abordará la Fase 4.

- **D-05:** La reconsulta se ofrece **únicamente desde la columna Sunat del
  listado**. Ni el modal de detalle (`showResponse()`) ni la columna CDR añaden un
  segundo punto de entrada.

### Legibilidad de los tres estados (WEB-04)

- **D-06:** La columna Sunat añade un **badge de texto junto al botón**, con
  `VoidedStatus::label()` y `VoidedStatus::badgeColor()` — ambos ya existen y ya se
  usan en el modal de detalle vía el prop dinámico `:color` de `x-wire-badge`. Se
  lee ACEPTADA / PENDIENTE / RECHAZADA sin pasar el ratón ni abrir nada. Los iconos
  actuales (check verde, reloj ámbar, ban rojo) **se conservan**: el badge se suma,
  no los sustituye.

- **D-07:** El listado gana un **`SelectFilter` por estado** sobre la columna
  `sunat_status`, con los tres valores más una opción para las filas sin estado. Es
  literalmente para lo que D-01 de la Fase 1 eligió columna dedicada en vez de clave
  JSON o accessor derivado, y es la vía por la que el operador encuentra las
  pendientes que tiene que reconsultar.

- **D-08:** Las filas históricas (`sunat_status` NULL, D-13 de la Fase 1) muestran
  un **badge gris "SIN ESTADO"**, coherente con el icono de interrogación gris que
  ya tienen. Hace explícito que es *desconocido*, no *sin enviar*. El caso NULL se
  resuelve en la vista y en el filtro: **el enum sigue con tres casos**, porque
  TICKET-05 fija tres estados y un cuarto caso `Desconocido` contradiría esa
  semántica.

  *Dato verificado durante la discusión:* `sunat_ticket` es columna nueva de la
  Fase 1, así que **ninguna fila histórica puede tener ticket**. El caso
  "histórica con ticket" no existe y el árbol de D-02 ya las cubre enteras:
  virgen → reenviar, con `sunatResponse` guardado → avisar.

### Desenlace de la reconsulta

- **D-09:** El usuario recibe un **swal por rama, igual que hoy**: si sigue
  pendiente, `info` con "Aún en proceso"; si llegó el CDR (aceptada o rechazada),
  `showResponse()` con el detalle completo. Es exactamente lo que `sendXml()` ya
  hace en sus dos ramas actuales; no se introduce un patrón de notificación nuevo.

- **D-10:** **No se añade columna `last_checked_at`** ni ninguna otra migración.
  `markPending()` reescribe `sunatResponse` en cada intento y su `save()` actualiza
  `updated_at`, así que el último intento ya queda fechado; además cada pendiente
  emite un `Log::warning` con el ticket. **Esta fase no lleva migración.**

### Alcance de la reconsulta

- **D-11:** Reconsulta **fila a fila, sin acción masiva**. Ni bulk action de
  rappasoft sobre filas marcadas, ni botón de cabecera que recorra todas las
  pendientes: eso es POLL-01 disparado a mano, y el ROADMAP dejó el sondeo fuera
  (v2). Además N llamadas SOAP dentro de un request, sin cola ni reintento, dejan
  el lote resuelto a medias en cuanto una expira.

- **D-12:** Una anulación **Rechazada que conserva ticket** (SUNAT devolvió CDR con
  código distinto de `0`) **no se reconsulta**: es desenlace final, el CDR ya está
  archivado, y el ticket se conserva solo para que nadie reenvíe el XML y choque con
  el `2109`. Cae en la rama "avisar" de D-02.

- **D-13:** La fase toca **solo el listado**. `VoidedForm::send()` no se modifica:
  su swal de "En proceso" no ofrece consultar desde ahí, para no abrir un segundo
  punto de entrada a la reconsulta (coherente con D-05).

### Verificación

- **D-14:** Se hereda D-14 de la Fase 1 sin cambios: **prueba manual contra el
  ambiente beta de SUNAT** (`production = false` en la empresa de sesión). Para esta
  fase el recorrido es: dejar una baja en pendiente (código `98` o fallo de
  transporte), pulsar consultar y comprobar que se recupera el CDR **sin** que SUNAT
  responda `2109`. No se añade infraestructura de test con SUNAT simulado.

### Claude's Discretion

- Nombre y forma exacta del icono/botón de la rama "consultar" en la vista
  (D-01/D-02 fijan el ruteo del servidor, no el arte).
- Colocación concreta del badge dentro de la celda (antes o después del botón) y
  cómo se compone con el icono existente.
- Etiquetas literales del `SelectFilter` y valor con que se representa el NULL en
  la consulta del filtro.
- Textos exactos de los swal de la rama "avisar", más allá de que expliquen por qué
  no se llama a SUNAT.
- Si el cálculo de `$directory`/`$name` se extrae a un método privado del componente
  o se deja inline (D-04 fija la **fuente** del RUC, no dónde se calcula).

### Folded Todos

- **`.planning/todos/pending/2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md`**
  — Diagnóstico raíz del milestone. Su **punto 4** es literalmente el trabajo de
  esta fase: *"la única recuperación de la UI (`VoidedTable::sendXml()`) reenvía el
  XML, lo que en SUNAT devuelve `2109` (ya fue informada) y nunca recupera el CDR"*.
  Los puntos 1–3 los cerró la Fase 1; el hallazgo de `Api/VoidedController` es de la
  Fase 3. El todo se cierra cuando termine el milestone, no esta fase.

- **`.planning/todos/pending/2026-08-04-test-de-regresion-para-las-plantillas-sunat-del-repo.md`**
  — Doblado **solo como referencia de contexto**, igual que en la Fase 1.
  `REQUIREMENTS.md` lo declara Out of Scope como milestone aparte. **No genera
  trabajo en esta fase** — y menos aún aquí, que no toca ninguna plantilla.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Decisiones heredadas (leer primero)
- `.planning/phases/01-ticket-persistido-y-sem-ntica-de-tres-estados/01-CONTEXT.md` — D-01 a D-14 de la Fase 1; esta fase **no las re-litiga**, las consume
- `.planning/todos/pending/2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md` — diagnóstico raíz; el punto 4 es el trabajo de esta fase
- `.planning/ROADMAP.md` §"Phase 2" y §Notes — criterios de éxito y restricciones transversales
- `.planning/PROJECT.md` §Context — origen del milestone y estado del módulo SUNAT

### Código a modificar (los dos únicos archivos en alcance)
- `app/Livewire/VoidedTable.php` — `sendXml()` (líneas 108-200) es donde entra el ruteo de tres ramas; `canResendXml()` (229-245) sobrevive; `builder()` (247-253) muestra el filtrado multi-tenant vigente; `configure()` (17-22) ya hace `setAdditionalSelects(['id','sunat_ticket','sunatResponse'])` — habrá que sumar lo que el badge y el filtro necesiten
- `resources/views/voideds/partials/response.blade.php` — árbol de decisión de la vista; el badge y la rama "consultar" se añaden aquí

### Código a leer y consumir, no modificar
- `app/Services/Sunat/VoidedTicketFlowService.php` — `resolveTicket()` (84-164) es la reconsulta; `classifyFailure()` (172-209) para el catch exterior; `markPending()` (216-236) explica qué queda escrito en pendiente
- `app/Enums/VoidedStatus.php` — `label()` y `badgeColor()`, ya listos para el badge
- `app/Models/Voided.php` — `$fillable`, `$casts` y el JSON `company` del que sale el RUC (D-04)
- `app/Observers/VoidedObserver.php` — rellena `correlativo` y `company` en `creating`

### Patrón a portar (leer, no modificar)
- `app/Livewire/DespatchTable.php` líneas 156-253 — `sendXml()` que consulta con ticket guardado en vez de reenviar. **Es el patrón que D-01 adopta**, con la salvedad de D-02: allí dos ramas bastan porque `Despatch` no limpia el ticket al aceptar; aquí no
- `app/Livewire/Forms/DespatchForm.php` líneas 292-325 — persistir ticket antes de consultar, limpiar al obtener CDR

### Fuera de alcance, no tocar
- `resources/views/vouchers/partials/cdr.blade.php` y `resources/views/vouchers/partials/response.blade.php` — compartidas por `VoucherTable.php:131`, `DespatchTable.php:105` y `DespatchTransportistaTable.php:96`
- `app/Livewire/Forms/VoidedForm.php` — el envío quedó correcto en la Fase 1 (D-13)
- `.planning/phases/01-ticket-persistido-y-sem-ntica-de-tres-estados/01-REVIEW.md` §CR-02, §CR-04 — hallazgos de autorización, asignados a la **Fase 4**. Esta fase no los cierra

### Restricciones del proyecto
- `./.claude/CLAUDE.md` §Constraints — Greenter solo como modelos/firma/envío/CDR, nada bajo `vendor/`, multi-tenant por sesión
- `./CLAUDE.md` §"SUNAT output is generated from repo-owned templates"

### Advertencia sobre los mapas de codebase
- `.planning/codebase/*.md` son del 2026-07-24 y están desactualizados: reportan como abiertos hallazgos ya corregidos. Verificar contra el código antes de usarlos como fuente.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets

- **`VoidedTicketFlowService::resolveTicket($voided, $cdrPath, ?See $see)`** — la
  reconsulta entera ya está escrita: consulta el ticket, distingue "sin CDR" de
  "CDR llegado", archiva el ZIP siempre que llegue, decide por el código del propio
  CDR (`'0'` → aceptada dentro de `DB::transaction()` con `markVoidedInvoices()`),
  conserva el ticket en el rechazo y devuelve un `VoidedStatus`. **El tercer
  parámetro es opcional**: pasando `null` construye su propio `See`, que es
  exactamente el uso de esta fase.
- **`VoidedTicketFlowService::classifyFailure($voided, $e)`** — para el catch
  exterior del componente. Ya protege el invariante de no degradar un desenlace
  confirmado por SUNAT.
- **`VoidedStatus::label()` / `badgeColor()`** — el badge de D-06 no necesita
  inventar textos ni colores; el modal de detalle ya los consume con `x-wire-badge`.
- **`VoidedTable::showResponse()`** — ya elige el icono del swal por `sunat_status`
  con fallback para filas históricas. Sirve tal cual para la rama de D-09.

### Established Patterns

- **Un solo método de acción por columna**: `DespatchTable::sendXml()` concentra
  regenerar, enviar y consultar. D-01 adopta esa forma.
- **Guarda de servidor espejo del árbol de la vista**: `canResendXml()` se escribió
  "hoja por hoja" replicando `response.blade.php`. Si la vista cambia en esta fase,
  la guarda tiene que cambiar con ella o se desincronizan.
- **Multi-tenant por sesión sin scope global**: `builder()` filtra por
  `branch_id`, `company_id` y `production`. Las **acciones** no lo hacen (CR-02,
  Fase 4) — D-04 reduce la exposición leyendo el RUC del modelo, sin cerrarla.
- **Filtros de rappasoft**: `DespatchTable::filters()` (116-134) muestra la forma de
  declarar filtros en este proyecto; ahí es un `DateRangeFilter`, aquí será un
  `SelectFilter`.

### Integration Points

- **Transporte SOAP**: `UtilService::getSee()` construye el cliente con el
  certificado y la clave SOL según `production`. La anulación va por `See`
  (`sendXmlFile` + `getStatus`), **no** por la API REST GRE de las guías.
- **Archivado del CDR**: `Storage::put($directory.'cdr/R-'.$name.'.zip', $zip)` con
  `$directory` = `sunat/` o `sunat/beta/`. El `$name` es donde entra D-04.
- **Marcado de comprobantes**: lo hace `markVoidedInvoices()` dentro de
  `resolveTicket()`; el componente **no** lo invoca ni lo replica.
- **`setAdditionalSelects`**: el badge y el filtro leen `sunat_status`, que ya es
  columna del `Column::make('Sunat', 'sunat_status')`; comprobar que la selección
  cubra lo que la vista use tras el cambio.

</code_context>

<specifics>
## Specific Ideas

- El usuario eligió **deliberadamente** el patrón de `DespatchTable` sobre un método
  `checkStatus()` separado, aun cuando la recomendación iba por lo segundo: pesa más
  mantener un solo modelo mental de "el botón de la columna Sunat hace lo que toque"
  que la separación semántica. La consecuencia — que `Aceptada` tiene ticket `null`
  y caería en la rama de reenvío — se resolvió con la tercera rama de D-02, no
  volviendo atrás.
- El usuario mantuvo el criterio multi-tenant de la Fase 1 incluso donde el código
  actual hace lo contrario: el RUC sale del modelo, no de la sesión (D-04).
- Nada de infraestructura nueva: sin migración, sin columna de timestamps, sin
  acción masiva. La fase debe caber en dos archivos.

</specifics>

<deferred>
## Deferred Ideas

- **Método `checkStatus()` separado del reenvío** — considerado y descartado en
  D-01 a favor del patrón de `DespatchTable`. Si el árbol de tres ramas resultara
  ilegible en la práctica, este es el camino de vuelta.
- **Acción de reconsulta desde el modal de detalle** — descartada en D-05 para no
  meter un `wire:click` dentro del HTML que `showResponse()` renderiza a mano.
- **Reconsulta masiva** (bulk action o botón de cabecera) — descartada en D-11: es
  POLL-01 disparado a mano. Sigue siendo v2.
- **Columna `last_checked_at`** — descartada en D-10; volvería a tener sentido el
  día que exista el sondeo automático (POLL-01/POLL-02, v2).
- **Reconsultar una anulación Rechazada con ticket** para recuperar un ZIP que falló
  al archivarse — descartado en D-12 por ser desenlace final.
- **Visibilidad de pendientes que superan un umbral de tiempo** (POLL-02) — v2.
- **Autorización de las acciones de `VoidedTable`** (CR-02) y comprobación de
  `vouchers.anulate` en el punto de la anulación (CR-04) — **Fase 4**.
- **Endpoint API de anulación** (API-01, API-02, API-03) — **Fase 3**.
- **Resumen diario con la misma semántica de estados** (SUM-01, SUM-02) — v2.
- **Tests automatizados con SUNAT simulado** — descartado, se hereda D-14.

### Reviewed Todos (not folded)

*(ninguno: los dos todos coincidentes se doblaron — el segundo solo como
referencia de contexto, sin generar trabajo. Ver `<decisions>` §Folded Todos.)*

</deferred>

---

*Phase: 2-reconsulta-de-anulaciones-pendientes*
*Context gathered: 2026-08-05*
