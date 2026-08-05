---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
verified: 2026-08-04T00:00:00Z
status: gaps_found
score: 5/9 must-haves verified
behavior_unverified: 0
overrides_applied: 0
gaps:
  - truth: "Un fallo posterior a la persistencia del ticket no puede reescribir la anulación como rechazo (D-06, must-have de 01-02-PLAN.md)"
    status: failed
    reason: "classifyFailure() (app/Services/Sunat/VoidedTicketFlowService.php:157-174) clasifica exclusivamente por is_null($voided->sunat_ticket). En la rama de aceptación (líneas 134-142), sunat_ticket se pone a null ANTES de invocar markVoidedInvoices() (línea 140). Si markVoidedInvoices() lanza (un foreach sin validar sobre $voided->details, o un fallo de DB), la excepción sube al catch exterior de VoidedForm::send() o VoidedTable::sendXml(), que llama a classifyFailure(). Como el ticket ya es null, is_null() es true y se ejecuta markRejected(), sobrescribiendo sunat_status=Aceptada por Rechazada y pisando el sunatResponse que ya tenía el CDR real archivado. Una baja que SUNAT aceptó (con CDR en disco) queda registrada como RECHAZADA — exactamente la clase de defecto que la fase existe para eliminar, reintroducida por el otro extremo."
    artifacts:
      - path: "app/Services/Sunat/VoidedTicketFlowService.php"
        issue: "classifyFailure() (líneas 157-174) no comprueba si sunat_status ya es VoidedStatus::Aceptada antes de delegar en is_null(sunat_ticket)"
    missing:
      - "classifyFailure() debe devolver Aceptada sin reescribir cuando $voided->sunat_status ya es VoidedStatus::Aceptada"
      - "Envolver la escritura de aceptación (sunatResponse + sunat_status + sunat_ticket=null) y markVoidedInvoices() en una transacción única, de modo que un fallo en el marcado de comprobantes no dispare el catch exterior con el ticket ya limpiado"
  - truth: "Una anulación con ticket guardado no ofrece el botón de reenviar el XML, ni desde la vista ni invocando la acción directamente (D-09, must-have de 01-03-PLAN.md)"
    status: failed
    reason: "VoidedTable::configure() (línea 20) declara setAdditionalSelects(['id']) y la única Column con campo relevante es Column::make('Sunat','sunat_status') (línea 61). Verificado por reflexión en tiempo de ejecución contra el código actual: rappasoft/laravel-livewire-tables construye el SELECT solo con additionalSelects + columnas declaradas (vendor/rappasoft/laravel-livewire-tables/src/Traits/WithData.php:218-236); sunat_ticket y sunatResponse NO están declarados como columnas ni en additionalSelects, así que $row->sunat_ticket y $row->sunatResponse llegan siempre null al partial, sin importar el valor real en base de datos. Consecuencia verificada leyendo resources/views/voideds/partials/response.blade.php:16-32: una fila RECHAZADA cuyo ticket SÍ está guardado (rechazo por CDR con código≠0, D-07 lo conserva a propósito) evalúa is_null($row->sunat_ticket) como true y muestra el botón wire:click=\"sendXml\" en vez del botón showResponse sin reenvío que el propio comentario D-09 de la línea 27 dice que debería mostrar. La vista, tal como quedó, no puede cumplir la condición que declara implementar."
    artifacts:
      - path: "app/Livewire/VoidedTable.php"
        issue: "configure() no incluye sunat_ticket ni sunatResponse en setAdditionalSelects(); confirmado con un script que instancia VoidedTable, invoca configure()+columns() y refleja getAdditionalSelects(): devuelve solo [\"id\"]"
      - path: "resources/views/voideds/partials/response.blade.php"
        issue: "líneas 18 y 41 deciden el gating de reenvío leyendo $row->sunat_ticket / $row->sunatResponse, que siempre llegan null por el punto anterior"
    missing:
      - "Añadir 'sunat_ticket' y 'sunatResponse' a setAdditionalSelects() en VoidedTable::configure()"
  - truth: "Las anulaciones históricas con estado NULL siguen renderizando y no inducen a reenviar un XML que SUNAT pudo haber recibido (D-13, must-have de 01-03-PLAN.md)"
    status: failed
    reason: "La misma falta de selección de columnas hace que, para TODA fila histórica (sunat_status NULL), is_null($row->sunat_ticket) && empty($row->sunatResponse) (response.blade.php:41) sea siempre true — independientemente de si esa fila histórica realmente tiene una respuesta de SUNAT guardada. El propio comentario de la vista (líneas 36-39) declara la intención contraria: 'cualquier otra combinación pudo haber sido recibida por SUNAT y no debe inducir a reenviar'. En la práctica, TODA fila histórica (incluidas las que fueron aceptadas o rechazadas con CDR bajo el sistema anterior, con sunatResponse poblado) muestra el botón de reenvío. Si el usuario hace clic, sendXml() recibe un Voided recién hidratado por route-model-binding con sunat_ticket genuinamente NULL en BD (la columna nunca tuvo backfill, D-12/D-13), así que la guarda del servidor (VoidedTable.php:86) tampoco lo detiene: el XML se reenvía, SUNAT responde 2109 u otro código, y sendAndResolve()/markRejected() sobrescribe sunat_status=Rechazada sobre una baja que SUNAT ya había aceptado o resuelto bajo el sistema anterior. Es la vía de daño exacta que D-13 se propuso cerrar."
    artifacts:
      - path: "resources/views/voideds/partials/response.blade.php"
        issue: "línea 41 — el doble guardián (ticket nulo Y sunatResponse vacío) es correcto en su diseño pero ambos operandos llegan siempre null/vacío por la falta de selección en VoidedTable"
    missing:
      - "Mismo fix que el gap anterior: seleccionar sunat_ticket y sunatResponse en VoidedTable::configure()"
  - truth: "Los comprobantes de la anulación se marcan como dados de baja únicamente tras un CDR con código 0, dentro del ámbito correcto (WEB-05 / Criterio 4 del ROADMAP)"
    status: failed
    reason: "markVoidedInvoices() (app/Services/Sunat/VoidedTicketFlowService.php:224-234) filtra por tipoDoc, serie, correlativo, branch_id y company_id, pero NO por production. Invoice tiene columna production (confirmado: app/Models/Invoice.php:84 en $fillable, línea 105 en $casts) y beta/producción conviven en la misma tabla con la misma serie/correlativo tras cada paso a producción — es exactamente el filtro que VoidedTable::builder() y VoucherTable::builder() sí aplican para leer, pero que este servicio nuevo omite para escribir. Una anulación de PRUEBA aceptada en beta marca voided=true sobre la factura REAL de producción con la misma serie/correlativo. El ERP mostraría entonces como anulado un comprobante que SUNAT sigue teniendo como vigente — el escenario que el Core Value del proyecto identifica explícitamente como inaceptable ('si el estado que muestra el sistema no coincide con el que SUNAT tiene, el resto del ERP deja de ser confiable para contabilidad')."
    artifacts:
      - path: "app/Services/Sunat/VoidedTicketFlowService.php"
        issue: "markVoidedInvoices() (líneas 224-234) no incluye ->where('production', $voided->production) en la consulta de Invoice"
    missing:
      - "Añadir el filtro por production, leído de $voided->production, al where() de markVoidedInvoices()"
