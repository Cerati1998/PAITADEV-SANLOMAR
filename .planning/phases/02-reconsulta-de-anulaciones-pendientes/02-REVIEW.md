---
phase: 02-reconsulta-de-anulaciones-pendientes
reviewed: 2026-08-05T17:47:40Z
depth: standard
files_reviewed: 2
files_reviewed_list:
  - app/Livewire/VoidedTable.php
  - resources/views/voideds/partials/response.blade.php
findings:
  critical: 2
  warning: 9
  info: 6
  total: 17
status: issues_found
---

# Fase 2: Informe de revisión de código

**Revisado:** 2026-08-05T17:47:40Z
**Profundidad:** standard
**Archivos revisados:** 2
**Estado:** issues_found

## Resumen

Se revisó el árbol de tres ramas de `VoidedTable::sendXml()`, el nuevo helper
privado `cdrPathFor()`, el `SelectFilter` de estado y los badges añadidos a
`voideds/partials/response.blade.php`, cruzando cada rama contra
`VoidedTicketFlowService`, `VoidedObserver`, la migración
`2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php`, el modelo
Greenter `Voided` y el listener `Livewire.on('swal')` de
`resources/views/layouts/dashboard.blade.php`.

La rama de reconsulta hace lo que promete para el caso feliz (Pendiente + ticket
→ `resolveTicket()` en vez de reenviar), y el filtro compone correctamente sobre
`builder()` sin ensanchar el ámbito multi-tenant. Pero el árbol de guardas se
apoya en un supuesto que el código no garantiza —que «ticket presente y estado
distinto de Pendiente» implica `Rechazada`— y esa grieta deja filas con ticket
vivo sin ninguna vía de reconsulta, mostrando además al operador un rechazo de
SUNAT que nunca ocurrió. Eso ataca directamente el core value del proyecto: el
estado que muestra el sistema deja de coincidir con el que SUNAT tiene, sin
salida por la interfaz.

Además hay defectos de menor calibre pero comprobables: el swal de detalle nunca
llega a verse cuando el `catch` de la reconsulta dispara dos eventos, el nombre
del ZIP del CDR no coincide con el nombre del comprobante que certifica, el
badge del estado `Rechazada` se renderiza sin color porque `danger` no existe en
WireUI, y la mitad «empresa dueña de la fila» de D-04 quedó aplicada al path
pero no a las credenciales SUNAT.

Se confirma que los hallazgos de autorización solapan con CR-02/CR-04 ya
programados para la Fase 4; se dejan anotados como duplicado explícito, salvo la
asimetría nueva que esta fase introdujo (WR-03), que no está cubierta por ese
trabajo planificado.

## Narrative Findings (AI reviewer)

### Críticos (BLOCKER)

#### CR-01: La rama «Desenlace confirmado» captura filas con ticket vivo y les niega la reconsulta, afirmando un rechazo que nunca ocurrió

**Archivo:** `app/Livewire/VoidedTable.php:195-207` (y su reflejo en `resources/views/voideds/partials/response.blade.php:58-76`)

**Problema:**
La rama 2 se condiciona sólo por el ticket:

```php
if (! is_null($voided->sunat_ticket)) {
    // "Rechazada que conserva ticket"
```

El comentario (y el plan `02-01-PLAN.md`, línea 205) asumen que, filtrada ya la
rama 1, «aquí sólo cae `Rechazada` que conserva ticket». Ese supuesto no está
garantizado por el código: existe al menos un estado alcanzable con
`sunat_ticket != null` y `sunat_status === null`.

`VoidedTicketFlowService::sendAndResolve()` persiste el ticket con un `save()`
propio (líneas 73-74) y **después** llama a `resolveTicket()`, que abre otra
llamada de red SOAP. Si el proceso muere entre ambos puntos —`max_execution_time`
agotado durante `getStatus()` (un fatal de PHP, no un `Throwable` atrapable),
OOM, reinicio de php-fpm, o una excepción dentro del propio `save()` de
`markPending()`, que escaparía al `catch` porque es `classifyFailure()` quien
falla— la fila queda con ticket guardado y `sunat_status` en `NULL`. Es
exactamente el modo de fallo que motivó esta fase: SUNAT sí tiene la baja y el
sistema no sabe cómo terminó.

Consecuencias para esa fila:

