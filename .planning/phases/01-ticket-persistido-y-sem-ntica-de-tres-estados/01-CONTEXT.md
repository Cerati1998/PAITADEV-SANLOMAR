# Phase 1: Ticket persistido y semántica de tres estados - Context

**Gathered:** 2026-08-04
**Status:** Ready for planning

<domain>
## Phase Boundary

El envío web de una **comunicación de baja de comprobantes de pago** (`Voided`)
persiste el ticket que SUNAT devuelve antes de consultar su estado, y registra el
estado real que SUNAT respondió — aceptada, pendiente o rechazada — en lugar del
`success: true/false` actual. Los comprobantes incluidos en la baja se marcan como
dados de baja únicamente cuando el CDR llega con código `0`.

**Esta fase toca únicamente el flujo de anulación.** `Despatch` /
`DespatchTransportista` (guías de remisión) aparecen solo como **patrón de
referencia a portar**, tal como fijó el ROADMAP: *"Se porta, no se rediseña."*
Ningún archivo de guías de remisión se modifica.

**Archivos en alcance:**
- migración nueva sobre la tabla `voideds`
- `app/Models/Voided.php`
- `app/Livewire/Forms/VoidedForm.php`
- `app/Livewire/VoidedTable.php`
- servicio nuevo en `app/Services/Sunat/`
- enum nuevo de estados
- `resources/views/voideds/partials/sunatResponse.blade.php`
- vista nueva `resources/views/voideds/partials/response.blade.php`

**Explícitamente fuera:** `resources/views/vouchers/partials/response.blade.php`
es compartida por `VoucherTable` (facturas, boletas, notas), `DespatchTable` y
`DespatchTransportistaTable`. **No se modifica.**

La acción "Consultar estado" del listado (WEB-02) y el badge de tres estados
(WEB-04) son de la Fase 2. Esta fase solo impide el daño del reenvío, sin
construir la reconsulta.

</domain>

<decisions>
## Implementation Decisions

### Forma y ubicación del estado

- **D-01:** El estado vive en una **columna dedicada `sunat_status`** en `voideds`,
  junto a la nueva columna `sunat_ticket`. Se eligió columna sobre clave JSON o
  accessor derivado porque la Fase 2 (WEB-04) necesitará filtrar y ordenar el
  listado por estado con `rappasoft/laravel-livewire-tables`, y una consulta SQL
  directa es lo único que eso soporta con naturalidad. `Despatch` no tiene columna
  de estado, pero tampoco tenía que filtrar su listado por él.

- **D-02:** Los tres valores se modelan como **enum PHP respaldado por string**
  (p. ej. `VoidedStatus: string` con casos aceptada / pendiente / rechazada),
  casteado en el modelo `Voided`. PHP 8.2 y Laravel 11 lo soportan de forma nativa
  y deja la semántica en un tipo reutilizable — que es lo que el ROADMAP pide para
  que el resumen diario (SUM-01, v2) no la reescriba.

- **D-03:** La semántica de estados vive en un **servicio nuevo dedicado** bajo
  `app/Services/Sunat/` (nombre sugerido: `TicketFlowService`). No se mete en
  `UtilService` — que ya mezcla firma, envío, lectura de CDR y generación de PDF —
  ni se apoya en `UtilTrait::getResponseTicket()`, que está roto (obtiene el
  ticket, consulta el estado y no retorna nada).

### Qué se persiste en cada desenlace

- **D-04:** Cuando la baja queda pendiente se escribe un **`sunatResponse` explícito
  de pendiente**, con el motivo distinguible (código `98` de SUNAT vs. fallo de
  transporte) y el mensaje recibido. No se deja `sunatResponse` en null como hace
  `DespatchForm` en el código 98: aquí null es indistinguible de "nunca se envió",
  y ese es exactamente el estado que hoy hace que el listado ofrezca reenviar el XML.

- **D-05:** **Todo fallo de la consulta de estado cuando ya existe ticket se
  clasifica como pendiente.** No hay lista blanca de códigos ni de mensajes. El
  razonamiento: si el envío devolvió ticket, SUNAT ya recibió el XML, de modo que
  un fallo posterior de `getStatus()` no prueba rechazo en ningún caso. Esto cubre
  tanto el código `98` como el `SoapFault` de transporte que `BaseSunat::getErrorFromFault()`
  traduce al literal `"Bad Request"` con código vacío. Es la lectura que valida el
  caso observado en producción.

