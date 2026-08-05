---
phase: 02-reconsulta-de-anulaciones-pendientes
reviewed: 2026-08-05T21:10:00Z
depth: standard
files_reviewed: 3
files_reviewed_list:
  - app/Livewire/VoidedTable.php
  - app/Services/Sunat/VoidedTicketFlowService.php
  - resources/views/voideds/partials/response.blade.php
findings:
  critical: 1
  warning: 8
  info: 10
  total: 19
status: issues_found
---

# Fase 2: Informe de revisión de código

**Revisado:** 2026-08-05T21:10:00Z
**Profundidad:** standard
**Archivos revisados:** 3
**Estado:** issues_found

## Resumen

Se revisó el estado del código tras el plan de cierre de gaps 02-03: el árbol de
tres ramas de `VoidedTable::sendXml()`, el nuevo `dispatchPendingSwal()`,
`cdrPathFor()`, el `SelectFilter` de estado, la normalización
`(int) $code === 98` en `VoidedTicketFlowService::resolveTicket()` y las hojas
del árbol de `voideds/partials/response.blade.php`. Cada rama se cruzó contra
`VoidedTicketFlowService` completo, `VoidedForm::send()` (el otro llamador del
servicio), `VoidedObserver`, las dos migraciones de `voideds`, el modelo Greenter
`Voided`, `UtilService::getSee()/readCdr()`, `config/filesystems.php`,
`FilesystemAdapter::get()/download()`, `SelectFilter::validate()` del paquete
rappasoft y el listener `Livewire.on('swal')` de
`resources/views/layouts/dashboard.blade.php`.

Lo que sí quedó cerrado respecto de la revisión anterior: la rama 1 ahora se
condiciona por estado y no por presencia de ticket, así que la fila histórica con
ticket vivo tiene por fin vía de reconsulta (nueva hoja de `response.blade.php`,
líneas 67-78); el aviso de pendiente dejó de afirmar progreso cuando el motivo es
`fallo_transporte`; y el código `0098` con ceros a la izquierda ya se reconoce.

Lo que no: **el hallazgo WR-09 de la revisión previa sigue intacto y es el fallo
más grave del conjunto** — un fichero XML ausente en disco hace que la fila se
grabe como «Rechazada por SUNAT» sin que SUNAT haya dicho nada. Eso es exactamente
el modo de fallo que esta fase existe para eliminar, y ahora es más visible porque
el resto del flujo sí es honesto. Junto a él siguen sin corregir el doble `swal`
que se traga el modal de detalle, la ausencia de guarda en
`downloadPDF()`/`downloadXML()` (asimétrica con el cuidado que sí recibió
`downloadCDR()`), el volcado del mensaje crudo de excepción sin ningún
`Log::error`, y la mitad de D-04 que dejó las credenciales SUNAT ancladas a la
sesión.

Como efecto colateral del cambio de la hoja `Pendiente` (el reloj ahora dispara
red en vez de abrir el detalle), la rama `Pendiente` de
`voideds/partials/sunatResponse.blade.php` quedó **inalcanzable desde la interfaz**:
ya no existe forma de leer el ticket ni el motivo de una fila pendiente sin
golpear a SUNAT. Y esa misma rama sigue afirmando «SUNAT ... aún la procesa» dos
líneas antes de decir «No se pudo confirmar el estado» — la contradicción que
GAP 2 eliminó del toast pero no del modal.

CR-02/CR-04 (propiedad multi-tenant y permisos), WR-01 (prefijo `RC-` del ZIP) y
WR-07 (color `danger`) se registran como Info por indicación explícita de alcance;
siguen presentes y verificados en el código actual.

## Narrative Findings (AI reviewer)

### Críticos (BLOCKER)

#### CR-01: Un XML ausente en disco graba la fila como «Rechazada por SUNAT» sin que SUNAT haya respondido nada

**Archivo:** `app/Livewire/VoidedTable.php:228-243` (con el desenlace escrito en
`app/Services/Sunat/VoidedTicketFlowService.php:196-204`)

**Problema:** la guarda de regeneración sólo mira la columna, no el fichero:

```php
if (empty($voided->xml_path)) {
    // ... regenera XML y PDF
} else {
    $xml = $voided->xml;      // Storage::get($this->xml_path)
}
```

