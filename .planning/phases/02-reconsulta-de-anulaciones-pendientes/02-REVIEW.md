---
phase: 02-reconsulta-de-anulaciones-pendientes
reviewed: 2026-08-05T23:40:00Z
depth: standard
files_reviewed: 3
files_reviewed_list:
  - app/Livewire/VoidedTable.php
  - app/Services/Sunat/VoidedTicketFlowService.php
  - resources/views/voideds/partials/response.blade.php
findings:
  critical: 1
  warning: 10
  info: 10
  total: 21
status: issues_found
---

# Fase 2: Informe de revisión de código (re-revisión tras 02-04)

**Revisado:** 2026-08-05T23:40:00Z
**Profundidad:** standard
**Archivos revisados:** 3
**Estado:** issues_found

## Resumen

Re-revisión posterior al cierre de gaps 02-04 (`bcb69da`, `412c2cd`). Se
verificó cada uno de los hallazgos de la revisión anterior (`b2d5b4e`) contra el
código actual, y se volvió a recorrer el flujo completo cruzándolo con
`VoidedTicketFlowService`, `VoidedForm::send()`, `UtilService::getSee()`,
`Voided` (accesor `xml`), `VoidedStatus`, `voideds/partials/sunatResponse.blade.php`,
`vouchers/partials/{pdf,xml,cdr}.blade.php`, `config/filesystems.php`,
`Greenter\See::sendXmlFile()` y `Greenter\Ws\Resolver\XmlTypeResolver`.

### Cierres verificados (no se re-reportan)

- **WR-04 — CERRADO.** `downloadPDF()` (`:102-115`) y `downloadXML()`
  (`:117-130`) llevan ahora la misma guarda `empty(...) || ! Storage::exists(...)`
  que `downloadCDR()`. El error 500 de Livewire sobre filas sin fichero
  desaparece. Verificado línea a línea, no asumido.
- **CR-01, vector de almacenamiento — CERRADO.** La preparación local del XML
  vive en su propio `try` (`:251-277`) cuyo `catch` llama a
  `dispatchXmlUnavailableSwal()` y **no** a `classifyFailure()`; la guarda de
  regeneración comprueba el fichero real (`:257`) y la guarda `blank($xml)`
  (`:279`) cubre el disco no-throwing. Ese vector concreto ya no puede escribir
  `Rechazada`.

### Lo que no quedó cerrado

**CR-01 sigue abierto con alcance reducido.** 02-04 sacó del `try` las
operaciones locales *que estaban en el componente*, pero la causa raíz —el
`catch` de `sendAndResolve()` escribe `Rechazada` para cualquier `Throwable`
producido antes de que SUNAT conteste— quedó intacta dentro del servicio.
`See::sendXmlFile()` parsea y resuelve el documento **antes** de cualquier E/S de
red, y un fallo de transporte (timeout, DNS, TLS, `SoapFault`) tampoco es una
respuesta de SUNAT. En ambos casos la fila se persiste como `Rechazada` y el
modal de detalle imprime literalmente «Rechazado por SUNAT»
(`sunatResponse.blade.php:99-102`), afirmando un desenlace que SUNAT nunca
emitió. Es la mitad del defecto que la fase existe para eliminar.

Siguen sin corregir, verificados uno a uno en el código actual: el doble `swal`
que se traga el modal de detalle (WR-01), las filas `Pendiente` sin vía de
lectura y el modal que se autocontradice (WR-02), el callejón sin salida de
`Pendiente` sin ticket (WR-03), la ausencia de protección contra doble envío y de
invariante en `markRejected()` (WR-05), el volcado del mensaje crudo de excepción
sin `Log::error()` (WR-06, fuera de alcance de 02-04 por decisión explícita), la
mitad de D-04 que dejó las credenciales SUNAT ancladas a la sesión (WR-07) y
`resolveTicket()` público sin guarda de entrada (WR-08).

**Nuevos hallazgos de esta pasada:** WR-10 (el código `0127` de SUNAT se
diagnostica como fallo de transporte y deja la fila irrecuperable — la deuda
registrada en 02-04, que confirmo real y numero aquí), WR-11 (la generación del
RIDE bloquea el reenvío fiscal y deja un XML huérfano) y WR-12 (la comparación
`(string) $cdr->getCode() === '0'` no recibió la normalización que sí recibió el
código 98).

**Nota de trazabilidad de IDs.** Se conservan los IDs de la revisión previa.
`WR-09` queda retirado deliberadamente: era el ID con que la revisión anterior a
`b2d5b4e` reportó lo que después se promovió a `CR-01`; los nuevos hallazgos
arrancan en `WR-10` para que ningún ID signifique dos cosas distintas en el
historial de la fase.

**Alcance aceptado (se reportan a su severidad, no se escalan):** IN-01
(propiedad multi-tenant y permisos) diferido a la Fase 4 por decisión de ROADMAP;
WR-06 excluido a propósito de 02-04; IN-03 (`danger`) pertenece al enum de la
Fase 1, intocable por D-08.

## Narrative Findings (AI reviewer)

### Críticos (BLOCKER)

#### CR-01 (parcialmente cerrado, sigue abierto): un fallo local o de transporte anterior a la respuesta de SUNAT se persiste como «Rechazada» y el modal afirma «Rechazado por SUNAT»

