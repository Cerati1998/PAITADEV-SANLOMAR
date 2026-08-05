---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
reviewed: 2026-08-04T00:00:00Z
depth: standard
files_reviewed: 8
files_reviewed_list:
  - app/Enums/VoidedStatus.php
  - app/Livewire/Forms/VoidedForm.php
  - app/Livewire/VoidedTable.php
  - app/Models/Voided.php
  - app/Services/Sunat/VoidedTicketFlowService.php
  - database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php
  - resources/views/voideds/partials/response.blade.php
  - resources/views/voideds/partials/sunatResponse.blade.php
findings:
  critical: 6
  warning: 12
  info: 4
  total: 22
status: issues_found
---

# Phase 01: Code Review Report

**Reviewed:** 2026-08-04
**Depth:** standard
**Files Reviewed:** 8
**Status:** issues_found

## Summary

La fase extrae correctamente el flujo del ticket a `VoidedTicketFlowService` y el
punto exacto del defecto original (TICKET-01: `save()` propio del ticket antes de
cualquier consulta) está bien resuelto. `vendor/bin/pint --test` pasa en los seis
archivos PHP.

Sin embargo, la gating de la tabla —la mitad visible del entregable— **no funciona**:
el partial nuevo decide si ofrecer el reenvío leyendo `sunat_ticket` y `sunatResponse`,
y ninguna de esas dos columnas se selecciona ya en la consulta del listado. Ambas
llegan siempre `null` al blade, así que toda fila rechazada y toda fila histórica
muestran el botón de reenvío que D-09 vino a eliminar. Combinado con que la guarda de
`sendXml()` solo mira `sunat_ticket` —columna sin backfill, `null` en el 100 % de las
filas históricas— existe un camino de un solo clic para reenviar a SUNAT una
comunicación de baja que ya fue aceptada, recibir el `2109` y sobrescribir la fila
como RECHAZADA. Eso es exactamente la divergencia entre lo que el sistema muestra y
lo que SUNAT tiene que la fase existe para cerrar.

A eso se suman: limpieza del ticket en la aceptación que convierte cualquier
excepción posterior en un rechazo escrito sobre una aceptación real (CR-05), IDOR
multi-tenant en las cuatro acciones Livewire del listado (CR-03), marcado de facturas
sin filtrar `production` (CR-04) y salida sin escapar del mensaje de error de SUNAT
dentro del `html` de SweetAlert (CR-06).

## Critical Issues

### CR-01: El listado no selecciona `sunat_ticket` ni `sunatResponse`: la gating de D-09 nunca se aplica

**File:** `app/Livewire/VoidedTable.php:17-22,61-64` + `resources/views/voideds/partials/response.blade.php:18,41`

**Issue:** `rappasoft/laravel-livewire-tables` construye el `SELECT` únicamente con
`setAdditionalSelects()` más las columnas declaradas
(`vendor/rappasoft/laravel-livewire-tables/src/Traits/WithData.php:218-236`). Aquí eso
es `id, fecGeneracion, fecComunicacion, correlativo, pdf_path, xml_path, cdr_path,
sunat_status`. **`sunat_ticket` y `sunatResponse` no están.**

El partial nuevo decide con esas dos:

- línea 18 — `@if (is_null($row->sunat_ticket))` → siempre `true` (Eloquent devuelve
  `null` para un atributo no cargado);
- línea 41 — `@if (is_null($row->sunat_ticket) && empty($row->sunatResponse))` → siempre
  `true` (el cast `array` sobre un atributo ausente devuelve `null`).

Consecuencias reales:

1. **Toda fila RECHAZADA muestra el botón de reenvío**, incluidas las que SUNAT ya
   recibió (rechazo por CDR con código ≠ 0, que conserva el ticket a propósito). La
   rama "SUNAT ya recibió el XML: reenviarlo devolvería 2109" del blade es código
   muerto.
