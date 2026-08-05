---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
verified: 2026-08-05T00:00:00Z
status: human_needed
score: 9/9 must-haves verified
behavior_unverified: 0
overrides_applied: 0
re_verification:
  previous_status: gaps_found
  previous_score: 5/9
  gaps_closed:
    - "Un fallo posterior a la persistencia del ticket no puede reescribir la anulación como rechazo (Gap 1, D-06)"
    - "Una anulación con ticket guardado no ofrece el botón de reenviar el XML (Gap 2, D-09)"
    - "Las anulaciones históricas con estado NULL no inducen a reenviar un XML que SUNAT pudo haber recibido (Gap 3, D-13)"
    - "Los comprobantes se marcan como dados de baja únicamente dentro del ambiente correcto (Gap 4, WEB-05 / Criterio 4)"
  gaps_remaining: []
  regressions: []
human_verification:
  - test: "Enviar una comunicación de baja real contra el ambiente beta de SUNAT (production=false) y observar los tres desenlaces: CDR entregado en el mismo envío (código 0), código 98, y fallo de transporte (SoapFault/'Bad Request')."
    expected: "sunat_ticket queda guardado inmediatamente tras el envío. En los desenlaces 98/fallo de transporte: sunat_status=pendiente, sunat_ticket conservado, sunatResponse con clave pending y sin clave error. En el desenlace aceptado: sunat_status=aceptada, sunat_ticket=null, cdr_path apunta a un ZIP que existe de verdad en el disco configurado, y las facturas incluidas (y solo las del ambiente production=false) pasan a voided=1 — verificable con una factura de producción que comparta serie/correlativo, que debe seguir voided=0."
    why_human: "D-14 descartó explícitamente construir infraestructura de test con SUNAT simulado; solo se puede observar contra el SOAP real de SUNAT beta con una sesión de empresa activa, que no está disponible en una sesión de verificación de código."
  - test: "Provocar (o esperar) un fallo posterior a que SUNAT ya confirmara un desenlace — por ejemplo un error de base de datos durante el marcado de comprobantes, o un fallo al renderizar showResponse() justo después de un envío aceptado — y comprobar la fila en BD y storage/logs/laravel.log."
    expected: "La fila permanece en sunat_status='aceptada' (o 'rechazada' si el desenlace previo fue un rechazo por CDR con ticket) — nunca se sobrescribe. Aparece un Log::warning con voided_id, el sunat_status preservado y el mensaje de la excepción."
    why_human: "Es un escenario de fallo posterior al envío real; los cuatro escenarios de classifyFailure() ya se comprobaron en runtime con modelos en memoria (ver Behavioral Spot-Checks), pero forzar el fallo dentro de una transacción real contra SUNAT no es reproducible sin infraestructura de test con SUNAT simulado (D-14)."
  - test: "Recorrer el listado de anulaciones con al menos una fila de cada uno de los tres estados y con filas históricas (anteriores a la migración), y confirmar visualmente el ícono/estado que muestra cada una y qué botón ofrece."
    expected: "Una fila rechazada con ticket conservado (rechazo por CDR) y una fila histórica con sunatResponse poblado NO ofrecen el botón de reenvío — muestran el icono de detalle. Una fila rechazada sin ticket (el envío nunca llegó a SUNAT) SÍ ofrece reenviar. El botón de la columna CDR sobre una fila aceptada o histórica-con-respuesta responde 'No se puede reenviar' sin generar tráfico SUNAT."
    why_human: "Depende de datos reales en la tabla voideds de una empresa con sesión activa. La lógica ya se confirmó por lectura de código y por reflexión en runtime sobre canResendXml() y getAdditionalSelects() (ver Behavioral Spot-Checks), pero la confirmación visual definitiva con filas reales de producción/beta requiere una sesión activa."
  - test: "Abrir el modal de detalle de una anulación rechazada real (con mensaje de SUNAT) y de una fila histórica con sunat_status NULL y sunatResponse poblado."
    expected: "El código y el mensaje de SUNAT se leen igual que antes del cambio de CR-06, sin entidades HTML visibles (&lt;, &amp;) en un mensaje de texto plano. Si el desMotivoBaja original contuviera marcado, se lee como texto literal en vez de ejecutarse."
    why_human: "El escape ya se confirmó en runtime con un payload de prueba (<img src=x onerror=alert(1)>) sobre las dos ramas del partial (ver Behavioral Spot-Checks), pero la confirmación visual con un mensaje real de SUNAT no se ha ejecutado."
