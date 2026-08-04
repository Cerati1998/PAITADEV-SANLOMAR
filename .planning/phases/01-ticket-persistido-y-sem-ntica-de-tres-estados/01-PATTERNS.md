# Fase 1: Ticket persistido y semántica de tres estados - Mapa de Patrones

**Mapeado:** 2026-08-04
**Archivos analizados:** 8 (nuevos + modificados)
**Análogos encontrados:** 6 / 8

## Clasificación de Archivos

| Archivo nuevo/modificado | Rol | Flujo de datos | Análogo más cercano | Calidad de match |
|---|---|---|---|---|
| migración `add_sunat_ticket_and_status_to_voideds_table` | migration | CRUD (schema) | `database/migrations/2024_11_06_014056_create_despatches_table.php` (línea 36) | exacto (mismo patrón de columna `sunat_ticket`, distinto: `sunat_status` no tiene análogo de columna, solo de mecánica de enum) |
| `app/Models/Voided.php` | model | CRUD | `app/Models/Despatch.php` | exacto (mismo shape: `$fillable`, `$casts`, accessor `xml`/`cdr` vía `Attribute`) |
| `app/Livewire/Forms/VoidedForm.php` (`send()`) | controller (Livewire Form) | request-response + event-driven (SOAP async) | `app/Livewire/Forms/DespatchForm.php` (`sendDespatch()`, líneas 245-347) | role-match (mismo rol, pero transporte distinto: SOAP `See` vs REST `Api`) |
| `app/Livewire/VoidedTable.php` (`sendXml()`, `showResponse()`, `builder()`) | component (Livewire DataTable) | request-response + CRUD (listado) | `app/Livewire/DespatchTable.php` (líneas 186-242, reconsulta) — referencia de forma, no de literal, para Fase 2; para Fase 1 el propio `VoidedTable::sendXml()` es el código a corregir delegando en el servicio nuevo | role-match |
| servicio nuevo `app/Services/Sunat/TicketFlowService.php` (nombre sugerido) | service | event-driven (clasificación de resultado async) | `app/Services/Sunat/UtilService.php` (`getResponse()` líneas 123-149, `getErrorResponse()` líneas 168-177, `readCdr()` líneas 179-192, `getSee()` línea 27) — no es un análogo de servicio completo, es el conjunto de piezas reutilizables sobre las que se construye | parcial — no existe ya un servicio "de flujo" equivalente; se compone a partir de fragmentos de `UtilService` |
| enum nuevo `app/Enums/VoidedStatus.php` | model (value object) | transform | **sin análogo** — ver sección dedicada abajo | sin análogo |
| `resources/views/voideds/partials/sunatResponse.blade.php` | component (Blade partial) | request-response (render) | el propio archivo actual (se adapta, no se reemplaza) | exacto (es modificación in-place) |
| `resources/views/voideds/partials/response.blade.php` (nuevo) | component (Blade partial) | request-response (render) | `resources/views/vouchers/partials/response.blade.php` — referencia estructural de la que **diverge** deliberadamente | sin análogo directo, ver sección dedicada abajo |

## Asignaciones de Patrón

### `database/migrations/2026_xx_xx_add_sunat_ticket_and_status_to_voideds_table.php` (migration, CRUD)

**Análogo:** `database/migrations/2024_11_06_014056_create_despatches_table.php`

**Patrón de columna `sunat_ticket`** (línea 36):
```php
$table->string('sunat_ticket')->nullable()/* ->after('cdr_path') */;
```

**Esquema actual de `voideds`** (`database/migrations/2024_12_16_111346_create_voideds_table.php`, líneas 28-35), punto de inserción tras `hash`:
```php
$table->string('pdf_path')->nullable();
$table->string('xml_path')->nullable();
$table->string('cdr_path')->nullable();

$table->string('hash')->nullable();

$table->json('sunatResponse')->nullable();
```

**A escribir** (D-01, D-13 — nullable, sin default, sin backfill):
```php
Schema::table('voideds', function (Blueprint $table) {
    $table->string('sunat_ticket')->nullable()->after('hash');
    $table->string('sunat_status')->nullable()->after('sunat_ticket');
});
```

`sunat_status` no tiene columna análoga en ningún migration existente del proyecto — es la primera columna de estado tipado enum del código base. La única guía disponible es la mecánica de cast de enum de Laravel 11 (ver sección del modelo).

---