2. **Toda fila histórica (`sunat_status` NULL, sin backfill por D-13) muestra el botón
   de reenvío**, incluidas las aceptadas con CDR archivado. Ese es el vector de daño
   de CR-02.

Esto es una regresión introducida por el cambio de columna: el partial anterior
(`vouchers.partials.response`) recibía `$value` desde `Column::make('Sunat',
'sunatResponse')`, que sí se seleccionaba. Al cambiar la columna a `sunat_status` y
pasar solo `$row`, los dos campos que la nueva lógica necesita dejaron de viajar.

**Fix:**
```php
public function configure(): void
{
    $this->setPrimaryKey('id');
    // sunat_ticket y sunatResponse los lee voideds.partials.response para decidir
    // si ofrece el reenvío (D-09); sin declararlos aquí llegan siempre null.
    $this->setAdditionalSelects(['id', 'sunat_ticket', 'sunatResponse']);
    $this->setDefaultSort('id', 'desc');
}
```

---

### CR-02: La guarda de reenvío no cubre las aceptadas ni las filas históricas

**File:** `app/Livewire/VoidedTable.php:79-94`

**Issue:** La guarda es `if (! is_null($voided->sunat_ticket))`. Pero:

- `resolveTicket()` **pone el ticket a `null` al aceptar**
  (`VoidedTicketFlowService.php:137`), así que una baja ACEPTADA pasa la guarda;
- las filas históricas nunca tuvieron ticket (columna nueva, sin backfill por D-13),
  así que **todas** pasan la guarda, estén aceptadas o no.