deferred: []
human_verification:
  - test: "Enviar una comunicación de baja real contra el ambiente beta de SUNAT (production=false) y observar los tres desenlaces: CDR entregado en el mismo envío, código 98, y fallo de transporte (SoapFault/'Bad Request')"
    expected: "sunat_ticket queda guardado inmediatamente tras el envío (antes de cualquier consulta); en los desenlaces 98/fallo de transporte, sunat_status=pendiente, sunat_ticket conservado, sunatResponse con clave pending y sin clave error; en el desenlace aceptado, sunat_status=aceptada, sunat_ticket=null, cdr_path apunta a un ZIP existente en el disco configurado, y las facturas incluidas pasan a voided=1"
    why_human: "D-14 descartó explícitamente construir infraestructura de test con SUNAT simulado; sólo se puede observar contra el SOAP real de SUNAT beta con una sesión de empresa activa, que no está disponible en esta sesión de verificación"
  - test: "Recorrer el listado de anulaciones con al menos una fila de cada uno de los tres estados y con filas históricas, y confirmar visualmente el ícono/estado mostrado y qué botones ofrece cada uno"
    expected: "El icono y el botón de cada fila corresponden a su estado real, y sólo las filas sin ticket y sin respuesta previa ofrecen reenviar"
    why_human: "Depende de datos reales en la tabla voideds de una empresa con sesión activa; los gaps 2 y 3 de este informe ya demuestran por lectura de código que el resultado esperado no se cumple hoy, pero la confirmación visual definitiva requiere una sesión con datos reales"