### `app/Models/Voided.php` (model, CRUD)

**Análogo:** `app/Models/Despatch.php` (líneas 1-67)

**`$fillable` con `sunat_ticket`** (`Despatch.php` líneas 14-36):
```php
protected $fillable = [
    // ...
    'pdf_path',
    'xml_path',
    'cdr_path',
    'sunat_ticket',
    'hash',

    'sunatResponse',
    'branch_id',
    'company_id',
    'production',
];
```

**`$casts`** (`Despatch.php` líneas 38-46, incluye `production` booleano — `Voided` hoy no lo castea, es una divergencia preexistente, no tocar salvo que se decida ampliar):
```php
protected $casts = [
    'fechaEmision' => 'datetime',
    'company' => 'array',
    'destinatario' => 'array',
    'envio' => 'array',
    'details' => 'array',
    'sunatResponse' => 'array',
    'production' => 'boolean',
];
```

**Estado actual de `Voided.php`** (líneas 14-37), que hay que extender:
```php
protected $fillable = [
    'correlativo',
    'fecGeneracion',
    'fecComunicacion',
    'details',

    'pdf_path',
    'xml_path',
    'cdr_path',
    'hash',

    'sunatResponse',
    'branch_id',
    'company_id',
    'production'
];

protected $casts = [
    'fecGeneracion' => 'datetime',
    'fecComunicacion' => 'datetime',
    'company' => 'array',
    'details' => 'array',
    'sunatResponse' => 'array',
];
```

**Cambio necesario:** agregar `'sunat_ticket'`, `'sunat_status'` a `$fillable`, y `'sunat_status' => VoidedStatus::class` a `$casts` (cast de enum nativo de Laravel 11).

**Comportamiento verificado con NULL** (crítico para D-13, filas históricas sin backfill) — de `vendor/laravel/framework/.../Concerns/HasAttributes.php:896-900`, confirmado por RESEARCH.md:
```php
protected function getEnumCastableAttributeValue($key, $value)
{
    if (is_null($value)) {
        return; // null, no lanza ValueError
    }
    // ...
}
```

**Accessors `xml`/`cdr` a preservar sin cambios** (`Voided.php` líneas 40-52) — no tocar, no relacionados con esta fase:
```php
protected function xml():Attribute
{
    return new Attribute(
        get: fn() => Storage::get($this->xml_path),
    );
}

protected function cdr():Attribute
{
    return new Attribute(
        get: fn() => Storage::get($this->cdr_path),
    );
}
```

**Nota — `VoidedObserver::creating()` es obligatorio:** `voideds.company` (JSON) y `voideds.correlativo` son `NOT NULL` y no están en `$fillable`; se rellenan solo en `app/Observers/VoidedObserver.php::creating()`. El servicio nuevo no crea filas (recibe un `Voided` ya existente), así que esto no aplica al flujo del servicio, pero si el planner toca la creación en `VoidedForm::send()` debe seguir pasando por `Voided::create()`.

---

### `app/Livewire/Forms/VoidedForm.php::send()` (controller/Livewire Form, request-response + event-driven)

**Análogo:** `app/Livewire/Forms/DespatchForm.php::sendDespatch()` (líneas 245-363)

**Patrón completo a portar — persistir ticket antes de consultar, tratar fallo como pendiente, limpiar ticket al obtener CDR** (`DespatchForm.php` líneas 292-328):
```php
// Guardar ticket en BD por si la consulta falla y hay que reintentar
$ticket = $result->getTicket();
$despatch->sunat_ticket = $ticket;
$despatch->save();

// Consultar CDR con el ticket
$statusResult = $api->getStatus($ticket);

if (!$statusResult->isSuccess()) {
    $code = $statusResult->getError()?->getCode();

    // Código 98: SUNAT aún procesando → ticket queda guardado para reintentar
    if ($code === '98') {
        session()->flash('swal', [
            'icon' => 'info',
            'title' => 'En proceso',
            'text' => 'SUNAT recibió la guía pero aún la procesa. Usa "Consultar estado" en unos segundos.',
        ]);
        return redirect()->route('despatchs.index');
    }

    $despatch->sunatResponse = $util->getErrorResponse($statusResult);
    $despatch->save();
    $this->showResponse($despatch);
    return redirect()->route('despatchs.index');
}

// CDR obtenido: guardar y limpiar ticket
$despatch->cdr_path = $directory . 'cdr/R-' . $despatchGreenter->getName() . '.zip';
Storage::put($despatch->cdr_path, $statusResult->getCdrZip());

$despatch->sunatResponse = $util->readCdr($statusResult->getCdrResponse());
$despatch->sunat_ticket = null; // Ya procesado, no se necesita más
$despatch->save();
```