---

# Phase 1: Ticket persistido y semántica de tres estados — Verification Report (Re-verificación)

**Phase Goal:** Una comunicación de baja enviada desde la web conserva el ticket que SUNAT devolvió, y el estado que queda registrado refleja lo que SUNAT realmente respondió — aceptada, pendiente o rechazada.
**Verified:** 2026-08-05
**Status:** human_needed
**Re-verification:** Sí — tras el cierre de gaps (planes 01-04, 01-05, 01-06) y tres correcciones de seguridad adicionales de `01-REVIEW.md` (CR-01, CR-03, CR-05)

## Goal Achievement

Los cuatro gaps que dejó abiertos la verificación del 2026-08-04 (5/9 must-haves) se cerraron con evidencia verificada de forma independiente en esta sesión, leyendo el código fuente actual y ejecutando scripts desechables de solo lectura (arranque del kernel de Laravel, sin tocar la base de datos real ni SUNAT, borrados al terminar):

1. **Gap 1 (`classifyFailure()` degradaba una aceptación a rechazo)** — corregido en `app/Services/Sunat/VoidedTicketFlowService.php`. `classifyFailure()` ahora comprueba primero si `sunat_status` ya es `Aceptada`, o `Rechazada` con `sunat_ticket` no nulo, y en ambos casos devuelve ese mismo estado sin escribir nada. Confirmado ejecutando los cuatro escenarios del propio contrato del plan (`OK-ACEPTADA-NO-DEGRADA`, `OK-RECHAZO-CDR-NO-DEGRADA`, `OK-SIN-TICKET-SIGUE-RECHAZANDO`, `OK-CON-TICKET-SIGUE-PENDIENTE`) sobre modelos en memoria dentro de una transacción revertida.
2. **Gap 4 (`markVoidedInvoices()` no filtraba por `production`)** — corregido en el mismo archivo: el `where()` de `Invoice` ahora incluye `->where('production', $voided->production)` como sexto filtro. Confirmado capturando la consulta con `DB::pretend()` (sin ejecutarla): contiene los seis nombres de columna, `production` incluido.
3. **Gaps 2 y 3 (el listado nunca veía `sunat_ticket`/`sunatResponse`, y las filas históricas ofrecían reenvío)** — corregidos en `app/Livewire/VoidedTable.php`. `configure()` selecciona ahora `['id', 'sunat_ticket', 'sunatResponse']` (confirmado por reflexión: `getAdditionalSelects()` devuelve exactamente esos tres valores, frente al `["id"]` de la verificación anterior) y `sendXml()` delega su guarda en un nuevo predicado privado `canResendXml()` que replica hoja por hoja el árbol de `voideds/partials/response.blade.php`. Confirmado ejecutando los seis escenarios del árbol de decisión: los resultados obtenidos (`false, false, false, true, false, true`) coinciden exactamente con los seis que exige el contrato.

Como bonus atómico (Mitad B del plan 01-04), la rama de aceptación de `resolveTicket()` quedó envuelta en `DB::transaction()`: si el marcado de comprobantes falla, `refresh()` resincroniza el modelo y la fila revierte a `Pendiente` con su ticket vivo en vez de quedar aceptada con los comprobantes a medias — confirmado por lectura de código (una única llamada a `DB::transaction` y una a `refresh()` en todo el archivo).

Después de esta verificación de cierre de gaps, la sesión de revisión de código (`01-REVIEW.md`) encontró tres hallazgos críticos adicionales (fuera del alcance de los cuatro gaps, pero corregidos por decisión del usuario en esta misma pasada), y esta re-verificación los confirma también:

4. **CR-01 (el CDR archivado no se podía descargar)** — `VoidedTable` no definía `downloadCDR()`, y la columna CDR compartida (`vouchers.partials.cdr`) la invoca en cuanto hay `cdr_path`. Como `resolveTicket()` archiva el ZIP siempre que llega, prácticamente toda anulación resuelta mostraba ese botón y toda pulsación fallaba con `MethodNotFoundException`. Confirmado: el método existe ahora (líneas 93-106), comprueba `Storage::exists()` antes de descargar y evita `Storage::download(null)`.
5. **CR-03 (el ámbito multi-tenant de `VoidedForm` era invertible desde el navegador)** — `branch_id`, `company_id` y `production` eran propiedades públicas sin `#[Locked]` de un `Livewire\Form`, así que un `$wire.set` podía invertir el filtro de `production` que el propio Gap 4 acababa de añadir. Confirmado: las tres propiedades llevan ahora `#[Locked]`, y `send()` las vuelve a derivar de `session()` justo antes de `Voided::create()`, cubriendo también el caso de que `send()` se alcance sin pasar por `VoucherTable::voidReason()`.
6. **CR-05 (inyección XML vía `desMotivoBaja` — `]]>` rompía el `CDATA` firmado)** — la única validación era de longitud (`max:250`). Confirmado: `VoidedService::detailRules()` centraliza ahora la regla (`max:100`, límite real de SUNAT; `not_regex` contra `]]>`; lista de permitidos que excluye `<`, `>`, `]`) y la comparten los dos únicos puntos de entrada, `VoidedForm::send()` y `Api\VoidedController::validateDetails()` — verificado leyendo ambos archivos.

**Ningún hallazgo indica una regresión** sobre los cinco must-haves que la verificación anterior ya había dado por `VERIFIED` (persistencia del ticket, clasificación por excepción de código 98/SoapFault, aceptación por código de CDR, enum de tres estados, punto único de envío/clasificación): se releyeron y se comprueban intactos.

