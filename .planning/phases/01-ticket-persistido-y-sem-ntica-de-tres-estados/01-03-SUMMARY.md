---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
plan: 03
subsystem: sunat-invoicing
tags: [laravel-11, livewire-3, rappasoft-livewire-tables, sunat, voided, comunicacion-de-baja, blade]

# Dependency graph
requires:
  - phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
    provides: "App\\Enums\\VoidedStatus y App\\Services\\Sunat\\VoidedTicketFlowService (Plan 01); patron de orquestacion de VoidedForm::send() (Plan 02)"
provides:
  - "resources/views/voideds/partials/response.blade.php — vista propia del listado con las cuatro ramas de estado (aceptada/pendiente/rechazada/historica) y la guarda de reenvio por ausencia de ticket"
  - "VoidedTable::sendXml() delegando en VoidedTicketFlowService, con guarda de servidor contra el reenvio de una baja ya recibida por SUNAT"
  - "VoidedTable::showResponse() derivando el icono del swal del enum sunat_status, igual que VoidedForm"
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "La guarda de una accion Livewire invocable desde el cliente vive en el metodo del componente, no solo en la vista que oculta el boton (D-09)"
    - "Un listado y un formulario que comparten el mismo flujo de dominio delegan en el mismo servicio en vez de duplicar la orquestacion (D-11), ya aplicado en Plan 02 y ahora en el listado"

key-files:
  created:
    - resources/views/voideds/partials/response.blade.php
  modified:
    - app/Livewire/VoidedTable.php

key-decisions:
  - "El icono de la rama pendiente en el listado usa x-wire-icon name=\"clock\" en ambar, coherente con el badgeColor() 'warning' del enum y con el icono usado en sunatResponse.blade.php del Plan 02"
  - "La rama historica (sunat_status NULL) usa un icono neutro fa-question-circle gris para distinguirla visualmente de las tres ramas con estado conocido, sin inventar un cuarto estado del enum"
  - "cdrPath en sendXml() se sigue calculando con el $name construido a mano (RUC-RC-fecha-correlativo), igual que la linea 132 original, porque en la rama donde el XML ya existia no hay documento Greenter disponible para leer getName()"

requirements-completed: [TICKET-01, TICKET-05, WEB-01]

coverage:
  - id: D1
    description: "La vista propia voideds/partials/response.blade.php reemplaza al partial compartido de vouchers en la columna Sunat del listado, sin tocar vouchers/despatches"
    requirement: TICKET-05
    verification:
      - kind: manual_procedural
        ref: "Script PHP con bootstrap completo de Laravel (no tinker, ver Issues Encountered) que renderiza voideds.partials.response para los 6 escenarios (aceptada, pendiente con ticket, rechazada con ticket, rechazada sin ticket, historica con respuesta, historica sin respuesta) -> las 6 combinaciones renderizan sin excepcion; sendXml aparece solo en rechazada-sin-ticket e historica-sin-respuesta; git diff --name-only -- resources/views/vouchers/ vacio"
        status: pass
    human_judgment: false
  - id: D2
    description: "VoidedTable::sendXml() rechaza en el servidor reenviar una anulacion con sunat_ticket ya persistido, antes de generar XML o llamar a SUNAT"
    requirement: TICKET-01
    verification:
      - kind: unit
        ref: "Lectura de codigo: la guarda `if (! is_null($voided->sunat_ticket))` es la primera sentencia del cuerpo del metodo, antes de Storage::put, getXmlSigned y sendAndResolve; grep confirma sunat_ticket aparece antes de cualquier llamada de red"
        status: pass
    human_judgment: false
  - id: D3
    description: "sendXml() delega el envio y la clasificacion en VoidedTicketFlowService (sendAndResolve en la ruta feliz, classifyFailure en el catch exterior), sin cliente SOAP ni marcado de Invoice inline; showResponse() deriva el icono del swal con un match sobre sunat_status"
    requirement: WEB-01
    verification:
      - kind: unit
        ref: "php -l app/Livewire/VoidedTable.php; grep de getSee(/sendXmlFile/->getStatus( = 0 ocurrencias fuera de comentarios; sendAndResolve+classifyFailure = 2 ocurrencias; Invoice::where/App\\Models\\Invoice = 0 ocurrencias; vendor/bin/pint --test = PASS; builder() conserva sus tres filtros multi-tenant"
        status: pass
    human_judgment: false
  - id: D4
    description: "Verificacion manual contra el ambiente beta de SUNAT (D-14): reenvio bloqueado con ticket, reenvio permitido sin ticket, tres estados visibles en el listado, filas historicas intactas, listados ajenos sin regresion"
    verification: []
    human_judgment: true
    rationale: "D-14 descarta explicitamente infraestructura de test con SUNAT simulado; requiere una sesion de empresa activa con production=false y observar los desenlaces reales descritos en <human-check> del plan, incluyendo confirmar que el listado de facturas/guias no cambio su comportamiento. No ejecutado en esta sesion (requiere credenciales y sesion de empresa activa) — es el mismo pendiente ya anotado por el Plan 02 (item D5), ahora extendido al listado."