**Divergencia obligatoria (SOAP vs REST):** `DespatchForm` usa `Greenter\Api::getStatus()` (REST GRE) que solo lanza el `if (!$statusResult->isSuccess())` sin necesitar `try/catch` amplio. `VoidedForm` usa `Greenter\See::getStatus()` (SOAP), donde `ExtService::getStatus()` solo atrapa `SoapFault` — una respuesta 200 mal formada lanza `InvalidServiceResponseException` (`LogicException`, no `SoapFault`) que **no queda atrapada** dentro de Greenter y escapa al llamador. El servicio nuevo debe envolver la llamada a `getStatus()` en su propio `try { } catch (\Throwable $e) { /* pendiente */ }`, no confiar solo en `isSuccess()`.

**Código actual defectuoso a reemplazar** (`VoidedForm.php` líneas 58-97):
```php
$see = $util->getSee();
$result = $see->sendXmlFile($xml);

if (!$result->isSuccess()) {
    $voided->sunatResponse = [
        'success' => false,
        'error' => [
            'code' => $result->getError()->getCode(),
            'message' => $result->getError()->getMessage()
        ]
    ];
    $voided->save();
    return redirect()->route('vouchers.index');
}

$ticket = $result->getTicket();
$result = $see->getStatus($ticket);   // <- ticket nunca se persiste antes de esto

$voided->sunatResponse = $util->getResponse($result);

if ($result->getCdrZip()) {
    $voided->cdr_path = $directory . 'cdr/R-' . $voidedGreenter->getName() . '.zip';
    Storage::put($voided->cdr_path, $result->getCdrZip());
}

$voided->save();

if($voided->sunatResponse['success'] && $voided->sunatResponse['cdrResponse']['code'] == '0')
{
    foreach ($voided->details as $item) {
        Invoice::where('tipoDoc', $item['tipoDoc'])
            ->where('serie', $item['serie'])
            ->where('correlativo', $item['correlativo'])
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id)
            ->update(['voided' => true]);
    }
}
```

**Multi-tenant en `VoidedForm`:** el marcado de `Invoice` filtra por `session('branch')->id`, `session('company')->id` (líneas 93-94, arriba). D-11/Pitfall 7 fijan que el servicio nuevo NO debe leer `session()` internamente — debe recibir `branch_id`/`company_id`/`production` desde el propio `$voided` recibido (columnas ya presentes en el modelo), que es la fuente más segura dentro de una capa de servicio.

**Aviso "en proceso" (WEB-01, D-10)** — patrón exacto a copiar (`DespatchForm.php` líneas 303-310):
```php
session()->flash('swal', [
    'icon' => 'info',
    'title' => 'En proceso',
    'text' => 'SUNAT recibió la guía pero aún la procesa. Usa "Consultar estado" en unos segundos.',
]);
```
(adaptar el texto a "comunicación de baja").

**`showResponse()` actual, a migrar del booleano `success` al enum** (`VoidedForm.php` líneas 123-136):
```php
public function showResponse(Voided $voided)
{
    $title = 'Detalle de la comunicación de baja';

    $html = view('voideds.partials.sunatResponse', [
        'document' => $voided
    ])->render();

    session()->flash('swal', [
        'icon' => $voided->sunatResponse['success'] ? 'info' : 'error',
        'title' => $title,
        'html' => $html
    ]);
}
```

---

### `app/Livewire/VoidedTable.php` (`sendXml()`, `showResponse()`, `builder()`) (component/Livewire DataTable, request-response + CRUD)

**Código actual, duplica el mismo flujo defectuoso de `VoidedForm::send()`** (`VoidedTable.php` líneas 78-159) — candidato a delegar en el servicio nuevo (D-11):
```php
public function sendXml(Voided $voided)
{
    $directory = $voided->production ? 'sunat/' : 'sunat/beta/';
    $name = session('company')->ruc . "-RC-" . $voided->fecGeneracion->format('Ymd') . '-' . $voided->correlativo;

    try {
        $util = new UtilService(session('company'));

        if (empty($voided->xml_path)) {
            // regenera XML/PDF si nunca se generó
            // ...
        } else {
            $xml = $voided->xml;
        }

        $see = $util->getSee();
        $result = $see->sendXmlFile($xml);
        // ... mismo flujo defectuoso que VoidedForm::send() (líneas 109-147)
    } catch (\Throwable $e) {
        $this->dispatch('swal', [
            'icon' => 'error',
            'title' => 'Error al enviar el comprobante',
            'text' => $e->getMessage()
        ]);
    }
}
```