Lo que **no** se puede verificar desde el código — y por eso el estado de esta re-verificación es `human_needed`, no `passed` — es el comportamiento real contra el SOAP de SUNAT beta: D-14 descartó explícitamente construir infraestructura de test con SUNAT simulado en toda la fase, y ninguna de las tres sesiones de cierre (01-04, 01-05, 01-06) ejecutó la verificación manual que sus propios planes dejan pendiente. Los cuatro ítems de `human_verification` cubren exactamente eso.

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | El ticket se persiste en `voideds.sunat_ticket` antes de cualquier consulta de estado (TICKET-01) | ✓ VERIFIED | `VoidedTicketFlowService::sendAndResolve()` líneas 71-76: `$voided->sunat_ticket = $ticket; $voided->save();` antes de `resolveTicket()`. Sin cambios desde la verificación anterior; releído y confirmado |
| 2 | Un fallo de la consulta de estado (código 98 o `SoapFault`/error de transporte) deja la fila pendiente, nunca rechazada (TICKET-02, TICKET-03) | ✓ VERIFIED | `resolveTicket()` envuelve `$see->getStatus($ticket)` en `catch (\Throwable $e)` (líneas 89-97) y clasifica `markPending()`; el caso zip/cdr vacío sin excepción clasifica `markPending()` (líneas 102-110). Sin cambios; releído y confirmado |
| 3 | Un CDR con código `0` deja la fila aceptada, archiva el ZIP y limpia el ticket (TICKET-04) | ✓ VERIFIED | Líneas 115-118 (archiva siempre que llegue el zip) y 135-158 (`(string) $cdr->getCode() === '0'` decide la aceptación, ahora dentro de `DB::transaction()`, limpia `sunat_ticket`) |
| 4 | Los comprobantes se marcan como dados de baja únicamente tras un CDR aceptado, dentro del ámbito correcto (WEB-05 / Criterio 4) | ✓ VERIFIED (antes FAILED — Gap 4) | `markVoidedInvoices()` (líneas 261-272) filtra ahora por `tipoDoc`, `serie`, `correlativo`, `branch_id`, `company_id` **y `production`**. Confirmado con `DB::pretend()`: la consulta capturada contiene los seis filtros |
| 5 | El estado guardado se lee como uno de tres valores explícitos de un enum, no como `success:true/false` (TICKET-05) | ✓ VERIFIED | `App\Enums\VoidedStatus` (3 casos, sin `default` en los `match`), casteado en `Voided::$casts`. Sin cambios; releído y confirmado |
| 6 | Un fallo posterior a la persistencia del ticket no puede reescribir la anulación como rechazo (D-06) | ✓ VERIFIED (antes FAILED — Gap 1) | `classifyFailure()` (líneas 172-209) comprueba primero `sunat_status === Aceptada \|\| (Rechazada && !is_null(ticket))` y devuelve sin escribir. Confirmado con los 4 escenarios ejecutados en esta sesión: `SCENARIO-A-OK`, `SCENARIO-B-OK`, `SCENARIO-C-OK`, `SCENARIO-D-OK` |
| 7 | Una anulación con ticket guardado no ofrece el botón de reenviar el XML (D-09) | ✓ VERIFIED (antes FAILED — Gap 2) | `VoidedTable::configure()` selecciona `sunat_ticket`/`sunatResponse`; `canResendXml()` es la contraparte de servidor. Confirmado por reflexión en esta sesión: `AdditionalSelects: ["id","sunat_ticket","sunatResponse"]`, y los 6 escenarios de `canResendXml()` devuelven `false,false,false,true,false,true` — coincide exactamente con el árbol de la vista |
| 8 | Las anulaciones históricas con estado NULL no inducen a reenviar un XML que SUNAT pudo haber recibido (D-13) | ✓ VERIFIED (antes FAILED — Gap 3) | Mismo fix; el escenario 6 (`sunat_status=null, sunat_ticket=null, sunatResponse=null`) devuelve `true` (reenvío legítimo, nunca se envió nada) y el escenario 5 (`sunat_status=null, sunatResponse` poblado) devuelve `false` — el doble guardián de D-13 ahora opera con datos reales |
| 9 | El listado y el formulario comparten un único punto de envío y clasificación (D-11) | ✓ VERIFIED | `VoidedForm::send()` y `VoidedTable::sendXml()` construyen ambos `new VoidedTicketFlowService($util)` e invocan `sendAndResolve()`/`classifyFailure()`. Sin cambios; releído y confirmado |

**Score:** 9/9 truths verified (0 presentes-sin-comprobar-comportamiento)

### Correcciones adicionales verificadas (fuera de los 9 must-haves originales, cerradas por decisión del usuario en esta misma pasada)

