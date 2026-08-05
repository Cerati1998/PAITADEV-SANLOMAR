---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
reviewed: 2026-08-05T00:00:00Z
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
  critical: 5
  warning: 13
  info: 0
  total: 18
status: issues_found
---

# Phase 01: Code Review Report

**Reviewed:** 2026-08-05
**Depth:** standard
**Files Reviewed:** 8
**Status:** issues_found

> Esta revisión **sustituye** al informe anterior de 2026-08-04. Los hallazgos previos
> cerrados en los commits 790f4cd, ef1ec8e, e8fc321, 736b028 y 1efbea5 se verificaron
> como resueltos y no se re-reportan.

## Summary

La máquina de estados de `VoidedTicketFlowService` está bien construida en su núcleo:
el ticket se persiste con un `save()` propio antes de cualquier consulta (TICKET-01),
el CDR se archiva mirando el ZIP y no `isSuccess()` (Pitfall 4 — confirmado correcto
contra `ExtService::processResponse()`, que efectivamente puebla `cdrZip` con
`statusCode = 99` y luego marca `success = false` para códigos 100-1999), la aceptación
se decide por el código del propio CDR, y `classifyFailure()` protege un desenlace ya
confirmado. Esa parte resiste la revisión.

Lo que no resiste es el perímetro alrededor de ese núcleo. Las invariantes del dominio
se pueden violar desde el navegador porque las decisiones de confianza se apoyan en
datos que el cliente controla:

- `markVoidedInvoices()` pasó a filtrar por `$voided->production / company_id / branch_id`
  (commit 790f4cd) en lugar de por `session()`, pero esas tres columnas se escriben
  desde propiedades públicas de un `Livewire\Form` que el navegador puede modificar.
  El filtro por `production` que la fase añadió para no cruzar beta con producción es
  precisamente el que se puede invertir (**CR-03**).
- Las acciones de `VoidedTable` resuelven el `Voided` por un ID que envía el cliente y
  ninguna comprueba el ámbito multi-tenant (**CR-02**).
- La única validación de `desMotivoBaja` es de longitud, y ese texto llega `|raw`
  dentro de un `CDATA` del XML que se firma y se envía a SUNAT (**CR-05**).
- La operación privilegiada (`VoidedForm::send()`) no comprueba `vouchers.anulate`;
  el gate vive solo en el método que abre el modal (**CR-04**).

Además, el CDR que esta fase se esfuerza en archivar **no se puede descargar**: la
columna CDR del listado invoca `downloadCDR()`, que `VoidedTable` no define (**CR-01**).

**Sobre CR-06 de la revisión anterior (XSS almacenado por `desMotivoBaja` en la clave
`html` de swal): verificado como resuelto.** `sunatResponse.blade.php` usa `{{ }}` en
todos los puntos de datos, `x-wire-badge` renderiza `{{ $label ?? $slot }}` (escapado),
y `Swal.fire({!! json_encode(session('swal')) !!})` no puede romperse porque `e()`
convierte `<` en `&lt;`, de modo que ningún dato de usuario puede producir `</script>`.
No se re-reporta. Nótese, sin embargo, que ese mismo `desMotivoBaja` sí sigue siendo
inyectable en el XML firmado (**CR-05**): el escapado se arregló en la salida HTML pero
no en la salida XML, que es la que SUNAT recibe.

No se recibió bloque `<structural_findings>`, así que este informe es íntegramente
narrativo.

## Narrative Findings (AI reviewer)

## Critical Issues

### CR-01: El CDR archivado por el nuevo flujo no se puede descargar — `VoidedTable` no define `downloadCDR()`

**File:** `app/Livewire/VoidedTable.php:56-59` (columna) / `resources/views/vouchers/partials/cdr.blade.php:3`

**Issue:** La columna CDR del listado de anulaciones renderiza el parcial compartido
`vouchers.partials.cdr`, que cuando `cdr_path` está poblado emite
`wire:click="downloadCDR({{ $row->id }})"`. `VoidedTable` define `downloadPDF()`,
`downloadXML()`, `sendXml()` y `showResponse()`, pero **no** `downloadCDR()`
(verificado: el método solo existe en `VoucherTable`, `DespatchTable` y
`DespatchTransportistaTable`). Livewire 3 solo permite invocar métodos públicos
declarados en la subclase, así que la llamada revienta con `MethodNotFoundException`.