- **D-06:** Si el **envío inicial falla antes de devolver ticket** (`sendXmlFile`
  no exitoso), la baja queda **rechazada y sin ticket**. No se introduce un cuarto
  estado "no enviada": TICKET-05 fija tres estados explícitos. La Fase 2 tratará
  la ausencia de ticket como la condición que habilita el reenvío del XML (WEB-03).

- **D-07:** Al obtenerse el CDR, la baja queda **aceptada**, el ZIP del CDR se
  archiva en el disco configurado y **el ticket se limpia** (`sunat_ticket = null`),
  igual que hace `DespatchForm.php:319-325`.

### Alcance en la interfaz

- **D-08:** `VoidedTable` deja de usar el partial compartido y pasa a una **vista
  propia** (`resources/views/voideds/partials/response.blade.php`). Esto libera al
  JSON de la anulación de arrastrar la clave `success` por compatibilidad, y
  garantiza que los listados de facturas y de guías de remisión no se vean
  afectados por esta fase.

- **D-09:** Una anulación **con ticket guardado deja de ofrecer el botón de
  reenviar XML** en el listado. Hoy `vouchers/partials/response.blade.php:9-13`
  lo muestra cuando `sunatResponse` está vacío, y `VoidedTable::sendXml():107`
  reenvía el XML — lo que sobre una baja que SUNAT ya recibió devuelve `2109`
  (*la comunicación de baja ya fue informada*) y nunca recupera el CDR. La Fase 1
  corta esa vía de daño; la acción "Consultar estado" que la reemplaza es Fase 2.

- **D-10:** El aviso de "en proceso" al enviar (WEB-01) se resuelve con un **swal
  `info`** titulado "En proceso", explicando que SUNAT recibió la comunicación de
  baja pero aún la procesa. Mismo mecanismo que ya usa el formulario para el resto
  de avisos, y mismo patrón que `DespatchForm.php:305-309`.

### Duplicación y datos históricos

- **D-11:** El flujo se **extrae al servicio nuevo y lo consumen ambos llamadores**.
  Hoy `VoidedForm::send()` (líneas 75-97) y `VoidedTable::sendXml()` (líneas 126-147)
  repiten consulta de ticket, guardado de CDR y marcado de comprobantes — por eso el
  defecto vive por partida doble. El servicio concentra: persistir ticket → consultar
  estado → clasificar → archivar CDR → marcar comprobantes. La Fase 2 encontrará la
  pieza hecha cuando reescriba `sendXml` como reconsulta.

- **D-12:** Las filas históricas **no se reclasifican automáticamente**. Las
  anulaciones ya guardadas como rechazo por un `"Bad Request"` que SUNAT en realidad
  aceptó no tienen ticket con el que reconsultar, y **el usuario las corrige
  directamente por base de datos**. La fase no inventa estados que no puede verificar.

- **D-13:** En consecuencia, la migración crea **`sunat_status` nullable y sin
  valor por defecto**: las filas existentes quedan en NULL — estado desconocido — y
  solo las anulaciones nuevas nacen con estado. La migración no ejecuta backfill ni
  deduce estado a partir del `sunatResponse` guardado.

### Verificación

- **D-14:** La verificación es **prueba manual contra el ambiente beta de SUNAT**
  (`production = false` en la empresa de sesión), recorriendo las tres rutas: CDR
  entregado en el mismo envío, código `98`, y fallo de transporte. Es como se
  reprodujo el bug original. No se añade infraestructura de test con SUNAT simulado
  en esta fase.

### Claude's Discretion

- Nombre exacto del servicio, del enum y de sus casos (las decisiones fijan la
  forma y la ubicación, no la nomenclatura literal).
- Firma de los métodos del servicio y cómo se le inyecta la company de sesión.
- Estructura interna concreta del JSON `sunatResponse` en estado pendiente —
  D-04 fija que sea explícito y que distinga código `98` de fallo de transporte.
- Cómo se representa visualmente el estado pendiente en la vista nueva del listado
  (D-09 fija que no ofrezca reenvío; el resto es criterio del planner).

### Folded Todos