| # | Hallazgo | Status | Evidence |
|---|----------|--------|----------|
| A | CR-01 — `downloadCDR()` no existía, toda descarga de CDR fallaba | ✓ VERIFIED | `VoidedTable.php` líneas 93-106: método público con guarda de `cdr_path`/`Storage::exists()` antes de `Storage::download()` |
| B | CR-03 — ámbito multi-tenant de `VoidedForm` invertible desde el cliente | ✓ VERIFIED | `VoidedForm.php`: `#[Locked]` en `branch_id`, `company_id`, `production` (líneas 36-43) + re-derivación desde `session()` en `send()` (líneas 59-61) |
| C | CR-05 — inyección XML vía `desMotivoBaja` sin filtrar contenido | ✓ VERIFIED | `VoidedService::detailRules()` centraliza `max:100` + `not_regex:/\]\]>/` + lista de permitidos, consumida por `VoidedForm::send()` y `Api\VoidedController::validateDetails()` |
| D | CR-06 (cerrado antes de esta re-verificación, releído para confirmar que no hubo regresión) | ✓ VERIFIED | `grep -c '{!!'` sobre `sunatResponse.blade.php` devuelve `0`; las dos ocurrencias de `sunatResponse['error']['message']` usan `{{ }}` |

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `app/Services/Sunat/VoidedTicketFlowService.php` | servicio con semántica de tres estados, sin degradación, con atomicidad y filtro `production` | ✓ VERIFIED | `php -l` limpio; `vendor/bin/pint --test` en verde; los 4 escenarios de `classifyFailure()` y el filtro de 6 columnas de `markVoidedInvoices()` confirmados en runtime en esta sesión |
| `app/Livewire/VoidedTable.php` | `configure()` selecciona `sunat_ticket`/`sunatResponse`; `sendXml()` protegido por `canResendXml()`; `downloadCDR()` presente | ✓ VERIFIED | `php -l` limpio; `vendor/bin/pint --test` en verde; `getAdditionalSelects()` y los 6 escenarios de `canResendXml()` confirmados en runtime en esta sesión; `downloadCDR()` presente y con guarda |
| `app/Livewire/Forms/VoidedForm.php` | `#[Locked]` en el ámbito multi-tenant, re-derivación en `send()` | ✓ VERIFIED | `php -l` limpio; releído íntegro; `#[Locked]` en las tres propiedades y reasignación desde `session()` antes de `Voided::create()` |
| `app/Services/Sunat/VoidedService.php` | `detailRules()`/`detailMessages()` compartidas con validación de `desMotivoBaja` | ✓ VERIFIED | `php -l` limpio; releído íntegro; regla `max:100` + `not_regex` + lista de permitidos |
| `resources/views/voideds/partials/response.blade.php` | árbol de decisión de reenvío con paridad exacta frente a `canResendXml()` | ✓ VERIFIED | Releído íntegro; sus cuatro ramas coinciden exactamente con los 6 escenarios de `canResendXml()` verificados en runtime |
| `resources/views/voideds/partials/sunatResponse.blade.php` | modal de detalle sin salida de HTML sin escapar | ✓ VERIFIED | `grep -c '{!!'` = `0`; 216 líneas (sin cambio de tamaño); las dos referencias al mensaje de error siguen presentes con `{{ }}` |
| `database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php` | columnas nullable, sin default, sin backfill | ✓ VERIFIED | `php artisan migrate:status` la lista como `Ran` (batch 5); confirmado en esta sesión |

### Key Link Verification

| From | To | Via | Status | Details |
|------|-----|-----|--------|---------|
| `VoidedTicketFlowService::classifyFailure()` | invariante de no-degradación | comparación `sunat_status === Aceptada \|\| (Rechazada && ticket)` antes de `is_null($voided->sunat_ticket)` | ✓ WIRED | Confirmado con los 4 escenarios de runtime en esta sesión |
| `VoidedTicketFlowService::resolveTicket()` (aceptación) | `Invoice::where(...production...)->update(['voided' => true])` | `DB::transaction()` que envuelve la escritura de estado + `markVoidedInvoices()` | ✓ WIRED | Confirmado por lectura de código: una única `DB::transaction`, `refresh()` en el `catch` |
| `VoidedTable::configure()` | `resources/views/voideds/partials/response.blade.php` | `setAdditionalSelects(['id','sunat_ticket','sunatResponse'])` | ✓ WIRED | Confirmado con `getAdditionalSelects()` en runtime en esta sesión |
| `VoidedTable::sendXml()` | `VoidedTable::canResendXml()` | primera sentencia condicional del método (línea 118), antes de `Storage::put`/`sendAndResolve` | ✓ WIRED | Confirmado leyendo el orden de líneas del archivo |
| `vouchers.partials.cdr` (botón compartido) | `VoidedTable::downloadCDR()` | `wire:click="downloadCDR({{ $row->id }})"` | ✓ WIRED | Método presente y con guarda de ruta nula/inexistente |
| `VoidedForm` (`branch_id`/`company_id`/`production`) | `session('branch')`/`session('company')` | `#[Locked]` + reasignación en `send()` | ✓ WIRED | Releído; las tres propiedades no pueden llegar rehidratadas desde el cliente, y además se sobrescriben antes de `Voided::create()` |
| `VoidedForm::send()` / `Api\VoidedController::validateDetails()` | `VoidedService::detailRules()` | llamada directa a los mismos métodos estáticos | ✓ WIRED | Confirmado en ambos archivos: mismo contrato de validación en las dos superficies de entrada |

