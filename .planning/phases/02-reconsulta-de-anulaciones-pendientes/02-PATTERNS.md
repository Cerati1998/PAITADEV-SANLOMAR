# Fase 2: Reconsulta de anulaciones pendientes - Mapa de Patrones

**Mapeado:** 2026-08-05
**Archivos analizados:** 2 (los dos únicos en alcance)
**Análogos encontrados:** 2 / 2

**Nota importante de estado:** al leer el código real (no los mapas de
`.planning/codebase/*.md`, desactualizados) se confirma que **la Fase 1 ya dejó
`VoidedTable::sendXml()` delegando en `VoidedTicketFlowService`**, con guarda de
reenvío (`canResendXml()`), enum en `configure()`/columnas, y
`resources/views/voideds/partials/response.blade.php` ya con las tres ramas
visuales (aceptada/pendiente/rechazada) + el caso NULL histórico, badge de
ícono por estado. Es decir: **D-06 (badge de texto) y D-07 (SelectFilter) son
lo único que falta añadir** — el ruteo de tres ramas de D-02 y la guarda D-03
ya existen, solo falta la rama explícita "Consultar" para `sunat_ticket`
poblado con `sunat_status === Pendiente` (D-01) porque hoy `sendXml()` solo
sabe reenviar o avisar, no reconsultar con el ticket ya guardado.

## Clasificación de Archivos

| Archivo a modificar | Rol | Flujo de datos | Análogo más cercano | Calidad de match |
|---|---|---|---|---|
| `app/Livewire/VoidedTable.php` (`sendXml()`, `configure()`, `columns()`, `filters()` nuevo) | component (Livewire DataTable) | request-response + CRUD | `app/Livewire/DespatchTable.php::sendXml()` (líneas 156-253) para la rama "consultar"; `app/Livewire/VoucherTable.php::filters()` (líneas 141-154) para el `SelectFilter` de enum fijo | exacto (mismo servicio `VoidedTicketFlowService` ya inyectado; solo falta la rama de reconsulta) |
| `resources/views/voideds/partials/response.blade.php` | component (Blade partial) | request-response (render) | el propio archivo actual (ya tiene las tres ramas + NULL, D-06 añade badge, se necesita nueva sub-rama "consultar") | exacto (modificación in-place) |

## Verificación de líneas frente a CONTEXT.md

`canonical_refs` cita `sendXml()` en 108-200 y `canResendXml()` en 229-245 —
**confirmado, coinciden exactamente** con el archivo real leído hoy
(`app/Livewire/VoidedTable.php`). `builder()` está en 247-253 (coincide).
`configure()` está en 17-22 (coincide), y hoy ya hace
`setAdditionalSelects(['id', 'sunat_ticket', 'sunatResponse'])` — **falta
sumar `'sunat_status'`** si el badge/filtro lo necesitan explícitamente (en la
práctica `sunat_status` ya es la columna base de `Column::make('Sunat',
'sunat_status')`, línea 61, así que rappasoft ya la selecciona vía el nombre
de columna; confirmar en ejecución si `setAdditionalSelects` es estrictamente
necesario o redundante).

`DespatchTable::sendXml()` está en **156-253** (coincide con el rango citado
156-253 del CONTEXT). `DespatchTable::filters()` está en **116-134**
(coincide). `DespatchForm.php` líneas 292-325 no se releyeron en esta pasada
(ya extraídas literalmente en el `01-PATTERNS.md` de la Fase 1, sección
"`app/Livewire/Forms/VoidedForm.php::send()`" — el patrón de persistir/limpiar
ticket es idéntico al que ya vive en
`VoidedTicketFlowService::resolveTicket()`, no hace falta releerlo).

`VoidedTicketFlowService::resolveTicket()` está en **84-164** (coincide con
84-164 citado); `classifyFailure()` en **172-209** (coincide); `markPending()`
en **216-236** (el archivo real la tiene en 216-236, coincide).