Esto no es un detalle cosmético heredado: esta fase amplía enormemente el número de
filas con `cdr_path`, porque `resolveTicket()` archiva el ZIP **siempre que llegue**,
tanto en la rama de aceptación como en el rechazo por CDR con código distinto de 0
(`VoidedTicketFlowService.php:115-118`). Es decir, a partir de esta fase prácticamente
toda anulación resuelta muestra el botón de descarga del CDR y **toda pulsación falla**.
El CDR es la prueba documental de la baja ante SUNAT; archivarlo y no poder entregarlo
contradice el valor central de la fase.

**Fix:**
```php
// app/Livewire/VoidedTable.php
public function downloadCDR(Voided $voided)
{
    $this->authorizeVoided($voided); // ver CR-02

    if (empty($voided->cdr_path)) {
        $this->dispatch('swal', [
            'icon' => 'info',
            'title' => 'Sin CDR',
            'text' => 'Esta comunicación de baja todavía no tiene un CDR archivado.',
        ]);

        return;
    }

    return Storage::download($voided->cdr_path);
}
```

---

### CR-02: IDOR multi-tenant en todas las acciones de `VoidedTable` — fuga y escritura cruzada entre empresas

**File:** `app/Livewire/VoidedTable.php:69-77, 79-171, 173-193`

**Issue:** `downloadPDF(Voided $voided)`, `downloadXML(Voided $voided)`,
`sendXml(Voided $voided)` y `showResponse(Voided $voided)` reciben el modelo por
*implicit model binding* a partir de un ID que viaja en el payload del cliente
(`HandleComponents::callMethods()` toma `method` y `params` tal cual del request).
Ninguna comprueba que la fila pertenezca a `session('branch')`, `session('company')` ni
al flag `production` en curso. `builder()` (líneas 218-224) solo acota **el listado**,
no las acciones.

El comentario de `sendXml()` (líneas 81-88) razona explícitamente sobre que "las acciones
de Livewire son invocables directamente desde el cliente" y añade una guarda de reenvío,
pero omite la guarda de ámbito, que es la que el contexto de dominio marca como invariante.

Consecuencias comprobables, todas con solo cambiar un número en la llamada:

1. `downloadPDF` / `downloadXML` entregan el comprobante de baja de otra empresa
   (`Storage::download($voided->pdf_path)` sin filtro).
2. `showResponse` renderiza `sunatResponse`, `sunat_ticket` y el detalle del CDR ajenos
   en el modal.
3. `sendXml` sobre una fila ajena firma y envía con **las credenciales SUNAT de la
   empresa en sesión** (`new UtilService(session('company'))`, línea 112) un XML cuyo RUC
   es de otra empresa; y el desenlace (`markRejected` / `markPending`) se **escribe sobre
   la fila de la otra empresa**, corrompiendo su historial ante SUNAT.
4. Si esa baja llegase a aceptarse, `markVoidedInvoices()` marcaría como anuladas las
   facturas de la otra empresa (usa `$voided->company_id`, que es el ajeno).

**Fix:** guarda de ámbito compartida, invocada como primera línea de las acciones:
```php
// app/Livewire/VoidedTable.php
private function authorizeVoided(Voided $voided): void
{
    abort_unless(
        $voided->branch_id === session('branch')->id
            && $voided->company_id === session('company')->id
            && (bool) $voided->production === (bool) session('company')->production,
        403
    );
}
```
La alternativa robusta y menos propensa al olvido es no aceptar el modelo: recibir el
`int $id` y resolverlo con `$this->builder()->findOrFail($id)`, de modo que el filtro
multi-tenant del listado sea exactamente el mismo que gobierna las acciones.

---

### CR-03: `production`, `company_id` y `branch_id` llegan del cliente — el filtro anti-cruce beta/producción es invertible desde el navegador

**File:** `app/Livewire/Forms/VoidedForm.php:25-29, 37, 46` (consumidor: `app/Services/Sunat/VoidedTicketFlowService.php:261-272`)