Encadenado con CR-01 (que muestra el botón de reenvío en esas mismas filas), un
operador hace un clic sobre una anulación histórica ya aceptada →
`sendAndResolve()` reenvía el XML → SUNAT devuelve `2109` ("la comunicación de baja
ya fue informada") → `markRejected()` escribe `sunat_status = Rechazada` sobre una
baja que SUNAT tiene como aceptada. Aunque se corrija CR-01, la acción sigue siendo
invocable directamente desde el cliente, que es la razón que el propio comentario
D-09 da para poner la guarda en el servidor.

La guarda debe apoyarse en toda la evidencia de que SUNAT ya recibió el XML, no solo
en el ticket.

**Fix:**
```php
$yaRecibidaPorSunat = ! is_null($voided->sunat_ticket)
    || $voided->sunat_status === VoidedStatus::Aceptada
    || ! is_null($voided->cdr_path)
    || ! empty($voided->sunatResponse);

if ($yaRecibidaPorSunat) {
    $this->dispatch('swal', [
        'icon' => 'info',
        'title' => 'No se puede reenviar',
        'text' => 'SUNAT ya recibió esta comunicación de baja.',
    ]);

    return;
}
```
Además, replicar la guarda dentro de `VoidedTicketFlowService::sendAndResolve()`, que
el propio docblock declara "único punto del código base que envía una comunicación de
baja": ahí es donde pertenece la invariante.

---

### CR-03: IDOR multi-tenant en las cuatro acciones del listado

**File:** `app/Livewire/VoidedTable.php:69,74,79,160`

**Issue:** `downloadPDF`, `downloadXML`, `sendXml` y `showResponse` reciben el `Voided`
por route-model binding sin ninguna comprobación de pertenencia. `builder()` filtra
por `branch_id`/`company_id`/`production`, pero eso solo acota lo que se **pinta**: los
métodos Livewire son invocables desde el cliente con cualquier id (el propio comentario
de la línea 81-85 lo reconoce). Cualquier usuario autenticado con permiso `voideds`
puede:

- `downloadXML(<id ajeno>)` / `downloadPDF(<id ajeno>)` → descargar el comprobante de
  otra empresa;
- `showResponse(<id ajeno>)` → leer el CDR y el ticket de otra empresa;
- `sendXml(<id ajeno>)` → **firmar el XML de la empresa B con el certificado de la
  empresa A** (`new UtilService(session('company'))`, línea 99) y escribir
  `sunat_status`/`sunatResponse` sobre la fila de la empresa B.

Esto viola la regla dura del proyecto ("todo acceso a datos se ancla en
`session('company')` y `session('branch')`; cualquier consulta nueva debe filtrar por
ambos") y contamina el estado SUNAT de otro inquilino.

**Fix:** añadir una resolución acotada y usarla en las cuatro acciones:
```php
private function resolveVoided(int $id): Voided
{
    return Voided::where('branch_id', session('branch')->id)
        ->where('company_id', session('company')->id)
        ->where('production', session('company')->production)
        ->findOrFail($id);
}

public function sendXml(int $id)
{
    $voided = $this->resolveVoided($id);
    // ...
}
```
(o, si se conserva el binding, `abort_unless($voided->company_id === session('company')->id
&& $voided->branch_id === session('branch')->id, 403)` como primera línea de cada método).

---

### CR-04: `markVoidedInvoices()` no filtra por `production`: una baja de beta anula la factura de producción

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:224-234`

**Issue:** El `update` filtra por `tipoDoc`, `serie`, `correlativo`, `branch_id` y
`company_id`, pero no por `production`. `invoices` tiene columna `production`
(`app/Models/Invoice.php:84,105`) y beta y producción conviven en la misma tabla —
`VoidedTable::builder()` y `VoucherTable::builder()` filtran por ella justamente por
eso. Con la misma serie/correlativo en ambos entornos (lo habitual, porque las series
se reinician al pasar a producción), **una anulación de prueba aceptada en beta marca
`voided = true` sobre la factura real de producción**. Esa factura aparece entonces
como anulada en el ERP mientras SUNAT la sigue teniendo como válida, y
`VoucherTable::voidedInvoice()` (línea 385) bloquea para siempre su anulación
legítima.

Es el caso exacto de "el estado que muestra el sistema no coincide con el que SUNAT
tiene" del Core Value. El defecto viene del código anterior, pero se ha trasladado al
servicio nuevo cuyo docblock afirma que el filtrado multi-tenant es correcto.

**Fix:**
```php
Invoice::where('tipoDoc', $item['tipoDoc'])
    ->where('serie', $item['serie'])
    ->where('correlativo', $item['correlativo'])
    ->where('branch_id', $voided->branch_id)
    ->where('company_id', $voided->company_id)
    ->where('production', $voided->production)   // el entorno también es ámbito
    ->update(['voided' => true]);
```

---

### CR-05: Limpiar el ticket al aceptar hace que cualquier excepción posterior reescriba la aceptación como rechazo

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:134-143,157-174`

**Issue:** En la rama de aceptación se escribe `sunat_status = Aceptada`,
`sunat_ticket = null`, `save()` (líneas 136-138) y **después** se llama a
`markVoidedInvoices()` (línea 140). `classifyFailure()` clasifica exclusivamente por
`is_null($voided->sunat_ticket)` (línea 159).

Secuencia de daño:

1. `resolveTicket()` acepta, archiva el CDR, borra el ticket y guarda.
2. `markVoidedInvoices()` lanza (deadlock, pérdida de conexión, `details` con una clave
   ausente — es un `foreach` sobre JSON sin validar, líneas 226-231).
3. La excepción sube al `catch (\Throwable)` de `VoidedForm::send()` (línea 78) o de
   `VoidedTable::sendXml()` (línea 139).
4. `classifyFailure()` ve `sunat_ticket === null` → **`markRejected()`** → sobrescribe
   `sunat_status = Rechazada` y pisa el `sunatResponse` con el CDR real.

Resultado: una baja que SUNAT aceptó y cuyo CDR está archivado en disco figura como
RECHAZADA en el ERP. Es la misma clase de defecto que la fase elimina, reintroducida
por el otro extremo. El mismo camino se activa si el render de
`voideds.partials.sunatResponse` lanza (ver WR-10), porque `showResponse()` está
dentro del `try`.

**Fix:** hacer que `classifyFailure()` no pueda degradar un desenlace ya escrito, y
mover el efecto secundario antes de borrar el ticket:
```php
public function classifyFailure(Voided $voided, \Throwable $e): VoidedStatus
{
    // Un desenlace confirmado por CDR no se reescribe nunca desde un catch
    // exterior: SUNAT ya dijo la última palabra sobre esta baja.
    if ($voided->sunat_status === VoidedStatus::Aceptada) {
        return VoidedStatus::Aceptada;
    }

    if (is_null($voided->sunat_ticket)) {
        return $this->markRejected($voided, [...]);
    }

    return $this->markPending($voided, 'fallo_transporte', null, $e->getMessage());
}
```
y en `resolveTicket()` envolver `sunatResponse` + `sunat_status` + `sunat_ticket = null`
+ `markVoidedInvoices()` en una única `DB::transaction()` (ver WR-05).

---

### CR-06: XSS almacenado — mensaje de error de SUNAT renderizado sin escapar dentro del `html` de SweetAlert

**File:** `resources/views/voideds/partials/sunatResponse.blade.php:140,209`

**Issue:**
```blade
{!! $document->sunatResponse['error']['message'] ?? null !!}
```
`sunatResponse.error.message` proviene de terceros y de datos de usuario: o de
`$result->getError()->getMessage()` (texto que devuelve SUNAT) o de `$e->getMessage()`
(`VoidedTicketFlowService.php:46,65,165`), que puede arrastrar el contenido de
`details[].desMotivoBaja` —campo de texto libre de 250 caracteres que el usuario
escribe en el modal de anulación y que viaja al XML; los mensajes de validación de
SUNAT eco del valor rechazado son habituales. El valor se persiste en la columna JSON
y luego se inyecta sin escapar en el `html` de un SweetAlert, que sí interpreta HTML.
XSS almacenado ejecutable para cualquier usuario que abra el detalle de esa fila.

La línea 209 arrastra el patrón del partial anterior; la 140 lo **duplica en código
nuevo**, así que la corrección aplica a ambas.

**Fix:**
```blade
<div class="whitespace-normal">
    {!! nl2br(e($document->sunatResponse['error']['message'] ?? '')) !!}
</div>
```
(`e()` escapa y `nl2br` conserva el único formato que los mensajes de SUNAT necesitan;
si no hacen falta saltos de línea, basta con `{{ }}`).

## Warnings

### WR-01: `resolveTicket()` es público y no valida que exista ticket

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:83-89`
**Issue:** El docblock invita explícitamente a la Fase 2 a llamarlo desde el listado.
Si se invoca sobre una fila sin ticket, `$see->getStatus(null)` sale a la red con un
ticket vacío (el parámetro de Greenter no tiene tipo, así que no falla en PHP), SUNAT
devuelve error y el flujo cae en `markPending()`. La fila queda marcada como
"SUNAT recibió la comunicación de baja y aún la procesa" cuando SUNAT nunca recibió
nada, y `response.blade.php` deja de ofrecerle el reenvío: queda muerta.
**Fix:**
```php
public function resolveTicket(Voided $voided, string $cdrPath, ?See $see = null): VoidedStatus
{
    $ticket = $voided->sunat_ticket;

    if (empty($ticket)) {
        throw new \LogicException('resolveTicket() requiere un ticket persistido en el Voided.');
    }
    // ...
}
```

### WR-02: `markPending()` pisa un desenlace ya confirmado por CDR

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:181-201`
**Issue:** Una fila RECHAZADA por CDR conserva el ticket a propósito (línea 145-148).
Cuando la Fase 2 reconsulte esa fila y el transporte falle, `markPending()`
sobrescribe `sunat_status` a Pendiente y reemplaza el `sunatResponse` con el payload
genérico, **perdiendo el código y la descripción del CDR de rechazo** que SUNAT ya
había confirmado.
**Fix:** salir temprano si el desenlace ya es definitivo:
```php
private function markPending(...): VoidedStatus
{
    if (in_array($voided->sunat_status, [VoidedStatus::Aceptada, VoidedStatus::Rechazada], true)
        && isset($voided->sunatResponse['cdrResponse'])) {
        return $voided->sunat_status; // ya hay CDR: no se degrada
    }
    // ...
}
```

### WR-03: `$xml = $voided->xml` puede ser `null` y no se comprueba

**File:** `app/Livewire/VoidedTable.php:120,124`
**Issue:** El accesor `Voided::xml` hace `Storage::get($this->xml_path)`, que devuelve
`null` si el objeto no existe en el disco (fichero purgado, ruta de otro entorno,
migración de Spaces). Con `null`, `sendAndResolve(Voided $v, string $xml, ...)` lanza
un `TypeError` → `classifyFailure()` → `markRejected()` con el texto del `TypeError`
como mensaje de error SUNAT, mostrado al usuario en el swal.
**Fix:**
```php
$xml = $voided->xml;

if (empty($xml)) {
    $this->dispatch('swal', [
        'icon' => 'error',
        'title' => 'XML no disponible',
        'text' => 'No se encontró el XML firmado de esta comunicación de baja.',
    ]);

    return;
}
```

### WR-04: El valor de retorno de `Storage::put()` nunca se comprueba

**File:** `app/Livewire/Forms/VoidedForm.php:53,58`; `app/Livewire/VoidedTable.php:112,116`; `app/Services/Sunat/VoidedTicketFlowService.php:116`
**Issue:** `Storage::put()` devuelve `false` en vez de lanzar cuando la escritura falla
(Spaces caído, cuota, credenciales). En los cinco puntos se persiste igualmente
`xml_path` / `pdf_path` / `cdr_path`, dejando la fila apuntando a un objeto que no
existe: el botón de descarga aparece y revienta al pulsarlo. Es especialmente grave en
`resolveTicket()` (línea 114-117), donde el ZIP del CDR es la única prueba del
desenlace.
**Fix:**
```php
if ($zip) {
    if (! Storage::put($cdrPath, $zip)) {
        Log::error('No se pudo archivar el CDR de la comunicación de baja', [
            'voided_id' => $voided->id, 'cdr_path' => $cdrPath,
        ]);
    } else {
        $voided->cdr_path = $cdrPath;
    }
}
```

### WR-05: La aceptación y el marcado de facturas no son atómicos

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:135-142`
**Issue:** `$voided->save()` (Aceptada) y `markVoidedInvoices()` son dos escrituras
independientes. Si el proceso muere entre ambas (timeout de PHP, reinicio de FPM), la
baja queda ACEPTADA y las facturas sin `voided = true`: el ERP muestra como vigentes
comprobantes que SUNAT ya dio de baja, y no existe ningún mecanismo de reconciliación.
El proyecto ya usa transacciones para este caso (`ContainerOperationalTraceService`).
**Fix:** envolver ambas en `DB::transaction(function () use ($voided, $cdr) { ... });`.

### WR-06: La columna CDR sigue ofreciendo el reenvío que D-09 quitó

**File:** `app/Livewire/VoidedTable.php:56-59` (`resources/views/vouchers/partials/cdr.blade.php:6`)
**Issue:** El partial compartido de la columna CDR pinta un botón `sendXml($row->id)`
para toda fila sin `cdr_path`. Eso incluye las PENDIENTES, para las que el partial
nuevo declara "D-09: nunca ofrece reenvío, aunque el JSON no sea de éxito". El usuario
ve el botón, lo pulsa y recibe un "En proceso" contradictorio con el reloj ámbar que
ya está mirando. La gating cuidadosa de `voideds.partials.response` se anula desde la
columna de al lado.
**Fix:** dar a `VoidedTable` su propio partial de CDR (como se hizo con `response`),
que solo muestre la descarga y nunca el reenvío:
```php
Column::make('CDR', 'cdr_path')
    ->format(fn ($value, $row) => view('voideds.partials.cdr', compact('value', 'row')))
    ->collapseOnTablet(),
```

### WR-07: `VoidedTable` no define `downloadCDR()`, pero la columna CDR lo invoca

**File:** `app/Livewire/VoidedTable.php:56-59,69-77`
**Issue:** `vouchers.partials.cdr` hace `wire:click="downloadCDR({{$row->id}})"`. El
método existe en `VoucherTable`, `DespatchTable` y `DespatchTransportistaTable`, pero
**no en `VoidedTable`**: pulsar la descarga del CDR de una anulación lanza el error de
Livewire "method does not exist". El defecto es previo, pero el flujo nuevo archiva el
CDR en más escenarios (ahora también en los rechazos con CDR, línea 114-117), así que
pasa de raro a habitual.
**Fix:**
```php
public function downloadCDR(Voided $voided)
{
    return Storage::download($voided->cdr_path);
}
```
(sujeto también a la comprobación de inquilino de CR-03 y a un guard de `cdr_path` no
nulo).

### WR-08: El nombre del CDR se construye con el RUC de sesión, no con el del comprobante

**File:** `app/Livewire/VoidedTable.php:97,111,115,123`
**Issue:** `$name` sale de `session('company')->ruc`, mientras que el XML y el PDF de
la misma ejecución se nombran con `$voidedGreenter->getName()`, que usa el RUC
persistido en `voideds.company` (snapshot del observer). Si el RUC de la empresa se
editó después de crear la anulación —o si se explota CR-03— el ZIP del CDR se archiva
con un nombre que no corresponde al comprobante, rompiendo la correlación
XML/PDF/CDR en Spaces. Es además una instancia del anti-patrón "Company/Branch Context
Mixing" que documenta `.claude/CLAUDE.md`.
**Fix:** derivar siempre del comprobante:
```php
$name = $voided->company['ruc'].'-RC-'.$voided->fecGeneracion->format('Ymd').'-'.$voided->correlativo;
```

### WR-09: `showResponse()` y el swal "En proceso" están duplicados literalmente

**File:** `app/Livewire/Forms/VoidedForm.php:64-72,81-89,101-121`; `app/Livewire/VoidedTable.php:126-134,142-150,160-180`
**Issue:** `showResponse()` está copiado carácter a carácter en las dos clases (con la
única diferencia de `session()->flash` vs `$this->dispatch`), y el bloque swal "En
proceso" aparece **cuatro veces**. `CLAUDE.md` documenta que exactamente esta clase de
duplicación ya provocó una divergencia en producción (`getReportPdf()` /
`getReportHtml()` produciendo salidas distintas para el mismo comprobante). El `match`
del icono es lógica de presentación del estado y pertenece al enum, que ya tiene
`label()` y `badgeColor()`.
**Fix:** añadir `VoidedStatus::swalIcon(): string` y un trait o método compartido:
```php
// app/Enums/VoidedStatus.php
public function swalIcon(): string
{
    return match ($this) {
        self::Aceptada => 'info',
        self::Pendiente => 'warning',
        self::Rechazada => 'error',
    };
}
```
y extraer la construcción del payload (`title` + `html` + `icon`) a un único método
reutilizado por ambos llamadores.

### WR-10: La rama ACEPTADA del detalle accede a claves del CDR sin `??`

**File:** `resources/views/voideds/partials/sunatResponse.blade.php:23,27`
**Issue:** `$document->sunatResponse['cdrResponse']['code']` y `['description']` se
leen sin comprobar. Hoy solo `readCdr()` escribe ese estado, pero basta con una fila
migrada a mano, un `sunatResponse` truncado o un futuro camino que escriba Aceptada de
otra forma para que el `render()` lance. Y ese `render()` ocurre **dentro del `try`**
de `send()`/`sendXml()`, así que el fallo se convierte en CR-05: la aceptación se
reescribe como rechazo. La rama RECHAZADA sí se protege con `isset()` (línea 104); la
ACEPTADA no.
**Fix:** aplicar la misma guarda `@if (isset($document->sunatResponse['cdrResponse']))`
en la rama ACEPTADA y usar `?? null` en los accesos.

### WR-11: `classifyFailure()` guarda dentro del `catch` sin protección

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:157-174` (llamado desde `VoidedForm.php:79` y `VoidedTable.php:140`)
**Issue:** Ambos `markRejected()` y `markPending()` hacen `$voided->save()`. Si la
excepción original fue precisamente un fallo de base de datos, ese `save()` vuelve a
lanzar desde dentro del `catch`, la segunda excepción escapa sin manejar (500 en el
form, error de Livewire en la tabla) y **no se escribe ningún estado**: la fila queda
con el estado previo mientras SUNAT puede haber recibido ya el XML.
**Fix:** proteger la escritura de clasificación y registrar el fallo doble:
```php
try {
    $status = $flow->classifyFailure($voided, $e);
} catch (\Throwable $persistError) {
    Log::error('No se pudo persistir el desenlace de la comunicación de baja', [
        'voided_id' => $voided->id,
        'original' => $e->getMessage(),
        'persist' => $persistError->getMessage(),
    ]);
    $status = null;
}
```

### WR-12: `markRejected()` no deja rastro en el log

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:208-215`
**Issue:** `markPending()` registra un `Log::warning` con `voided_id`, ticket y motivo,
pero `markRejected()` —que es el estado que **borra** el `sunatResponse` anterior y el
que dispara los tres caminos de corrupción de CR-01/CR-02/CR-05— no registra nada. Sin
ese rastro es imposible reconstruir a posteriori si una fila RECHAZADA lo fue por
decisión de SUNAT o por un `catch` exterior.
**Fix:**
```php
Log::warning('Comunicación de baja rechazada', [
    'voided_id' => $voided->id,
    'sunat_ticket' => $voided->sunat_ticket,
    'code' => $response['cdrResponse']['code'] ?? $response['error']['code'] ?? null,
]);
```

## Info

### IN-01: `sunat_ticket` y `sunat_status` en `$fillable` sin usarse nunca en asignación masiva

**File:** `app/Models/Voided.php:25-26`
**Issue:** Ambas columnas se escriben siempre por asignación directa + `save()` en
`VoidedTicketFlowService`. Añadirlas a `$fillable` solo amplía la superficie de
asignación masiva (el modelo se crea con `Voided::create($this->all())` desde un Form
de Livewire) sin ningún beneficio.
**Fix:** quitarlas de `$fillable` y dejar solo el cast de `sunat_status` en `$casts`.

### IN-02: Botones con atributo `xs` inválido y sin `type="button"`

**File:** `resources/views/voideds/partials/response.blade.php:5,12,21,28,43,49`
**Issue:** `xs` es una prop del componente WireUI `<x-button>`, no un atributo HTML
válido en un `<button>` plano; se emite tal cual al DOM. Además falta
`type="button"`, así que si el partial acaba dentro de un `<form>` el clic provoca un
submit además del `wire:click`. Se arrastra del partial anterior.
**Fix:** `<button type="button" class="..." wire:click="...">`.

### IN-03: `sunat_status` como `string` sin longitud ni índice

**File:** `database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php:16`
**Issue:** La columna guarda uno de tres valores de ≤ 9 caracteres y la Fase 2 la
filtrará para listar pendientes; se crea como `VARCHAR(255)` sin índice.
**Fix:** `$table->string('sunat_status', 20)->nullable()->index()->after('sunat_ticket');`

### IN-04: `$directory` se deriva de fuentes distintas en cada llamador

**File:** `app/Livewire/Forms/VoidedForm.php:46` vs `app/Livewire/VoidedTable.php:96`
**Issue:** El form usa `$this->production` (propiedad del Form, estado de cliente
rehidratado) y la tabla usa `$voided->production` (valor persistido). Hoy coinciden,
pero la propiedad del Form es la fuente menos fiable de las dos: viaja en el snapshot
de Livewire.
**Fix:** usar `$voided->production` en ambos, después del `create()`.

---

_Reviewed: 2026-08-04_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_
