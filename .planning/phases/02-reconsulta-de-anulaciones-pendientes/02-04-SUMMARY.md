---
phase: 02-reconsulta-de-anulaciones-pendientes
plan: 04
subsystem: ventas-sunat
tags: [livewire, sunat, comunicacion-de-baja, storage, error-handling]

# Dependency graph
requires:
  - phase: 02-01
    provides: dispatchPendingSwal(), la exclusion por desenlaces confirmados en la rama Consultar de sendXml(), canResendXml()
  - phase: 02-02
    provides: badge de estado en la celda Sunat y SelectFilter de estado
  - phase: 02-03
    provides: reformulacion de dispatchPendingSwal() por motivo (codigo_98 vs cualquier otro), D-14 acotado a beta
provides:
  - "sendXml() separa la preparacion local del XML firmado (regeneracion o lectura del accessor) del try que envuelve el envio a SUNAT"
  - "guarda de regeneracion que comprueba Storage::exists($voided->xml_path), no solo la columna, cerrando el enlace NOT_WIRED de 02-VERIFICATION.md"
  - "dispatchXmlUnavailableSwal(): metodo privado que registra el fallo local en el log y avisa al operador sin volcar el mensaje de la excepcion"
  - "downloadPDF() y downloadXML() con la misma guarda que downloadCDR() ya tenia (WR-04)"
affects: [03-anulacion-api, fase-4-multi-tenant-y-permisos]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Separar la preparacion local de un recurso (I/O de almacenamiento) del try/catch que un servicio de dominio interpreta como respuesta de un tercero, para que un fallo de infraestructura no se traduzca en un veredicto de negocio"
    - "Guarda de existencia real en disco (empty(path) || ! Storage::exists(path)) antes de leer o descargar, replicada en las tres rutas de fichero del componente (XML, PDF, CDR)"

key-files:
  created: []
  modified:
    - app/Livewire/VoidedTable.php

key-decisions:
  - "La preparacion local del XML (regeneracion y lectura del accessor) sale entera del try que classifyFailure() interpreta como respuesta de SUNAT; su catch delega en dispatchXmlUnavailableSwal() y nunca en classifyFailure()"
  - "La guarda de regeneracion compara Storage::exists($voided->xml_path), no solo empty($voided->xml_path) — replica literalmente el patron ya usado en downloadCDR()"
  - "blank($xml) se comprueba fuera de cualquier try, entre la fase local y la fase de envio, para cubrir el disco no-throwing (local/public) que devuelve null o cadena vacia sin lanzar"
  - "dispatchXmlUnavailableSwal() no vuelca $e->getMessage() al operador (rutas absolutas de DO Spaces, SQL, WSDL); el Throwable completo va solo a Log::error()"
  - "No se extrajo el privado comun downloadOrWarn() que proponia 02-REVIEW.md §WR-04: hubiera obligado a reescribir downloadCDR(), declarado intocable por este plan. La triplicacion de la guarda queda anotada como deuda junto a IN-06"
  - "VoidedTicketFlowService, VoidedStatus, VoidedForm y las vistas de voideds/partials y vouchers/partials no se tocaron: el gap es estructural en el llamador, no en lo que clasifica"

requirements-completed: [WEB-03]