## Pattern Assignments

### `app/Livewire/VoidedTable.php::sendXml()` — añadir la rama "Consultar" (D-01, D-02)

**Estado actual real** (líneas 108-200, leído hoy completo): ya tiene la
guarda `canResendXml()` y las dos ramas reenviar/avisar del árbol D-02, **pero
no tiene la primera rama** ("`sunat_ticket` poblado → Consultar"). Hoy, si
`canResendXml()` es `false` y el `sunat_ticket` no es `null`, el código
**ya avisa correctamente** ("SUNAT ya recibió esta comunicación de baja y su
ticket quedó guardado", líneas 119-127) — **pero no reconsulta**, que es
justo el trabajo WEB-02 de esta fase. Ese bloque `if (! is_null($voided->sunat_ticket))`
(líneas 119-127) es el punto exacto a convertir en la rama "Consultar":
```php
// app/Livewire/VoidedTable.php:108-136 (estado actual, ya con canResendXml())
public function sendXml(Voided $voided)
{
    if (! $this->canResendXml($voided)) {
        if (! is_null($voided->sunat_ticket)) {
            // AQUÍ es donde D-01/D-02 insertan la reconsulta en vez de solo avisar.
            // Situación real: sunat_status === Pendiente (D-02, fila 1 de la tabla).
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'En proceso',
                'text' => 'SUNAT ya recibió esta comunicación de baja y su ticket quedó guardado.',
            ]);

            return;
        }

        $this->dispatch('swal', [
            'icon' => 'info',
            'title' => 'No se puede reenviar',
            'text' => 'Esta comunicación de baja ya tiene una respuesta de SUNAT registrada. Reenviar el XML devolvería el error 2109 (la comunicación de baja ya fue informada), sin recuperar nada.',
        ]);

        return;
    }
    // ... resto: reenvío vía $flow->sendAndResolve() (líneas 138-199)
}
```

**Patrón a portar para la nueva rama "Consultar"** — `DespatchTable::sendXml()`
líneas 184-215 (`app/Livewire/DespatchTable.php`), es el bloque literal citado
por D-01 ("si ya tiene ticket guardado, solo consultar el estado"):
```php
// DespatchTable.php:184-215 — patrón de "consultar en vez de reenviar"
$api = $util->getSeeApi();

// Si ya tiene ticket guardado, solo consultar el estado
if ($despatch->sunat_ticket) {
    $result = $api->getStatus($despatch->sunat_ticket);
} else {
    // No tiene ticket: reenviar el XML firmado guardado
    // ...
}
```

**Adaptación real para `Voided`:** el equivalente de `$api->getStatus()` **ya
está escrito y probado** en
`VoidedTicketFlowService::resolveTicket(Voided $voided, string $cdrPath, ?See $see = null)`
(líneas 84-164) — no hay que reimplementar la llamada SOAP, el archivado del
CDR, ni la clasificación por código de CDR: todo eso ya lo hace el servicio.
La rama nueva de `sendXml()` solo necesita:
1. Detectar la situación (`$voided->sunat_status === VoidedStatus::Pendiente`
   con `$voided->sunat_ticket` poblado — D-02, fila 1 de la tabla).
2. Calcular `$directory`/`$name`/`$cdrPath` igual que ya hace el bloque de
   reenvío (líneas 138-139, 165 del archivo actual — mismo cálculo, no hay que
   inventarlo).
3. Llamar `$flow->resolveTicket($voided, $cdrPath)` (sin `$see`, construye el
   suyo — exactamente el uso que documenta el propio docblock del método,
   línea 82: "la Fase 2 lo reutiliza para reconsultar anulaciones pendientes
   desde el listado sin reenviar el XML").
4. Envolver en el mismo `try/catch` que ya existe (líneas 145, 181-199) porque
   `resolveTicket()` puede lanzar si algo no atrapado internamente escapa
   (aunque el propio método ya atrapa `\Throwable` en su llamada a
   `getStatus()`, líneas 89-97) — usar `$flow->classifyFailure($voided, $e)`
   igual que la rama de reenvío (línea 182).
5. Ramificar el resultado con el mismo `match`/`if` que ya usa el reenvío
   (líneas 168-176): `VoidedStatus::Pendiente` → swal `info` "Aún en proceso";
   cualquier otro → `$this->showResponse($voided)`.

**Cálculo de `$directory`/`$name`/`$cdrPath` ya existente a reutilizar**
(líneas 138-139, 165 del archivo actual — D-04 exige que el RUC salga de
`$voided->company`, no de `session('company')->ruc`; **el código actual
todavía usa `session('company')->ruc` aquí, ese es el cambio real de D-04**):
```php
// app/Livewire/VoidedTable.php:138-139 (ACTUAL — a corregir por D-04)
$directory = $voided->production ? 'sunat/' : 'sunat/beta/';
$name = session('company')->ruc.'-RC-'.$voided->fecGeneracion->format('Ymd').'-'.$voided->correlativo;
// ...
// línea 165:
$cdrPath = $directory.'cdr/R-'.$name.'.zip';
```
**Cambio D-04:** `session('company')->ruc` → `$voided->company['ruc']` (el
cast `'company' => 'array'` ya existe en `app/Models/Voided.php:37`, el JSON
lo puebla `VoidedObserver::creating()`). Este mismo `$directory`/`$name`/`$cdrPath`
debe calcularse también para la nueva rama "Consultar", porque
`resolveTicket()` recibe `$cdrPath` como parámetro y no lo calcula
internamente.

---

### `app/Livewire/VoidedTable.php::configure()`/`columns()` — badge (D-06) y `SelectFilter` (D-07)

**Estado actual de `configure()`** (líneas 17-22, ya leído):
```php
public function configure(): void
{
    $this->setPrimaryKey('id');
    $this->setAdditionalSelects(['id', 'sunat_ticket', 'sunatResponse']);
    $this->setDefaultSort('id', 'desc');
}
```
Si el badge/filtro necesitan datos adicionales fuera de lo que ya trae
`sunat_status` (columna base de `Column::make('Sunat', 'sunat_status')`,
línea 61), sumarlos aquí — pero `sunat_status` ya se selecciona implícitamente
por ser la columna de la definición.

**Precedente literal del badge (D-06) — ya en uso hoy, no es que haya que
inventarlo:** `resources/views/voideds/partials/sunatResponse.blade.php`
líneas 13, 51, 96 (confirmado con lectura completa del archivo real):
```blade
<x-wire-badge lg :color="$document->sunat_status->badgeColor()" :label="$document->sunat_status->label()" />
```
Este es el **precedente exacto** que D-06 pide replicar en
`voideds/partials/response.blade.php` (el listado), junto al botón/ícono de
cada rama — mismo prop dinámico `:color`, mismo par `badgeColor()`/`label()`
de `App\Enums\VoidedStatus` (ya existe, `app/Enums/VoidedStatus.php:11-27`).
Para el caso NULL histórico (D-08), el badge debe ser gris "SIN ESTADO" —
no hay `VoidedStatus::badgeColor()` para `null`, así que la vista resuelve el
color/label directamente en Blade, sin pasar por el enum (igual que ya hace
la rama `@else` de `response.blade.php` con el ícono `fa-question-circle
text-gray-400`, línea 50).

**`SelectFilter` — análogo de forma citado por CONTEXT** (`DespatchTable.php`
líneas 116-134, `DateRangeFilter`, no aplica literalmente por ser de fecha).
**Mejor análogo real encontrado** (búsqueda ampliada, D-07 pide exactamente
esta forma — enum de valores fijos + opción "Todos"):
`app/Livewire/VoucherTable.php::filters()` (líneas 141-154):
```php
public function filters(): array
{
    return [
        SelectFilter::make('Documentos')
            ->options([
                '' => 'Todos',
                '01' => 'Factura',
                '03' => 'Boleta',
                '07' => 'Nota de Crédito',
                '08' => 'Nota de Débito',
            ])
            ->filter(function (Builder $builder, string $value) {
                $builder->where('tipoDoc', $value);
            }),

        //Filtro por fecha
        DateRangeFilter::make('Fecha')
            ->config([...]),
    ];
}
```
**Import necesario** (no está hoy en `VoidedTable.php` — confirmado, el
archivo actual no importa `SelectFilter` ni declara `filters()`):
```php
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
```

**Adaptación para D-07** (tres estados + opción para NULL, valores desde el
enum en vez de hardcodeados — mismo espíritu que `EirTable::filters()` líneas
168-187, que sí construye opciones desde datos de dominio, aunque ahí es un
`pluck()` de valores de columna libre, no un enum; para `VoidedStatus` los
tres casos son fijos y conocidos, así que el patrón de `VoucherTable` con
`->options([...])` literal es el más directo):
```php
SelectFilter::make('Estado Sunat')
    ->options([
        '' => 'Todos',
        \App\Enums\VoidedStatus::Aceptada->value => \App\Enums\VoidedStatus::Aceptada->label(),
        \App\Enums\VoidedStatus::Pendiente->value => \App\Enums\VoidedStatus::Pendiente->label(),
        \App\Enums\VoidedStatus::Rechazada->value => \App\Enums\VoidedStatus::Rechazada->label(),
        // valor para NULL a discreción del planner (D-07 "Claude's Discretion")
    ])
    ->filter(function (Builder $builder, string $value) {
        if ($value === '') {
            return;
        }
        // rama NULL a decidir: whereNull('sunat_status') vs comparación directa
        $builder->where('sunat_status', $value);
    }),
```
Nota: `sunat_status` es un cast de enum nativo — filtrar con `->where('sunat_status', $value)`
compara contra la columna cruda (string), no contra el objeto `VoidedStatus`,
así que usar `->value` como clave de `options()` es correcto para que el
filtro llegue como string comparable.

---

### `resources/views/voideds/partials/response.blade.php` — nueva sub-rama "Consultar" + badge (D-06/D-08)

**Contenido actual completo** (57 líneas, ya leído en esta pasada — el
`01-PATTERNS.md` de la Fase 1 lo documentó como "sin análogo directo" porque
en ese momento no existía; **ahora sí existe y es el propio archivo el
análogo**, esta fase lo extiende):
```blade
<div>

    @if ($row->sunat_status === \App\Enums\VoidedStatus::Aceptada)

        <button wire:click="showResponse({{ $row->id }})" xs>
            <x-wire-icon name="check-circle" class="w-6.5 h-6.5 text-green-500" solid />
        </button>

    @elseif ($row->sunat_status === \App\Enums\VoidedStatus::Pendiente)

        {{-- D-09: nunca ofrece reenvío, aunque el JSON no sea de éxito --}}
        <button wire:click="showResponse({{ $row->id }})" xs>
            <x-wire-icon name="clock" class="w-6.5 h-6.5 text-amber-500" solid />
        </button>

    @elseif ($row->sunat_status === \App\Enums\VoidedStatus::Rechazada)

        @if (is_null($row->sunat_ticket))
            <button wire:click="sendXml({{ $row->id }})" xs>
                <img class='h-6' src="{{ asset('img/icons/get_cdr.svg') }}"/>
            </button>
        @else
            <button wire:click="showResponse({{ $row->id }})" xs>
                <i class="text-xl fas fa-ban text-red-500"></i>
            </button>
        @endif

    @else

        @if (is_null($row->sunat_ticket) && empty($row->sunatResponse))
            <button wire:click="sendXml({{ $row->id }})" xs>
                <img class='h-6' src="{{ asset('img/icons/get_cdr.svg') }}"/>
            </button>
        @else
            <button wire:click="showResponse({{ $row->id }})" xs>
                <i class="text-xl fas fa-question-circle text-gray-400"></i>
            </button>
        @endif

    @endif

</div>
```

**Cambio D-01/D-02 requerido:** la rama `@elseif ($row->sunat_status ===
\App\Enums\VoidedStatus::Pendiente)` (líneas 9-14) hoy solo muestra un ícono
que abre `showResponse()` — **debe cambiar a un botón que dispare
`sendXml({{ $row->id }})`** (mismo método, D-01: un solo punto de entrada), ya
que con la corrección de D-01/D-02 en el componente, `sendXml()` sobre una
fila `Pendiente` con ticket ya no "avisa sin consultar" sino que reconsulta de
verdad. El ícono `clock`/ámbar puede conservarse como parte del botón (D-06 no
pide sustituir los íconos, solo sumar el badge de texto).

**Cambio D-06 requerido (todas las ramas):** sumar el badge junto al botón/ícono
en cada rama, usando el precedente literal citado arriba
(`sunatResponse.blade.php:13`):
```blade
<x-wire-badge sm :color="$row->sunat_status->badgeColor()" :label="$row->sunat_status->label()" />
```
Para la rama `@else` (NULL histórico, D-08): badge gris fijo, sin pasar por el
enum:
```blade
<x-wire-badge sm label="SIN ESTADO" />
```
(color por defecto de `x-wire-badge` sin prop `:color` explícito es gris —
verificar en ejecución contra el componente WireUI real si hace falta forzar
`gray`/`secondary`; la decisión exacta del color queda a discreción del
planner, D-08 solo fija el texto y la coherencia con el ícono `fa-question-circle
text-gray-400` ya existente).

## Contratos de los servicios consumidos (no se modifican)

### `VoidedTicketFlowService::resolveTicket(Voided $voided, string $cdrPath, ?See $see = null): VoidedStatus`
(líneas 84-164, `app/Services/Sunat/VoidedTicketFlowService.php`)

**Contrato de retorno:**
- `VoidedStatus::Aceptada` — CDR con código `'0'`; efecto secundario: escribe
  `sunatResponse`, `sunat_status = Aceptada`, `sunat_ticket = null`, guarda
  dentro de `DB::transaction()`, y marca los `Invoice` relacionados como
  `voided = true` vía `markVoidedInvoices()` (privado, no invocar aparte).
- `VoidedStatus::Pendiente` — sin CDR (código 98, fallo de transporte
  atrapado, o excepción en la propia transacción de aceptación); efecto
  secundario: `markPending()` reescribe `sunatResponse['pending']`, **no
  toca `sunat_ticket`** (permanece guardado para el próximo intento), emite
  `Log::warning`.
- `VoidedStatus::Rechazada` — CDR presente con código distinto de `'0'`;
  efecto secundario: `markRejected()` escribe `sunatResponse`,
  `sunat_status = Rechazada`, **conserva `sunat_ticket`** (D-09/D-12: no se
  limpia para no reabrir la vía de reenvío hacia el error 2109).

**Efecto secundario transversal:** si `$zip` viene poblado (aunque `isSuccess()`
sea `false`), el CDR se archiva en `$cdrPath` **siempre**, antes de decidir el
desenlace (línea 115-118) — el componente no necesita archivar nada por su
cuenta.

**No lanza `\Throwable` en el flujo feliz ni en los casos SOAP esperados** —
los atrapa internamente (`getStatus()` en `try/catch` línea 89-97, la
transacción de aceptación en `try/catch` línea 136-155). El único punto donde
una excepción podría escapar hacia el componente es fuera de estos bloques
(p. ej. si `$voided->refresh()` fallara) — cubrir con
`classifyFailure()` en el `catch` exterior del componente, igual que ya hace
`sendAndResolve()` en la rama de reenvío (línea 182).

### `VoidedTicketFlowService::classifyFailure(Voided $voided, \Throwable $e): VoidedStatus`
(líneas 172-209)

**Contrato:** clasifica una excepción que escapó de `sendAndResolve()` o
`resolveTicket()`. Respeta el invariante de no degradar un desenlace ya
confirmado (`Aceptada`, o `Rechazada` con ticket) — en esos casos **devuelve
el `sunat_status` actual sin tocar la fila**, solo loguea. Si no hay ticket,
marca `Rechazada`; si hay ticket, marca `Pendiente`. El componente debe usar
el valor de retorno (no volver a leer `$voided->sunat_status` después, aunque
en la práctica coincide porque el método ya persiste antes de devolver).

### `App\Enums\VoidedStatus` (`app/Enums/VoidedStatus.php`, completo, 28 líneas)

```php
enum VoidedStatus: string
{
    case Aceptada = 'aceptada';
    case Pendiente = 'pendiente';
    case Rechazada = 'rechazada';

    public function label(): string { /* ACEPTADA / PENDIENTE / RECHAZADA */ }
    public function badgeColor(): string { /* primary / warning / danger */ }
}
```
Cast en `Voided::$casts['sunat_status']` ya activo (`app/Models/Voided.php:40`).

### `App\Models\Voided` — fuente del RUC para D-04

`$casts` (línea 37, confirmado): `'company' => 'array'`. `$fillable` no
incluye `'company'` (línea 15-32) porque **solo lo escribe `VoidedObserver::creating()`**,
no el `Voided::create()` del flujo web — coherente con la nota ya documentada
en `01-PATTERNS.md`. Acceso para D-04: `$voided->company['ruc']`, no
`session('company')->ruc`.

## Patrones Compartidos

### Multi-tenant por sesión en `builder()` (sin cambios, ya correcto)
**Fuente:** `app/Livewire/VoidedTable.php::builder()` (líneas 247-253, confirmado sin cambios respecto a Fase 1):
```php
public function builder(): Builder
{
    return Voided::query()
        ->where('branch_id', session('branch')->id)
        ->where('company_id', session('company')->id)
        ->where('production', session('company')->production);
}
```
No se modifica en esta fase — el CONTEXT lo cita como "referencia de filtrado
vigente", no como archivo a tocar.

### Un solo método de acción por columna (D-01)
**Fuente:** `DespatchTable::sendXml()` íntegro (156-253) — concentra
regenerar, enviar y consultar en un único `wire:click`. `VoidedTable::sendXml()`
adopta la misma forma, con la salvedad de la tercera rama (D-02).

### Guarda de servidor espejo de la vista (D-03)
**Fuente:** `VoidedTable::canResendXml()` (líneas 229-245, ya existe,
confirmado sin cambios necesarios — D-03 solo dice que sobrevive, no la
modifica). Si `response.blade.php` cambia sus condiciones de qué botón
mostrar, `canResendXml()` debe seguir replicándolas "hoja por hoja"
(comentario ya presente en el código, líneas 224-228).

## Sin Análogo Encontrado

Ninguno — los dos archivos en alcance tienen análogo exacto (ellos mismos, en
estado ya parcialmente avanzado por la Fase 1) o análogo de forma fuerte
(`DespatchTable::sendXml()` para la rama de consulta, `VoucherTable::filters()`
para el `SelectFilter`).

## Metadata

**Alcance de búsqueda de análogos:** `app/Livewire/*.php` (grep de
`SelectFilter` en 8 archivos), `app/Livewire/VoidedTable.php`,
`app/Livewire/DespatchTable.php`, `app/Services/Sunat/VoidedTicketFlowService.php`,
`app/Enums/VoidedStatus.php`, `app/Models/Voided.php`,
`resources/views/voideds/partials/response.blade.php`,
`resources/views/voideds/partials/sunatResponse.blade.php`
**Archivos leídos completos en esta pasada:** los 8 anteriores (todos ≤ 274
líneas, una sola lectura cada uno, sin relecturas)
**Fecha de extracción de patrones:** 2026-08-05