**Multi-tenant en `builder()`** (`VoidedTable.php` líneas 176-182) — patrón que toda consulta nueva sobre `Voided`/`Invoice` debe replicar:
```php
public function builder(): Builder
{
    return Voided::query()
        ->where('branch_id', session('branch')->id)
        ->where('company_id', session('company')->id)
        ->where('production', session('company')->production);
}
```

**D-09 — condicionar el botón de reenvío a ausencia de ticket, no a `sunatResponse` vacío.** El código actual del listado (`vouchers/partials/response.blade.php`, ver más abajo) ofrece reenviar cuando `empty($value)` (JSON vacío); la nueva vista de `voideds` debe usar `is_null($voided->sunat_ticket)` como condición.

**Diferencia con `DespatchTable::` (referencia de forma para Fase 2, no de literal para esta fase):** `DespatchTable.php` líneas 186-242 implementan **reconsulta** (usar el ticket guardado para volver a preguntar el estado) en vez de reenviar el XML. Esa reescritura de `sendXml()` como reconsulta es explícitamente Fase 2 (WEB-02); en esta fase `VoidedTable::sendXml()` solo deja de ofrecerse cuando ya hay ticket (D-09), delegando el envío inicial en el servicio nuevo igual que `VoidedForm`.

**Column `Sunat` actual, usa el partial compartido a reemplazar (D-08):** (`VoidedTable.php` líneas 60-63):
```php
Column::make('Sunat', 'sunatResponse')
    ->format(
        fn($value, $row) => view('vouchers.partials.response', compact('value', 'row'))
    )->collapseOnTablet(),
```
Debe pasar a `view('voideds.partials.response', compact('value', 'row'))` (vista nueva).

---

### Servicio nuevo `app/Services/Sunat/TicketFlowService.php` (nombre sugerido en D-03) (service, event-driven)

**Sin análogo de servicio completo.** Se compone reutilizando piezas ya existentes de `app/Services/Sunat/UtilService.php`:

**`getSee()`** (líneas 27-40) — construye el cliente SOAP, transporte de la anulación (no leído completo en esta sesión de patrones, ya cubierto en RESEARCH.md):
```php
public function getSee()
{
    // construye Greenter\See con certificado + credenciales SOL según production
}
```

**`getResponse($result)`** (líneas 123-149) — normaliza éxito/error de un `StatusResult`, reutilizable para el caso aceptado:
```php
public function getResponse($result)
{
    $response['success'] = $result->isSuccess();

    if (!$response['success']) {
        $response['error'] = [
            'code' => $result->getError()->getCode(),
            'message' => $result->getError()->getMessage()
        ];
        return $response;
    }

    $cdr = $result->getCdrResponse();
    $response['cdrResponse'] = [
        'id' => $cdr->getId(),
        'code' => (int)$cdr->getCode(),
        'description' => $cdr->getDescription(),
        'notes' => $cdr->getNotes(),
    ];

    return $response;
}
```

**`getErrorResponse($result)`** (líneas 168-177) — normaliza un error de resultado SUNAT, útil para el caso rechazado (D-06, envío inicial fallido antes de ticket):
```php
public function getErrorResponse($result)
{
    return [
        'success' => false,
        'error' => [
            'code' => $result->getError()->getCode(),
            'message' => $result->getError()->getMessage()
        ]
    ];
}
```

**`readCdr($cdr)`** (líneas 179-192) — ya normaliza un CDR a la forma correcta para el caso aceptado (D-07):
```php
public function readCdr($cdr)
{
    $response = [
        'success' => true,
        'cdrResponse' => [
            'id' => $cdr->getId(),
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes(),
        ]
    ];

    return $response;
}
```

**`getResponseVoided($result)`** (líneas 151-166) — **no usar como base**: depende de `$this->see`, poblado solo tras llamar `getSee()` en la misma instancia; es frágil y mezcla dos pasos (consultar + normalizar) que D-03 quiere separados en el servicio nuevo.