coverage:
  - id: D1
    description: "Un fallo puramente local en la preparacion del XML (columna vacia, fichero ausente, fichero vacio, regeneracion que falla) ya no se graba como Rechazada por SUNAT: sunat_status y sunatResponse quedan intactos y el operador ve un aviso de aplicacion, no un veredicto fiscal"
    requirement: "WEB-03"
    verification:
      - kind: unit
        ref: "probeta desechable /tmp/gsd-02-04-xml.php (arnes de 7 filas replicando la cadena de decision de sendXml()) — RED contra el codigo anterior (3 aserciones estructurales fallaban), GREEN tras el fix (5 aserciones estructurales + 7 filas de comportamiento)"
        status: pass
      - kind: manual_procedural
        ref: "human-check Tarea 1 (02-04-PLAN.md): caso A fichero ausente y caso B fichero vacio, reproducibles en local sin SUNAT beta"
        status: unknown
    human_judgment: true
    rationale: "El comportamiento observable en la interfaz real (sunat_status intacto, aviso legible, entrada en storage/logs/laravel.log) exige reproducir el escenario en el entorno local del usuario; la probeta prueba la logica de decision pero no sustituye la comprobacion visual de la fila y del log."
  - id: D2
    description: "downloadPDF() y downloadXML() aplican la misma guarda Storage::exists() que downloadCDR(): pulsar el icono sobre una fila sin fichero devuelve un aviso legible en vez de un error 500 de Livewire"
    requirement: "WEB-03"
    verification:
      - kind: unit
        ref: "probeta desechable /tmp/gsd-02-04-descargas.php (delimita las tres regiones de metodo y verifica exists-antes-de-download) — RED contra el codigo anterior (downloadPDF/downloadXML sin guarda), GREEN tras el fix"
        status: pass
      - kind: manual_procedural
        ref: "human-check Tarea 2 (02-04-PLAN.md): iconos PDF/XML sobre fila con paths NULL y sobre fila con fichero borrado; icono CDR sin regresion"
        status: unknown
    human_judgment: true
    rationale: "Confirmar la ausencia de error 500 de Livewire y la legibilidad del aviso requiere pulsar el icono en el navegador contra una fila preparada en el entorno local; no reproducible por grep."

# Metrics
duration: 20min
completed: 2026-08-05
status: complete
---

# Phase 02 Plan 04: Un fallo local de almacenamiento nunca se afirma como rechazo de SUNAT — Summary

**Reestructura `VoidedTable::sendXml()` para que la preparación local del XML firmado quede fuera del `try` que `VoidedTicketFlowService::classifyFailure()` interpreta como respuesta de SUNAT, y añade a `downloadPDF()`/`downloadXML()` la misma guarda `Storage::exists()` que `downloadCDR()` ya tenía.**

## Performance

- **Duration:** ~20 min
- **Completed:** 2026-08-05
- **Tasks:** 2
- **Files modified:** 1 (`app/Livewire/VoidedTable.php`)

## Accomplishments

- Cerrado el gap #12 (único `status: failed` de `02-VERIFICATION.md`, CR-01 de `02-REVIEW.md`): un fallo local en la preparación del XML firmado ya no se graba como `sunat_status = Rechazada` con el mensaje crudo de una excepción de PHP en el lugar de la respuesta de SUNAT.
- Cerrado WR-04: `downloadPDF()` y `downloadXML()` dejan de reventar con error 500 de Livewire sobre filas sin fichero archivado.
- Documentado y dejado explícitamente fuera de esta ronda el hallazgo nuevo del código `0127` (aportado por el usuario en la sesión de planificación), para que la próxima re-verificación no lo confunda con un gap nuevo.

## Task Commits

Cada tarea se comprobó primero contra el código sin corregir (RED) con una probeta desechable, y sólo entonces se aplicó el fix (GREEN), tal como exigía el plan:

1. **Tarea 1: Un fallo local de almacenamiento nunca se graba como rechazo de SUNAT** — `bcb69da` (fix)
2. **Tarea 2: Los iconos de descarga dejan de reventar sobre filas sin fichero** — `412c2cd` (fix)

**Plan metadata:** (commit de documentación pendiente al final de este SUMMARY)

## Files Created/Modified

- `app/Livewire/VoidedTable.php` — único fichero tocado por el plan (verificado con `git show --stat` sobre ambos commits). Cambios:
  - Import nuevo: `use Illuminate\Support\Facades\Log;`.
  - `sendXml()` rama de reenvío reestructurada en dos fases separadas (ver detalle abajo).
  - Método privado nuevo `dispatchXmlUnavailableSwal(Voided $voided, ?\Throwable $e): void`.
  - Guardas `Storage::exists()` nuevas en `downloadPDF()` y `downloadXML()`.

## Decisions Made

### 1. Forma final de la rama de reenvío de `sendXml()`

La rama de reenvío queda en tres fases, en este orden de líneas:

1. **Fase local** (nueva, líneas 245-277): declara `$xml = null;` y abre un `try` que contiene *sólo* la obtención del XML — la regeneración completa (`VoidedService::getVoided()`, `UtilService::getXmlSigned()`, `getHashSign()`, los dos `Storage::put()`, `getReportPdf()` y el `save()`) o la lectura del accessor `$voided->xml`. Su `catch (\Throwable $e)` llama a `dispatchXmlUnavailableSwal($voided, $e)` y hace `return;` — **nunca** invoca `classifyFailure()`.
   - Guarda de regeneración exacta: `empty($voided->xml_path) || ! Storage::exists($voided->xml_path)` — comprueba el disco, no sólo la columna, replicando literalmente el patrón de `downloadCDR()`.
2. **Validación** (nueva, línea 279-287): `if (blank($xml))` — cubre el disco no-throwing (`local`/`public`, `'throw' => false`), donde `Storage::get()` puede devolver `null` o `''` sin lanzar. Si es cierto, `dispatchXmlUnavailableSwal($voided, null)` y `return;`. Queda **antes** del `try` de envío y **antes** de la llamada a `sendAndResolve(`.
3. **Fase de envío** (la de siempre, acotada, líneas 289-320): el `try` contiene únicamente `$flow->sendAndResolve($voided, $xml, $this->cdrPathFor($voided))`, la ramificación por `VoidedStatus::Pendiente`, `showResponse()` y su `catch (\Throwable $e)` con `classifyFailure()` — **byte a byte igual que antes**, incluido `'text' => $e->getMessage()` (WR-06 sigue fuera de alcance).

El árbol de tres ramas que fijó `02-01`/`02-03` (Consultar / Desenlace confirmado / Reenviar), `dispatchPendingSwal()`, `cdrPathFor()`, `canResendXml()` y el `SelectFilter` de `02-02` **no cambiaron**: verificado por conteo (`dispatchPendingSwal`=5, `canResendXml`=2, `Aún en proceso`=1, las tres condiciones literales de las ramas Consultar/Avisar).

### 2. Contrato de `dispatchXmlUnavailableSwal()`

```php
private function dispatchXmlUnavailableSwal(Voided $voided, ?\Throwable $e): void
```

- **Dos puntos de llamada:** el `catch` de la fase local (con el `Throwable` real) y la validación `blank($xml)` (con `null`, porque no hubo excepción).
- **Qué registra:** `Log::error('No se pudo preparar el XML firmado de la comunicación de baja', ['voided_id' => ..., 'xml_path' => ..., 'exception' => $e])` — el `Throwable` completo (o `null`), nunca `$e->getMessage()` a secas.
- **Qué muestra al operador:** un `swal` de icono `error`, título "No se pudo preparar el XML", y un texto fijo que dice explícitamente: (a) el XML firmado no está disponible en el almacenamiento, (b) **no se envió nada a SUNAT y el estado de la fila no cambió**, y (c) el detalle quedó registrado. El mensaje de la excepción **no** viaja al texto — decisión deliberada (T-02-04-03 del threat_model): un `UnableToWriteFile` o `UnableToReadFile` trae rutas absolutas del bucket de DO Spaces; un `QueryException` trae SQL.
- Es el único punto de log del componente: `grep -c 'Log::error'` = 1.

### 3. Guardas de descarga y decisión de no extraer un privado común

`downloadPDF()` y `downloadXML()` replican exactamente el patrón que ya tenía `downloadCDR()`: `empty($voided->{path}) || ! Storage::exists($voided->{path})` → `swal` de icono `info` (título "Sin PDF" / "Sin XML") → `return;` → si pasa la guarda, `Storage::download()` intacto.

**Decisión declarada:** no se extrajo el privado común `downloadOrWarn()` que proponía `02-REVIEW.md` §WR-04. Extraerlo habría obligado a reescribir `downloadCDR()` — cuyo docblock razona el motivo de su guarda y que este plan declara intocado —, y el ahorro de líneas no compensaba salir del alcance de un plan quirúrgico de un solo fichero. La duplicación de las tres guardas (`downloadPDF`, `downloadXML`, `downloadCDR`) queda anotada como **deuda**, junto a IN-06 (que ya señalaba `sendXml()` por encima de 130 líneas y `$directory` duplicado).