**Archivo:** `app/Services/Sunat/VoidedTicketFlowService.php:38-50` (con el efecto
visible en `resources/views/voideds/partials/sunatResponse.blade.php:99-102`, y
alcanzado desde `app/Livewire/VoidedTable.php:294` y
`app/Livewire/Forms/VoidedForm.php:88`)

**Qué cerró 02-04 y qué no.** El `try` local de `sendXml()` (`VoidedTable.php:251-277`)
y la guarda `blank($xml)` (`:279`) impiden que un XML ausente o vacío llegue al
envío, y su `catch` ya no delega en `classifyFailure()`. Correcto y verificado.
Pero el mismo modo de fallo sobrevive un nivel más abajo, en el único `try` que
02-04 no tocó:

```php
$see = $this->util->getSee();

try {
    $result = $see->sendXmlFile($xml);
} catch (\Throwable $e) {
    // D-06: el envío falló antes de devolver ticket — no hay nada que
    // reconsultar, es rechazo.
    return $this->markRejected($voided, [ ... 'message' => $e->getMessage() ]);
}
```

El comentario asume que todo lo que sale de ese `catch` es «el envío falló». No
lo es, por dos vías distintas:

1. **Fallo puramente local, sin una sola llamada de red.**
   `See::sendXmlFile()` (`vendor/greenter/lite/src/Greenter/See.php:189-206`)
   hace `$doc->loadXML($xmlContent)` y a continuación
   `XmlTypeResolver::getType($doc)`, que ejecuta
   `$doc->documentElement->localName`
   (`vendor/greenter/ws/src/Ws/Resolver/XmlTypeResolver.php:40-42`). Con un XML
   almacenado corrupto o truncado —fichero presente y no vacío, así que las dos
   guardas nuevas lo dejan pasar— `loadXML()` devuelve `false`, `documentElement`
   queda en `null` y se lanza `Error: Attempt to read property "localName" on null`
   **antes de abrir ningún socket**. Ese `Error` entra en el `catch` y se escribe
   como rechazo de SUNAT. La guarda `blank($xml)` protege del fichero vacío, no
   del fichero inválido, que es el resultado típico de un `Storage::put()`
   interrumpido o de una subida parcial a DO Spaces.
2. **Fallo de transporte.** Un `SoapFault` por timeout, DNS caído, TLS o
   mantenimiento de SUNAT llega al mismo `catch`. SUNAT no rechazó nada: nunca
   recibió el documento.

En los dos casos se persiste:

```
sunat_status  = rechazada
sunatResponse = {"success":false,"error":{"code":null,"message":"<texto interno>"}}
```

y la interfaz lo declara sin matices: `response.blade.php:25-35` pinta la rama
`Rechazada`, y el modal de detalle escribe **«Rechazado por SUNAT»**
(`sunatResponse.blade.php:99-102`) seguido del mensaje del `Throwable`. Eso viola
directamente el core value declarado del proyecto: la base de datos y la interfaz
afirman un desenlace que SUNAT no comunicó.

Que la fila siga siendo reenviable (`canResendXml()` devuelve `true` para
`Rechazada` + ticket nulo) mitiga la pérdida de recuperabilidad, no la falsedad
del estado: entre el fallo y el reenvío, contabilidad ve una baja «rechazada por
SUNAT» que SUNAT jamás vio, y ese estado es el que queda si nadie vuelve a pulsar.

**Fix:** distinguir el rechazo *de SUNAT* del fallo *previo a SUNAT*, tanto en el
dato como en el texto. Mínimo viable sin tocar el esquema, usando la forma que
`markRejected()` ya escribe (`error.code === null` sólo se da en los fallos
pre-CDR):

```php
// VoidedTicketFlowService::sendAndResolve()
try {
    $result = $see->sendXmlFile($xml);
} catch (\Throwable $e) {
    // El documento no llegó a SUNAT: no es un desenlace del tercero fiscal.
    Log::error('El envío de la comunicación de baja no llegó a SUNAT', [
        'voided_id' => $voided->id,
        'exception' => $e,
    ]);

    return $this->markRejected($voided, [
        'success' => false,
        'delivered' => false,   // ← marca explícita: SUNAT nunca lo vio
        'error' => ['code' => null, 'message' => $e->getMessage()],
    ]);
}
```

```blade
{{-- sunatResponse.blade.php, rama Rechazada --}}
@if (($document->sunatResponse['delivered'] ?? true) === false)
    <i class="text-xl fas fa-plug text-red-500 mr-2"></i>
    No se pudo entregar a SUNAT (el envío no llegó a completarse)
@else
    <i class="text-xl fas fa-times text-red-500 mr-2"></i>
    Rechazado por SUNAT
@endif
```

Y añadir una validación del XML antes de entregarlo al envío, para que el caso 1
caiga en `dispatchXmlUnavailableSwal()` junto con el resto de fallos locales:

```php
// VoidedTable::sendXml(), justo después de la guarda blank($xml)
$probe = new \DOMDocument();
if (! @$probe->loadXML($xml) || is_null($probe->documentElement)) {
    $this->dispatchXmlUnavailableSwal($voided, null);

    return;
}
```

---

### Advertencias (WARNING)

#### WR-01: Dos `swal` en la misma respuesta: el detalle que se acaba de renderizar nunca llega a verse, y se muestra «error» sobre desenlaces confirmados

**Archivo:** `app/Livewire/VoidedTable.php:202-208`