1. `sendXml()` la desvía a la rama 2 y muestra *«SUNAT ya devolvió el CDR
   rechazando esta comunicación de baja»* — una afirmación falsa sobre un
   tercero fiscal. No existe CDR ni rechazo.
2. Nunca se puede reconsultar: la rama 1 exige `sunat_status === Pendiente`, así
   que el ticket queda huérfano para siempre y el CDR jamás se recupera.
3. En el listado, la fila cae en el `@else` de la vista
   (`response.blade.php:58`): `is_null($row->sunat_ticket)` es falso, así que se
   pinta el interrogante con `showResponse`, que renderiza *«Error al Enviar a
   SUNAT»* (`voideds/partials/sunatResponse.blade.php`, rama histórica) — otra
   afirmación falsa, y encima incoherente con la de `sendXml()`.
4. El filtro «SIN ESTADO» de D-07 la lista como pendiente de atención, pero
   ninguna acción de la interfaz puede resolverla.

Caso secundario de la misma grieta: una fila `Pendiente` **sin** ticket (drift de
datos o corrección manual) no entra en la rama 1, no entra en la rama 2 y
`canResendXml()` devuelve `false` → callejón sin salida «No se puede reenviar».

**Fix:** condicionar por estado, no por la presencia del ticket, y reconsultar
todo ticket vivo cuyo desenlace no esté confirmado por un CDR:

```php
// Rama 1 — Consultar: cualquier ticket vivo sin desenlace confirmado.
if (! is_null($voided->sunat_ticket)
    && $voided->sunat_status !== VoidedStatus::Rechazada
    && $voided->sunat_status !== VoidedStatus::Aceptada) {
    // ... resolveTicket()
}

// Rama 2 — Desenlace confirmado: sólo Rechazada con ticket (D-12).
if ($voided->sunat_status === VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket)) {
    // ... swal "Desenlace confirmado"
}
```

Y en la vista, ofrecer `sendXml` (reconsulta) cuando la fila histórica tenga
ticket:

```blade
@if (is_null($row->sunat_ticket) && empty($row->sunatResponse))
    {{-- reenvío legítimo --}}
@elseif (! is_null($row->sunat_ticket))
    {{-- ticket vivo sin estado: ofrecer reconsulta --}}
    <button wire:click="sendXml({{ $row->id }})" xs> ... </button>
@else
    {{-- showResponse --}}
@endif
```

---

#### CR-02: Las acciones públicas del componente aceptan cualquier `Voided` por id, sin comprobación de propiedad ni de permiso

**Archivo:** `app/Livewire/VoidedTable.php:101-138, 140-284, 286-306`

**Problema:** `sendXml()`, `showResponse()`, `downloadPDF()`, `downloadXML()` y
`downloadCDR()` son métodos públicos de un componente Livewire, es decir
endpoints invocables directamente desde el cliente con cualquier id. El binding
de modelo resuelve la fila sin pasar por `builder()`, que es el único sitio que
filtra `branch_id` / `company_id` / `production`. Un usuario autenticado de la
empresa A puede leer el `sunatResponse` completo de la empresa B, descargar su
PDF/XML/CDR y disparar envíos o reconsultas contra SUNAT sobre documentos
ajenos. Tampoco hay comprobación de permiso Spatie dentro del componente: el
gate `can:` sólo protege la ruta que renderiza la página, no la acción.

**Duplicado señalado:** esto solapa con **CR-02 y CR-04 de la revisión de la
Fase 1**, ya programados para la **Fase 4** (propiedad multi-tenant y permisos
en las acciones de anulación). Se registra por completitud del informe de esta
fase; no debería abrirse un segundo frente de trabajo. Lo que **no** cubre ese
trabajo planificado está aislado en WR-03.

**Fix (el previsto en Fase 4):** re-resolver la fila dentro del ámbito de sesión
y autorizar en cada acción, p. ej.

```php
private function scopedVoided(Voided $voided): Voided
{
    abort_unless(
        $voided->company_id === session('company')->id
            && $voided->branch_id === session('branch')->id
            && $voided->production === session('company')->production,
        404
    );

    return $voided;
}
```

más `$this->authorize('voideds.send')` (o el nombre de permiso del seeder) al
entrar en `sendXml()`.

---

### Advertencias (WARNING)

#### WR-01: El ZIP del CDR se archiva con un nombre que no corresponde al comprobante que certifica

**Archivo:** `app/Livewire/VoidedTable.php:314-320`