Si `xml_path` está poblado pero el fichero no está en el disco —purga, cambio de
bucket, `Storage::put()` que falló silenciosamente en el envío original, o
migración de `local` a `do`— pasan dos cosas según el disco por defecto
(`config/filesystems.php:16`, `FILESYSTEM_DISK`):

- disco `local` / `public` (`'throw' => false`): `FilesystemAdapter::get()`
  (líneas 285-292 del framework) no devuelve nada → `null`. Ese `null` llega a
  `sendAndResolve(Voided $voided, string $xml, ...)`, cuyo parámetro está tipado
  `string` → **TypeError**.
- disco `do` (`'throw' => true`, `config/filesystems.php:57`): lanza
  `UnableToReadFile`.

En ambos casos el `catch (\Throwable $e)` de la línea 259 recoge la excepción y
llama a `classifyFailure()`. Como esta rama sólo se alcanza cuando
`canResendXml()` fue `true` —y sus dos casos exigen `sunat_ticket` nulo—
`classifyFailure()` entra por `is_null($voided->sunat_ticket)` y ejecuta
`markRejected()`. Resultado persistido:

```
sunat_status  = rechazada
sunatResponse = {"success":false,"error":{"code":null,"message":"<texto del TypeError>"}}
```

El listado pinta el aspa roja y el modal de detalle escribe literalmente
**«Rechazado por SUNAT»** (`voideds/partials/sunatResponse.blade.php:99-102`).
SUNAT no ha visto jamás esta comunicación de baja: el fallo fue puramente local.
La fila queda además en un estado más pobre que antes (`sunat_ticket` sigue nulo,
así que la vista ofrece reenviar y el ciclo se repite indefinidamente mientras el
fichero no aparezca), y las facturas involucradas nunca se marcan.

Esto viola directamente el core value declarado del proyecto: el estado que
muestra el sistema afirma un desenlace que SUNAT no comunicó. Es la misma clase de
defecto que la fase corrigió en la vía del ticket, dejada sin corregir en la vía
del reenvío. Fue reportado como WR-09 en la revisión anterior y no se tocó en
02-03.

Es además incoherente con el cuidado que sí recibió `downloadCDR()` en esta misma
fase (línea 127), que comprueba `Storage::exists()` antes de tocar el fichero
justamente porque «la ruta puede estar poblada sin fichero detrás».

**Fix:** comprobar la existencia real, y no dejar que un fallo local se escriba
como desenlace de SUNAT:

```php
// Regenerar también cuando la columna apunta a un fichero que ya no está.
if (empty($voided->xml_path) || ! Storage::exists($voided->xml_path)) {
    $voidedGreenter = (new VoidedService)->getVoided($voided);

    $xml = $util->getXmlSigned($voidedGreenter);
    // ... (resto igual)
} else {
    $xml = $voided->xml;
}

// Defensa adicional, por si el disco devuelve vacío igualmente:
throw_if(blank($xml), new \RuntimeException(
    "El XML firmado de la anulación {$voided->id} no está disponible en el almacenamiento."
));
```

Y, complementariamente, que `classifyFailure()` no convierta en rechazo de SUNAT
una excepción producida **antes** de llamar a `sendXmlFile()`. La forma más
barata es no entrar al `try` sin XML: validar el `$xml` fuera del bloque
`try/catch` que envuelve el envío, para que un fallo de almacenamiento se muestre
como error de la aplicación y no como respuesta de un tercero fiscal.

---

### Advertencias (WARNING)

#### WR-01: Dos `swal` en la misma respuesta: el detalle que se acaba de renderizar nunca llega a verse, y se muestra «error» sobre desenlaces confirmados

**Archivo:** `app/Livewire/VoidedTable.php:181-189`

**Problema:** en el `catch` de la rama de reconsulta se llama a
`showResponse($voided)` —que hace `dispatch('swal', [... 'html' ...])`— e
inmediatamente después se despacha un segundo `swal` de error. El listener es
`Livewire.on('swal', data => Swal.fire(data[0]))`
(`resources/views/layouts/dashboard.blade.php:77-79`): ambos eventos llegan en la
misma respuesta y se procesan seguidos, así que el segundo `Swal.fire()` cierra y
sustituye el popup del primero. La llamada a `showResponse()` es, en la práctica,
código muerto.