**Problema:** en el `catch` de la rama de reconsulta se llama a
`showResponse($voided)` —que hace `dispatch('swal', [... 'html' ...])`— e
inmediatamente después se despacha un segundo `swal` de error. El listener es
`Livewire.on('swal', data => Swal.fire(data[0]))`
(`resources/views/layouts/dashboard.blade.php:77-79`): ambos eventos llegan en la
misma respuesta y se procesan seguidos, así que el segundo `Swal.fire()` cierra y
sustituye el popup del primero. La llamada a `showResponse()` es código muerto.

Agravante: `classifyFailure()` puede devolver `Aceptada` o `Rechazada` por su rama
de invariante (`VoidedTicketFlowService.php:185-194`), es decir cuando el
desenlace ya estaba confirmado por CDR y sólo falló algo posterior. En ese caso el
operador ve un modal titulado «Error al consultar el comprobante» sobre una baja
que SUNAT aceptó.

**Verificado:** presente sin cambios tras 02-04.

**Fix:** un solo `swal` que combine ambas cosas y no se titule «error» cuando el
estado es un desenlace confirmado:

```php
$html = view('voideds.partials.sunatResponse', ['document' => $voided])->render();

$this->dispatch('swal', [
    'icon' => $status === VoidedStatus::Aceptada ? 'info' : 'error',
    'title' => 'Detalle de la comunicación de baja',
    'html' => $html.'<p class="mt-4 text-sm text-red-600">No se pudo completar la consulta con SUNAT.</p>',
]);
```

---

#### WR-02: Las filas `Pendiente` se quedaron sin ninguna vía de lectura, y el modal que ya no se alcanza sigue contradiciéndose

**Archivo:** `resources/views/voideds/partials/response.blade.php:13-23`;
`app/Livewire/VoidedTable.php:333-367`;
`resources/views/voideds/partials/sunatResponse.blade.php:47-90`

**Problema:** dos defectos encadenados.

1. **La rama `Pendiente` del modal de detalle quedó inalcanzable.**
   `showResponse()` sólo se invoca desde `response.blade.php:6` (Aceptada), `:41`
   (Rechazada con ticket), `:83` (histórica con respuesta), desde
   `VoidedTable.php:190/202/302` (siempre con `$status !== Pendiente`) y desde
   `VoidedForm::send()` (ídem). El único botón que abría el detalle de una fila
   pendiente —el reloj— ahora dispara `sendXml()`. No queda forma en la aplicación
   de leer el número de ticket, el código ni el mensaje guardados de una fila
   pendiente sin lanzar una llamada SOAP contra SUNAT. Sobre un lote filtrado con
   el nuevo `SelectFilter` «PENDIENTE», eso es una llamada de red por cada fila que
   uno quiera mirar, y las ~44 líneas de `sunatResponse.blade.php:47-90` pasan a
   ser código muerto.
2. **Esa rama, mientras existe, se contradice.** La línea 56 afirma sin condición
   «SUNAT recibió la comunicación de baja y aún la procesa», y ocho líneas más
   abajo `$reasonLabel` imprime «No se pudo confirmar el estado con SUNAT»
   (línea 63) para el motivo `fallo_transporte`. Es exactamente la afirmación que
   GAP 2 eliminó del toast (`dispatchPendingSwal()`, `:338-366`) y que quedó viva
   en el modal. El criterio del toast —sólo `codigo_98` autoriza afirmar
   progreso— no se replicó aquí.

**Verificado:** presente sin cambios tras 02-04. Interactúa con WR-10: con el
código `0127` la fila queda `Pendiente` para siempre y este texto la declara «en
proceso» indefinidamente.

**Fix:** conservar la lectura y hacer el texto dependiente del motivo:

```blade
{{-- response.blade.php, hoja Pendiente --}}
<div class="flex items-center justify-between gap-2">
    <button wire:click="sendXml({{ $row->id }})" type="button"
            title="Consultar el estado del ticket en SUNAT">
        <x-wire-icon name="clock" class="w-6.5 h-6.5 text-amber-500" solid />
    </button>
    <button wire:click="showResponse({{ $row->id }})" type="button" title="Ver detalle guardado">
        <x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
    </button>
</div>
```

```blade
{{-- sunatResponse.blade.php, rama Pendiente: no afirmar progreso sin codigo_98 --}}
@if (($pending['reason'] ?? null) === 'codigo_98')
    SUNAT recibió la comunicación de baja y aún la procesa
@else
    El desenlace de esta comunicación de baja no se pudo confirmar todavía
@endif
```

---

#### WR-03: Una fila `Pendiente` sin ticket es un callejón sin salida al que el servidor responde con una afirmación falsa

**Archivo:** `app/Livewire/VoidedTable.php:167-238`;
`resources/views/voideds/partials/response.blade.php:13-23`

**Problema:** la hoja `Pendiente` de la vista ofrece el botón de reconsulta **sin
comprobar `sunat_ticket`**, mientras que la rama 1 del servidor exige
`! is_null($voided->sunat_ticket)` (`:168`). Para la combinación `Pendiente` +
ticket nulo el flujo cae: rama 1 no (falta ticket) → rama 2 no (no es
`Rechazada`) → `canResendXml()` devuelve `false` → el operador recibe:

> «Esta comunicación de baja ya tiene una respuesta de SUNAT registrada. Reenviar
> el XML devolvería el error 2109…»

