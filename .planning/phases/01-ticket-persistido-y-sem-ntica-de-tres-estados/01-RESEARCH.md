# Phase 1: Ticket persistido y semántica de tres estados - Research

**Researched:** 2026-08-04
**Domain:** Persistencia de estado SUNAT (comunicación de baja, transporte SOAP `Greenter\See`) en un mini-ERP Laravel 11 / Livewire 3
**Confidence:** HIGH (todo lo crítico se verificó leyendo el código real de la app y de `vendor/greenter`, no solo la documentación)

## Summary

Esta fase corrige un flujo defectuoso ya diagnosticado línea por línea en
`.planning/todos/pending/2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md`.
Se verificó ese diagnóstico contra el código real: **todos los números de línea
citados en `01-CONTEXT.md` siguen siendo exactos** (no ha habido drift desde que
se escribió el contexto), así que se pueden tratar como referencias confiables
para planificar.

La investigación de código en `vendor/greenter` (solo lectura) confirmó el
mecanismo exacto por el que la anulación pierde información: `sendXmlFile()`
sobre un documento `Voided` resuelve a `SummarySender` (no `BillSender`), que
solo devuelve un **ticket** — SUNAT trata la comunicación de baja de forma
asíncrona, a diferencia de facturas/boletas (`sendBill`, síncrono, CDR
inmediato). Esa asincronía es la razón de dominio por la que existe la
consulta de estado y, con ella, la superficie de fallo que originó el bug.

Se descubrió un matiz **no mencionado en el diagnóstico original** que cambia
cómo debe escribirse el servicio nuevo: `ExtService::getStatus()` (la clase que
`See::getStatus()` invoca internamente) solo atrapa `SoapFault`. Si SUNAT
responde HTTP 200 pero con un cuerpo sin la propiedad `status` esperada,
`getStatusInternal()` lanza `Greenter\Services\InvalidServiceResponseException`
(extiende `LogicException`, no `SoapFault`) **sin que nada la atrape** — escapa
tal cual hasta el llamador. Si el servicio nuevo solo revisa
`$result->isSuccess()` después de llamar a `getStatus()` sin envolver la
llamada en su propio `try/catch(\Throwable)`, esta excepción reventaría el
flujo completo y, con la estructura de captura actual de `VoidedForm`, la
anulación terminaría escrita como **rechazo silencioso** en vez de pendiente —
exactamente el defecto que esta fase existe para eliminar. D-05 ya lo cubre en
espíritu ("todo fallo... sin lista blanca"), pero el planner necesita saber que
"fallo" incluye una excepción PHP, no solo un `StatusResult` con
`success = false`.

También se confirmó por qué el patrón de `Despatch` **se porta en forma, no en
literal**: `DespatchForm` usa `Greenter\Api` (REST GRE), mientras que
`VoidedForm`/`VoidedTable` usan `Greenter\See` (SOAP). La comparación de código
`98` (`$result->getError()?->getCode() === '98'`) sí transfiere literalmente —
se verificó en `ExtService::processResponse()` que también en SOAP el caso
pendiente deja `$result->getError()->getCode() === '98'` — pero el manejo del
`SoapFault` de transporte es exclusivo de SOAP y no tiene equivalente en el
código de `Despatch` que se está portando.

**Recomendación principal:** extraer un servicio nuevo (`app/Services/Sunat/`)
que reciba el `Voided` con ticket ya persistido, envuelva la llamada a
`$see->getStatus($ticket)` en su propio `try/catch(\Throwable)` (no solo
`catch(SoapFault)`), y clasifique el resultado en tres ramas explícitas —
aceptada (CDR con código `0`), pendiente (cualquier `\Throwable` o
`!$result->isSuccess()` una vez que el ticket ya existe, sin lista blanca de
códigos) y rechazada (solo cuando `sendXmlFile()` falla **antes** de devolver
ticket) — usando un enum PHP respaldado por string nuevo en `app/Enums/`
(directorio que hoy no existe).

## Phase Requirements

| ID | Descripción | Soporte de la investigación |
|----|-------------|------------------------------|
| TICKET-01 | Guardar el ticket antes de consultar estado | `DespatchForm.php:292-295` es el patrón exacto a portar; `Voided` necesita columna `sunat_ticket` igual que `despatches.sunat_ticket` (migración `2024_11_06_014056:36`) |
| TICKET-02 | Código `98` conserva ticket, no rechaza | Verificado en `ExtService::processResponse()` (`vendor/greenter/ws/.../ExtService.php:73-77`): código `98` dejaisSuccess()=false pero **no** lanza excepción — se detecta con un `if` normal |
| TICKET-03 | `SoapFault`/"Bad Request" conserva ticket, no rechaza | Verificado en `ExtService::getStatus()` (líneas 29-39): atrapa `SoapFault` y lo convierte en `StatusResult` con `error` poblado por `BaseSunat::getErrorFromFault()` |
| TICKET-04 | CDR obtenido → guardar respuesta, archivar ZIP, limpiar ticket | Patrón idéntico en `DespatchForm.php:319-325`; `UtilService::readCdr()` (líneas 179-192) ya devuelve la forma correcta |
| TICKET-05 | Tres estados explícitos en vez de `success` booleano | Enum PHP respaldado por string (D-02); no hay precedente en el repo — será el primer enum del proyecto |
| WEB-01 | Aviso "en proceso" al enviar | Patrón en `DespatchForm.php:303-310` (swal `info`); D-10 lo fija igual |
| WEB-05 | Marcar comprobantes dados de baja solo con CDR código `0` | Ya existe la consulta (`VoidedForm.php:87-97`); falta condicionarla al nuevo enum en vez de a `sunatResponse['success']` |