Hay un agravante que la revisión anterior no señaló: `classifyFailure()` puede
devolver `Aceptada` o `Rechazada` **por su rama de invariante**
(`VoidedTicketFlowService.php:185-194`), es decir cuando el desenlace ya estaba
confirmado por un CDR y sólo falló algo posterior. En ese caso el operador ve un
modal titulado «Error al consultar el comprobante» sobre una comunicación de baja
que SUNAT aceptó, sin ninguna otra señal en pantalla hasta que el listado se
repinta.

**Fix:** un solo `swal` que combine ambas cosas, y no titularlo «error» cuando el
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
`app/Livewire/VoidedTable.php:323-343`;
`resources/views/voideds/partials/sunatResponse.blade.php:47-90`

**Problema:** dos defectos encadenados.

1. **La rama `Pendiente` del modal de detalle quedó inalcanzable.** Tras la fase,
   `showResponse()` sólo se invoca desde: `response.blade.php:6` (Aceptada),
   `:41` (Rechazada con ticket), `:83` (histórica con respuesta), y desde
   `VoidedTable.php:169/181/256` (siempre con `$status !== Pendiente`) y
   `VoidedForm::send()` (idem). El único botón que antes abría el detalle de una
   fila pendiente —el reloj— ahora dispara `sendXml()`. Consecuencia práctica:
   **ya no hay forma en la aplicación de leer el número de ticket, el código ni el
   mensaje guardados de una fila pendiente**; para inspeccionarla hay que lanzar
   una llamada SOAP contra SUNAT. Sobre un lote filtrado con el nuevo
   `SelectFilter` «PENDIENTE», eso es una llamada por fila que uno quiera mirar.
   Y las 60 líneas de `sunatResponse.blade.php:47-90` pasan a ser código muerto
   alcanzable sólo invocando el endpoint `showResponse` a mano.

2. **Esa rama, mientras existe, se contradice.** La línea 56 afirma sin condición
   «SUNAT recibió la comunicación de baja y aún la procesa», y ocho líneas más
   abajo `$reasonLabel` puede imprimir «No se pudo confirmar el estado con SUNAT»
   (línea 63) para el motivo `fallo_transporte`. Es exactamente la afirmación que
   GAP 2 eliminó del toast (`dispatchPendingSwal()`, líneas 305-320) y que quedó
   viva en el modal. El criterio aplicado en el toast —sólo `codigo_98` autoriza
   afirmar progreso— no se replicó aquí.

**Fix:** conservar la lectura y hacer el texto dependiente del motivo, igual que
en el toast:

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
    SUNAT recibió la comunicación de baja; su desenlace no se pudo confirmar todavía
@endif
```

---

#### WR-03: Una fila `Pendiente` sin ticket es un callejón sin salida al que el servidor responde con una afirmación falsa

**Archivo:** `app/Livewire/VoidedTable.php:146-217`;
`resources/views/voideds/partials/response.blade.php:13-23`

**Problema:** la hoja `Pendiente` de la vista ofrece el botón de reconsulta **sin
comprobar `sunat_ticket`**, mientras que la rama 1 del servidor exige
`! is_null($voided->sunat_ticket)`. Para la combinación `Pendiente` + ticket nulo
el flujo cae: rama 1 no (falta ticket) → rama 2 no (no es `Rechazada`) →
`canResendXml()` devuelve `false` (ninguno de sus dos casos aplica) → el operador
recibe:

> «Esta comunicación de baja ya tiene una respuesta de SUNAT registrada. Reenviar
> el XML devolvería el error 2109…»

Las dos frases son falsas para una fila pendiente: no hay respuesta de SUNAT
—`Pendiente` significa precisamente que no la hay— y no está demostrado que SUNAT
recibiera nada. Y no queda ninguna acción disponible en la interfaz.

**Alcanzabilidad, con honestidad:** hoy los tres escritores de `Pendiente`
(`markPending()` desde `resolveTicket()`, desde `sendAndResolve()` y desde
`classifyFailure()`) conservan siempre el ticket, así que la combinación exige
deriva de datos (corrección manual en BD, restauración parcial, seeder). Se
reporta como WARNING y no como Info porque la vista y el servidor discrepan
estructuralmente sobre la precondición, y porque la salida elegida para el caso
imprevisto es una afirmación categórica sobre un tercero fiscal en vez de un «no
se puede determinar» — el criterio contrario al que la fase adoptó en
`dispatchPendingSwal()`.

**Fix:** hacer que la hoja de la vista replique la precondición del servidor, y
que el aviso de reenvío no afirme lo que no sabe:

```blade
@elseif ($row->sunat_status === \App\Enums\VoidedStatus::Pendiente)
    @if (! is_null($row->sunat_ticket))
        {{-- reconsulta --}}
    @else
        {{-- sólo lectura: estado inconsistente, requiere revisión --}}
    @endif