Las dos frases son falsas para una fila pendiente: no hay respuesta de SUNAT
—`Pendiente` significa precisamente que no la hay— y no está demostrado que SUNAT
recibiera nada. No queda ninguna acción disponible en la interfaz.

**Alcanzabilidad, con honestidad:** los tres escritores de `Pendiente`
(`markPending()` desde `resolveTicket()`, desde `sendAndResolve()` y desde
`classifyFailure()`) conservan siempre el ticket, así que la combinación exige
deriva de datos. Se reporta como WARNING y no como Info porque la vista y el
servidor discrepan estructuralmente sobre la precondición, y porque la salida
elegida para el caso imprevisto es una afirmación categórica sobre un tercero
fiscal en vez de un «no se puede determinar».

**Verificado:** presente sin cambios tras 02-04.

**Fix:** que la hoja de la vista replique la precondición del servidor, y que el
aviso de reenvío no afirme lo que no sabe:

```blade
@elseif ($row->sunat_status === \App\Enums\VoidedStatus::Pendiente)
    @if (! is_null($row->sunat_ticket))
        {{-- reconsulta --}}
    @else
        {{-- sólo lectura: estado inconsistente, requiere revisión --}}
    @endif
```

---

#### WR-05: Ninguna protección contra doble envío, y `markRejected()` no respeta el invariante que `classifyFailure()` declara

**Archivo:** `resources/views/voideds/partials/response.blade.php:19, 31, 61, 74`;
`app/Services/Sunat/VoidedTicketFlowService.php:245-252`

**Problema:** los cuatro botones que disparan `sendXml` inician una llamada SOAP
que puede tardar decenas de segundos y ninguno lleva `wire:loading.attr="disabled"`
ni `wire:target`. La fase convirtió el reloj de las filas pendientes —las que el
operador va a pulsar repetidamente— en un disparador de red, de modo que el patrón
«no pasó nada, vuelvo a hacer clic» es el escenario esperable. Hay además una
quinta vía: `vouchers/partials/cdr.blade.php:6` renderiza otro `sendXml` sobre
toda fila sin `cdr_path`, también sin guarda de carga.

Con dos peticiones concurrentes (dos pestañas, dos operadores), ambas leen la fila
antes de que la primera persista el ticket y ambas llegan a `sendXmlFile()`. La
segunda recibe el error 2109 y `sendAndResolve()` la clasifica con
`markRejected()` (`:52-54`), que **no tiene** el guardián de «desenlace ya
confirmado» que sí aplica `classifyFailure()` (`:185-194`). El comentario de
`classifyFailure()` declara el invariante en términos absolutos —«un desenlace ya
confirmado por un CDR de SUNAT no se reescribe nunca»— pero de los tres escritores
de estado sólo uno lo aplica. Un invariante que vive en el llamador y no en el
escritor se rompe en cuanto se añade una vía de entrada, y la Fase 3 va a añadir
una.

**Verificado:** presente sin cambios tras 02-04.

**Fix (dos capas).** En la vista:

```blade
<button wire:click="sendXml({{ $row->id }})" type="button"
        wire:loading.attr="disabled" wire:target="sendXml({{ $row->id }})">
```

En el servicio, mover el invariante al escritor:

```php
private function markRejected(Voided $voided, array $response): VoidedStatus
{
    if ($voided->sunat_status === VoidedStatus::Aceptada) {
        Log::warning('Intento de rechazar una baja ya aceptada por SUNAT; se ignora', [
            'voided_id' => $voided->id,
        ]);

        return VoidedStatus::Aceptada;
    }
    // ...
}
```

y releer la fila bajo bloqueo antes de decidir la rama en `sendXml()`
(`Voided::whereKey($voided->getKey())->lockForUpdate()->firstOrFail()`).

---

#### WR-06: El mensaje crudo de la excepción se muestra al operador, y ninguna de las dos ramas SUNAT registra nada en el log

**Archivo:** `app/Livewire/VoidedTable.php:204-208` y `:314-318`

**Problema:** `'text' => $e->getMessage()` vuelca al `swal` el texto de cualquier
`Throwable`: `SoapFault` con la URL del endpoint y detalles del WSDL,
`QueryException` con SQL y nombres de columnas, `UnableToWriteFile` con rutas
absolutas del bucket de DO Spaces. Es divulgación de detalle interno a un operador
de patio y, a la vez, ruido incomprensible para él. Además ninguna de las dos
ramas llama a `Log::error()`: si el operador cierra el modal, el mensaje se pierde
y no queda en `storage/logs/laravel.log`. Contradice la convención declarada en
`.claude/CLAUDE.md`.

**Verificado:** los dos bloques están byte por byte iguales tras 02-04, **por
decisión explícita de alcance**. El plan 02-04 aplicó el criterio correcto en el
método nuevo (`dispatchXmlUnavailableSwal()`, `:386-399`, que sí registra el
`Throwable` y muestra un texto neutro): la asimetría entre ese método y estos dos
bloques es hoy la mejor prueba de que el arreglo pendiente es mecánico.

**Fix:** replicar el patrón de `dispatchXmlUnavailableSwal()`:

```php
Log::error('Fallo al consultar la comunicación de baja en SUNAT', [
    'voided_id' => $voided->id,
    'sunat_ticket' => $voided->sunat_ticket,
    'exception' => $e,
]);

$this->dispatch('swal', [
    'icon' => 'error',
    'title' => 'Error al consultar el comprobante',
    'text' => 'No se pudo completar la consulta con SUNAT. El detalle quedó registrado; reintente en unos minutos.',
]);
```