### 4. `Log::error()` sólo en la ruta nueva; WR-06 sigue abierto

`grep -c 'Log::error'` = 1 (sólo `dispatchXmlUnavailableSwal()`). Los dos `catch` preexistentes de SUNAT (rama Consultar y rama de envío) siguen sin `Log::error()` y siguen mostrando `'text' => $e->getMessage()` al operador — `grep -c -F '$e->getMessage()'` = 2, sin cambios. **WR-06 sigue siendo deuda registrada, sin tocar**, por indicación explícita de alcance del plan.

## Deuda preexistente registrada (no confundir con gaps nuevos en la próxima re-verificación)

Ninguno de estos ítems se corrigió en este plan; quedan tal como estaban:

- **WR-01** — doble `swal` en el `catch` de la rama Consultar (muestra `showResponse()` y además un swal de error).
- **WR-02 / WR-03** — rama Pendiente del modal de detalle inalcanzable / sin condicionar por motivo.
- **WR-05 / WR-08** — `markRejected()` sin invariante de "desenlace confirmado" propio; `resolveTicket()` público sin guarda de entrada; ausencia de `wire:loading` (doble pulsación).
- **WR-06** — los dos `catch` de SUNAT muestran `$e->getMessage()` sin `Log::error()`. Fuera de alcance por indicación explícita; conteo verificado en 2.
- **WR-07** — certificado/endpoint SUNAT anclados a `session('company')` mientras la ruta del CDR usa la empresa de la fila. Sólo explotable vía CR-02/IN-01.
- **CR-02 / IN-01** — falta de comprobación de propiedad multi-tenant/permiso en las acciones públicas del componente. **Diferido a la Fase 4** del ROADMAP (criterios 1 y 2), por ser transversal a todas las tablas Livewire.
- **IN-02** — nomenclatura RC/fecGeneracion del ZIP del CDR frente a RA/fecComunicacion de Greenter. Anterior a la Fase 2; requiere migración de datos.
- **IN-03** — `VoidedStatus::badgeColor()` devuelve `danger`, no registrado en WireUI. D-08 declara el enum intocable en esta fase.
- **IN-04…IN-10** — rama muerta del filtro, cadena mágica `sin-estado` duplicada, `sendXml()` por encima de 130 líneas y `$directory` duplicado (ahora agravado por la triplicación de la guarda de descarga, ver decisión §3), `$voided->company['ruc']` sin comprobación, código `99` clasificado `fallo_transporte`, botones sin `type`/`title`, ausencia de cobertura automatizada persistente.

### El código `0127` no es un fallo de comunicación — hallazgo nuevo, fuera de esta ronda

Aportado por el usuario durante la sesión de planificación (`/gsd-plan-phase 02 --gaps`), no figura en `02-VERIFICATION.md` ni en `02-REVIEW.md`. `0127` es una **respuesta efectiva de SUNAT** ("el ticket no existe"), no un fallo de transporte:

1. **Motivo persistido falso:** `VoidedTicketFlowService.php:109` clasifica cualquier código distinto de `98` como `fallo_transporte`, así que un `0127` —observado en vivo el 2026-08-05 sobre `voided_id` 3, documentado en `02-UAT.md`— queda registrado como fallo de red en `sunatResponse.pending.reason` cuando en realidad SUNAT sí respondió. Alcance tras `02-03`: sólo el diagnóstico persistido, no lo que ve el operador (`dispatchPendingSwal()` ya es honesto en pantalla).
2. **Sin vía de recuperación:** `canResendXml()` exige `sunat_ticket` nulo en sus dos casos; una fila cuyo ticket SUNAT no reconoce queda `Pendiente` de forma permanente, sin ruta de salida en la interfaz.