**Problema:** `cdrPathFor()` compone
`R-{ruc}-RC-{fecGeneracion:Ymd}-{correlativo}.zip`, mientras que el XML firmado
del mismo documento se guarda (línea 235) con `$voidedGreenter->getName()`, que
Greenter define como `{ruc}-RA-{fecComunicacion:Ymd}-{correlativo}`
(`vendor/greenter/core/src/Core/Model/Voided/Voided.php:159-181`). Hay dos
divergencias: el prefijo (`RC` en vez de `RA`, que es el que SUNAT usa para las
comunicaciones de baja) y la fecha (`fecGeneracion` en vez de
`fecComunicacion`, que en la práctica difieren, ya que la generación es la fecha
de los comprobantes y la comunicación la del envío). Resultado: el CDR —la
prueba documental de la baja ante SUNAT, y lo que se entrega a contabilidad o a
una fiscalización— queda archivado con un nombre que no cruza con su XML ni con
la nomenclatura oficial. El defecto es anterior a esta fase (venía de la línea
que `cdrPathFor()` sustituye), pero la fase lo consolidó en un helper y lo
extendió a la nueva vía de reconsulta, así que ahora es el único punto donde
corregirlo.

**Fix:** derivar el nombre de la misma fuente que el XML:

```php
private function cdrPathFor(Voided $voided): string
{
    $directory = $voided->production ? 'sunat/' : 'sunat/beta/';
    $name = $voided->company['ruc'].'-RA-'.$voided->fecComunicacion->format('Ymd').'-'.$voided->correlativo;

    return $directory.'cdr/R-'.$name.'.zip';
}
```

(o construir el Greenter `Voided` con `VoidedService::getVoided()` y usar
`getName()`). Ojo: cambiar la ruta rompe la correspondencia con los ZIP ya
archivados, así que el cambio necesita una migración de datos o una tolerancia
de lectura para los `cdr_path` históricos.

---

#### WR-02: Dos `swal` en la misma respuesta: el detalle que se acaba de renderizar nunca se ve

**Archivo:** `app/Livewire/VoidedTable.php:183-189`

**Problema:** en el `catch` de la rama de reconsulta se llama a
`showResponse($voided)` —que hace `dispatch('swal', [...'html'...])`— e
inmediatamente después se despacha un segundo `swal` de error. El listener es
`Livewire.on('swal', data => Swal.fire(data[0]))`
(`resources/views/layouts/dashboard.blade.php:76-78`): ambos eventos llegan en la
misma respuesta y se procesan seguidos, de modo que el segundo `Swal.fire()`
sustituye el popup del primero. El operador **nunca** ve el detalle del
comprobante; sólo el mensaje de error. La intención declarada («además del
detalle conviene que el usuario vea el mensaje del error») no se cumple.

**Fix:** componer un solo swal con ambas cosas, en vez de despachar dos:

```php
$html = view('voideds.partials.sunatResponse', ['document' => $voided])->render();

$this->dispatch('swal', [
    'icon' => 'error',
    'title' => 'Error al consultar el comprobante',
    'html' => $html.'<p class="mt-4 text-red-600">'.e($e->getMessage()).'</p>',
]);
```

(La misma corrección aplica al despacho doble equivalente que quede en otras
tablas si se replica el patrón.)

---

#### WR-03: D-04 quedó aplicado a medias: la ruta del CDR usa la empresa de la fila, pero las credenciales SUNAT siguen siendo las de la sesión

**Archivo:** `app/Livewire/VoidedTable.php:151, 223, 314-320`

**Problema:** el docblock de `cdrPathFor()` declara que el RUC sale de «la
empresa dueña del documento, no la empresa de la sesión que dispara la acción»,
pero el cliente SUNAT que hace el envío y la consulta se sigue construyendo con
`new UtilService(session('company'))`, es decir con el certificado, el usuario
SOL, el `client_id/secret` y el flag `production` de la empresa **de la sesión**.
Si ambas empresas divergen —lo cual sólo es alcanzable hoy explotando CR-02, pero
también lo será el día que se añada un selector o una acción masiva— el sistema
consultaría el ticket con las credenciales del contribuyente equivocado y
archivaría el ZIP resultante bajo el RUC del otro, mezclando documentación fiscal
entre inquilinos. El riesgo real de esta advertencia no es el escenario
explotado (eso es CR-02), sino que el comentario induce a creer que la fila ya es
la fuente de verdad del ámbito, cuando sólo lo es para el nombre del fichero.