---

#### WR-07: D-04 quedó aplicado a medias: la ruta del CDR usa la empresa de la fila, pero el certificado y el endpoint SUNAT siguen siendo los de la sesión

**Archivo:** `app/Livewire/VoidedTable.php:178, 242, 429-435`

**Problema:** el docblock de `cdrPathFor()` declara que el RUC sale de «la empresa
dueña del documento, no la empresa de la sesión que dispara la acción», pero las
dos construcciones del cliente SUNAT siguen siendo
`new UtilService(session('company'))`. `UtilService::getSee()`
(`app/Services/Sunat/UtilService.php:27-40`) deriva de esa empresa el certificado,
el usuario/clave SOL **y el endpoint** (`FE_PRODUCCION` vs `FE_BETA`, según
`$company->production`), mientras que `cdrPathFor()` elige el directorio con
`$voided->production` (`:431`). Son dos fuentes de verdad distintas para el mismo
eje.

Hoy `builder()` (`:469-475`) mantiene ambas alineadas para las filas visibles, así
que la divergencia sólo se alcanza explotando el hueco de autorización (IN-01) —
pero el comentario induce a creer que la fila ya es la fuente de verdad del ámbito
cuando sólo lo es para el nombre del fichero.

**Verificado:** presente sin cambios tras 02-04.

**Fix:** derivar también las credenciales de la fila, o dejar la precondición
explícita en código y no sólo en un comentario:

```php
$util = new UtilService(Company::findOrFail($voided->company_id));
```

o bien `abort_if($voided->company_id !== session('company')->id, 404);` al entrar
en la acción (que es lo que la Fase 4 debería instalar de todos modos).

---

#### WR-08: `resolveTicket()` es público y reutilizable, pero el invariante de «desenlace confirmado» sólo lo protege el llamador

**Archivo:** `app/Services/Sunat/VoidedTicketFlowService.php:84-166` (y su guarda
externa en `app/Livewire/VoidedTable.php:167-171`)

**Problema:** `resolveTicket()` está documentado como «público a propósito (D-11)»
y la Fase 3 lo reutilizará desde otro punto de entrada. Sin embargo no comprueba
nada sobre el estado de la fila que recibe: lo único que impide invocarlo sobre
una fila `Aceptada` es la condición de tres términos escrita en `sendXml()`.

Si un llamador futuro omite esa guarda, la secuencia es destructiva: una fila
`Aceptada` tiene `sunat_ticket` en `null` (se limpia en `:142`), así que
`$see->getStatus(null)` lanza `TypeError`, el `catch` de `:91` llama a
`markPending()` y **`markPending()` sobrescribe `sunatResponse` completo**
(`:221-228`), destruyendo el `cdrResponse` con el código, la descripción y las
observaciones del CDR que SUNAT devolvió. La fila pasa a `Pendiente` con ticket
nulo —el callejón sin salida de WR-03— y el registro de la aceptación sólo
sobrevive en el ZIP del disco.

La asimetría es evidente al comparar con `classifyFailure()`, que sí abre con el
guardián del invariante. El servicio se describe a sí mismo como «único punto del
código base que envía una comunicación de baja y consulta su estado»; siendo así,
la precondición pertenece al servicio.

**Verificado:** presente sin cambios tras 02-04.

**Fix:** guarda de entrada en el propio método, barata e idempotente:

```php
public function resolveTicket(Voided $voided, string $cdrPath, ?See $see = null): VoidedStatus
{
    if ($voided->sunat_status === VoidedStatus::Aceptada
        || ($voided->sunat_status === VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket))) {
        return $voided->sunat_status; // desenlace ya confirmado por CDR
    }

    if (blank($voided->sunat_ticket)) {
        throw new \RuntimeException("La anulación {$voided->id} no tiene ticket que consultar.");
    }
    // ...
}
```

---

#### WR-10 (nuevo): el código `0127` de SUNAT («el ticket no existe») se diagnostica como fallo de transporte y deja la fila irrecuperable

**Archivo:** `app/Services/Sunat/VoidedTicketFlowService.php:102-112` (con el
efecto en `app/Livewire/VoidedTable.php:451-467` y
`resources/views/voideds/partials/sunatResponse.blade.php:54-57`)

**Problema:** confirmo como real la deuda anotada durante la planificación de
02-04. La clasificación es una dicotomía:

```php
$code = $result->getError()?->getCode();
$reason = (int) $code === 98 ? 'codigo_98' : 'fallo_transporte';
```

`0127` es una **respuesta de SUNAT**, no un fallo de comunicación: significa que
el ticket consultado no existe en sus registros. Persistirlo como
`fallo_transporte` guarda un diagnóstico falso en `sunatResponse.pending.reason`,
que es justo el campo del que depende `dispatchPendingSwal()` para decidir qué
puede afirmar la interfaz.

Peor que el diagnóstico es el desenlace. La fila queda `Pendiente` **con el ticket
conservado** (`markPending()` no lo toca, `:218-238`), y con ticket vivo:

- la rama Consultar de `sendXml()` (`:167-212`) la vuelve a mandar a
  `resolveTicket()`, que volverá a recibir `0127` — bucle indefinido;
- `canResendXml()` (`:451-467`) exige `is_null($voided->sunat_ticket)` en sus dos
  casos, así que el reenvío del XML nunca se ofrece.