**A NO copiar — flujo defectuoso de `VoidedForm::send()`/`VoidedTable::sendXml()`** (ambos, líneas citadas arriba): la comparación booleana `$voided->sunatResponse['success']` como único criterio de "aceptado", y el hecho de no persistir el ticket antes de `getStatus()`.

**Patrón de clasificación con `try/catch` amplio recomendado por RESEARCH.md** (adaptación SOAP, sin análogo literal en el repo — es código nuevo que combina el patrón de `DespatchForm` con el matiz SOAP):
```php
$ticket = $result->getTicket();
$voided->sunat_ticket = $ticket;
$voided->save(); // TICKET-01

try {
    $statusResult = $see->getStatus($ticket);
} catch (\Throwable $e) {
    // D-05: cualquier fallo de consulta con ticket ya persistido es pendiente
    return $this->clasificarPendiente($voided, $e->getMessage());
}

if (!$statusResult->isSuccess()) {
    return $this->clasificarPendiente($voided, $statusResult->getError()?->getMessage());
}

$cdr = $statusResult->getCdrResponse();
if ($cdr && (int) $cdr->getCode() === 0) {
    Storage::put($voided->cdr_path, $statusResult->getCdrZip());
    $voided->sunat_status = VoidedStatus::Aceptada;
    $voided->sunat_ticket = null;
    $voided->save();
    return true;
}
```

**Multi-tenant en el servicio (Pitfall 7):** debe recibir `branch_id`/`company_id`/`production` leyéndolos del `Voided $voided` recibido (`$voided->branch_id`, `$voided->company_id`, `$voided->production`), replicando el mismo filtro que usa `VoidedForm.php:93-94` y `VoidedTable.php:178-181`, sin volver a leer `session()` dentro de la capa de servicio.

---

## Enum nuevo: `app/Enums/VoidedStatus.php` — SIN ANÁLOGO

**`app/Enums/` no existe en el proyecto.** Se confirmó con `ls` que el directorio no está creado — este es el **primer enum PHP** del código base.

**Constructo de valor tipado más próximo en el repo:** no hay ningún enum PHP nativo (`enum ... : string`) en ningún directorio de `app/`. El constructo más cercano en espíritu (un conjunto cerrado de valores con semántica reutilizable) son las constantes de clase usadas informalmente en otros modelos, pero **no hay un patrón de constantes de estado a portar** — la búsqueda no encontró un caso equivalente de "estado con tres valores" en otro módulo (`WorkOrder`, `Dispatch`, `EirInspection` usan strings libres o booleanos, no un tipo cerrado).

**Referencia de mecánica de Laravel 11 (no de código de dominio del proyecto), confirmada en RESEARCH.md leyendo el vendor:**
```php
// app/Enums/VoidedStatus.php — código nuevo, sin precedente en el repo
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

El planner debe tratar este archivo como código enteramente nuevo sin plantilla local que copiar; solo sigue la convención de nomenclatura PascalCase del proyecto (`./.claude/CLAUDE.md` §Naming Patterns) y la mecánica nativa de Laravel 11 para el cast.

---

## Vista nueva: `resources/views/voideds/partials/response.blade.php` — SIN ANÁLOGO DIRECTO, diverge de `vouchers/partials/response.blade.php`

**Referencia estructural (leer, no modificar):** `resources/views/vouchers/partials/response.blade.php` (23 líneas completas):
```blade
<div>

    @if (isset($value) && $value['success'])
        <button wire:click="showResponse({{$row->id}})" xs>
            <x-wire-icon name="check-circle" class="w-6.5 h-6.5 text-green-500" solid />
        </button>
    @else

        @if (empty($value))
            
            <button wire:click="sendXml({{$row->id}})" xs>
                <img class='h-6' src="{{asset('img/icons/get_cdr.svg')}}"/>
            </button>

        @else

            <button wire:click="showResponse({{$row->id}})" xs>
                <i class="text-xl fas fa-ban text-red-500"></i>
            </button>

        @endif

    @endif