## Architectural Responsibility Map

| Capacidad | Tier primario | Tier secundario | Justificación |
|-----------|---------------|------------------|----------------|
| Persistencia del ticket y clasificación de estado | Backend (servicio de dominio, `app/Services/Sunat/`) | Base de datos (`voideds.sunat_ticket`, `voideds.sunat_status`) | Es lógica de negocio pura (interpretar la respuesta SUNAT); no pertenece a Livewire ni a la vista |
| Envío/consulta SOAP a SUNAT | Backend (servicio, vía `Greenter\See`) | — | Transporte externo; ya encapsulado en `UtilService::getSee()` |
| Formulario de creación de la anulación | Frontend Server (Livewire `VoidedForm`) | Backend (delega en el servicio nuevo) | Sigue el patrón Controller-shell + Livewire + Form object del proyecto |
| Listado y acción "reenviar"/mostrar detalle | Frontend Server (Livewire `VoidedTable`) | Backend (delega en el mismo servicio) | Debe dejar de duplicar lógica (D-11); solo orquesta UI |
| Vista de estado (badge/detalle) | Browser/Blade (`resources/views/voideds/partials/*.blade.php`) | — | Solo lectura del enum ya calculado, no debe re-derivar estado |
| Marcado de comprobantes como `voided` | Backend (servicio, `Invoice::where(...)->update(...)`) | Base de datos | Ya filtra por `branch_id`/`company_id`; debe migrar a condicionarse por el enum, no por `sunatResponse['success']` |

## Standard Stack

Esta fase **no introduce dependencias nuevas**. Usa exclusivamente lo ya
instalado y ya verificado en `composer.json`:

| Librería | Versión instalada | Uso en esta fase | Por qué es estándar aquí |
|----------|--------------------|-------------------|----------------------------|
| `greenter/lite` | `^5.1` [VERIFIED: composer.json] | `Greenter\See`, `StatusResult`, `SummaryResult` | Ya es el transporte SOAP de toda la emisión SUNAT del proyecto; no se reemplaza (restricción del proyecto) |
| `rappasoft/laravel-livewire-tables` | `^3.4` [VERIFIED: composer.json] | Columna `Sunat` en `VoidedTable`; la futura Fase 2 filtrará por `sunat_status` | Ya es el motor de listados de todo el ERP |
| `laravel/framework` | `^11.9` [VERIFIED: composer.json] | Enum cast nativo en Eloquent, migración con columnas nullable | PHP 8.2 + Laravel 11 soportan enums respaldados nativamente (ver Code Examples) |

**Instalación:** ninguna — no hay `composer require` en esta fase.

### Alternativas consideradas (y descartadas por CONTEXT.md, no por esta investigación)

| En vez de | Podría usarse | Por qué se descartó (ver `01-CONTEXT.md`) |
|-----------|----------------|----------------------------------------------|
| Enum PHP respaldado por string (D-02) | Constantes de clase / array de mapeo | D-02 ya fija el enum; PHP 8.2 lo soporta nativo y es lo que pide el ROADMAP para que SUM-01 (v2) no reescriba la semántica |
| Columna dedicada `sunat_status` (D-01) | Derivar el estado con un accessor desde `sunatResponse` (JSON) | D-01 ya lo descarta: Fase 2 necesita filtrar/ordenar con `rappasoft/laravel-livewire-tables`, que opera sobre columnas SQL reales |

## Package Legitimacy Audit

**N/A — esta fase no instala paquetes nuevos.** Todo el código nuevo (enum,
servicio, vista) se apoya en `greenter/lite`, `laravel/framework` y
`rappasoft/laravel-livewire-tables`, ya presentes en `composer.lock` y en uso
productivo en el resto del módulo SUNAT (facturas, notas, guías de remisión).
No aplica el protocolo de verificación de legitimidad de paquetes.

## Architecture Patterns

### Diagrama del flujo objetivo (envío web de una anulación)

```text
[VoidedForm::send()]                         [Livewire, capa de interacción]
        │
        ▼
Voided::create()  ──────────────────────────► voideds (fila creada, sin ticket ni estado)
        │
        ▼
UtilService::getXmlSigned()+getReportPdf()   [firma/plantillas repo-owned, sin tocar vendor/]
        │
        ▼
$see = $util->getSee()  ─────────────────────► construye cliente SOAP (SunatEndpoints::FE_BETA o FE_PRODUCCION)
        │
        ▼
$see->sendXmlFile($xml)  ─────────────────────► SummarySender::send() [SOAP sendSummary]
        │
   ┌────┴─────────────────────────────┐
   │ SoapFault (antes de ticket)       │ éxito (ticket devuelto)
   ▼                                    ▼
D-06: rechazada, sin ticket    TicketFlowService::persistirTicket()
   │                                    │  → voideds.sunat_ticket = $ticket (SAVE inmediato)
   │                                    ▼
   │                          try { $see->getStatus($ticket) }
   │                          catch (\Throwable $e)  ─────────► D-05: pendiente
   │                                    │                        (SoapFault "Bad Request"
   │                                    │                         o InvalidServiceResponseException,
   │                                    │                         cualquiera sin lista blanca)
   │                                    ▼
   │                          $result->isSuccess() === false
   │                          (código 98, sin excepción)  ────► D-05: pendiente
   │                                    │
   │                                    ▼
   │                          $result->getCdrZip() presente
   │                          → CDR archivado en Storage
   │                          → sunat_status = ACEPTADA
   │                          → sunat_ticket = null
   │                                    │
   ▼                                    ▼
sunat_status = RECHAZADA        Invoice::where(...)->update(['voided'=>true])
                                 (solo si cdrResponse.code === '0', WEB-05)
```