Resultado: una comunicación de baja que SUNAT nunca llegó a registrar queda
permanentemente marcada como «en proceso», sin ninguna vía en la interfaz para
resolverla, y el modal de detalle afirma sin condición «SUNAT recibió la
comunicación de baja y aún la procesa» (WR-02). Las facturas involucradas nunca se
marcan como anuladas. Es el mismo tipo de divergencia estado-sistema /
estado-SUNAT que la fase existe para eliminar, en una vía distinta.

**Caveat que condiciona el arreglo (verificado contra la memoria del proyecto):**
en el ambiente **beta** de SUNAT, `getStatus` responde `0127` para *cualquier*
ticket de comunicación de baja, porque beta no soporta la consulta. En beta,
`0127` es indistinguible de «el ticket no existe». Por eso el arreglo tiene que
ser condicional al ambiente y no puede limitarse a añadir un `case`.

**Se mantiene en WARNING y no se escala a BLOCKER** por esa ambigüedad: hoy no
existe forma fiable de distinguir el caso en el ambiente contra el que se
verifica. Si se implementa la lectura del ambiente, el hallazgo sube de rango.

**Fix:**

```php
$code = $result->getError()?->getCode();
$normalized = (int) $code;

$reason = match (true) {
    $normalized === 98 => 'codigo_98',
    // 0127: SUNAT dice que el ticket no existe. En beta ese mismo código
    // significa "beta no soporta getStatus", así que sólo es concluyente
    // contra producción.
    $normalized === 127 && $voided->production => 'ticket_inexistente',
    $normalized === 127 => 'beta_sin_consulta',
    default => 'fallo_transporte',
};
```

y para `ticket_inexistente`, liberar la fila para reenvío —limpiando
`sunat_ticket` y dejando constancia del motivo— o, como mínimo, exponerla en la
interfaz con una acción de resolución manual en vez de dejarla en el bucle.

---

#### WR-11 (nuevo): la generación del RIDE bloquea el reenvío fiscal y deja un XML huérfano cuando falla

**Archivo:** `app/Livewire/VoidedTable.php:257-277`

**Problema:** la rama de regeneración encadena en el mismo bloque la producción
del XML firmado (lo que SUNAT necesita) y la del PDF (lo que sólo necesita el
cliente):

```php
$xml = $util->getXmlSigned($voidedGreenter);
$voided->hash = $util->getHashSign($xml);
$voided->xml_path = $directory.'xml/'.$voidedGreenter->getName().'.xml';
Storage::put($voided->xml_path, $xml);

$pdf = $util->getReportPdf($voidedGreenter, $voided->hash);   // ← wkhtmltopdf
$voided->pdf_path = $directory.'cpe/'.$voidedGreenter->getName().'.pdf';
Storage::put($voided->pdf_path, $pdf);

$voided->save();
```

`getReportPdf()` invoca wkhtmltopdf a través de `Greenter\Report\PdfReport`
(`UtilService.php:244-317`), un binario externo que depende de
`WKHTMLTOPDF_BINARIES` y que en este proyecto ya está documentado como frágil al
ejecutarse desde PHP. Dos consecuencias:

1. **Un fallo del generador de PDF impide reenviar la comunicación de baja.** El
   `catch` de `:273` devuelve el control con `dispatchXmlUnavailableSwal()` —cuyo
   texto, «El XML firmado no está disponible en el almacenamiento», es además
   incorrecto para este caso: el XML sí se generó y se escribió. El documento
   fiscal queda bloqueado por un artefacto de presentación.
2. **El XML recién escrito queda huérfano.** `Storage::put()` del XML ya ocurrió,
   pero `$voided->save()` está después del PDF, así que `hash` y `xml_path` nunca
   se persisten. Queda un fichero en el bucket que ninguna fila referencia, y el
   siguiente intento vuelve a regenerarlo desde cero. El `Log::error` de
   `dispatchXmlUnavailableSwal()` (`:388-392`) registra `$voided->xml_path` con el
   valor **nuevo y no guardado**, es decir apunta a la ruta que sí se escribió en
   lugar de a la que falló: el diagnóstico induce a error.

**Fix:** separar las dos responsabilidades y persistir el XML en cuanto está
disponible:

```php
$xml = $util->getXmlSigned($voidedGreenter);
$voided->hash = $util->getHashSign($xml);
$voided->xml_path = $directory.'xml/'.$voidedGreenter->getName().'.xml';
Storage::put($voided->xml_path, $xml);
$voided->save();   // el XML ya no se pierde pase lo que pase con el RIDE

try {
    $pdf = $util->getReportPdf($voidedGreenter, $voided->hash);
    $voided->pdf_path = $directory.'cpe/'.$voidedGreenter->getName().'.pdf';
    Storage::put($voided->pdf_path, $pdf);
    $voided->save();
} catch (\Throwable $e) {
    // El RIDE es reproducible después; no bloquea la comunicación a SUNAT.
    Log::warning('No se pudo regenerar el RIDE de la comunicación de baja', [
        'voided_id' => $voided->id,
        'exception' => $e,
    ]);
}
```

---

#### WR-12 (nuevo): la comparación del código del CDR no recibió la normalización que sí recibió el código 98

**Archivo:** `app/Services/Sunat/VoidedTicketFlowService.php:137`

**Problema:** la aceptación se decide con una comparación de cadena estricta:

```php
if ((string) $cdr->getCode() === '0') {
```