# Metrics
duration: 12min
completed: 2026-08-04
status: complete
---

# Phase 1 Plan 3: El listado de anulaciones distingue los tres estados y corta el reenvío indebido Summary

**`voideds/partials/response.blade.php` (vista propia del listado con cuatro ramas de estado) y `VoidedTable::sendXml()` delegando en `VoidedTicketFlowService` con una guarda de servidor que bloquea reenviar una baja que SUNAT ya recibió.**

## Performance

- **Duration:** 12 min
- **Started:** 2026-08-04T23:43:00Z
- **Completed:** 2026-08-04T23:55:01Z
- **Tasks:** 2
- **Files modified:** 2 (1 nuevo, 1 modificado)

## Accomplishments
- El listado de anulaciones deja de usar el partial compartido de `vouchers` y renderiza su propia vista (`voideds/partials/response.blade.php`), que distingue cuatro ramas: aceptada (icono verde), pendiente (icono ámbar, sin reenvío aunque el JSON no sea de éxito), rechazada con ticket (icono rojo, sin reenvío) y rechazada sin ticket (botón de reenvío legítimo porque el envío inicial nunca llegó a SUNAT)
- Las filas históricas anteriores a la migración (`sunat_status` `NULL`) siguen renderizando: solo ofrecen reenvío cuando no hay ticket **ni** respuesta guardada (nunca se envió nada); cualquier otra combinación muestra un icono neutro sin inducir a reenviar
- `VoidedTable::sendXml()` aborta con el aviso "En proceso" antes de generar nada si `sunat_ticket` ya tiene valor — la guarda vive en el servidor porque las acciones de Livewire son invocables directamente desde el cliente y ocultar el botón no impide la llamada
- `sendXml()` delega el envío en `VoidedTicketFlowService::sendAndResolve()` y el `catch` exterior en `classifyFailure()`, replicando exactamente el patrón que el Plan 02 aplicó a `VoidedForm::send()` (D-11): un único punto de envío y clasificación para formulario y listado
- `showResponse()` deriva el icono del swal con un `match` sobre `sunat_status` (`Aceptada` → info, `Pendiente` → warning, `Rechazada` → error, `null` → fallback booleano heredado)
- El marcado inline de `Invoice::where(...)->update(['voided' => true])` se retira de `VoidedTable`: vive únicamente en `VoidedTicketFlowService::markVoidedInvoices()` (Plan 01)
- `builder()` conserva intactos sus tres filtros multi-tenant (`branch_id`, `company_id`, `production`)
- Los listados de facturas/boletas, guías de remisión y guías transportista quedan intactos: ninguno de sus archivos aparece en el diff de la fase

## Task Commits

Each task was committed atomically:

1. **Task 1: Vista propia del listado con los tres estados y sin reenvío con ticket** - `09c2c8a` (feat)
2. **Task 2: sendXml() del listado delega en el servicio y protege el reenvío** - `04afc80` (feat)

_Note: Ningún task de este plan usaba `tdd="true"`; no hay commits `test(...)` separados._

## Files Created/Modified
- `resources/views/voideds/partials/response.blade.php` - Vista propia del listado con las cuatro ramas de estado y la guarda de reenvío por ausencia de ticket
- `app/Livewire/VoidedTable.php` - Columna `Sunat` renderiza la vista propia sobre `sunat_status`; `sendXml()` delega en `VoidedTicketFlowService` con guarda de reenvío; `showResponse()` deriva el icono del enum; se retira el import y el marcado inline de `Invoice`