---

# Phase 1: Ticket persistido y semántica de tres estados — Verification Report

**Phase Goal:** Una comunicación de baja enviada desde la web conserva el ticket que SUNAT devolvió, y el estado que queda registrado refleja lo que SUNAT realmente respondió — aceptada, pendiente o rechazada.
**Verified:** 2026-08-04
**Status:** gaps_found
**Re-verification:** No — verificación inicial

## Goal Achievement

La capa de dominio (`VoidedTicketFlowService`, el enum `VoidedStatus`, las columnas nuevas) está bien resuelta y corrige de raíz el defecto original: el ticket se persiste con un `save()` propio antes de cualquier consulta de estado, cualquier `\Throwable` no atrapable de la consulta clasifica pendiente en vez de rechazo, y la aceptación se decide por el código del CDR y no por el indicador de éxito de Greenter. Esa mitad de la fase — el "núcleo de dominio" del Plan 01 — se sostiene bajo lectura de código independiente.

Sin embargo, la mitad visible del entregable — el listado de anulaciones (Plan 03) — **no cumple la condición que declara implementar**. `VoidedTable` no selecciona `sunat_ticket` ni `sunatResponse` en la consulta del listado (confirmado en tiempo de ejecución, no solo por lectura), de modo que el partial nuevo que decide si ofrecer el botón de reenvío recibe siempre `null` en esos dos campos, independientemente del valor real en base de datos. El resultado es que toda fila rechazada-con-ticket-conservado y toda fila histórica ofrecen reenviar el XML — la vía de daño exacta (`2109`, sobrescritura de un desenlace ya confirmado por SUNAT) que D-09 y D-13 se propusieron cerrar. A esto se suma un defecto independiente en `VoidedTicketFlowService::markVoidedInvoices()`, que no filtra por `production` y puede marcar como anulada una factura de producción a partir de una prueba en beta con la misma serie/correlativo, y un tercero (`classifyFailure()`) que puede sobrescribir una aceptación real como rechazo si el marcado de comprobantes falla después de que el ticket ya quedó en `null`.