Siete líneas más arriba, en el mismo método, la fase acaba de corregir exactamente
esta clase de defecto para el código de estado del ticket, con el comentario
explícito «SUNAT devuelve el código con ceros a la izquierda (0098, no 98): se
compara el valor numérico normalizado, no la cadena tal cual llegó» (`:106-109`).
El criterio no se aplicó al código del CDR, pese a que `UtilService::readCdr()`
(`:179-192`) sí lo trata como numérico (`'code' => (int) $cdr->getCode()`), señal
de que el propio código base no confía en la forma textual.

**Consecuencia si llega a dispararse:** un CDR con `'0000'`, `' 0'` o `'00'` haría
que una baja **aceptada** por SUNAT se persista como `Rechazada` y —lo más
grave— que `markVoidedInvoices()` (`:263-274`) **nunca se ejecute**: las facturas
quedan sin marcar como anuladas aunque SUNAT las considera dadas de baja. Y como
`markRejected()` conserva el ticket en esa rama, la fila entra en la rama
«Desenlace confirmado» de `sendXml()` (`:214-226`) y el guardián de invariante de
`classifyFailure()` (`:185-186`) blinda para siempre el estado equivocado. No hay
vía de recuperación desde la interfaz.

**Honestidad sobre la probabilidad:** los CDR reales de SUNAT usan
`<cbc:ResponseCode>0</cbc:ResponseCode>` sin relleno, así que el disparo es poco
probable; por eso es WARNING y no BLOCKER. Pero el coste del arreglo es un cast y
la consecuencia es irreversible y silenciosa, y la fase ya demostró en carne
propia que el supuesto sobre el formato de los códigos de SUNAT no se sostiene.

**Fix:**

```php
if ((int) $cdr->getCode() === 0) {
```

---

### Info

#### IN-01: Las acciones públicas siguen aceptando cualquier `Voided` por id, sin propiedad ni permiso

**Archivo:** `app/Livewire/VoidedTable.php:102, 117, 146, 161, 401`
**Problema:** `downloadPDF()`, `downloadXML()`, `downloadCDR()`, `sendXml()` y
`showResponse()` son endpoints Livewire invocables desde el cliente con cualquier
id; el binding de modelo no pasa por `builder()`, único sitio que filtra
`branch_id`/`company_id`/`production`. Un usuario de la empresa A puede leer el
`sunatResponse` de la empresa B, descargar su XML firmado, su RIDE y su CDR, y
disparar envíos o reconsultas contra SUNAT sobre documentos ajenos. Verificado que
sigue presente tras 02-04; las nuevas guardas de `downloadPDF()`/`downloadXML()`
comprueban la existencia del fichero, no la propiedad.
**Fix:** el previsto para la **Fase 4** (decisión de ROADMAP): helper
`scopedVoided()` con `abort_unless()` sobre los tres ejes + `$this->authorize()`
con el permiso Spatie correspondiente. Se registra como Info por indicación de
alcance; **no abrir un segundo frente de trabajo**.

#### IN-02: El ZIP del CDR se archiva con un nombre que no corresponde al comprobante que certifica

**Archivo:** `app/Livewire/VoidedTable.php:429-435`
**Problema:** `cdrPathFor()` compone `R-{ruc}-RC-{fecGeneracion:Ymd}-{correlativo}.zip`
mientras el XML del mismo documento se guarda con `$voidedGreenter->getName()`,
que Greenter define como `{ruc}-RA-{fecComunicacion:Ymd}-{correlativo}`. Divergen
el prefijo (`RC` en vez de `RA`) y la fecha. `VoidedForm::send():87` usa
`getName()`, así que el mismo documento se archiva bajo dos nomenclaturas según
qué flujo resuelva el ticket. No rompe la descarga (`cdr_path` guarda la ruta
usada), pero deja el CDR —la prueba documental de la baja— sin cruzar con su XML.
**Fix:** fuera de alcance de esta fase. Cuando se aborde, derivar el nombre de
`getName()` y prever tolerancia de lectura para los `cdr_path` históricos.

#### IN-03: `VoidedStatus::badgeColor()` devuelve `danger`, que no es un color de WireUI

**Archivo:** `app/Enums/VoidedStatus.php:25`, consumido en
`resources/views/voideds/partials/response.blade.php:34, 44`
**Problema:** el pack de colores del badge de WireUI 2 no define `danger`
(`negative` es el equivalente); el badge acaba con una clase inexistente y el
estado más importante del listado se pinta sin color.
**Fix:** fuera de alcance — el enum pertenece a la Fase 1 y D-08 lo declara
intocable esta fase. Cuando se aborde: `positive` / `warning` / `negative`.

#### IN-04: Rama muerta en el filtro: `$value === ''` es inalcanzable

**Archivo:** `app/Livewire/VoidedTable.php:42-44`
**Problema:** `SelectFilter::getKeys()` descarta las claves de longitud cero y
`validate()` devuelve `false` para `''`, así que el paquete elimina el filtro
antes de invocar la closure. La opción `'' => 'Todos'` sí es necesaria (la vista
sólo renderiza las opciones declaradas); la guarda no.
**Fix:** eliminar el `if` y dejar un comentario explicando que el paquete ya
descarta el valor vacío.

#### IN-05: Cadena mágica `sin-estado` / `SIN ESTADO` duplicada entre componente y vista