### Data-Flow Trace (Level 4)

| Artefacto | Variable | Fuente | ¿Datos reales? | Status |
|-----------|----------|--------|-----------------|--------|
| `voideds/partials/response.blade.php` | `$row->sunat_ticket` | `VoidedTable::builder()` → SELECT ahora incluye `sunat_ticket` vía `setAdditionalSelects()` | Sí — confirmado con `getAdditionalSelects()` en runtime | ✓ FLOWING (antes ✗ HOLLOW_PROP) |
| `voideds/partials/response.blade.php` | `$row->sunatResponse` | ídem | Sí | ✓ FLOWING (antes ✗ HOLLOW_PROP) |
| `voideds/partials/response.blade.php` | `$row->sunat_status` | `Column::make('Sunat', 'sunat_status')` | Sí | ✓ FLOWING (sin cambio) |
| `VoidedTicketFlowService::markVoidedInvoices()` | filtro `production` | `$voided->production` (modelo recibido) | Sí — nunca `session()` | ✓ FLOWING (antes ✗ DISCONNECTED — Gap 4) |
| `VoidedForm` → `Voided::create()` | `branch_id`/`company_id`/`production` | `session('branch')`/`session('company')`, re-derivados en `send()`, no del payload del cliente | Sí — `#[Locked]` impide la rehidratación desde el navegador | ✓ FLOWING (corrige CR-03) |

### Behavioral Spot-Checks

Todos los checks siguientes se ejecutaron en esta sesión de re-verificación con scripts desechables que arrancan el kernel de Laravel (no `tinker`, que en este repo falla con un artefacto de PsySH/WireUI ajeno al código, ya documentado por los ejecutores de 01-02/01-03), usan modelos en memoria y no tocan la base de datos real ni SUNAT. Los scripts se borraron al terminar (`git status --porcelain` confirma que no quedó ninguno).

| Comportamiento | Comando | Resultado | Status |
|-----------------|---------|-----------|--------|
| `classifyFailure()` no degrada un desenlace confirmado (Aceptada) | Script con `assert()` sobre 4 escenarios, dentro de `DB::beginTransaction()`/`rollBack()` | `SCENARIO-A-OK` (Aceptada intacta), `SCENARIO-B-OK` (Rechazada-con-ticket intacta) | ✓ PASS |
| `classifyFailure()` conserva el comportamiento preexistente (D-05/D-06) | mismo script | `SCENARIO-C-OK` (sin ticket → rechaza), `SCENARIO-D-OK` (con ticket → pendiente) | ✓ PASS |
| `markVoidedInvoices()` filtra por `production` | `DB::pretend()` capturando la consulta UPDATE, invocación por reflexión | 1 consulta capturada, contiene `tipodoc`, `serie`, `correlativo`, `branch_id`, `company_id`, `production` → `PRODUCTION-FILTER-OK` | ✓ PASS |
| `VoidedTable::canResendXml()` es privado | `ReflectionMethod::isPrivate()` | `CANRESEND-IS-PRIVATE` | ✓ PASS |
| `VoidedTable::canResendXml()` — 6 escenarios del árbol de decisión | Invocación por reflexión con 6 `Voided` en memoria | `false,false,false,true,false,true` — los 6 coinciden con el contrato | ✓ PASS |
| `VoidedTable::configure()` selecciona las 3 columnas necesarias | `getAdditionalSelects()` en runtime | `["id","sunat_ticket","sunatResponse"]` | ✓ PASS |
| Columna `Sunat` sigue declarada sobre `sunat_status` | `columns()` en runtime, inspección de `getField()` | `sunat_status` | ✓ PASS |
| `sunatResponse.blade.php` no tiene salida sin escapar | `grep -c '{!!'` | `0` | ✓ PASS |
| `builder()` conserva los tres filtros multi-tenant | `grep` sobre `VoidedTable.php` | `session('branch')->id`, `session('company')->id`, `session('company')->production` presentes | ✓ PASS |
| Migración aplicada | `php artisan migrate:status` | `2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table` ... `Ran` (batch 5) | ✓ PASS |
| Estilo de código en los archivos tocados | `vendor/bin/pint --test` sobre los 4 archivos PHP relevantes | `PASS 4 files` | ✓ PASS |
| `php -l` en los 7 archivos PHP relevantes de la fase | `php -l` | Sin errores de sintaxis en ninguno | ✓ PASS |
| Suite completa PHPUnit | no ejecutada | — | ? SKIP — la suite corre contra la BD configurada (no hay override sqlite habilitado) y es destructiva sin él; ya estaba en rojo antes de esta fase por un fallo no relacionado de `UserFactory` |