Estos tres hallazgos coinciden exactamente con CR-01, CR-02, CR-04 y CR-05 de `01-REVIEW.md`, verificados de forma independiente en esta sesión leyendo el código fuente actual y, en el caso de CR-01, ejecutando un script que instancia `VoidedTable`, invoca `configure()`/`columns()` y refleja `getAdditionalSelects()` — el resultado (`["id"]`, sin `sunat_ticket` ni `sunatResponse`) confirma el hallazgo sin ambigüedad. Ninguno de los seis hallazgos críticos del review tiene un commit de corrección posterior (`git log` no muestra commits después de `e3768de`, que solo añade el propio informe).

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | El ticket se persiste en `voideds.sunat_ticket` antes de cualquier consulta de estado (TICKET-01) | ✓ VERIFIED | `VoidedTicketFlowService::sendAndResolve()` (líneas 72-75): `$voided->sunat_ticket = $ticket; $voided->save();` ocurre antes de `return $this->resolveTicket(...)`. Ambos llamadores Livewire (`VoidedForm.php:62`, `VoidedTable.php:124`) invocan `sendAndResolve()` como único punto de envío |
| 2 | Un fallo de la consulta de estado (código 98 o `SoapFault`/error de transporte) deja la fila pendiente, nunca rechazada (TICKET-02, TICKET-03) | ✓ VERIFIED | `resolveTicket()` envuelve `$see->getStatus($ticket)` en `catch (\Throwable $e)` (líneas 88-96, no solo `SoapFault`) y clasifica `markPending()`; el caso zip/cdr vacío sin excepción (código 98 típico) también clasifica `markPending()` con motivo derivado del código (líneas 101-109), sin lista blanca de códigos |
| 3 | Un CDR con código `0` deja la fila aceptada, archiva el ZIP y limpia el ticket (TICKET-04) | ✓ VERIFIED | Líneas 114-117 (archiva el ZIP siempre que llegue, sin mirar `isSuccess()`) y líneas 134-142 (`(string) $cdr->getCode() === '0'` decide la aceptación, limpia `sunat_ticket`). Nota menor no bloqueante: `Storage::put()` no comprueba su valor de retorno (WR-04 del review) |
| 4 | Los comprobantes se marcan como dados de baja únicamente tras un CDR aceptado, dentro del ámbito correcto (WEB-05 / Criterio 4) | ✗ FAILED | Ver Gap 4. El disparador (solo la rama aceptada invoca `markVoidedInvoices()`) es correcto, pero el ámbito de la consulta omite `production`, permitiendo marcar facturas del entorno equivocado |
| 5 | El estado guardado se lee como uno de tres valores explícitos de un enum, no como `success:true/false` (TICKET-05) | ✓ VERIFIED | `App\Enums\VoidedStatus` (3 casos, sin `default` en los `match`), casteado en `Voided::$casts` (línea 40), leído en `VoidedForm::showResponse()`, `VoidedTable::showResponse()`, `sunatResponse.blade.php` y `voideds/partials/response.blade.php` — ninguno de estos puntos decide ya por `sunatResponse['success']` salvo el fallback explícito para filas históricas con `sunat_status === null` |
| 6 | Un fallo posterior a la persistencia del ticket no puede reescribir la anulación como rechazo (D-06, must-have de 01-02-PLAN.md) | ✗ FAILED | Ver Gap 1. `classifyFailure()` clasifica solo por `is_null(sunat_ticket)`, y la rama de aceptación pone el ticket a `null` antes de invocar `markVoidedInvoices()`; un fallo ahí sobrescribe una aceptación real como rechazo |
| 7 | Una anulación con ticket guardado no ofrece el botón de reenviar el XML, ni desde la vista ni invocando la acción directamente (D-09, must-have de 01-03-PLAN.md) | ✗ FAILED | Ver Gap 2. La vista nunca puede evaluar correctamente `is_null($row->sunat_ticket)` porque esa columna no llega seleccionada desde `VoidedTable` |
| 8 | Las anulaciones históricas con estado NULL no inducen a reenviar un XML que SUNAT pudo haber recibido (D-13, must-have de 01-03-PLAN.md) | ✗ FAILED | Ver Gap 3. Mismo defecto de selección de columnas; el doble guardián de la vista es inoperante en la práctica |
| 9 | El listado y el formulario comparten un único punto de envío y clasificación (D-11) | ✓ VERIFIED | `VoidedForm::send()` y `VoidedTable::sendXml()` construyen ambos `new VoidedTicketFlowService($util)` e invocan exactamente `sendAndResolve()`/`classifyFailure()`; ninguno de los dos vuelve a construir un cliente SOAP ni a repetir el marcado de `Invoice` inline (confirmado: 0 apariciones de `getSee(`, `sendXmlFile`, `->getStatus(`, `Invoice::where` fuera de comentarios en ambos archivos) |