**Fix:** derivar también la empresa del cliente SUNAT de la fila, o dejar
constancia explícita de la precondición:

```php
$company = Company::findOrFail($voided->company_id);
$util = new UtilService($company);
```

Si se prefiere no cambiar el origen de las credenciales todavía, añadir una
guarda dura que impida la divergencia:
`abort_if($voided->company_id !== session('company')->id, 404);`.

---

#### WR-04: `cdrPathFor()` lee `$voided->company['ruc']` sin ninguna comprobación

**Archivo:** `app/Livewire/VoidedTable.php:317`

**Problema:** `company` es una columna JSON poblada por
`VoidedObserver::creating()`, que a su vez depende de `Company::find()`. Si el
JSON no trae la clave `ruc` (empresa borrada, fila importada, observador
saltado por un `insert()` directo o por un seeder), PHP 8 emite un warning
«Undefined array key» y devuelve `null`: la ruta resultante es
`sunat/cdr/R--RC-20260805-12.zip`. `Storage::put()` acepta esa ruta sin
protestar, así que el CDR se archiva silenciosamente con un nombre corrupto y
`cdr_path` queda apuntando a él. No hay error visible en ningún punto.

**Fix:** validar y fallar ruidosamente antes de tocar el almacenamiento:

```php
$ruc = data_get($voided->company, 'ruc');

throw_if(blank($ruc), new \RuntimeException(
    "La anulación {$voided->id} no tiene RUC en su JSON company; no se puede archivar el CDR."
));
```

---

#### WR-05: El mensaje crudo de la excepción se muestra al usuario final

**Archivo:** `app/Livewire/VoidedTable.php:188` (nuevo en esta fase) y `:281` (preexistente)

**Problema:** `'text' => $e->getMessage()` vuelca al swal el texto de cualquier
`Throwable`: `SoapFault` con la URL del endpoint y detalles del WSDL,
`QueryException` con SQL y nombres de columnas, `UnableToWriteFile` con rutas
absolutas del servidor o del bucket de DO Spaces, o un `TypeError` de PHP
(ver WR-09). Es divulgación de información interna a un operador de patio, y
además ruido incomprensible para él. Nótese que ninguna de estas ramas registra
nada en el log, así que el mensaje que se pierde en el swal no queda en ningún
otro sitio.

**Fix:** registrar el detalle y mostrar un mensaje acotado:

```php
Log::error('Fallo al consultar la comunicación de baja', [
    'voided_id' => $voided->id,
    'exception' => $e,
]);

$this->dispatch('swal', [
    'icon' => 'error',
    'title' => 'Error al consultar el comprobante',
    'text' => 'No se pudo completar la consulta con SUNAT. El detalle quedó registrado; reintente en unos minutos.',
]);
```

---

#### WR-06: Los botones que llaman a SUNAT no tienen ninguna protección contra doble envío

**Archivo:** `resources/views/voideds/partials/response.blade.php:19, 31, 61`; `app/Livewire/VoidedTable.php:140`

**Problema:** los tres botones que disparan `sendXml` inician una llamada SOAP
que puede tardar decenas de segundos, sin indicador de carga ni deshabilitado
(`wire:loading.attr` / `wire:target` no aparecen en ninguno). La fase acaba de
convertir el icono del reloj —el de las filas pendientes, justamente las que el
operador va a pulsar repetidamente— en un disparador de red, así que el patrón
«no pasó nada, vuelvo a hacer clic» es el escenario esperable.

Con dos peticiones realmente concurrentes (dos pestañas, dos operadores, o el
usuario recargando y reintentando), ambas leen la fila antes de que la primera
persista el ticket y ambas llegan a `sendXmlFile()`. La segunda recibe el error
2109 y `VoidedTicketFlowService::sendAndResolve()` la clasifica con
`markRejected()` (líneas 52-54), que **no** tiene el guardián de «desenlace ya
confirmado» que sí tiene `classifyFailure()`. Resultado posible: una baja que
SUNAT aceptó queda escrita en la BD como `Rechazada` — divergencia de estado
irrecuperable desde la interfaz.

**Fix (dos capas):** en la vista, bloquear el botón mientras la acción está en
vuelo:

```blade
<button wire:click="sendXml({{ $row->id }})"
        wire:loading.attr="disabled"
        wire:target="sendXml({{ $row->id }})"
        type="button">
```