### Probe Execution

No aplica — el proyecto no define probes bajo `scripts/*/tests/probe-*.sh` ni ningún plan de esta fase los declara. **SKIPPED (no hay probes definidos).**

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|--------------|--------|----------|
| TICKET-01 | 01-01, 01-02, 01-03, 01-05 | Guarda el ticket antes de consultar el estado | ✓ SATISFIED | `sendAndResolve()`; ambos llamadores y el listado (post-fix) lo consumen correctamente |
| TICKET-02 | 01-01 | Código 98 conserva el ticket y no rechaza | ✓ SATISFIED | `resolveTicket()` líneas 102-110 |
| TICKET-03 | 01-01 | `SoapFault`/fallo de transporte conserva el ticket y no rechaza | ✓ SATISFIED | `resolveTicket()` líneas 89-97 |
| TICKET-04 | 01-01, 01-04 | CDR guardado, ZIP archivado, ticket limpio, ahora atómico con el marcado de comprobantes | ✓ SATISFIED | Líneas 115-118, 135-158 |
| TICKET-05 | 01-01, 01-02, 01-03, 01-04, 01-05 | Tres estados explícitos en vez de `success` booleano | ✓ SATISFIED | Enum + cast + consumo consistente; atomicidad de la rama de aceptación confirmada |
| WEB-01 | 01-02, 01-03, 01-05, 01-06 | Aviso "En proceso" en vez de error; detalle sin XSS | ✓ SATISFIED | `VoidedForm.php`/`VoidedTable.php` avisos; `sunatResponse.blade.php` escapado |
| WEB-05 | 01-01, 01-02, 01-04 | Comprobantes marcados solo tras CDR aceptado, dentro del ambiente correcto | ✓ SATISFIED | Gap 4 cerrado: filtro `production` confirmado con `DB::pretend()` |

No hay requisitos huérfanos: los 7 IDs de esta fase (TICKET-01..05, WEB-01, WEB-05) aparecen en `requirements:` de al menos un plan y en la tabla de trazabilidad de `REQUIREMENTS.md` ("Phase 1 / Complete"), marcado que esta re-verificación confirma con evidencia independiente para los siete.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `app/Enums/VoidedStatus.php` | 25 | `badgeColor()` devuelve `'danger'`, color inexistente en el pack de WireUI (WR-02 de `01-REVIEW.md`) | ⚠️ Warning (cosmético) | La insignia RECHAZADA se pinta sin color; **fuera del alcance de los 9 must-haves de esta fase**, se deja abierto por decisión explícita del usuario |
| `app/Livewire/VoidedTable.php`, `app/Livewire/Forms/VoidedForm.php` | múltiples | CR-02 (IDOR — acciones resuelven `Voided` por ID del cliente sin revalidar `branch_id`/`company_id`/`production`) y CR-04 (`send()`/`sendXml()` no comprueban `vouchers.anulate`) siguen abiertos | 🛑 Blocker de seguridad, pero **fuera del alcance de esta fase** | `REQUIREMENTS.md` §Out of Scope lista explícitamente "Revalidación de la sucursal de sesión contra empresa/usuario" como ajeno a este milestone; CR-04 no está entre los 7 requisitos de la Fase 1. Ambos quedan documentados en `01-REVIEW.md` para una fase de seguridad aparte, no creada todavía en `ROADMAP.md` |
| Varios (`WR-01`, `WR-03`, `WR-05`, `WR-07`, `WR-08`, `WR-09`, `WR-10`, `WR-11`, `WR-12`, `WR-13`) | — | 10 warnings adicionales de `01-REVIEW.md`, deliberadamente no atendidos en el cierre de gaps | ℹ️ Info | No afectan los 9 must-haves ni los 7 requisitos de esta fase; documentados en `01-REVIEW.md` para trabajo futuro |