**Issue:** `VoidedForm` declara `$branch_id`, `$company_id`, `$production`, `$details` y
`$sunatResponse` como propiedades **públicas** de un `Livewire\Form`. En Livewire 3 todas
las propiedades públicas de un Form se serializan al navegador y se rehidratan desde el
payload salvo que lleven `#[Locked]`; ninguna lo lleva. `VoucherTable::voidReason()`
(líneas 409-411) las siembra desde `session()`, pero entre esa llamada y `sendVoided()`
el cliente puede reescribirlas (`$wire.set('voided.production', true)`). `send()` las
inserta tal cual con `Voided::create($this->all())` (línea 37) y usa `$this->production`
para elegir el directorio de almacenamiento (línea 46).

Esto es una **regresión introducida por esta fase**. El código anterior filtraba con
`session('branch')->id` / `session('company')->id`; el commit 790f4cd movió el filtro al
modelo y añadió `production`:

```php
// VoidedTicketFlowService.php:264-270 — confía en el modelo
->where('branch_id', $voided->branch_id)
->where('company_id', $voided->company_id)
->where('production', $voided->production)
```

El docblock de `markVoidedInvoices()` afirma que "el filtro por production evita que una
baja de prueba en beta marque como anulada la factura real de producción". Con el valor
bajo control del cliente ocurre exactamente lo contrario:

- Empresa en **beta** (`session('company')->production === false`) + `production` forzado
  a `true`: `UtilService::getSee()` sigue apuntando al endpoint beta con `MODDATOS`, beta
  acepta casi cualquier cosa, el CDR llega con código `0` y `markVoidedInvoices()` marca
  como anuladas las facturas **reales de producción** que comparten serie/correlativo.
- Empresa en **producción** + `production` forzado a `false`: la baja se ejecuta de verdad
  contra SUNAT pero queda registrada como beta y marca facturas de beta — la baja real no
  queda reflejada, que es justo el desajuste sistema/SUNAT que la fase busca eliminar.

`company_id` manipulado hace además que el `VoidedObserver` construya el snapshot
`$voided->company` (RUC, razón social) de **otra** empresa y que el correlativo se derive
del `max()` de esa otra empresa. `details` manipulado cambia el `serie`/`correlativo` que
viaja en el XML a SUNAT y el que `markVoidedInvoices()` acaba marcando.

**Fix:** no confiar en el estado del Form para el ámbito; re-derivarlo en el servidor
justo antes de persistir, y bloquear las propiedades:
```php
use Livewire\Attributes\Locked;

class VoidedForm extends Form
{
    #[Locked] public $branch_id;
    #[Locked] public $company_id;
    #[Locked] public $production;
    // ...

    public function send()
    {
        $this->validate([
            'details' => 'required|array|min:1',
            'details.*.tipoDoc' => 'required|string|size:2',
            'details.*.serie' => 'required|string|max:4',
            'details.*.correlativo' => 'required',
            'details.*.desMotivoBaja' => [/* ver CR-05 */],
        ]);

        // El ámbito multi-tenant se re-deriva aquí; nunca se acepta del cliente.
        $this->branch_id  = session('branch')->id;
        $this->company_id = session('company')->id;
        $this->production = session('company')->production;

        $voided = Voided::create($this->except('sunatResponse'));
        // ...
    }
}
```
(`sunatResponse` tampoco debe venir del cliente; ver WR-12.)

---

### CR-04: `VoidedForm::send()` ejecuta la baja ante SUNAT sin comprobar `vouchers.anulate`

**File:** `app/Livewire/Forms/VoidedForm.php:31-99` (y `app/Livewire/VoidedTable.php:79`)

**Issue:** La única comprobación de permiso del flujo de anulación está en
`VoucherTable::voidReason()` (línea 356: `if (!auth()->user()->can('vouchers.anulate'))`),
que es el método que **abre el modal**. La operación privilegiada e irreversible —
`VoidedForm::send()`, invocada desde `VoucherTable::sendVoided()` — no comprueba nada, y
`sendVoided()` es un método público del componente, luego invocable directamente desde el
cliente sin pasar nunca por `voidReason()`. Con `details`, `fecGeneracion` y
`fecComunicacion` puestos vía `$wire.set` (ver CR-03), un usuario con solo `vouchers.index`
completa una comunicación de baja ante SUNAT.

No es teórico: `vouchers.anulate` existe como permiso **separado** de `vouchers.index`
(`RoleSeeder.php:89,93`), y los roles son editables en runtime
(`app/Livewire/Roles/RoleTable.php:116` → `syncPermissions`). En cuanto un administrador
revoque `vouchers.anulate` conservando `vouchers.index`, el gate queda completamente vacío.
Que el seeder por defecto conceda ambos a los mismos cuatro roles enmascara el fallo hoy,
no lo elimina.