- **`2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md`** —
  Diagnóstico raíz de este milestone. Documenta los cuatro defectos encadenados
  con referencias a línea exacta: ticket no persistido, consulta sin espera,
  `getResponse()` sin distinguir pendiente de rechazado, y recuperación de UI que
  reenvía el XML. Entra completo en el alcance de esta fase salvo la parte de
  `VoidedTable` como reconsulta (Fase 2) y el hallazgo de `Api/VoidedController`
  (Fase 3).

- **`2026-08-04-test-de-regresion-para-las-plantillas-sunat-del-repo.md`** —
  Doblado **solo como referencia de contexto**. `REQUIREMENTS.md` lo declara Out of
  Scope como milestone aparte porque toca las siete plantillas SUNAT y no comparte
  código con la anulación. La Fase 1 **no genera trabajo de plantillas**. Sigue en
  Deferred Ideas.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Diagnóstico del bug (leer primero)
- `.planning/todos/pending/2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md` — los cuatro defectos encadenados con referencias a línea exacta, y el trabajo concreto propuesto
- `.planning/PROJECT.md` §Context — origen del milestone, causa raíz y estado del módulo SUNAT
- `.planning/ROADMAP.md` §Notes — restricciones que atraviesan las tres fases y cómo se verifica

### Patrón a portar (leer, no modificar)
- `app/Livewire/Forms/DespatchForm.php` líneas 292-325 — persistir ticket antes de consultar, tratar `98` como pendiente, limpiar ticket al obtener CDR
- `app/Livewire/DespatchTable.php` líneas 186-242 — reconsulta con ticket guardado en vez de reenvío (referencia para la Fase 2)
- `database/migrations/2024_11_06_014056_create_despatches_table.php` línea 36 — forma de la columna `sunat_ticket`
- `app/Models/Despatch.php` línea 28 — `sunat_ticket` en `$fillable`

### Código a modificar
- `app/Livewire/Forms/VoidedForm.php` — `send()`, el flujo defectuoso
- `app/Livewire/VoidedTable.php` — `sendXml()` duplica el mismo flujo; `builder()` muestra el filtrado multi-tenant vigente
- `app/Models/Voided.php` — `$fillable` y `$casts`
- `database/migrations/2024_12_16_111346_create_voideds_table.php` — esquema actual de `voideds`
- `app/Observers/VoidedObserver.php` — rellena `correlativo` y `company` en `creating`
- `app/Services/Sunat/UtilService.php` — `getResponse()`, `getErrorResponse()`, `readCdr()`, y `getResponseVoided()` (que depende de `$this->see`, solo poblado tras llamar `getSee()`)
- `app/Traits/Sunat/UtilTrait.php` líneas 134-153 — `getResponseTicket()` roto, no retorna nada
- `resources/views/voideds/partials/sunatResponse.blade.php` — modal de detalle, exclusivo de anulación

### Restricciones del proyecto
- `./.claude/CLAUDE.md` §Constraints — Greenter solo como modelos/firma/envío/CDR, nada bajo `vendor/`, multi-tenant por sesión
- `./CLAUDE.md` §"SUNAT output is generated from repo-owned templates" — por qué no se toca `vendor/greenter`

### Fuera de alcance, no tocar
- `resources/views/vouchers/partials/response.blade.php` — compartida por `VoucherTable.php:131`, `DespatchTable.php:105`, `DespatchTransportistaTable.php:96` y `VoidedTable.php:62`
- `.planning/todos/pending/2026-08-04-test-de-regresion-para-las-plantillas-sunat-del-repo.md` — referencia de contexto, milestone aparte

### Advertencia sobre los mapas de codebase
- `.planning/codebase/*.md` son del 2026-07-24 y están desactualizados: reportan como abiertos hallazgos ya corregidos. Verificar contra el código antes de usarlos como fuente.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets

- **`UtilService::readCdr()`** (líneas 179-192) — ya normaliza un CDR a
  `['success' => true, 'cdrResponse' => [...]]`. `DespatchForm` lo usa al obtener
  el CDR; el servicio nuevo puede apoyarse en él para el caso aceptado.
- **`UtilService::getErrorResponse()`** (líneas 168-177) — normaliza un error de
  resultado SUNAT. Utilizable para el caso rechazado.
- **`UtilService::getSee()`** (líneas 27-40) — construye el cliente SOAP con el
  certificado y la clave SOL según `production`. Es el transporte de la anulación.
- **`VoidedObserver::creating()`** — ya rellena `correlativo` y el JSON `company`,
  que la migración declara NOT NULL y el modelo no expone en `$fillable`. Cualquier
  cambio en la creación de `Voided` debe respetarlo.