</div>
```

**Qué renderiza exactamente hoy (para que el planner sepa qué debe cubrir el reemplazo):**
1. Si `$value['success']` es verdadero → botón con ícono `check-circle` verde que abre `showResponse()` (detalle del comprobante aceptado).
2. Si `$value` está vacío (`empty($value)`, es decir `sunatResponse` es `null`/`[]`, nunca se envió) → botón que dispara `sendXml()` (reenviar/generar el XML).
3. En cualquier otro caso (JSON con `success: false`, es decir hubo respuesta pero fue error) → botón con ícono `fa-ban` rojo que abre `showResponse()` (detalle del error/rechazo).

**Por qué NO se puede reutilizar tal cual para `VoidedTable` (D-08, D-09):**
- Es compartida por `VoucherTable.php:131`, `DespatchTable.php:105`, `DespatchTransportistaTable.php:96` y `VoidedTable.php:62` — modificarla afecta facturas/boletas/guías, fuera de alcance de esta fase.
- Su condición de "ofrecer reenvío" es `empty($value)` (JSON vacío), que con D-04 (la anulación pendiente siempre escribe un `sunatResponse` explícito, nunca null) ya no distingue correctamente "nunca enviado" de "pendiente" — y D-09 exige la condición correcta: `is_null($voided->sunat_ticket)` (ausencia de ticket), no la forma del JSON.
- Solo modela dos estados visuales (éxito/no-éxito), no los tres estados de D-01/D-02 (aceptada/pendiente/rechazada).

**Lo que la vista nueva debe cubrir, como mínimo (equivalente funcional adaptado a tres estados):**
- Estado **aceptada** (`$voided->sunat_status === VoidedStatus::Aceptada`): mismo botón `check-circle` verde → `showResponse()`.
- Estado **pendiente** (`$voided->sunat_status === VoidedStatus::Pendiente`): un ícono/badge distinto que comunique "en proceso" (D-10 usa swal `info` para el aviso al enviar; la vista de listado debería reflejar visualmente ese mismo tono, p. ej. badge/ícono amarillo o `info`) → `showResponse()`, y **sin** ofrecer reenvío aunque el JSON no sea `success: true`.
- Estado **rechazada** (`$voided->sunat_status === VoidedStatus::Rechazada`): distinguir dos sub-casos según D-06/D-09 —
  - sin ticket (`is_null($voided->sunat_ticket)`) → ofrecer `sendXml()` (mismo patrón `sendXml({{$row->id}})` del original), porque el envío inicial nunca llegó a SUNAT.
  - con ticket guardado → **no** ofrecer reenvío (D-09), solo `showResponse()` con el ícono de error/rechazo (`fa-ban` rojo, igual que el original).
- Estado `NULL` (filas históricas sin backfill, D-13) → tratar como "desconocido"; el planner decide la representación visual, pero no debe inducir a reenviar un XML que ya pudo haber sido recibido por SUNAT.

---

## Vista existente a adaptar: `resources/views/voideds/partials/sunatResponse.blade.php`

**No requiere análogo — es modificación in-place del propio archivo** (74 líneas, contenido completo leído):

```blade
<div class="w-full text-xl space-y-5">
    <div class="flex justify-between">
        Comunicación de baja
        <x-wire-badge lg primary :label="$document->correlativo" />
    </div>

    <div class="flex">
        @if ($document->sunatResponse['success'])
            <x-wire-icon name="check" class="w-6 h-6 text-green-500 mr-2" solid />
            Enviado a SUNAT
        @else
            <i class="text-xl fas fa-times text-red-500 mr-2"></i>
            Error al Enviar a SUNAT
        @endif
    </div>

    @if ($document->sunatResponse['success'])
        <div class="flex justify-between">
            Estado:
            @if ($document->sunatResponse['cdrResponse']['code'] == '0')
                <x-wire-badge lg primary label="ACEPTADO" />
            @elseif ($document->sunatResponse['cdrResponse']['code'] >= 2000 && $document->sunatResponse['cdrResponse']['code'] <= 3999)
                <x-wire-badge lg danger label="RECHAZADO" />
            @else
                <x-wire-badge lg warning label="EXCEPCIÓN" />
            @endif
        </div>
        <!-- ... código + descripción + notas ... -->
    @else
        <!-- ... código + mensaje de error ... -->
    @endif