y en el servidor, releer la fila bajo bloqueo antes de decidir la rama:

```php
$voided = Voided::whereKey($voided->getKey())->lockForUpdate()->firstOrFail();
```

---

#### WR-07: El badge de `Rechazada` se renderiza sin color: `danger` no es un color de WireUI

**Archivo:** `resources/views/voideds/partials/response.blade.php:34, 44` (vía `App\Enums\VoidedStatus::badgeColor()`)

**Problema:** `VoidedStatus::Rechazada->badgeColor()` devuelve `'danger'`, pero
el pack de colores del badge de WireUI 2
(`vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Solid.php` y
`src/Enum/Packs/Color.php`) no define `danger`: los válidos son `primary`,
`secondary`, `positive`, `negative`, `warning`, `info`, `dark`, `base` y los
nombres de la paleta Tailwind. `ComponentPack::get()` hace
`data_get($this->all(), $attribute) ?? $attribute`, es decir devuelve la propia
cadena, y el badge acaba con `class="danger"` — una clase inexistente. El estado
más importante del listado, el rechazo, se pinta sin color mientras que
`Aceptada` (`primary`, índigo de marca) y `Pendiente` (`warning`, ámbar) sí se
distinguen. Se cumple así justo lo contrario del objetivo de D-06. De paso,
`Aceptada => 'primary'` es semánticamente flojo teniendo `positive` disponible,
y choca con el icono verde que lleva al lado.

**Fix:** en `app/Enums/VoidedStatus.php`:

```php
public function badgeColor(): string
{
    return match ($this) {
        self::Aceptada => 'positive',
        self::Pendiente => 'warning',
        self::Rechazada => 'negative',
    };
}
```

(El mismo `danger` inerte aparece en
`resources/views/vouchers/partials/sunatResponse.blade.php:39`; conviene
corregirlo en el mismo barrido.)

---

#### WR-08: Las filas Pendiente pierden el acceso de sólo lectura al detalle, y el icono cambia de significado sin avisar

**Archivo:** `resources/views/voideds/partials/response.blade.php:13-23`

**Problema:** el icono del reloj pasó de abrir `showResponse` (lectura local:
motivo del pendiente, código, mensaje y ticket) a disparar `sendXml` (consulta
real contra SUNAT). El icono, el color y el tooltip son idénticos a los de
antes, así que el operador no tiene forma de saber que el mismo gesto ahora sale
a un tercero. Y, más importante, **ya no hay ninguna vía en el listado para leer
el detalle de una fila pendiente**: para ver el ticket o el motivo hay que
golpear SUNAT. Sobre un lote de pendientes filtrado con el nuevo `SelectFilter`,
eso significa una llamada SOAP por cada fila que uno quiera simplemente
inspeccionar.

**Fix:** conservar ambas acciones — por ejemplo el badge como disparador de
lectura y el icono como reconsulta— y etiquetar la acción remota:

```blade
<button wire:click="sendXml({{ $row->id }})" type="button"
        title="Consultar el estado del ticket en SUNAT" aria-label="Consultar en SUNAT">
    <x-wire-icon name="clock" class="w-6.5 h-6.5 text-amber-500" solid />
</button>
<button wire:click="showResponse({{ $row->id }})" type="button" title="Ver detalle guardado">
    <x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
</button>
```

---

#### WR-09: `empty($voided->xml_path)` no comprueba que el XML exista; si falta, se escribe un rechazo que SUNAT nunca emitió

**Archivo:** `app/Livewire/VoidedTable.php:230-245`

**Problema:** la guarda de regeneración sólo mira la columna. Si `xml_path` está
poblado pero el fichero no está en el disco (purga, migración de bucket, fallo
previo de `Storage::put`), `$voided->xml` ejecuta
`Storage::get($this->xml_path)`, que en Laravel 11 devuelve **`null`** en vez de
lanzar (las excepciones del disco están desactivadas por defecto). Ese `null`
llega a `$flow->sendAndResolve($voided, $xml, ...)`, cuyo parámetro está tipado
`string`, y produce un `TypeError`. El `catch (\Throwable)` lo recoge, y
`classifyFailure()` —con `sunat_ticket` nulo— llama a `markRejected()`: la fila
queda marcada **Rechazada por SUNAT** por un fallo puramente local, y el usuario
ve el texto del `TypeError` en el swal (WR-05). Es incoherente con el cuidado
que sí tiene `downloadCDR()` (línea 127), que comprueba `Storage::exists()`
antes de tocar el fichero.