### Established Patterns

- **Ticket persistido**: `Despatch` y `DespatchTransportista` guardan `sunat_ticket`
  nullable, lo escriben apenas SUNAT lo devuelve y lo limpian al obtener el CDR.
  Es el patrón a portar.
- **Controller (shell) + Livewire (interacción) + Form object**: el fix debe encajar
  aquí; la lógica de negocio vive en servicios bajo `app/Services/`.
- **Multi-tenant por sesión**: `VoidedTable::builder()` filtra por
  `session('branch')->id`, `session('company')->id` y `production`. El marcado de
  `Invoice` como `voided` en `VoidedForm.php:90-95` filtra por los mismos campos.
  Toda consulta nueva debe hacerlo — no hay scope global.
- **Respuesta SUNAT como JSON en el modelo**: `sunatResponse` es una columna JSON
  casteada a array, no una tabla aparte.

### Integration Points

- **Transporte**: la anulación va por **SOAP** (`Greenter\See`, `sendXmlFile` +
  `getStatus`), no por la API REST GRE (`Greenter\Api`) que usan las guías. Por eso
  el patrón de `Despatch` se porta en forma pero no en literal: allí el `98` llega
  limpio en `getError()->getCode()`, aquí el `SoapFault` llega con código vacío y
  el `faultstring` crudo como mensaje.
- **Almacenamiento del CDR**: `Storage::put($directory . 'cdr/R-' . $name . '.zip', ...)`
  con `$directory` = `sunat/` o `sunat/beta/` según `production`. Disco configurado
  vía filesystem de Laravel (DO Spaces en producción).
- **Marcado de comprobantes**: `Invoice::where(...)->update(['voided' => true])`
  sobre `tipoDoc` + `serie` + `correlativo` + branch + company. Es el paso que
  WEB-05 condiciona a CDR con código `0`.
- **Consumidores del estado**: los dos `showResponse()` (`VoidedForm.php:132`,
  `VoidedTable.php:170`) eligen el ícono del swal leyendo `sunatResponse['success']`.
  Ambos son código de anulación y se migran al estado nuevo.

</code_context>

<specifics>
## Specific Ideas

- El usuario corrige a mano, por base de datos, las anulaciones históricas que
  quedaron marcadas como rechazo siendo aceptadas. La fase no debe intentar
  repararlas ni deducir su estado.
- El alcance se verificó archivo por archivo tras una objeción explícita del
  usuario: esta fase es **solo anulación de comprobantes de pago**. La vista
  compartida `vouchers/partials/response.blade.php` quedó fuera precisamente por
  eso, y esa restricción es firme.

</specifics>

<deferred>
## Deferred Ideas

- **Acción "Consultar estado" en el listado de anulaciones** (WEB-02) y **badge de
  tres estados a simple vista** (WEB-04) — Fase 2.
- **Reenvío del XML condicionado a ausencia de ticket** (WEB-03) — Fase 2. La Fase 1
  solo impide el reenvío cuando hay ticket; la lógica completa de cuándo sí reenviar
  es de la fase siguiente.
- **Endpoint API de anulación** (API-01, API-02, API-03) y el hallazgo de
  `App\Services\Sunat\VoidedService`, que importa `UtilTrait` sin hacer `use UtilTrait;`
  en el cuerpo de la clase — Fase 3.
- **Resumen diario con la misma semántica de estados** (SUM-01, SUM-02) — v2. Por eso
  D-03 exige que la semántica viva en un servicio reutilizable.
- **Comando de sondeo automático de CDRs pendientes** (POLL-01, POLL-02) — v2,
  excluido deliberadamente del milestone.
- **Tests automatizados con SUNAT simulado** — considerado y descartado para esta
  fase (D-14); la verificación es manual contra beta.

### Reviewed Todos (not folded)

- **`2026-08-04-test-de-regresion-para-las-plantillas-sunat-del-repo.md`** — doblado
  únicamente como referencia de contexto. `REQUIREMENTS.md` lo declara Out of Scope:
  toca las siete plantillas SUNAT y no comparte código con la anulación. No genera
  trabajo en esta fase.

</deferred>

---

*Phase: 1-ticket-persistido-y-semantica-de-tres-estados*
*Context gathered: 2026-08-04*