Ningún marcador de deuda (`TBD`/`FIXME`/`XXX`/`TODO`/`HACK`/`PLACEHOLDER`) en los archivos de la fase relevantes para los must-haves.

**Nota sobre CR-02/CR-04/los 13 warnings:** no se listan como `deferred` en el frontmatter porque `ROADMAP.md` no tiene todavía una fase futura que los mencione explícitamente (el Paso 9b de la metodología de verificación exige evidencia concreta de una fase posterior, y no la hay). Se registran aquí como anti-patrones informativos porque el propio `01-REVIEW.md` y las notas del usuario en esta sesión los declaran deuda conocida y aceptada, no como gaps de esta fase — ninguno de los dos pertenece a TICKET-01..05/WEB-01/WEB-05.

### Human Verification Required

Ver `human_verification` en el frontmatter. En resumen: la verificación manual contra SUNAT beta (D-14) que las seis sesiones de ejecución de esta fase (01-01 a 01-06) dejaron explícitamente pendiente **sigue sin ejecutarse**. Todas las verificaciones automatizadas posibles sin tocar SUNAT ni la base de datos real —lectura de código, `php -l`, `vendor/bin/pint --test`, y los scripts desechables de esta sesión que ejercitan `classifyFailure()`, `canResendXml()`, `markVoidedInvoices()` y el escape de `sunatResponse.blade.php` con datos en memoria— pasaron íntegras. Lo que falta es la confirmación con tráfico SOAP real, que ningún agente puede ejecutar sin credenciales y una sesión de empresa activa con `production = false`.

### Gaps Summary

No hay gaps activos. Los cuatro gaps de la verificación anterior (2026-08-04) se cerraron con evidencia verificada de forma independiente en esta sesión, y no se detectó ninguna regresión sobre los cinco must-haves que ya estaban `VERIFIED`. Tres hallazgos críticos adicionales del code review (CR-01, CR-03, CR-05) también se confirmaron corregidos.

El estado de esta re-verificación es `human_needed`, no `passed`, exclusivamente porque el comportamiento contra el SOAP real de SUNAT beta (D-14) no se ha confirmado en ninguna sesión de esta fase. Es una limitación de entorno, no un defecto de código: no hay infraestructura de test con SUNAT simulado por decisión explícita del proyecto, y la única vía de confirmación es una sesión de empresa activa contra el ambiente beta.

Dos hallazgos de seguridad permanecen deliberadamente abiertos y fuera del alcance de los 9 must-haves de esta fase — CR-02 (IDOR multi-tenant en las acciones de `VoidedTable`) y CR-04 (falta de gate `vouchers.anulate` en la operación de envío) — junto con 10 warnings adicionales de `01-REVIEW.md`. Ninguno de los dos aparece todavía como fase futura en `ROADMAP.md`, así que se reportan aquí como deuda conocida para que el orquestador decida si abrir una fase de seguridad, en vez de listarse como `deferred`.

---

_Verified: 2026-08-05_
_Verifier: Claude (gsd-verifier)_