</div>
```

**Cambio necesario:** reemplazar la lectura de `$document->sunatResponse['success']` (booleano) como discriminador principal por lectura del enum `$document->sunat_status`, manteniendo el bloque de `sunatResponse['cdrResponse']`/`sunatResponse['error']` para el detalle textual (D-04 exige que el JSON siga siendo explícito para pendiente/rechazado).

---

## Patrones Compartidos

### Multi-tenant por sesión (aplica a TODA consulta nueva sobre `Voided`/`Invoice`)
**Fuente:** `app/Livewire/VoidedTable.php::builder()` (líneas 176-182), `app/Livewire/Forms/VoidedForm.php::send()` (líneas 90-95)
**Aplicar a:** servicio nuevo, cualquier query nueva en `VoidedForm`/`VoidedTable`
```php
// VoidedTable::builder()
Voided::query()
    ->where('branch_id', session('branch')->id)
    ->where('company_id', session('company')->id)
    ->where('production', session('company')->production);

// VoidedForm::send() — marcado de Invoice como voided
Invoice::where('tipoDoc', $item['tipoDoc'])
    ->where('serie', $item['serie'])
    ->where('correlativo', $item['correlativo'])
    ->where('branch_id', session('branch')->id)
    ->where('company_id', session('company')->id)
    ->update(['voided' => true]);
```
**Advertencia (Pitfall 7 de RESEARCH.md):** el servicio nuevo NO debe leer `session()` directamente; debe recibir `branch_id`/`company_id`/`production` desde las columnas del propio `Voided` recibido, que ya las tiene pobladas por `VoidedObserver`.

### Persistir ticket antes de consultar estado (patrón asíncrono SUNAT)
**Fuente:** `app/Livewire/Forms/DespatchForm.php` líneas 292-296
**Aplicar a:** servicio nuevo (`TicketFlowService`)
```php
$ticket = $result->getTicket();
$despatch->sunat_ticket = $ticket;
$despatch->save(); // save() propio, antes de cualquier llamada que pueda fallar
```

### Manejo de excepciones ancho en transporte SOAP (matiz exclusivo de `Voided`, sin análogo en `Despatch`)
**Fuente:** RESEARCH.md Pitfall 2, mecánica de `vendor/greenter/ws/src/Ws/Services/ExtService.php` (solo lectura)
**Aplicar a:** servicio nuevo, envoltura de `$see->getStatus($ticket)`
```php
try {
    $statusResult = $see->getStatus($ticket);
} catch (\Throwable $e) {
    // clasificar como pendiente — cubre SoapFault e InvalidServiceResponseException
}
```

### Aviso "en proceso" (swal `info`)
**Fuente:** `app/Livewire/Forms/DespatchForm.php` líneas 303-310
**Aplicar a:** `VoidedForm::send()` (D-10)
```php
session()->flash('swal', [
    'icon' => 'info',
    'title' => 'En proceso',
    'text' => 'SUNAT recibió la comunicación de baja pero aún la procesa.',
]);
```

## Sin Análogo Encontrado

| Archivo | Rol | Flujo de datos | Razón |
|---|---|---|---|
| `app/Enums/VoidedStatus.php` | model (value object) | transform | Primer enum PHP del proyecto; `app/Enums/` no existe (confirmado con `ls`). No hay constructo de valor tipado cerrado equivalente en ningún otro módulo (`WorkOrder`, `Dispatch`, `EirInspection` usan strings libres o booleanos). El planner debe apoyarse solo en la mecánica nativa de Laravel 11 / PHP 8.2 documentada en RESEARCH.md, no en un patrón local. |
| `resources/views/voideds/partials/response.blade.php` | component (Blade partial) | request-response (render) | No existe una vista de listado de tres estados en el proyecto — todas las existentes (`vouchers/partials/response.blade.php`) modelan éxito/no-éxito binario. Debe divergir deliberadamente de `vouchers/partials/response.blade.php` (ver sección dedicada arriba con el contenido completo citado) para cubrir aceptada/pendiente/rechazada y la regla D-09 de no ofrecer reenvío con ticket guardado. |

## Metadata

**Alcance de búsqueda de análogos:** `app/Livewire/`, `app/Livewire/Forms/`, `app/Models/`, `app/Services/Sunat/`, `database/migrations/`, `resources/views/vouchers/partials/`, `resources/views/voideds/partials/`, `app/Enums/` (inexistente, verificado)
**Archivos escaneados:** `DespatchForm.php`, `VoidedForm.php`, `DespatchTable.php` (parcial, líneas citadas en RESEARCH.md), `VoidedTable.php`, `Voided.php`, `Despatch.php`, `UtilService.php` (métodos 27-192), migraciones de `despatches` y `voideds`, `vouchers/partials/response.blade.php`, `voideds/partials/sunatResponse.blade.php`
**Fecha de extracción de patrones:** 2026-08-04