Lo mismo aplica a `VoidedTable::sendXml()`: su permiso de ruta es `can:voideds`
(`routes/web.php:66`), un permiso de **lectura del listado**, y sin embargo la acción envía
a SUNAT y puede acabar marcando facturas como anuladas.

**Fix:** poner el gate en la operación, no en el abridor del modal:
```php
// app/Livewire/Forms/VoidedForm.php::send(), primera instrucción
abort_unless(auth()->user()->can('vouchers.anulate'), 403);

// app/Livewire/VoidedTable.php::sendXml(), primera instrucción
abort_unless(auth()->user()->can('vouchers.anulate'), 403);
```

---

### CR-05: Inyección XML en el comprobante firmado vía `desMotivoBaja` (`]]>` rompe el `CDATA`)

**File:** `app/Livewire/Forms/VoidedForm.php:33-35` (punto de validación) → `app/Services/Sunat/Templates/voided.xml.twig:46`

**Issue:** La única validación del motivo de baja es de longitud:
```php
$this->validate([
    'details.*.desMotivoBaja' => 'required|min:3|max:250',
]);
```
Ese valor llega a la plantilla del XML que se firma y se envía a SUNAT:
```twig
<sac:VoidReasonDescription><![CDATA[{{ det.desMotivoBaja|raw }}]]></sac:VoidReasonDescription>
```
`SunatXmlBuilder::createTwig()` instancia `new Environment($loader, $options)` con
`$options = []`, así que el autoescape por defecto de Twig sí protege `{{ det.serie }}`,
`{{ det.correlativo }}` y `{{ det.tipoDoc }}` — pero `|raw` lo desactiva explícitamente
para `desMotivoBaja`, y un `CDATA` no protege de nada: basta con que el texto contenga
`]]>` para cerrar la sección e inyectar marcado arbitrario en el documento.