## Decisions Made
- El icono de la rama pendiente en el listado (`x-wire-icon name="clock"` en ámbar) se eligió coherente con `badgeColor()` = `'warning'` del enum y con el icono ya usado en `sunatResponse.blade.php` (Plan 02), en vez de inventar una variante visual distinta.
- La rama histórica (`sunat_status` `NULL`) usa un icono neutro (`fa-question-circle` gris) en vez de reutilizar el icono verde o rojo de un estado conocido, para que el operador distinga a simple vista una fila sin clasificar de una fila con desenlace confirmado.
- `cdrPath` en `sendXml()` se sigue calculando con el `$name` construido a mano (`RUC-RC-fecha-correlativo`), igual que la expresión original de la línea 132: en la rama donde el XML ya existía (`xml_path` no vacío) no hay documento Greenter disponible del que leer `getName()`.

## Deviations from Plan

None - plan ejecutado exactamente como estaba escrito. La verificación de los seis escenarios de render se hizo con un script PHP que bootstrapea Laravel completo en vez de `tinker --execute=...` (ver Issues Encountered), porque el comando literal del plan usa esa sintaxis y el entorno tiene el mismo artefacto de PsySH ya documentado en el Plan 02; no es un cambio de código ni de alcance.

## Issues Encountered

**Los comandos de verificación vía `php artisan tinker --execute=...` del plan fallan en este entorno con el mismo artefacto de PsySH/WireUI ya documentado en el Plan 02** (`WireUi\View\WireUiComponent::useValidation(): Return value must be of type bool, null returned` al renderizar componentes `x-wire-icon`/`x-wire-badge` dentro de tinker). Se confirmó reutilizando el patrón del Plan 02: un script PHP que bootstrapea Laravel de la misma forma que una petición HTTP real (sin pasar por el REPL de PsySH) renderiza las seis combinaciones de `voideds.partials.response` (aceptada, pendiente con ticket, rechazada con ticket, rechazada sin ticket, histórica con respuesta, histórica sin respuesta) sin excepción y con el contenido exacto que exige el plan (`sendXml` presente solo en los dos casos previstos). No se modificó ningún comando de verificación del plan; se documenta para que la próxima sesión no persiga un fallo inexistente de tinker en este proyecto (ver también la nota ya registrada en memoria del usuario sobre `wkhtmltopdf` fallando desde la shell de PHP sin ser un fallo real del código).

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Los cuatro defectos encadenados del diagnóstico raíz quedan cortados en el código: TICKET-01 (ticket persistido antes de consultar), TICKET-05 (semántica de tres estados en formulario y listado), WEB-01 (aviso "En proceso" en ambos puntos de envío) y D-09/D-11 (reenvío bloqueado por ausencia de ticket, único servicio de dominio compartido).
- **Pendiente antes de cerrar la fase (D-14, heredado del Plan 02 y ahora extendido al listado):** la verificación manual contra el ambiente beta de SUNAT con `production = false`, recorriendo los cinco criterios de `<human-check>` de este plan — reenvío bloqueado con ticket, reenvío permitido sin ticket, tres estados visibles, filas históricas intactas, y listados ajenos (facturas/boletas, guías de remisión, guías transportista) sin regresión. No ejecutado en esta sesión; requiere credenciales y sesión de empresa activa.
- La acción "Consultar estado" que sustituirá a la guarda de reenvío del listado es WEB-02 de la Fase 2 — deliberadamente no implementada aquí. `VoidedTicketFlowService::resolveTicket()` (público desde el Plan 01) queda listo para que esa fase la reutilice sin reenviar el XML.
- El hallazgo de `Api/VoidedController` mencionado en el todo raíz queda fuera de esta fase y corresponde a la Fase 3, según el propio contexto de la fase.
- Sin bloqueos de código para dar la Fase 1 por completa en el aspecto de implementación; solo queda pendiente la verificación manual D-14 antes de considerarla verificada end-to-end.

## Self-Check: PASSED

Ambos archivos declarados (`resources/views/voideds/partials/response.blade.php`, `app/Livewire/VoidedTable.php`) existen en disco con los cambios descritos, y los dos hashes de commit (`09c2c8a`, `04afc80`) están presentes en el historial de git de la rama actual.

---
*Phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados*
*Completed: 2026-08-04*