### Estructura de proyecto recomendada (solo archivos nuevos/tocados)

```text
app/
├── Enums/
│   └── VoidedStatus.php          # nuevo — primer enum del proyecto
├── Services/Sunat/
│   └── TicketFlowService.php     # nuevo (nombre sugerido en D-03) — consumido por VoidedForm y VoidedTable
├── Models/Voided.php             # $fillable + $casts: agregar sunat_ticket, sunat_status
├── Livewire/Forms/VoidedForm.php # send() delega en el servicio
└── Livewire/VoidedTable.php      # sendXml()/showResponse() delegan en el servicio

database/migrations/
└── 2026_xx_xx_add_sunat_ticket_and_status_to_voideds_table.php   # nullable, sin default, sin backfill (D-13)

resources/views/voideds/partials/
├── sunatResponse.blade.php       # existente, se adapta a leer el enum
└── response.blade.php            # nuevo (D-08) — reemplaza vouchers/partials/response.blade.php SOLO para VoidedTable
```

### Patrón 1: Persistir ticket antes de consultar estado (ya probado en `Despatch`)

**Qué:** Guardar el ticket devuelto por el envío inmediatamente, con un `save()`
propio, antes de hacer cualquier llamada de consulta que pueda fallar.
**Cuándo usarlo:** Siempre que el transporte SUNAT sea asíncrono (ticket +
`getStatus`), como en `Voided`, `Summary` y `Reversion` (los tres tipos que
`WsSenderResolver` resuelve a `SummarySender`).
**Ejemplo (fuente real, ya en producción):**
```php
// Source: app/Livewire/Forms/DespatchForm.php:292-296 (leído en esta sesión)
$ticket = $result->getTicket();
$despatch->sunat_ticket = $ticket;
$despatch->save();

// Consultar CDR con el ticket
$statusResult = $api->getStatus($ticket);
```
**Adaptación necesaria para `Voided` (SOAP, no REST):** el `try/catch` alrededor
de `getStatus()` debe ser `catch (\Throwable $e)`, no solo confiar en
`$statusResult->isSuccess()`, porque `Greenter\See::getStatus()` puede lanzar
`InvalidServiceResponseException` sin envolverla (ver Common Pitfalls #2).

### Patrón 2: Enum PHP respaldado por string, casteado en el modelo (Laravel 11 / PHP 8.2)

**Qué:** Representar los tres estados como casos de un enum `string`-backed,
declarado en `$casts` del modelo.
**Cuándo usarlo:** Cuando el valor tiene un conjunto cerrado y conocido de
posibilidades que se reutilizará en más de un lugar (aquí: formulario, tabla,
futura Fase 2, futuro resumen diario v2).
**Ejemplo (mecánica de Laravel 11, verificada leyendo el vendor de este proyecto):**
```php
// app/Enums/VoidedStatus.php — no existe directorio Enums/ aún, se crea en esta fase
enum VoidedStatus: string
{
    case Aceptada = 'aceptada';
    case Pendiente = 'pendiente';
    case Rechazada = 'rechazada';
}
```
```php
// app/Models/Voided.php
protected $casts = [
    // ...
    'sunat_status' => VoidedStatus::class,
];
```
**Comportamiento verificado con NULL (crítico para D-13):**
```php
// Source: vendor/laravel/framework/.../Concerns/HasAttributes.php:896-900 (leído en esta sesión)
protected function getEnumCastableAttributeValue($key, $value)
{
    if (is_null($value)) {
        return;   // devuelve null, NO intenta Enum::from(null) — no lanza ValueError
    }
    // ...
}
```
Esto confirma que una columna `sunat_status` nullable sin backfill (D-13) es
segura: las filas históricas con `NULL` se leen como `null` en PHP, no rompen
el cast ni lanzan excepción al acceder a `$voided->sunat_status`.

### Anti-patrones a evitar

- **Whitelisting de códigos de error SOAP:** D-05 lo prohíbe explícitamente.
  No escribir `if ($code === '98' || $code === 'HTTP' || $message === 'Bad Request')`.
  La regla es: si ya hay ticket y `getStatus()` no tiene éxito (por la razón
  que sea, incluida una excepción), es pendiente.
- **Copiar la condición `empty($value)` de `vouchers/partials/response.blade.php:9`
  en la vista nueva de `voideds`:** esa condición gatilla el reenvío de XML
  cuando `sunatResponse` está vacío, no cuando falta el ticket. Con D-04 (la
  baja pendiente escribe un `sunatResponse` explícito, nunca vacío), esa
  condición nunca dispararía el reenvío para una baja pendiente — pero tampoco
  es la señal correcta para decidir "reenviar" según D-09. La vista nueva debe
  condicionar el botón de reenvío en `is_null($voided->sunat_ticket)` (ausencia
  de ticket), no en la forma de `sunatResponse`.
- **Confiar en `$result->isSuccess()` como único criterio de "hay CDR":** se
  verificó en `ExtService::processResponse()` (líneas 79-96) que un CDR puede
  estar presente (`cdrZip` y `cdrResponse` poblados) y aun así
  `isSuccess()` terminar en `false`, si el código propio del CDR cae en el
  rango de "excepción" (100-1999) — ver Common Pitfalls #4. El servicio nuevo
  debe decidir "aceptada" mirando `cdrResponse->getCode() === '0'`
  (como ya hace `VoidedForm.php:87`), no `$result->isSuccess()`.

## Don't Hand-Roll

| Problema | No construir | Usar en su lugar | Por qué |
|----------|--------------|-------------------|---------|
| Persistencia de ticket + reintento | Un mecanismo nuevo de "estado async" desde cero | El patrón ya probado de `Despatch` (`sunat_ticket` nullable, guardado antes de consultar, limpiado al obtener CDR) | Ya está validado en producción para guías de remisión; reinventar duplica riesgo de introducir el mismo bug de otra forma |
| Representación de tres estados | Array de constantes de clase, strings mágicos, o una segunda columna booleana adicional | Enum PHP `string`-backed nativo de PHP 8.2/Laravel 11 | Es lo que pide D-02 y lo que consumirá el resumen diario (SUM-01, v2) sin reescritura |
| Clasificación de fallos SOAP | Una tabla de mapeo código→significado (whitelist) | La regla binaria de D-05: "si hay ticket y la consulta no tuvo éxito (código o excepción), es pendiente" | SUNAT puede emitir códigos/mensajes de fallo de transporte no documentados; una whitelist siempre queda incompleta, como probó el caso real en producción |
| Reintento automático / sondeo | Un comando `schedule` que reconsulte tickets pendientes | Nada en esta fase — POLL-01/POLL-02 son v2, deliberadamente excluidos | El ROADMAP acotó el milestone a recuperación manual vía UI/API; construir sondeo aquí amplía superficie sin resolver el bug reportado |

**Insight clave:** el problema de esta fase no es que falte una librería —
es que el código existente **ya tiene el patrón correcto en otro archivo**
(`DespatchForm.php`) y nunca se aplicó a `Voided`. El trabajo de research más
valioso fue confirmar qué parte de ese patrón transfiere literalmente (la
comparación de código `98`) y qué parte no (el manejo de `SoapFault`, exclusivo
del transporte SOAP que usa `Voided` y no usa `Despatch`).

## Common Pitfalls

### Pitfall 1: Los cuatro defectos encadenados del diagnóstico original
**Qué sale mal:** ticket vive solo en variable local (`VoidedForm.php:75`,
`VoidedTable.php:126`) → se pierde si `getStatus()` falla; `getResponse()`
(`UtilTrait.php:106-132`, no confundir con `UtilService::getResponse()` que sí
se usa) no distingue pendiente de rechazado; `getResponseTicket()`
(`UtilTrait.php:134-153`) obtiene el ticket, consulta el estado y **no
retorna nada** (`return` ausente, función cae al final); la única recuperación
de UI reenvía el XML (`VoidedTable.php:107`), lo que SUNAT responde con `2109`
("la comunicación de baja ya fue informada").
**Por qué pasa:** el flujo se escribió copiando el patrón síncrono de facturas
(`sendBill`, CDR inmediato) sin adaptarlo al patrón asíncrono real de
`sendSummary` (ticket + polling).
**Cómo evitarlo:** el servicio nuevo (D-03) debe ser el único punto que llama
`sendXmlFile()` y `getStatus()`; ambos llamadores (`VoidedForm`, `VoidedTable`)
delegan en él (D-11).
**Señales de alerta:** cualquier código nuevo que llame `$see->getStatus()`
fuera del servicio nuevo, o que vuelva a llamar `sendXmlFile()` sin comprobar
antes si `sunat_ticket` ya existe.

### Pitfall 2 (CRÍTICO, no estaba en el diagnóstico original): excepciones que no son `SoapFault` escapan de `getStatus()`
**Qué sale mal:** `ExtService::getStatus()` (`vendor/greenter/ws/src/Ws/Services/ExtService.php:29-39`)
solo atrapa `SoapFault`. Si SUNAT responde HTTP 200 con un cuerpo sin la
propiedad `status` (`getStatusInternal()`, líneas 47-59), se lanza
`Greenter\Services\InvalidServiceResponseException` (extiende `LogicException`,
confirmado leyendo `vendor/greenter/core/src/Core/Services/InvalidServiceResponseException.php`)
que **no es un `SoapFault`** y por tanto no la atrapa `ExtService::getStatus()`.
**Por qué pasa:** el autor de Greenter asumió que solo fallos de transporte
(HTTP/SOAP) necesitan manejo especial; una respuesta 200 mal formada es un caso
menos común pero real (SUNAT bajo carga, mantenimiento parcial, etc.).
**Cómo evitarlo:** el servicio nuevo debe envolver la llamada a `getStatus()`
en su propio `try { ... } catch (\Throwable $e) { /* clasificar como pendiente */ }`,
no delegar el manejo de errores únicamente a revisar `$result->isSuccess()`
después de una llamada sin protección. Esto es consistente con la letra de D-05
("todo fallo... sin lista blanca de códigos ni de mensajes") pero el planner
debe saberlo explícitamente porque no es obvio desde el código de la app — solo
aparece leyendo `vendor/greenter`.
**Señales de alerta:** un test manual contra beta que devuelva un error 500 de
Laravel en vez del swal "en proceso" indica que esta excepción no está siendo
atrapada.

### Pitfall 3: el envío inicial (`sendXmlFile`) también puede fallar con el mismo `SoapFault`/"Bad Request" — no es lo mismo que un fallo de consulta
**Qué sale mal:** `SummarySender::send()` (`vendor/greenter/ws/src/Ws/Services/SummarySender.php:34-46`)
también atrapa `SoapFault` y puede producir un error con la misma forma
("Bad Request") que el fallo de `getStatus()`. Si el servicio nuevo distingue
pendiente vs. rechazada mirando solo el mensaje/código de error, **no podrá
diferenciar** un `sendXmlFile()` fallido (D-06: rechazada, sin ticket) de un
`getStatus()` fallido (D-05: pendiente, con ticket).
**Por qué pasa:** ambas llamadas comparten el mismo transporte SOAP y el mismo
`BaseSunat::getErrorFromFault()`.
**Cómo evitarlo:** la clasificación correcta no depende del contenido del
error — depende de **si ya existe un ticket persistido en ese punto del
flujo**. Es la señal fiable, no el mensaje.
**Señales de alerta:** código que hace `if ($error->getMessage() === 'Bad Request') { pendiente }` sin verificar antes si el ticket ya se guardó.

### Pitfall 4: un CDR puede estar presente y aun así `isSuccess()` devolver `false`
**Qué sale mal:** en `ExtService::processResponse()` (líneas 65-103), cuando el
`statusCode` es `0` o `99` (procesado), se marca `success = true` y se rellena
`cdrZip`/`cdrResponse` — pero **después**, si el código propio del CDR (no el
`statusCode` del ticket) cae en el rango 100-1999 ("excepción"), se llama
`loadErrorByCode()` (`BaseSunat.php:183-194`), que **sobrescribe
`success = false`** aunque el CDR ya esté descargado.
**Por qué pasa:** SUNAT distingue "excepciones de proceso" (100-1999) de
rechazos reales (2000-3999) y aceptación (`0` o `>=4000`, según
`CdrResponse::isAccepted()`), y Greenter decidió tratar las excepciones como
fallo de la operación completa, no como un CDR válido con una anotación.
**Cómo evitarlo:** el criterio de "aceptada" para WEB-05 y D-07 debe ser
`$result->getCdrResponse()?->getCode() === '0'` (el patrón que ya usa
`VoidedForm.php:87`), no `$result->isSuccess()`. Si `getCdrZip()` viene
poblado, debe archivarse igual, sin importar el valor de `isSuccess()`.
**Señales de alerta:** un CDR guardado en disco (`cdr_path` no vacío) sobre
una fila marcada como pendiente/rechazada — sería la evidencia exacta de este
caso si se presenta contra beta.

### Pitfall 5: `getErrorFromFault()` no necesariamente deja el código "vacío" (matiz sobre el propio D-05)
**Qué sale mal (lectura literal del código):** `BaseSunat::getErrorByCode()`
(líneas 111-127) construye `$error = new Error($code)` con el `faultcode`
**crudo original** (p. ej. lo que PHP's `SoapClient` ponga ahí, típicamente el
literal `"HTTP"` para fallos de transporte a nivel HTTP, o un código SOAP tipo
`"soap:Client"`), y **solo lo sobrescribe** con `setCode()` si logra extraer
dígitos de `faultcode` o de `faultstring`. Si ninguno trae dígitos (como en
"Bad Request"), `setCode()` nunca se llama, así que `$error->getCode()`
devuelve el `faultcode` crudo, **no una cadena vacía**.
**Por qué importa:** D-05 dice "código vacío" como descripción del síntoma
observado; el código real puede no ser una cadena `''` sino algo como
`"HTTP"`. Esto no cambia la decisión (D-05 ya prohíbe una lista blanca), pero
el planner no debe escribir una verificación como
`if ($error->getCode() === '')` esperando que siempre sea así — debe seguir la
regla de "cualquier fallo cuenta", sin mirar el valor exacto del código.
**Cómo verificar en la práctica:** D-14 (prueba manual contra beta) es el único
modo de confirmar el valor exacto de `faultcode`/`faultstring` para el caso
real; no se puede determinar con certeza sin disparar la llamada SOAP real.

### Pitfall 6: `VoidedObserver::creating()` es obligatorio para crear una fila válida
**Qué sale mal:** `voideds.company` (JSON) y `voideds.correlativo` son
`NOT NULL` en la migración, y ninguno está en `Voided::$fillable`
(`app/Models/Voided.php:14-29`) — se rellenan exclusivamente en
`VoidedObserver::creating()` (`app/Observers/VoidedObserver.php:10-29`).
**Por qué importa:** cualquier ruta nueva de creación de `Voided` (o de
"regenerar" una fila) debe pasar por `Voided::create()`/`->save()` normal de
Eloquent para que el observer se dispare; no se puede usar un `insert()` masivo
ni construir la fila manualmente sin persistirla por Eloquent.
**Cómo evitarlo:** el servicio nuevo no crea la fila (`VoidedForm::send()` ya
la crea antes de invocar el servicio); solo debe actualizarla, lo cual no
dispara `creating()` — no hay riesgo aquí mientras el servicio reciba un
`Voided` ya existente y solo llame a `save()`/`update()`.

### Pitfall 7: multi-tenant — ninguna consulta nueva puede saltarse `company`/`branch`
**Qué sale mal:** `VoidedTable::builder()` (líneas 176-182) y el `Invoice::where(...)`
de `VoidedForm.php:90-95` filtran explícitamente por
`session('branch')->id`, `session('company')->id` y `production`. El servicio
nuevo va a repetir el bloque de marcado de comprobantes (D-11) — si se
extrae mal, es fácil olvidar alguno de esos tres filtros.
**Cómo evitarlo:** el servicio nuevo debe recibir explícitamente `branch_id`,
`company_id` y `production` (ya están en `$voided->branch_id`,
`$voided->company_id`, `$voided->production` como columnas del propio modelo,
así que no hace falta leer la sesión dentro del servicio — puede leerlos del
`Voided` recibido, que es más seguro que depender de `session()` dentro de una
capa de servicio).

## Code Examples

### Persistir ticket + clasificar con `try/catch` amplio (adaptación propuesta, SOAP)

```php
// Patrón de referencia real: app/Livewire/Forms/DespatchForm.php:292-325
// Adaptado para SOAP (Greenter\See), donde getStatus() puede lanzar
// excepciones que NO son SoapFault (ver Pitfall 2).

$ticket = $result->getTicket();
$voided->sunat_ticket = $ticket;
$voided->save(); // TICKET-01: persistido antes de cualquier consulta

try {
    $statusResult = $see->getStatus($ticket);
} catch (\Throwable $e) {
    // D-05: cualquier fallo de la consulta, con ticket ya persistido, es pendiente.
    // Cubre SoapFault (transporte) e InvalidServiceResponseException (respuesta
    // mal formada) sin distinguir por código ni mensaje.
    return $this->clasificarPendiente($voided, $e->getMessage());
}

if (!$statusResult->isSuccess()) {
    // Código 98 (u otro) sin excepción: mismo tratamiento, D-05.
    return $this->clasificarPendiente($voided, $statusResult->getError()?->getMessage());
}

$cdr = $statusResult->getCdrResponse();
if ($cdr && (int) $cdr->getCode() === 0) {
    // D-07: aceptada, archivar CDR, limpiar ticket.
    Storage::put($voided->cdr_path, $statusResult->getCdrZip());
    $voided->sunat_status = VoidedStatus::Aceptada;
    $voided->sunat_ticket = null;
    $voided->save();

    return true; // habilita el marcado de comprobantes (WEB-05)
}

// Pitfall 4: llegó aquí con isSuccess()=true pero código de CDR fuera de rango
// de aceptación (rechazo real 2000-3999, o excepción que igual archivó CDR).
// Tratar según el código del propio CDR, no según isSuccess().
```

### Migración: columnas nuevas nullable, sin backfill (D-13)

```php
// Source: patrón estándar Laravel 11 Schema Blueprint, aplicado a las
// decisiones D-01/D-13. No hay default ni backfill — las filas existentes
// quedan en NULL (estado desconocido), tal como fija D-13.
Schema::table('voideds', function (Blueprint $table) {
    $table->string('sunat_ticket')->nullable()->after('hash');
    $table->string('sunat_status')->nullable()->after('sunat_ticket');
});
```

### Enum en `$fillable`/`$casts` del modelo

```php
// app/Models/Voided.php — agregar a lo ya existente
protected $fillable = [
    // ... existentes ...
    'sunat_ticket',
    'sunat_status',
];

protected $casts = [
    // ... existentes ...
    'sunat_status' => VoidedStatus::class,
];
```

## State of the Art

| Antes | Ahora (esta fase) | Cuándo cambió | Impacto |
|-------|---------------------|-----------------|---------|
| `sunatResponse['success']` booleano como única señal de estado | Columna `sunat_status` con enum de tres valores explícitos | Esta fase (2026-08-04) | El listado, el detalle y el futuro resumen diario (SUM-01, v2) dejan de re-derivar "¿fue exitoso?" desde un JSON; leen un valor tipado |
| Ticket vive solo en variable local de `send()`/`sendXml()` | Ticket persistido en `voideds.sunat_ticket`, igual que `despatches.sunat_ticket` desde 2024-11-06 | Esta fase | Habilita la Fase 2 (reconsulta) sin reenviar XML |
| Recuperación de UI = reenviar XML (`sendXmlFile` de nuevo) | Reenvío condicionado a ausencia de ticket (D-09); la reconsulta real es Fase 2 | Esta fase (corta el daño) / Fase 2 (agrega la reconsulta) | Elimina el error SUNAT `2109` ("la comunicación de baja ya fue informada") |

**Nada de esto es una migración de librería** — es una migración de patrón
dentro del mismo código base, del patrón "sincrónico" (facturas) mal aplicado
a un flujo asincrónico (anulación), hacia el patrón asincrónico correcto que
`Despatch` ya demostró.

## Assumptions Log

| # | Afirmación | Sección | Riesgo si es incorrecta |
|---|------------|---------|---------------------------|
| A1 | El `faultcode` real que SUNAT/PHP `SoapClient` produce para el caso "Bad Request" observado en producción no se puede determinar con certeza sin disparar la llamada SOAP real contra beta; se documentó el mecanismo de `getErrorByCode()` pero no el valor exacto del `faultcode`. | Pitfall 5 | Bajo — D-05 ya evita depender del valor exacto del código, así que la clasificación funciona igual sin conocerlo. Solo afecta a mensajes de log/depuración más precisos si se quisiera diagnosticar en el futuro. |
| A2 | El nombre sugerido `TicketFlowService` y el enum `VoidedStatus` (con casos `Aceptada`/`Pendiente`/`Rechazada`) son sugerencias tomadas literalmente de `01-CONTEXT.md` (zona de "Claude's Discretion"), no una verificación de que sea el nombre óptimo. | Architecture Patterns, Code Examples | Ninguno funcional — es solo nomenclatura; el planner puede cambiarlo libremente sin impacto técnico. |
| A3 | Se asume que ningún otro código del proyecto (fuera de los archivos listados en `01-CONTEXT.md`) llama a `UtilTrait::getResponseTicket()` o depende de que quede roto; no se hizo una búsqueda exhaustiva de referencias a ese método fuera del árbol de anulación. | Pitfall 1 | Bajo — es una función que hoy no retorna nada útil; si algo más la llama, ya está recibiendo `null` hoy, así que no hay regresión posible al no tocarla. |

## Open Questions

1. **¿Qué mensaje/código exacto trae el `SoapFault` en el caso "Bad Request" real?**
   - Qué se sabe: el mecanismo de `BaseSunat::getErrorFromFault()` está
     completamente mapeado (Pitfall 5); el síntoma ("Bad Request" como
     mensaje) está confirmado por el diagnóstico de producción en
     `01-CONTEXT.md` y en el todo original.
   - Qué falta: el valor literal de `$fault->faultcode` (p. ej. `"HTTP"` vs.
     algo tipo `"soap:Client"`) — no se puede obtener sin ejecutar la llamada
     SOAP real.
   - Recomendación: no bloquea el plan (D-05 no depende de ese valor); D-14 ya
     cubre confirmarlo empíricamente al verificar contra beta.

2. **¿El servicio nuevo debe registrar (`Log::warning`) cada vez que clasifica
   como pendiente, para dar visibilidad operativa antes de que exista el
   sondeo automático (POLL-01/02, v2)?**
   - Qué se sabe: el proyecto usa `Log::warning()`/`Log::error()` de forma
     consistente en otros servicios (`EirPhotoService`, según `CLAUDE.md`).
   - Qué falta: no fue fijado como decisión en `01-CONTEXT.md`; queda a
     criterio del planner si añadirlo mejora la operación sin ampliar alcance.
   - Recomendación: agregar un `Log::warning()` con el ticket y el motivo
     (código `98` vs. excepción de transporte) es barato y no contradice
     ninguna decisión — se sugiere incluirlo como detalle de implementación,
     no como requisito nuevo.

## Environment Availability

| Dependencia | Requerida por | Disponible | Versión | Fallback |
|-------------|----------------|------------|---------|----------|
| Extensión PHP SOAP (`ext-soap`) | `Greenter\Ws\Services\SoapClient extends \SoapClient` | ✓ (ya en uso productivo por facturas/boletas vía `InvoiceForm`) | — | — |
| Conectividad SOAP a `SunatEndpoints::FE_BETA` | Verificación manual D-14 | Se asume ✓ — es el mismo endpoint que ya usa el módulo de facturación en beta | — | Ninguno; si no hay red hacia SUNAT, D-14 no puede completarse (bloqueante para el gate de fase, no para el código) |
| Disco de almacenamiento (`Storage::put`, disco por defecto de `filesystems.php`) | Archivar el ZIP del CDR (TICKET-04) | ✓ — mismo mecanismo ya usado por `xml_path`/`pdf_path`/`cdr_path` de `Voided` y `Despatch` | `local` en dev, `do` (DigitalOcean Spaces) en producción [VERIFIED: config/filesystems.php:16,48] | — |
| `wkhtmltopdf` binario | Generación de PDF (`getReportPdf`), sin cambios en esta fase | Ya configurado vía `WKHTMLTOPDF_BINARIES` (`CLAUDE.md`); no se toca en esta fase | — | — |

**Sin dependencias faltantes.** Todo lo que esta fase necesita ya está
operativo en producción para otros documentos SUNAT (facturas, guías de
remisión).

## Security Domain

### Categorías ASVS aplicables

| Categoría ASVS | Aplica | Control estándar |
|-----------------|--------|--------------------|
| V2 Autenticación | No | Ruta ya protegida por `auth` + `CheckCompanySelected`; sin cambios |
| V3 Gestión de sesión | No | Sin cambios; el estado de la anulación no viaja por sesión |
| V4 Control de acceso | Sí (mantener lo existente) | El servicio nuevo debe seguir filtrando por `branch_id`/`company_id` del propio `Voided` recibido (ver Pitfall 7); no introduce mecanismo de acceso nuevo |
| V5 Validación de entrada | No (sin superficie nueva) | El ticket y el estado provienen de la respuesta de SUNAT, no de entrada de usuario; `desMotivoBaja` ya está validado y no se toca |
| V6 Criptografía | No | Credenciales SOL y certificado no se tocan en esta fase |
| V7 Manejo de errores y logging | Sí | El `try/catch(\Throwable)` alrededor de `getStatus()` (Pitfall 2) es, en sí mismo, un control de manejo de errores: ninguna excepción debe escapar sin clasificar el estado ni sin quedar registrada de forma útil |

### Patrones de amenaza conocidos para este stack

| Patrón | STRIDE | Mitigación estándar |
|--------|--------|-----------------------|
| Marcar un comprobante como `voided` sin que SUNAT realmente lo haya aceptado | Tampering | Condicionar `Invoice::update(['voided' => true])` estrictamente a `cdrResponse->getCode() === '0'` (dato que viene firmado/estructurado desde la respuesta de SUNAT, no de un campo editable por el usuario) — ya es el patrón existente en `VoidedForm.php:87`, esta fase solo lo ata al enum en vez de a `sunatResponse['success']` |
| Pérdida de rastro de auditoría sobre qué respondió SUNAT realmente | Repudiation | Mantener `sunatResponse` como JSON crudo de la respuesta (ya existente); el enum `sunat_status` es una interpretación adicional, no un reemplazo del JSON original |
| Excepción no atrapada expone mensaje interno en el `swal` mostrado al usuario | Information Disclosure (menor) | Los mensajes que hoy se muestran (`$e->getMessage()`) son en la práctica texto proveniente de SUNAT (`faultstring`) o de Greenter, no credenciales ni rutas del servidor — riesgo bajo, sin cambio necesario en esta fase |

**Nota sobre una debilidad preexistente, fuera de alcance:** `SoapClient`
(`vendor/greenter/ws/src/Ws/Services/SoapClient.php:34-45`) construye el
contexto SSL con `verify_peer => false`, `verify_peer_name => false`,
`allow_self_signed => true`. Es una configuración ya existente en producción
para todo el módulo SUNAT (no exclusiva de `Voided`), vive en `vendor/` (no se
puede tocar sin faltar a la restricción del proyecto), y tocarla arriesga
romper la conectividad contra el endpoint real de SUNAT. **No corresponde a
esta fase**; se documenta aquí solo para que quede constancia de que no es un
hallazgo nuevo introducido por este trabajo.

## Sources

### Primario (confianza HIGH — código leído directamente en esta sesión)

- `app/Livewire/Forms/VoidedForm.php` (líneas 1-137) — flujo defectuoso completo
- `app/Livewire/VoidedTable.php` (líneas 1-183) — duplicado del flujo, `builder()` multi-tenant
- `app/Models/Voided.php`, `app/Observers/VoidedObserver.php` — forma actual de persistencia
- `database/migrations/2024_12_16_111346_create_voideds_table.php` — esquema actual
- `app/Services/Sunat/UtilService.php` (líneas 1-318) — `getSee()`, `getResponse()`, `getErrorResponse()`, `readCdr()`, `getResponseVoided()`
- `app/Traits/Sunat/UtilTrait.php` (líneas 1-257) — `getResponseTicket()` roto confirmado
- `app/Livewire/Forms/DespatchForm.php` (líneas 245-363) — patrón de referencia completo
- `app/Livewire/DespatchTable.php` (líneas 1-253) — reconsulta con ticket (referencia Fase 2)
- `database/migrations/2024_11_06_014056_create_despatches_table.php`, `app/Models/Despatch.php` — forma de `sunat_ticket`
- `resources/views/vouchers/partials/response.blade.php`, `resources/views/voideds/partials/sunatResponse.blade.php` — vistas actuales
- `app/Services/Sunat/VoidedService.php`, `app/Http/Controllers/Api/VoidedController.php` — confirmación del bug de Fase 3 (fuera de alcance aquí)
- `vendor/greenter/lite/src/Greenter/See.php` — `sendXmlFile()`, `getStatus()`
- `vendor/greenter/lite/src/Greenter/Factory/WsSenderResolver.php` — confirma `Voided::class` → `SummarySender`
- `vendor/greenter/ws/src/Ws/Services/{ExtService,SummarySender,BillSender,BaseSunat,SoapClient}.php` — mecánica exacta de éxito/fallo/excepción
- `vendor/greenter/core/src/Core/Model/Response/{BaseResult,StatusResult,SummaryResult,BillResult,Error,CdrResponse}.php` — forma exacta de los objetos de respuesta
- `vendor/greenter/core/src/Core/Services/InvalidServiceResponseException.php` — confirmación de que extiende `LogicException`, no `SoapFault`
- `vendor/laravel/framework/.../Concerns/HasAttributes.php` (líneas 890-920) — comportamiento de cast de enum con `NULL`
- `composer.json`, `config/filesystems.php`, `phpunit.xml`, `.planning/config.json` — versiones, disco por defecto, confirmación de que `nyquist_validation: false` y no hay tests existentes del dominio SUNAT

### Secundario (confianza MEDIUM)

- `.planning/todos/pending/2026-08-04-anulacion-no-recupera-el-cdr-de-la-comunicacion-de-baja.md` — diagnóstico original, verificado línea por línea contra el código actual (sin drift)
- `.planning/phases/01-.../01-CONTEXT.md`, `.planning/ROADMAP.md`, `.planning/REQUIREMENTS.md`, `.planning/PROJECT.md`, `.planning/STATE.md` — decisiones y alcance ya fijados por el usuario

### Terciario (confianza LOW)

- Ninguna. Esta investigación no dependió de búsquedas web ni de documentación de terceros no verificada contra el código real; toda la mecánica SOAP crítica se confirmó leyendo el código fuente instalado de `vendor/greenter`.

## Metadata

**Desglose de confianza:**
- Stack estándar: HIGH — no hay dependencias nuevas, todo verificado contra `composer.json`
- Arquitectura: HIGH — el patrón a portar y sus límites se confirmaron leyendo tanto el código de la app como el de `vendor/greenter`
- Pitfalls: HIGH — los cuatro defectos originales se re-verificaron línea por línea; el Pitfall 2 (excepciones no-`SoapFault`) y el Pitfall 4 (CDR presente con `isSuccess()=false`) son hallazgos nuevos de esta sesión, confirmados leyendo el código fuente exacto de Greenter, no inferidos

**Fecha de investigación:** 2026-08-04
**Válido hasta:** sin fecha de caducidad estrecha — no depende de versiones de librerías que cambien seguido; revalidar solo si `greenter/lite` se actualiza de versión mayor o si `01-CONTEXT.md` cambia de decisiones.