**Archivo:** `app/Livewire/VoidedTable.php:39` y
`resources/views/voideds/partials/response.blade.php:64, 77, 86`
**Problema:** el valor del filtro y la etiqueta del badge se repiten como
literales en dos archivos y en tres hojas distintas de la vista; renombrar uno
deja los otros descolgados sin ningún error.
**Fix:** una constante en el componente o un método estático en `VoidedStatus`
(`VoidedStatus::unknownLabel()`) consumido por ambos.

#### IN-06: `sendXml()` creció a 160 líneas con ocho salidas, y `$directory` sigue duplicado

**Archivo:** `app/Livewire/VoidedTable.php:161-320`, con el ternario duplicado en
`:240` y `:431`
**Problema:** 02-04 añadió una fase local con su propio `try/catch` y una guarda
más, de modo que el método pasó de ~135 a 160 líneas y de seis a ocho salidas. Es
más correcto que antes y también más difícil de auditar de un vistazo; queda muy
por encima de la convención del proyecto («métodos de 20 a 50 líneas; los más
grandes delegan en servicios», `.claude/CLAUDE.md`). El ternario
`$voided->production ? 'sunat/' : 'sunat/beta/'` aparece dos veces en el archivo.
**Fix:** extraer `reconsultarTicket(Voided $voided)`, `prepararXml(Voided $voided)`
y `reenviarXml(Voided $voided, string $xml)`; mover el directorio a un accesor del
modelo (`Voided::storageDirectory()`).

#### IN-07: `cdrPathFor()` lee `$voided->company['ruc']` sin ninguna comprobación

**Archivo:** `app/Livewire/VoidedTable.php:432`
**Problema:** si el JSON `company` no trae la clave `ruc`, PHP emite un warning y
devuelve `null`, produciendo `sunat/cdr/R--RC-20260805-12.zip`; `Storage::put()`
lo acepta y el CDR queda archivado con un nombre corrupto sin error visible.
**Alcanzabilidad, verificada:** la columna `company` es `json` **NOT NULL**
(`2024_12_16_111346_create_voideds_table.php:24`), `VoidedObserver::creating()` la
puebla siempre y el RUC está validado con `regex:/^(10|20)\d{9}$/`, así que
tampoco hay vector de path traversal. Requiere una fila insertada saltándose el
observador.
**Fix:** `throw_if(blank(data_get($voided->company, 'ruc')), new \RuntimeException(...))`.

#### IN-08: El código `99` de SUNAT («proceso con errores») se clasifica como fallo de transporte

**Archivo:** `app/Services/Sunat/VoidedTicketFlowService.php:102-112`
**Problema:** misma raíz que WR-10, distinto código y distinta gravedad. La
normalización a `(int) $code === 98` es correcta, pero cualquier otro código sin
CDR cae en `fallo_transporte`, incluido el `99` («proceso con errores»), que es un
desenlace **terminal** de SUNAT y no un problema de comunicación. La fila quedaría
en `Pendiente` para siempre bajo la etiqueta «no se pudo confirmar el estado»,
atribuyendo a la red algo que SUNAT sí respondió.
**Fix:** resolverlo junto con WR-10, en el mismo `match`: motivo propio
`proceso_con_errores` con su etiqueta en el modal.

#### IN-09: Botones sin `type`, sin etiqueta accesible y con un atributo `xs` inerte

**Archivo:** `resources/views/voideds/partials/response.blade.php:6, 19, 31, 41, 61, 74, 83`
**Problema:** son `<button>` HTML planos, así que el atributo `xs` no hace nada
—resto de un componente anterior— y sin `type="button"` heredarían `submit` dentro
de un formulario. Al ser botones sólo-icono no tienen texto accesible: la fase
añadió `title` únicamente a la hoja nueva (línea 74) y dejó las otras seis sin él,
incluida la del reloj, que es la que cambió de significado (WR-02).
**Fix:** `type="button"` y `title`/`aria-label` descriptivos en las siete; retirar
el `xs`.

#### IN-10: El árbol de decisión sigue sin cobertura automatizada

**Archivo:** `app/Livewire/VoidedTable.php:161-320`;
`app/Services/Sunat/VoidedTicketFlowService.php`
**Problema:** ni el árbol de ramas de `sendXml()`, ni la nueva fase local de
preparación del XML, ni `canResendXml()`, ni `cdrPathFor()`, ni
`dispatchPendingSwal()`, ni `dispatchXmlUnavailableSwal()`, ni la clasificación de
`resolveTicket()` tienen pruebas, pese a gobernar el estado fiscal frente a SUNAT.
02-04 corrigió un defecto crítico sin añadir una sola prueba que impida su
regresión: nada detecta hoy si alguien vuelve a mover la preparación del XML
dentro del `try` del envío. Se mantiene en Info por el contexto conocido del
proyecto (la suite corre contra la BD configurada y hoy está roja de base), pero
es lo que convendría desbloquear antes de la Fase 3.
**Fix:** `Livewire::test(VoidedTable::class)->call('sendXml', $id)` por hoja del
árbol con `VoidedTicketFlowService` mockeado —incluida una fila con `xml_path`
apuntando a un fichero inexistente, que debe terminar con `sunat_status`
inalterado— y tests unitarios del servicio con un `See` doblado, sobre sqlite en
memoria.

---

_Revisado: 2026-08-05T23:40:00Z_
_Revisor: Claude (gsd-code-reviewer)_
_Profundidad: standard_