```

---

#### WR-04: `downloadPDF()` y `downloadXML()` no tienen la guarda que sí recibió `downloadCDR()`, y revientan en filas cuyo fichero nunca se generó

**Archivo:** `app/Livewire/VoidedTable.php:101-109`

**Problema:** ambos métodos hacen `Storage::download($voided->pdf_path)` /
`($voided->xml_path)` sin comprobar nada, mientras que `downloadCDR()` (líneas
125-138) razona explícitamente que «la ruta puede estar poblada sin fichero
detrás». Los partials de columna `vouchers/partials/pdf.blade.php` y
`xml.blade.php` renderizan el botón **incondicionalmente**, sin mirar si la
columna tiene valor.

El escenario es alcanzable y está reconocido por el propio código bajo revisión:
la línea 228 regenera el XML precisamente porque existen filas creadas con
`xml_path`/`pdf_path` en `NULL` («fallo al generar la primera vez» —
`VoidedForm::send()` persiste el `Voided` en la línea 63 y sólo escribe los paths
más abajo, así que cualquier excepción entre ambos puntos deja la fila sin
ficheros). Al pulsar el icono de PDF sobre una de esas filas,
`FilesystemAdapter::download(null)` falla y el operador se lleva un error 500 de
Livewire sin ninguna explicación.

**Fix:** replicar la guarda de `downloadCDR()` en ambos métodos (idealmente
extrayendo un privado común) y, en los partials compartidos, condicionar el botón
a que la columna tenga valor:

```php
private function downloadOrWarn(?string $path, string $label)
{
    if (empty($path) || ! Storage::exists($path)) {
        $this->dispatch('swal', [
            'icon' => 'info',
            'title' => "Sin {$label}",
            'text' => "Esta comunicación de baja no tiene {$label} archivado.",
        ]);

        return;
    }

    return Storage::download($path);
}
```

---

#### WR-05: Ninguna protección contra doble envío, y `markRejected()` no respeta el invariante que `classifyFailure()` declara

**Archivo:** `resources/views/voideds/partials/response.blade.php:19, 31, 61, 74`;
`app/Services/Sunat/VoidedTicketFlowService.php:245-252`

**Problema:** los cuatro botones que disparan `sendXml` inician una llamada SOAP
que puede tardar decenas de segundos y ninguno lleva `wire:loading.attr="disabled"`
ni `wire:target`. La fase acaba de convertir el reloj de las filas pendientes —las
que el operador va a pulsar repetidamente— en un disparador de red, de modo que
el patrón «no pasó nada, vuelvo a hacer clic» es el escenario esperable.

Con dos peticiones realmente concurrentes (dos pestañas, dos operadores, o
recargar y reintentar), ambas leen la fila antes de que la primera persista el
ticket, y ambas llegan a `sendXmlFile()`. La segunda recibe el error 2109 y
`sendAndResolve()` la clasifica con `markRejected()` (líneas 52-54), que **no
tiene** el guardián de «desenlace ya confirmado» que sí aplica `classifyFailure()`
(líneas 185-194). El comentario de `classifyFailure()` declara el invariante en
términos absolutos —«un desenlace ya confirmado por un CDR de SUNAT no se
reescribe nunca, sin importar qué falle después»— pero de los tres escritores de
estado sólo uno lo aplica: `markRejected()` y `markPending()` sobrescriben sin
mirar. Un invariante que vive en el llamador y no en el escritor se rompe en
cuanto se añade una vía de entrada, y la Fase 3 va a añadir una.

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

#### WR-06: El mensaje crudo de la excepción se muestra al operador, y ninguna de las dos ramas de error registra nada en el log

**Archivo:** `app/Livewire/VoidedTable.php:186` y `:271`

**Problema:** `'text' => $e->getMessage()` vuelca al `swal` el texto de cualquier
`Throwable`: `SoapFault` con la URL del endpoint y detalles del WSDL,
`QueryException` con SQL y nombres de columnas, `UnableToWriteFile` con rutas
absolutas del bucket de DO Spaces, o el `TypeError` de CR-01. Es divulgación de
detalle interno a un operador de patio y, a la vez, ruido incomprensible para él.

Peor: ninguna de las dos ramas llama a `Log::error()`. `markPending()` sí registra
(línea 231) y `classifyFailure()` también en su rama de invariante (línea 187),
pero los dos `catch` del componente no dejan rastro: si el operador cierra el
modal, el mensaje se pierde para siempre y no queda en `storage/logs/laravel.log`.
Contradice la convención declarada en `.claude/CLAUDE.md` («`Log::error()` para
fallos; controladores atrapan, registran y devuelven respuesta»).

**Fix:**

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

**Archivo:** `app/Livewire/VoidedTable.php:157, 221-222, 345-357`

**Problema:** el docblock de `cdrPathFor()` declara que el RUC sale de «la empresa
dueña del documento, no la empresa de la sesión que dispara la acción», pero las
dos construcciones del cliente SUNAT siguen siendo
`new UtilService(session('company'))`. `UtilService::getSee()`
(`app/Services/Sunat/UtilService.php:27-40`) deriva de esa empresa el certificado,
el usuario/clave SOL **y el endpoint** (`FE_PRODUCCION` vs `FE_BETA`, según
`$company->production`), mientras que `cdrPathFor()` elige el directorio con
`$voided->production`. Son dos fuentes de verdad distintas para el mismo eje.

Hoy `builder()` mantiene ambas alineadas para las filas visibles, así que la
divergencia sólo se alcanza explotando el hueco de autorización (IN-01) — pero
el comentario induce a creer que la fila ya es la fuente de verdad del ámbito
cuando sólo lo es para el nombre del fichero, y el día que se añada una acción
masiva o un selector, se consultará el ticket con las credenciales del
contribuyente equivocado archivando el ZIP bajo el RUC del otro.

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
externa en `app/Livewire/VoidedTable.php:146-150`)

**Problema:** `resolveTicket()` está documentado como «público a propósito (D-11):
la Fase 2 lo reutiliza para reconsultar anulaciones pendientes desde el listado»,
y la Fase 3 lo reutilizará desde otro punto de entrada. Sin embargo no comprueba
nada sobre el estado de la fila que recibe: lo único que impide invocarlo sobre
una fila `Aceptada` es la condición de tres términos escrita en `sendXml()`.

Si un llamador futuro omite esa guarda —o la copia mal— la secuencia es
destructiva: una fila `Aceptada` tiene `sunat_ticket` en `null` (se limpia en la
línea 142), así que `$see->getStatus(null)` falla, el `catch` de la línea 91 llama
a `markPending()` y **`markPending()` sobrescribe `sunatResponse` completo**
(líneas 221-228), destruyendo el `cdrResponse` con el código, la descripción y las
observaciones del CDR que SUNAT devolvió. La fila pasa a `Pendiente` con un ticket
nulo, es decir, al callejón sin salida de WR-03, y el registro de la aceptación
sólo sobrevive en el ZIP del disco.

La asimetría es evidente al comparar con `classifyFailure()`, que sí abre con el
guardián del invariante. El servicio se describe a sí mismo como «único punto del
código base que envía una comunicación de baja y consulta su estado»; siendo así,
la precondición pertenece al servicio.

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

### Info

#### IN-01: Las acciones públicas siguen aceptando cualquier `Voided` por id, sin propiedad ni permiso

**Archivo:** `app/Livewire/VoidedTable.php:101, 106, 125, 140, 323`
**Problema:** `downloadPDF()`, `downloadXML()`, `downloadCDR()`, `sendXml()` y
`showResponse()` son endpoints Livewire invocables desde el cliente con cualquier
id; el binding de modelo no pasa por `builder()`, único sitio que filtra
`branch_id`/`company_id`/`production`. Un usuario de la empresa A puede leer el
`sunatResponse` de la empresa B, descargar su XML firmado, su RIDE y su CDR, y
disparar envíos o reconsultas contra SUNAT sobre documentos ajenos. Verificado que
sigue presente en el código actual; la fase amplió la superficie con
`downloadCDR()` (nuevo) y con la reconsulta.
**Fix:** el previsto para la **Fase 4** (CR-02/CR-04): helper `scopedVoided()` con
`abort_unless()` sobre los tres ejes + `$this->authorize()` con el permiso Spatie
correspondiente. Se registra como Info por indicación de alcance; **no abrir un
segundo frente de trabajo**.

#### IN-02: El ZIP del CDR se archiva con un nombre que no corresponde al comprobante que certifica

**Archivo:** `app/Livewire/VoidedTable.php:351-357`
**Problema:** `cdrPathFor()` compone `R-{ruc}-RC-{fecGeneracion:Ymd}-{correlativo}.zip`
mientras el XML del mismo documento se guarda con `$voidedGreenter->getName()`,
que Greenter define como `{ruc}-RA-{fecComunicacion:Ymd}-{correlativo}`
(`vendor/greenter/core/src/Core/Model/Voided/Voided.php:159-181`). Divergen el
prefijo (`RC` en vez de `RA`) y la fecha (`fecGeneracion` en vez de
`fecComunicacion`). `VoidedForm::send():87` usa `getName()`, así que el mismo
documento se archiva bajo dos nomenclaturas según qué flujo resuelva el ticket.
No rompe la descarga (`cdr_path` guarda la ruta usada), pero deja el CDR —la
prueba documental de la baja— sin cruzar con su XML.
**Fix:** Fuera de alcance de esta fase (WR-01 ya registrado). Cuando se aborde,
derivar el nombre de `getName()` y prever tolerancia de lectura para los
`cdr_path` históricos.

#### IN-03: `VoidedStatus::badgeColor()` devuelve `danger`, que no es un color de WireUI

**Archivo:** `app/Enums/VoidedStatus.php:20-27`, consumido en
`resources/views/voideds/partials/response.blade.php:34, 44`
**Problema:** el pack de colores del badge de WireUI 2 no define `danger`
(`negative` es el equivalente); `ComponentPack::get()` devuelve la propia cadena,
de modo que el badge acaba con `class="danger"`, inexistente. El estado más
importante del listado se pinta sin color, justo lo contrario del objetivo de D-06.
**Fix:** fuera de alcance (WR-07 ya registrado). `positive` / `warning` / `negative`.

#### IN-04: Rama muerta en el filtro: `$value === ''` es inalcanzable

**Archivo:** `app/Livewire/VoidedTable.php:41-43`
**Problema:** `SelectFilter::getKeys()` descarta las claves de longitud cero
(`->filter(fn ($value) => strlen($value) > 0)`,
`vendor/rappasoft/laravel-livewire-tables/src/Views/Filters/SelectFilter.php:25-32`)
y `validate()` devuelve `false` para `''`, así que el paquete elimina el filtro
antes de invocar la closure. La opción `'' => 'Todos'` sí es necesaria (la vista
sólo renderiza las opciones declaradas); la guarda no.
**Fix:** eliminar el `if` y dejar un comentario explicando que el paquete ya
descarta el valor vacío.

#### IN-05: Cadena mágica `sin-estado` / `SIN ESTADO` duplicada entre componente y vista

**Archivo:** `app/Livewire/VoidedTable.php:38` y
`resources/views/voideds/partials/response.blade.php:64, 77, 86`
**Problema:** el valor del filtro y la etiqueta del badge se repiten como literales
en dos archivos y en tres hojas distintas de la vista; renombrar uno deja los
otros descolgados sin ningún error.
**Fix:** una constante en el componente o un método estático en `VoidedStatus`
(`VoidedStatus::unknownLabel()`) consumido por ambos.

#### IN-06: `sendXml()` sigue por encima de 130 líneas con seis salidas, y `$directory` está duplicado

**Archivo:** `app/Livewire/VoidedTable.php:140-274` y `:219`, `:353`
**Problema:** la extracción de `dispatchPendingSwal()` eliminó los cuatro payloads
duplicados de «En proceso» (mejora real respecto de la revisión anterior), pero el
método sigue muy por encima de la convención del proyecto («métodos de 20 a 50
líneas; los más grandes delegan en servicios», `.claude/CLAUDE.md`). El ternario
`$voided->production ? 'sunat/' : 'sunat/beta/'` aparece dos veces en el archivo.
**Fix:** extraer `reconsultarTicket(Voided $voided)` y `reenviarXml(Voided $voided)`;
mover el directorio a un accesor del modelo (`Voided::storageDirectory()`).

#### IN-07: `cdrPathFor()` lee `$voided->company['ruc']` sin ninguna comprobación

**Archivo:** `app/Livewire/VoidedTable.php:354`
**Problema:** si el JSON `company` no trae la clave `ruc`, PHP emite un warning y
devuelve `null`, produciendo `sunat/cdr/R--RC-20260805-12.zip`; `Storage::put()`
lo acepta y el CDR queda archivado con un nombre corrupto sin error visible.
**Alcanzabilidad, verificada:** la columna `company` es `json` **NOT NULL**
(`2024_12_16_111346_create_voideds_table.php:24`), `VoidedObserver::creating()` la
puebla siempre, y el RUC está validado como `digits:11` con
`regex:/^(10|20)\d{9}$/` (`app/Livewire/CompanyCreate.php:54-59`), así que tampoco
hay vector de path traversal. Se degrada a Info respecto de la revisión anterior:
requiere una fila insertada saltándose el observador, y hoy no existe tal código.
**Fix:** `throw_if(blank(data_get($voided->company, 'ruc')), new \RuntimeException(...))`.

#### IN-08: El código `99` de SUNAT («proceso con errores») se clasifica como fallo de transporte

**Archivo:** `app/Services/Sunat/VoidedTicketFlowService.php:102-112`
**Problema:** la normalización a `(int) $code === 98` es correcta y cierra el
defecto del `'0098'`. Pero cualquier otro código sin CDR cae en
`fallo_transporte`, incluido el `99` («proceso con errores»), que es un desenlace
**terminal** de SUNAT y no un problema de comunicación. Si se diera esa
combinación, la fila quedaría en `Pendiente` para siempre y cada reconsulta
repetiría el mismo resultado bajo la etiqueta «no se pudo confirmar el estado»,
que atribuye a la red algo que SUNAT sí respondió.
**Fix:** un tercer motivo (`proceso_con_errores`) con su etiqueta propia, o al
menos una entrada de log distinguible para poder detectarlo en producción.

#### IN-09: Botones sin `type`, sin etiqueta accesible y con un atributo `xs` inerte

**Archivo:** `resources/views/voideds/partials/response.blade.php:6, 19, 31, 41, 61, 74, 83`
**Problema:** son `<button>` HTML planos, así que el atributo `xs` no hace nada
—resto de un componente anterior— y sin `type="button"` heredarían `submit` dentro
de un formulario. Al ser botones sólo-icono no tienen texto accesible: la fase
añadió `title` únicamente a la hoja nueva (línea 74) y dejó las otras seis sin él,
incluida la del reloj, que es la que cambió de significado (WR-02).
**Fix:** `type="button"` y `title`/`aria-label` descriptivos en las siete; retirar
el `xs`.

#### IN-10: El árbol de decisión no tiene cobertura automatizada

**Archivo:** `app/Livewire/VoidedTable.php:140-274`;
`app/Services/Sunat/VoidedTicketFlowService.php`
**Problema:** ni el árbol de tres ramas, ni `canResendXml()`, ni `cdrPathFor()`,
ni `dispatchPendingSwal()`, ni la clasificación de `resolveTicket()` tienen
pruebas, pese a gobernar el estado fiscal frente a SUNAT y pese a que la UAT dejó
dos criterios bloqueados por las limitaciones de beta. Se mantiene como Info por
el contexto conocido del proyecto (la suite corre contra la BD configurada y hoy
está roja de base), pero eso es justamente lo que convendría desbloquear antes de
la Fase 3.
**Fix:** `Livewire::test(VoidedTable::class)->call('sendXml', $id)` por hoja del
árbol con `VoidedTicketFlowService` mockeado, y tests unitarios del servicio con
un `See` doblado, sobre sqlite en memoria.

---

_Revisado: 2026-08-05T21:10:00Z_
_Revisor: Claude (gsd-code-reviewer)_
_Profundidad: standard_