Un motivo como
`Error]]></sac:VoidReasonDescription></sac:VoidedDocumentsLine><sac:VoidedDocumentsLine><cbc:LineID>9</cbc:LineID><cbc:DocumentTypeCode>01</cbc:DocumentTypeCode><sac:DocumentSerialID>F001</sac:DocumentSerialID><sac:DocumentNumberID>123</sac:DocumentNumberID><sac:VoidReasonDescription><![CDATA[x`
produce un XML **bien formado** que `SignedXml::signXml()` firma sin objeción y que da de
baja ante SUNAT un comprobante que **no aparece en `$voided->details`**. El sistema
registraría una baja y SUNAT habría procesado otra: la divergencia exacta que el valor
central del proyecto ("todo comprobante que sale hacia SUNAT tiene que quedar registrado
fielmente") prohíbe. En su forma más leve, un `]]>` accidental produce un XML mal formado
y el envío falla sin explicación útil para el usuario.

Nota adicional: `max:250` excede el límite de SUNAT para *Motivo o sustento de baja*
(100 caracteres), lo que produce rechazos evitables.

**Fix:** validar el contenido, no solo la longitud, en el único punto donde el texto entra
al sistema:
```php
$this->validate([
    'details.*.desMotivoBaja' => [
        'required',
        'string',
        'min:3',
        'max:100',
        'not_regex:/\]\]>/',                // no puede cerrar el CDATA
        'regex:/^[\pL\pN \.,\-\/#()]+$/u',  // lista de permitidos
    ],
], [
    'details.*.desMotivoBaja.not_regex' => 'El motivo de baja contiene caracteres no permitidos.',
    'details.*.desMotivoBaja.regex'     => 'El motivo de baja contiene caracteres no permitidos.',
]);
```
Y, como defensa en profundidad, neutralizar la secuencia en la plantilla propia
(`voided.xml.twig` vive en el repo, no en `vendor/`), sustituyendo `]]>` por
`]]]]><![CDATA[>` antes de emitir el nodo.

---

## Warnings

### WR-01: Un rechazo de SUNAT queda grabado con `sunatResponse['success'] === true`

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:163`

**Issue:** La rama de rechazo por CDR con código distinto de 0 hace
`markRejected($voided, $this->util->readCdr($cdr))`, y `UtilService::readCdr()`
(líneas 181-189) **hardcodea** `'success' => true`. La fila queda con
`sunat_status = Rechazada` pero con un JSON que dice éxito. Hoy el blade no se confunde
porque discrimina primero por el enum, pero el propio `showResponse()`
(`VoidedForm.php:113`, `VoidedTable.php:185`) mantiene una rama de compatibilidad que lee
`sunatResponse['success']`, y cualquier reporte, export o consulta futura que lea ese
booleano —el discriminador que gobernó este módulo hasta ayer— leerá "aceptada" sobre un
rechazo.

**Fix:** normalizar el booleano en el punto de escritura del rechazo:
```php
// VoidedTicketFlowService.php:163
$response = $this->util->readCdr($cdr);
$response['success'] = false;

return $this->markRejected($voided, $response);
```

---

### WR-02: `VoidedStatus::badgeColor()` devuelve `'danger'`, color inexistente en WireUI — la anulación RECHAZADA se pinta sin estilo

**File:** `app/Enums/VoidedStatus.php:20-27`

**Issue:** `badgeColor()` devuelve `'danger'` para `Rechazada`. El pack de colores de
`x-wire-badge` (`vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Flat.php`) no
incluye `danger`; los negativos válidos son `negative` y `red`. `ComponentPack::get()`
hace `data_get($this->all(), $attribute) ?? $attribute`, es decir, ante una clave
desconocida **devuelve la propia cadena**, que acaba inyectada como clase CSS literal
`danger` (sin definición). Resultado: en `sunatResponse.blade.php:96` la insignia de
estado RECHAZADA sale sin color, mientras ACEPTADA (`primary`) y PENDIENTE (`warning`) sí
se colorean. El estado que más necesita destacarse es el único que no se distingue, y el
enum es precisamente el vocabulario de estado que esta fase introduce.

**Fix:**
```php
self::Rechazada => 'negative',
```
Considérese también `self::Aceptada => 'positive'` en lugar de `'primary'`, para que el
color comunique el desenlace y no el color de marca.

---

### WR-03: `resolveTicket()` es público pero no replica la invariante que sí protege `classifyFailure()`

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:84-96`

**Issue:** El docblock declara `resolveTicket()` público "a propósito (D-11): la Fase 2 lo
reutiliza para reconsultar anulaciones pendientes desde el listado". Pero el método:

1. No valida que `$voided->sunat_ticket` no sea nulo. `$ticket = $voided->sunat_ticket;`
   seguido de `$see->getStatus($ticket)` con `null` produce una consulta SOAP con ticket
   vacío cuyo desenlace, en el mejor caso, es un `markPending()` que ensucia una fila que
   nunca tuvo ticket.
2. No aplica la guarda de `classifyFailure()` (líneas 183-192). Si en Fase 2 se invoca
   sobre una fila ya `Aceptada`, o `Rechazada` **con** ticket (rechazo confirmado por CDR),
   y `getStatus()` lanza, el `catch` de la línea 91 llama a `markPending()` y
   **sobrescribe un desenlace que SUNAT ya confirmó** — la violación exacta de la
   invariante "un pendiente nunca pisa un desenlace confirmado".

Dentro de esta fase no es alcanzable (el único llamador es `sendAndResolve()`, justo tras
persistir el ticket), pero el método se declaró público precisamente para que otro llamador
lo use, y la protección vive en el llamador equivocado.

**Fix:** mover la invariante al método, no al llamador:
```php
public function resolveTicket(Voided $voided, string $cdrPath, ?See $see = null): VoidedStatus
{
    if ($voided->sunat_status === VoidedStatus::Aceptada
        || ($voided->sunat_status === VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket))) {
        return $voided->sunat_status;
    }

    if (is_null($voided->sunat_ticket)) {
        throw new \LogicException('resolveTicket() requiere un ticket persistido.');
    }
    // ...
}
```

---

### WR-04: `cdr_path` se asigna antes de escribir el ZIP — una fila puede apuntar a un CDR inexistente

**File:** `app/Services/Sunat/VoidedTicketFlowService.php:115-118`

**Issue:**
```php
if ($zip) {
    $voided->cdr_path = $cdrPath;
    Storage::put($cdrPath, $zip);
}
```
Si `Storage::put()` falla (DO Spaces caído, credenciales, disco lleno), la excepción sale
del servicio, el llamador la captura y llama a `classifyFailure()`, que como el ticket ya
existe hace `markPending()` → `$voided->save()`. Ese `save()` **persiste el `cdr_path` que
quedó en memoria** aunque el archivo nunca se escribió. La fila afirma tener CDR archivado
cuando no lo tiene, el listado muestra el botón de descarga y la descarga falla — un
registro que no refleja la realidad, en el módulo cuyo valor central es la fidelidad.

**Fix:** asignar después de confirmar la escritura.
```php
if ($zip) {
    Storage::put($cdrPath, $zip);
    $voided->cdr_path = $cdrPath;
}
```

---

### WR-05: La columna CDR ofrece "obtener CDR" en filas PENDIENTE y no lleva a ninguna parte

**File:** `app/Livewire/VoidedTable.php:56-59` + `resources/views/vouchers/partials/cdr.blade.php:7`

**Issue:** El parcial compartido `vouchers.partials.cdr` decide solo por `cdr_path`: toda
fila sin CDR muestra el botón `get_cdr.svg` que llama a `sendXml()`. Para una anulación
`Pendiente` la nueva guarda `canResendXml()` hace lo correcto (no reenvía) pero devuelve
un aviso "En proceso" y nada más. El usuario ve un botón que promete recuperar el CDR, lo
pulsa, y el sistema le pide esperar — sin ofrecer la reconsulta del ticket, que es la
acción que corresponde y que queda para Fase 2. Es una vía muerta visible en la UI que
invita a pulsar repetidamente.

**Fix:** dar al listado de anulaciones su propio parcial de columna CDR, coherente con el
árbol de `voideds/partials/response.blade.php` (descarga si hay `cdr_path`; indicador
inerte si el estado es `Pendiente`; reenvío solo en los dos casos que `canResendXml()`
permite).

---

### WR-06: `downloadPDF()` / `downloadXML()` sin guarda de ruta nula

**File:** `app/Livewire/VoidedTable.php:69-77`

**Issue:** Ambos hacen `Storage::download($voided->pdf_path)` sin comprobar que la ruta
exista; `Storage::download(null)` lanza `TypeError`. El caso es alcanzable por el propio
flujo de esta fase: `VoidedForm::send()` crea la fila **antes** del `try` (línea 37), así
que si falla `getXmlSigned()` o `getReportPdf()` la fila queda persistida con
`xml_path`/`pdf_path` nulos y estado `Rechazada`; el listado sigue mostrando los botones
PDF y XML de `vouchers.partials.pdf` / `vouchers.partials.xml`, que se renderizan
incondicionalmente.

**Fix:** guarda explícita con mensaje, en línea con el resto del componente:
```php
if (empty($voided->pdf_path)) {
    $this->dispatch('swal', [
        'icon' => 'info',
        'title' => 'Sin PDF',
        'text' => 'Esta comunicación de baja no llegó a generar su PDF. Reenvíela para regenerarlo.',
    ]);

    return;
}
```

---

### WR-07: `sendXml()` reconstruye el nombre del CDR con `session('company')->ruc` en vez de con el documento

**File:** `app/Livewire/VoidedTable.php:110, 124, 128, 136`

**Issue:** El método construye `$name = session('company')->ruc.'-RC-'....` (línea 110) y
lo usa solo para `$cdrPath` (línea 136), mientras que en la rama de regeneración las rutas
del XML y del PDF usan `$voidedGreenter->getName()` (líneas 124, 128), que deriva del
snapshot `$voided->company['ruc']`. Dos fuentes distintas para la misma convención de
nombres: divergen si el RUC de la empresa cambió después de emitir la anulación y, sobre
todo, si la fila no pertenece a la empresa en sesión (CR-02). Contradice además la regla
que el propio servicio enuncia ("no lee `session()`: el ámbito se lee del `Voided`
recibido").

**Fix:** derivar siempre del documento y eliminar `$name`:
```php
$voidedGreenter = (new VoidedService)->getVoided($voided);
$cdrPath = $directory.'cdr/R-'.$voidedGreenter->getName().'.zip';
```
(construyendo `$voidedGreenter` también en la rama en que el XML ya existe, o guardando el
nombre canónico en la fila al emitir).

---

### WR-08: El flujo roto que esta fase elimina sigue vivo y duplicado fuera del servicio

**File:** `app/Services/Sunat/UtilService.php:151-166` y `app/Http/Controllers/Api/VoidedController.php:31-52`

**Issue:** El docblock de `VoidedTicketFlowService` afirma ser el "único punto del código
base que envía una comunicación de baja y consulta su estado". No lo es:

- `UtilService::getResponseVoided()` quedó **sin ningún llamador** tras esta fase
  (verificado por búsqueda en `app/`, `resources/`, `routes/`, `database/`, `tests/`) y
  contiene literalmente el defecto que la fase corrige: `sendXmlFile` → `getTicket()` →
  `getStatus($ticket)` sin persistir el ticket en ningún sitio. Es una mina: quien busque
  mañana "cómo se envía una baja" la encontrará primero.
- `Api\VoidedController::send()` repite el mismo patrón sobre la superficie API (Sanctum),
  fuera del alcance del nuevo servicio.

De paso: ese controlador llama a `$sunat->getSee()`, `$sunat->getResponse()` y
`$sunat->getReportHtml()` sobre `VoidedService`, clase que **no define ninguno** de los
tres — `UtilTrait` aparece en el bloque de imports (`VoidedService.php:5`) pero nunca se
inserta en el cuerpo de la clase, así que el endpoint está roto de raíz.

**Fix:** eliminar `getResponseVoided()` y hacer que la ruta API delegue en
`VoidedTicketFlowService`, o marcar el endpoint como no soportado. Un flujo con la
semántica correcta y otro con la incorrecta conviviendo garantiza que se vuelva a usar el
segundo.

---

### WR-09: `showResponse()` duplicado literalmente y cuatro copias del bloque swal "En proceso"

**File:** `app/Livewire/Forms/VoidedForm.php:101-121` y `app/Livewire/VoidedTable.php:173-193`

**Issue:** Los dos `showResponse()` son idénticos salvo por `session()->flash('swal', ...)`
frente a `$this->dispatch('swal', ...)`, incluido el `match` de iconos con su rama de
compatibilidad `null`. El bloque swal "En proceso / SUNAT recibió la comunicación de baja
pero aún la procesa" aparece **cuatro veces** (`VoidedForm.php:65-69` y `84-88`;
`VoidedTable.php:140-144` y `156-160`). El mapeo estado→icono es una regla de negocio de
esta fase: en cuanto se añada un estado o cambie un texto, las copias divergirán y el
usuario verá un icono distinto según por dónde llegó.

**Fix:** llevar el mapeo al enum, que ya es el vocabulario de estado de la fase:
```php
// app/Enums/VoidedStatus.php
public function swalIcon(): string
{
    return match ($this) {
        self::Aceptada  => 'info',
        self::Pendiente => 'warning',
        self::Rechazada => 'error',
    };
}
```

---

### WR-10: Migración sin backfill, sin índice y sin restricción de dominio en `sunat_status`

**File:** `database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php:14-17`

**Issue:** Tres problemas en la misma migración:

1. **Sin backfill.** Las filas históricas quedan con `sunat_status = NULL` y caen en la
   rama D-13 del blade, que muestra la insignia "DESCONOCIDO"
   (`sunatResponse.blade.php:152`) incluso para anulaciones claramente aceptadas
   (`sunatResponse['success'] === true` y `cdrResponse.code === 0`). El dato para
   clasificarlas ya está en la BD; dejarlas como "desconocido" degrada información
   existente y contradice el objetivo de la fase.
2. **Sin índice.** `sunat_status` y `sunat_ticket` son exactamente las columnas por las que
   Fase 2 filtrará para reconsultar los pendientes; sin índice esa consulta es un scan.
3. **Sin restricción de dominio.** `string` libre de 255, sin `enum` ni `check`. Cualquier
   valor ajeno a `VoidedStatus` hace que el cast `VoidedStatus::class` lance `ValueError`
   **al hidratar el modelo**, lo que revienta el listado completo, no solo la fila afectada.

**Fix:**
```php
$table->string('sunat_status', 20)->nullable()->after('sunat_ticket')->index();
```
```php
DB::table('voideds')
    ->whereNull('sunat_status')
    ->whereRaw("json_extract(sunatResponse, '$.cdrResponse.code') = 0")
    ->update(['sunat_status' => VoidedStatus::Aceptada->value]);
```
(clasificando el resto de filas con `sunatResponse` no nulo según el mismo criterio.)

---

### WR-11: Nada impide el doble envío concurrente que produce el error 2109

**File:** `app/Livewire/VoidedTable.php:89-107` + `app/Services/Sunat/VoidedTicketFlowService.php:73-74`

**Issue:** `canResendXml()` es una comprobación de lectura sin bloqueo. Dos peticiones
concurrentes sobre la misma fila `Rechazada` sin ticket (doble clic, o dos pestañas) leen
ambas `sunat_ticket = null`, ambas pasan la guarda y ambas ejecutan `sendXmlFile()`. La
segunda recibe el 2109 de SUNAT y `markRejected()` machaca el estado que la primera acababa
de escribir. Es precisamente el escenario que el comentario de las líneas 81-88 dice querer
evitar; la guarda cubre la reinvocación desde el cliente pero no la concurrencia.

**Fix:** reclamo atómico antes de enviar, o bloqueo de fila:
```php
$claimed = Voided::whereKey($voided->id)
    ->whereNull('sunat_ticket')
    ->whereIn('sunat_status', [VoidedStatus::Rechazada->value])
    ->update(['sunat_status' => VoidedStatus::Pendiente->value]);

if ($claimed === 0) {
    return; // otro proceso ya tomó esta fila
}
```
Alternativamente, envolver lectura de la guarda y envío en
`DB::transaction(fn () => Voided::whereKey($id)->lockForUpdate()->first())`.

---

### WR-12: `sunat_ticket`, `sunat_status` y `sunatResponse` en `$fillable` sin que nada los necesite

**File:** `app/Models/Voided.php:25-26, 28`

**Issue:** Las dos columnas nuevas se añadieron a `$fillable`, pero el único `create()` del
modelo (`VoidedForm::send()`, línea 37) recibe `$this->all()` del Form, que no incluye
ninguna de las dos; el servicio las escribe siempre por asignación directa
(`$voided->sunat_ticket = ...`). Son, por tanto, superficie de asignación masiva sin uso.
Y no es superficie inocua: son las columnas que gobiernan tanto la guarda de reenvío
(`canResendXml()`) como la clasificación de desenlaces.

`sunatResponse` sí es propiedad pública del Form y por tanto **hoy** se puede inyectar en
el `create()` (ver CR-03); aunque el flujo actual la sobrescribe en todos sus caminos, es
un valor de origen cliente entrando directamente a la columna que representa la respuesta
de SUNAT.

**Fix:** retirar `sunat_ticket`, `sunat_status` y `sunatResponse` de `$fillable` —el
servicio las asigna directamente y no las necesita— o, como mínimo, excluirlas
explícitamente en el `create()` (`$this->except(...)`, ver CR-03).

---

### WR-13: `Voided::create()` fuera del `try`, y el `catch` puede lanzar a su vez

**File:** `app/Livewire/Forms/VoidedForm.php:37, 78-98`

**Issue:** Dos huecos de manejo de error en el mismo método:

1. `Voided::create($this->all())` está **antes** del `try` (línea 37). El
   `VoidedObserver::creating()` hace `Company::find($voided->company_id)` y acto seguido
   `$company->ruc` sin comprobar nulo, además de encadenar
   `$company->district->province->department`. Con un `company_id` inexistente (alcanzable
   vía CR-03) o una empresa sin distrito, la excepción escapa sin manejo alguno y el usuario
   recibe un 500 crudo en lugar del swal de error que el resto del método sí produce.
2. Dentro del `catch` (línea 79), `$flow->classifyFailure()` ejecuta un `save()` contra la
   BD. Si el fallo original **era** de BD, `classifyFailure()` lanza a su vez y el swal de
   error de las líneas 91-95 nunca llega a registrarse: se pierden a la vez el estado y el
   aviso al usuario.

**Fix:** mover el `create()` dentro del `try` y blindar el manejador:
```php
try {
    $voided = Voided::create($this->except('sunatResponse'));
    // ...
} catch (\Throwable $e) {
    try {
        $status = isset($voided) ? $flow->classifyFailure($voided, $e) : null;
    } catch (\Throwable $inner) {
        Log::error('No se pudo registrar el desenlace de la baja', [
            'voided_id' => $voided->id ?? null,
            'original'  => $e->getMessage(),
            'inner'     => $inner->getMessage(),
        ]);
        $status = null;
    }
    // ...
}
```

---

_Reviewed: 2026-08-05_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_