**Matiz imprescindible:** contra el ambiente **beta** no se puede distinguir "beta no admite `getStatus` sobre tickets de comunicaciones de baja" de "el ticket realmente no existe" — beta devuelve `0127` en los dos casos (la misma razón por la que D-14 se reformuló en `02-03`). Sólo en producción u homologación el `0127` es informativo. Queda fuera de `02-04` por decisión explícita del usuario al fijar el alcance de esta ronda; **candidato para la Fase 3 o una ronda de cierre posterior**.

## Verificación humana pendiente

Los **seis** ítems `human_verification` del frontmatter de `02-VERIFICATION.md` siguen abiertos — este plan no cierra ninguno (cubren D-14/beta, legibilidad de badges, el color `danger` del enum y el filtro `SelectFilter`, todos ajenos al alcance de `02-04`).

Este plan añade **dos** `human-check` propios, ambos reproducibles **en local, sin depender de SUNAT beta** (a diferencia de los seis anteriores):

- **Tarea 1:** sobre una fila con reenvío ofrecido, caso A (fichero de `xml_path` borrado del disco → debe regenerarse y continuar el envío) y caso B (fichero de `xml_path` con tamaño cero → debe aparecer "No se pudo preparar el XML", `sunat_status` sin cambiar, entrada `error` en `storage/logs/laravel.log`).
- **Tarea 2:** iconos PDF/XML sobre fila con `pdf_path`/`xml_path` en NULL y sobre fila con fichero borrado del disco → aviso "Sin PDF"/"Sin XML" sin error 500; icono CDR sin regresión (descarga con CDR archivado, aviso "Sin CDR" sin él).

Quedan pendientes de ejecución por el usuario contra su entorno local.

## Deviations from Plan

None - plan executed exactly as written. Las dos probetas desechables (`/tmp/gsd-02-04-xml.php`, `/tmp/gsd-02-04-descargas.php`) se escribieron y ejecutaron en RED contra el código sin corregir, confirmando que discriminan el defecto, y en GREEN tras aplicar cada fix; ambas se borraron al terminar (`git status --porcelain` no muestra ficheros nuevos dentro del repositorio).

## Issues Encountered

Dos comentarios de código añadidos durante la Tarea 1 mencionaban literalmente `sendAndResolve(` y `classifyFailure(` dentro de su prosa explicativa, lo que inflaba a 2 el recuento de `grep -c -F 'sendAndResolve('` (criterio de aceptación = 1) y a 4 el de `grep -c 'classifyFailure'` (criterio de aceptación = 2). Se reformularon ambos comentarios para no repetir esos identificadores literales, sin cambiar su intención explicativa, antes del commit de la Tarea 1. No es una desviación de alcance — no tocó lógica, sólo prosa de comentarios — así que no se registra como Rule 1/2/3.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- El único gap `status: failed` de `02-VERIFICATION.md` (gap #12, CR-01) y su advertencia hermana (WR-04) quedan cerrados en código. La próxima re-verificación de la Fase 2 debería confirmar ambos vía lectura directa (greps de este plan) y, cuando el usuario lo ejecute, los dos `human-check` nuevos.
- El hallazgo del código `0127` (documentado arriba) y toda la deuda preexistente listada quedan como candidatos explícitos para la Fase 3 o una ronda de cierre posterior — no deben confundirse con gaps nuevos introducidos por este plan.
- CR-02/IN-01 (propiedad multi-tenant/permiso en acciones públicas de componentes Livewire) sigue asignado a la Fase 4 del ROADMAP, sin cambios.

---
*Phase: 02-reconsulta-de-anulaciones-pendientes*
*Completed: 2026-08-05*

## Self-Check: PASSED

- FOUND: `app/Livewire/VoidedTable.php`
- FOUND: `.planning/phases/02-reconsulta-de-anulaciones-pendientes/02-04-SUMMARY.md`
- FOUND commit: `bcb69da` (fix(02-04): un fallo local del XML nunca se graba como rechazo de SUNAT)
- FOUND commit: `412c2cd` (fix(02-04): los iconos de PDF/XML dejan de reventar sobre filas sin fichero)