**Fix:**

```php
if (empty($voided->xml_path) || ! Storage::exists($voided->xml_path)) {
    // ... regenerar XML y PDF
} else {
    $xml = $voided->xml;
}
```

---

### Info

#### IN-01: Rama muerta en el filtro: `$value === ''` es inalcanzable

**Archivo:** `app/Livewire/VoidedTable.php:41-43`
**Problema:** `SelectFilter::validate()` construye las claves válidas con
`->filter(fn ($value) => strlen($value) > 0)`
(`vendor/rappasoft/laravel-livewire-tables/src/Views/Filters/SelectFilter.php:26-41`),
de modo que el valor `''` se descarta y el filtro se elimina antes de invocar la
closure. La guarda nunca se ejecuta. (La opción `'' => 'Todos'` sí hace falta:
la vista del paquete sólo renderiza las opciones declaradas.)
**Fix:** eliminar el `if` y dejar el comentario que explique que el paquete ya
descarta el valor vacío.

#### IN-02: Cadena mágica `sin-estado` / `SIN ESTADO` duplicada entre componente y vista

**Archivo:** `app/Livewire/VoidedTable.php:38` y `resources/views/voideds/partials/response.blade.php:64, 73`
**Problema:** el valor del filtro y la etiqueta del badge se repiten como
literales en dos archivos; si alguien renombra uno, el otro queda descolgado sin
error.
**Fix:** una constante (`public const SIN_ESTADO = 'sin-estado';`) o un método
estático en `VoidedStatus` (`VoidedStatus::unknownLabel()`) consumido por ambos.

#### IN-03: `$directory` duplicado

**Archivo:** `app/Livewire/VoidedTable.php:221` y `:316`
**Problema:** `$voided->production ? 'sunat/' : 'sunat/beta/'` aparece dos veces
en el mismo archivo (y otras seis en el resto de `app/`).
**Fix:** un accesor en el modelo, p. ej. `Voided::storageDirectory()`.

#### IN-04: `sendXml()` alcanza ~145 líneas con cuatro salidas y payloads de swal duplicados

**Archivo:** `app/Livewire/VoidedTable.php:140-284`
**Problema:** el texto «Aún en proceso» está escrito dos veces literalmente
(líneas 158-162 y 174-178) y «En proceso» otras dos (252-257 y 268-273); el
método excede la convención del proyecto («métodos de 20 a 50 líneas; los más
grandes delegan en servicios», `.claude/CLAUDE.md`). Los textos duplicados
derivarán en cuanto alguien edite uno solo.
**Fix:** extraer `reconsultarTicket(Voided $voided): void`,
`reenviarXml(Voided $voided): void` y un privado
`dispatchPendienteSwal(string $title): void`.

#### IN-05: Botones sin `type`, sin etiqueta accesible y con un atributo `xs` inerte

**Archivo:** `resources/views/voideds/partials/response.blade.php:6, 19, 31, 41, 61, 70`
**Problema:** son `<button>` HTML planos (no `x-wire-button`), así que el
atributo `xs` no hace nada —resto de un componente anterior— y sin
`type="button"` heredan el `submit` por defecto si algún día quedan dentro de un
formulario. Al ser botones sólo-icono, tampoco tienen texto accesible.
**Fix:** añadir `type="button"` y `title`/`aria-label` descriptivos, y eliminar
el `xs`.

#### IN-06: El árbol de tres ramas no tiene cobertura automatizada

**Archivo:** `app/Livewire/VoidedTable.php:140-284`
**Problema:** `tests/` sólo contiene los tests por defecto de Jetstream; ni el
árbol de guardas, ni `canResendXml()`, ni `cdrPathFor()` tienen pruebas, pese a
gobernar el estado fiscal frente a SUNAT. Se registra como Info y no como
Warning por el contexto conocido del proyecto (la suite corre contra la BD
configurada y hoy está rota), pero eso es precisamente lo que habría que
desbloquear.
**Fix:** un test Livewire con `Livewire::test(VoidedTable::class)->call('sendXml', $id)`
por cada hoja del árbol, con `VoidedTicketFlowService` mockeado, sobre sqlite en
memoria.

---

_Revisado: 2026-08-05T17:47:40Z_
_Revisor: Claude (gsd-code-reviewer)_
_Profundidad: standard_