**Score:** 5/9 truths verified (0 presentes-sin-comprobar-comportamiento)

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php` | columnas nullable, sin default, sin backfill | ✓ VERIFIED | Migración aplicada (`php artisan migrate:status` la lista como `Ran`, batch 5); `up()` añade dos `string()->nullable()` sin `default()`; `down()` limpio con `dropColumn` |
| `app/Enums/VoidedStatus.php` | enum string-backed, 3 casos, `label()`/`badgeColor()` | ✓ VERIFIED | Existe, 3 casos, ambos métodos con `match` sin `default` |
| `app/Services/Sunat/VoidedTicketFlowService.php` | servicio dedicado con la semántica de tres estados | ✓ VERIFIED (con defectos internos) | Existe, métodos públicos exactamente `__construct`, `sendAndResolve`, `resolveTicket`, `classifyFailure`; contiene los defectos de Gap 1 y Gap 4 |
| `app/Models/Voided.php` | `sunat_ticket`/`sunat_status` en `$fillable`, cast de enum | ✓ VERIFIED | Confirmado en `$fillable` (líneas 25-26) y `$casts` (línea 40) |
| `app/Livewire/Forms/VoidedForm.php` | `send()` delega en el servicio, avisa "En proceso" | ✓ VERIFIED | `send()` no contiene cliente SOAP directo ni marcado de `Invoice`; contiene el swal "En proceso" guardado por `VoidedStatus::Pendiente` en la ruta feliz y en el `catch` |
| `app/Livewire/VoidedTable.php` | `sendXml()` delega en el servicio y protege el reenvío; columna Sunat usa la vista propia | ⚠️ ORPHANED (selección incompleta) | `sendXml()` delega correctamente y su guarda de servidor (línea 86) es correcta para filas con ticket realmente cargado (route-model-binding trae el modelo completo); pero `configure()` (línea 20) no selecciona `sunat_ticket`/`sunatResponse`, que es lo que consume el partial de la columna Sunat — el artefacto existe y está conectado, pero los datos que necesita no llegan |
| `resources/views/voideds/partials/sunatResponse.blade.php` | modal de detalle con tres ramas + fallback histórico | ✓ VERIFIED | Discrimina por `$document->sunat_status`; badge/ícono/texto por rama; rama pendiente muestra `sunat_ticket`; fallback `DESCONOCIDO` para `null` |
| `resources/views/voideds/partials/response.blade.php` | vista propia del listado, gating por ticket | ✗ STUB EFECTIVO (lógica correcta, datos ausentes) | El árbol de decisión está bien construido (D-09/D-13), pero opera sobre dos variables (`$row->sunat_ticket`, `$row->sunatResponse`) que siempre llegan `null` desde `VoidedTable` — el resultado observable es indistinguible de una vista que no implementara el gating |

### Key Link Verification

| From | To | Via | Status | Details |
|------|-----|-----|--------|---------|
| `VoidedForm::send()` | `VoidedTicketFlowService::sendAndResolve()` | llamada directa con `$util` de sesión | ✓ WIRED | Confirmado, líneas 39, 62 |
| `VoidedForm::send()` catch | `VoidedTicketFlowService::classifyFailure()` | llamada directa | ✓ WIRED | Línea 79 |
| `VoidedTable::sendXml()` | `VoidedTicketFlowService::sendAndResolve()` / `classifyFailure()` | llamada directa | ✓ WIRED | Líneas 100, 124, 140 |
| `VoidedTable::columns()` (columna Sunat) | `resources/views/voideds/partials/response.blade.php` | `Column::make('Sunat','sunat_status')->format(...)` | ⚠️ WIRED PERO CON DATOS INCOMPLETOS | La vista se renderiza (el link existe), pero dos de las tres variables que lee (`sunat_ticket`, `sunatResponse`) nunca llegan pobladas porque `configure()` no las selecciona — confirmado por reflexión en tiempo de ejecución sobre `getAdditionalSelects()` (`["id"]`) y `getColumns()` (sin `sunat_ticket` ni `sunatResponse`) |
| `VoidedTicketFlowService::resolveTicket()` (aceptación) | `Invoice::where(...)->update(['voided' => true])` | `markVoidedInvoices()` | ⚠️ WIRED PERO SIN FILTRO DE AMBIENTE | El link funciona (marca facturas), pero omite `production`, cruzando entornos beta/producción con la misma serie/correlativo |

### Data-Flow Trace (Level 4)

| Artefacto | Variable | Fuente | ¿Datos reales? | Status |
|-----------|----------|--------|-----------------|--------|
| `voideds/partials/response.blade.php` | `$row->sunat_ticket` | `VoidedTable::builder()` → query sin `sunat_ticket` en el SELECT | No — siempre `null` en el partial, independientemente del valor en BD | ✗ HOLLOW_PROP |
| `voideds/partials/response.blade.php` | `$row->sunatResponse` | ídem | No — siempre `null`/vacío | ✗ HOLLOW_PROP |
| `voideds/partials/response.blade.php` | `$row->sunat_status` | `Column::make('Sunat','sunat_status')` (sí declarada) | Sí — se selecciona correctamente | ✓ FLOWING |
| `sunatResponse.blade.php` | `$document->sunat_status`, `$document->sunat_ticket`, `$document->sunatResponse` | `Voided::find($id)` vía `showResponse(Voided $voided)` (route-model-binding, todas las columnas) | Sí | ✓ FLOWING |

Confirmación en tiempo de ejecución (script PHP que instancia `App\Livewire\VoidedTable`, invoca `configure()`+`columns()` y refleja `getAdditionalSelects()`/`getColumn()` de cada columna declarada):

```
AdditionalSelects: ["id"]
Declared column fields: .fecGeneracion, .fecComunicacion, .correlativo, .pdf_path, .xml_path, .cdr_path, .sunat_status,
sunat_ticket declared as column: NO
sunatResponse declared as column: NO
```

### Behavioral Spot-Checks

| Comportamiento | Comando | Resultado | Status |
|-----------------|---------|-----------|--------|
| Migración aplicada | `php artisan migrate:status` | `2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table` ... `Ran` (batch 5) | ✓ PASS |
| Estilo de código en los 6 archivos PHP de la fase | `vendor/bin/pint --test` sobre los 6 archivos | `PASS 6 files` | ✓ PASS |
| `VoidedTable` no selecciona `sunat_ticket`/`sunatResponse` | script de reflexión sobre `getAdditionalSelects()`/`getColumns()` | `AdditionalSelects: ["id"]`; ninguna columna declarada `sunat_ticket`/`sunatResponse` | ✓ PASS (confirma el gap, no lo contradice) |
| Suite completa PHPUnit | no ejecutada | — | ? SKIP — la suite ya estaba en rojo antes de esta fase por un fallo no relacionado de `UserFactory`/Jetstream (17 errores + 2 fallos de scaffolding); no hay tests de `Voided`/SUNAT en el repo; ejecutarla sin `DB_CONNECTION=sqlite DB_DATABASE=:memory:` destruiría la base de datos local de desarrollo |

### Probe Execution

No aplica — el proyecto no define probes bajo `scripts/*/tests/probe-*.sh` ni el plan los declara. **SKIPPED (no hay probes definidos).**

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|--------------|--------|----------|
| TICKET-01 | 01-01, 01-02, 01-03 | Guarda el ticket antes de consultar el estado | ✓ SATISFIED | `sendAndResolve()` líneas 72-75; ambos llamadores lo consumen |
| TICKET-02 | 01-01 | Código 98 conserva el ticket y no rechaza | ✓ SATISFIED | `resolveTicket()` líneas 101-109 |
| TICKET-03 | 01-01 | `SoapFault`/fallo de transporte conserva el ticket y no rechaza | ✓ SATISFIED | `resolveTicket()` líneas 88-96 |
| TICKET-04 | 01-01 | CDR guardado, ZIP archivado, ticket limpio | ✓ SATISFIED (con nota menor) | Líneas 114-117, 134-142; `Storage::put()` no comprueba su retorno (WR-04, no bloqueante) |
| TICKET-05 | 01-01, 01-02, 01-03 | Tres estados explícitos en vez de `success` booleano | ✓ SATISFIED | Enum + cast + consumo consistente en form, tabla y ambas vistas |
| WEB-01 | 01-02, 01-03 | Aviso "En proceso" en vez de error | ✓ SATISFIED | `VoidedForm.php` líneas 64-72, 81-89; `VoidedTable.php` líneas 86-94, 126-134, 142-150 |
| WEB-05 | 01-01, 01-02 | Comprobantes marcados solo tras CDR aceptado | ⚠️ SATISFIED CON DEFECTO GRAVE ADYACENTE | El disparador es correcto (Gap 4 es sobre el ámbito del filtro, no sobre el momento de la escritura); ver Gap 4 |

No hay requisitos huérfanos: los 7 IDs de esta fase (TICKET-01..05, WEB-01, WEB-05) aparecen todos en `requirements:` de al menos un plan y en la tabla de trazabilidad de `REQUIREMENTS.md`, que los marca "Phase 1 / Complete" — marcado que este informe contradice para el aspecto de WEB-05 señalado arriba y no evalúa de forma independiente para TICKET-01..05/WEB-01 más allá de lo aquí verificado.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `app/Livewire/VoidedTable.php` | 20, 61 | Columnas necesarias para el gating de reenvío (`sunat_ticket`, `sunatResponse`) no seleccionadas | 🛑 Blocker | Ver Gap 2/3 |
| `app/Services/Sunat/VoidedTicketFlowService.php` | 157-174 | `classifyFailure()` puede degradar `Aceptada` a `Rechazada` | 🛑 Blocker | Ver Gap 1 |
| `app/Services/Sunat/VoidedTicketFlowService.php` | 224-234 | `markVoidedInvoices()` no filtra por `production` | 🛑 Blocker | Ver Gap 4 |
| `resources/views/voideds/partials/sunatResponse.blade.php` | 140, 209 | `{!! $document->sunatResponse['error']['message'] ?? null !!}` sin escapar; el mensaje puede arrastrar texto de usuario (`desMotivoBaja`, 250 caracteres libres) reflejado por SUNAT | ⚠️ Warning (seguridad) | XSS almacenado potencial en el modal de detalle (CR-06 del review); no evaluado dentro de los criterios de éxito del ROADMAP, pero es una regresión de seguridad introducida/duplicada por esta fase (la línea 140 es código nuevo) |
| `app/Services/Sunat/VoidedTicketFlowService.php` | 116 | `Storage::put()` no comprueba su valor de retorno al archivar el CDR | ⚠️ Warning | Un fallo silencioso de escritura (Spaces caído/cuota) dejaría `cdr_path` apuntando a un objeto inexistente |
| `app/Livewire/VoidedTable.php` | 69, 74, 79, 160 | `downloadPDF`, `downloadXML`, `sendXml`, `showResponse` reciben `Voided` por route-model-binding sin revalidar `company_id`/`branch_id` | ℹ️ Info | Preexistente a esta fase (confirmado: el mismo patrón está en `git show b527bb4:app/Livewire/VoidedTable.php`); no se atribuye a este trabajo, pero el nuevo `sendXml()` firma el XML con las credenciales de la empresa de sesión sobre un `Voided` potencialmente ajeno si se explota |
| `app/Livewire/VoidedTable.php` | 56-59 | La columna CDR reutiliza `vouchers.partials.cdr`, que ofrece `sendXml` para toda fila sin `cdr_path` (incluidas las pendientes) | ℹ️ Info | Botón confuso junto al de la columna Sunat, pero no explota daño porque `sendXml()` sí bloquea correctamente cuando el ticket está realmente cargado (WR-06 del review) |

Ningún marcador de deuda (`TBD`/`FIXME`/`XXX`/`TODO`/`HACK`/`PLACEHOLDER`) en los 8 archivos de la fase.

### Human Verification Required

Ver `human_verification` en el frontmatter. En resumen: la verificación manual contra SUNAT beta (D-14) que los tres planes dejaron explícitamente pendiente no se ejecutó en ninguna sesión de ejecución ni en esta verificación — ambos SUMMARY.md (Plan 02 y Plan 03) lo declaran como pendiente antes de dar la fase por cerrada. Esta verificación no la resuelve porque requiere una sesión de empresa activa con `production=false`.

### Gaps Summary

Cuatro defectos bloquean el objetivo de la fase, todos con corrección acotada y de bajo riesgo:

1. **`classifyFailure()` puede sobrescribir una aceptación real como rechazo** cuando `markVoidedInvoices()` falla después de que el ticket ya se limpió. Fix: comprobar `sunat_status === Aceptada` antes de la comprobación de ticket, y/o envolver la escritura de aceptación + marcado de facturas en una transacción.
2. **El listado nunca puede aplicar el gating de reenvío que declara implementar** porque `VoidedTable::configure()` no selecciona `sunat_ticket` ni `sunatResponse`. Fix de una línea: añadirlos a `setAdditionalSelects()`.
3. **Las filas históricas heredan el mismo defecto de selección**, así que el guardián doble de D-13 nunca opera con datos reales. Se resuelve con el mismo fix del punto 2.
4. **El marcado de comprobantes no filtra por `production`**, permitiendo que una anulación de beta marque como anulada una factura real de producción con la misma serie/correlativo. Fix: añadir `->where('production', $voided->production)`.

Los puntos 2 y 3 comparten la misma causa raíz y el mismo fix (una línea en `configure()`); no se cuentan como un solo hallazgo porque violan dos must-haves distintos y declarados por separado en `01-03-PLAN.md` (D-09 y D-13).

Estos cuatro puntos replican, de forma verificada de manera independiente, los hallazgos CR-01/CR-02 (fusionados aquí en los gaps 2-3), CR-04 (gap 4) y CR-05 (gap 1) de `01-REVIEW.md`. No hay commits posteriores a `e3768de` (que solo añade el propio informe de revisión) que los corrijan.

La capa de dominio del Plan 01 (persistencia del ticket, clasificación por excepción, decisión de aceptación por código de CDR) está sólida y no requiere cambios. Los cuatro gaps son localizados y no exigen replantear el diseño del servicio ni de los planes.

---

_Verified: 2026-08-04_
_Verifier: Claude (gsd-verifier)_
