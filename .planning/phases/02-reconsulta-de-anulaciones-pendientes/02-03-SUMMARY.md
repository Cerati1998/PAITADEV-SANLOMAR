---
phase: 02-reconsulta-de-anulaciones-pendientes
plan: 03
subsystem: sunat
tags: [livewire, sunat, greenter, voided, ticket-reconsulta]

requires:
  - phase: 02-reconsulta-de-anulaciones-pendientes (01, 02)
    provides: reconsulta desde el listado, legibilidad de los tres estados con badge y filtro
provides:
  - todo ticket vivo sin desenlace confirmado (incluida sunat_status NULL) rutea a resolveTicket() en vez de al aviso de rechazo falso
  - el aviso "desenlace confirmado" queda acotado a Rechazada con ticket, único caso en que la afirmación sobre el CDR es cierta
  - clasificación del motivo pendiente normalizada por valor numérico (0098 == 98)
  - único punto de composición del aviso de pendiente (dispatchPendingSwal), que distingue "en proceso real" de "fallo de consulta" y nombra el código de SUNAT en el segundo caso
affects: [voideds, fase-3-endpoint-api-anulacion, fase-4-autorizacion]

tech-stack:
  added: []
  patterns:
    - "Guarda de ruteo por exclusión de desenlaces confirmados en vez de enumeración de estados que sí se consultan (más resiliente a estados futuros)"
    - "Único método privado de composición de swal (dispatchPendingSwal) para eliminar payload duplicado entre las cuatro salidas de una misma familia de aviso"

key-files:
  created: []
  modified:
    - app/Livewire/VoidedTable.php
    - resources/views/voideds/partials/response.blade.php
    - app/Services/Sunat/VoidedTicketFlowService.php

key-decisions:
  - "sendXml() rama Consultar se expresa por exclusión (! Aceptada && ! Rechazada), no por enumeración (Pendiente || NULL), para que ningún estado presente ni futuro con ticket vivo quede huérfano (CR-01)"
  - "sendXml() rama 'Desenlace confirmado' se acota a Rechazada + ticket; ya no basta la sola presencia del ticket (D-12)"
  - "canResendXml() no cambia su lógica (D-03); su comentario documenta las dos hojas nuevas de la vista que tampoco entran en la lista de permitidos"
  - "dispatchPendingSwal() es privado y único punto de composición del aviso de pendiente, sustituyendo las cuatro salidas duplicadas (rama Consultar y rama de reenvío, cada una con try/catch) — IN-04 de 02-REVIEW.md"
  - "La normalización del código en resolveTicket() compara (int) $code === 98, no la cadena; el $code que se persiste en markPending() sigue siendo el crudo de SUNAT, con sus ceros, porque es el dato que el operador ve y busca en la documentación de SUNAT"
  - "D-14 reformulado para esta fase: contra beta sólo se verifica ruteo y honestidad del aviso (SUNAT responde 0127, no 2109); la recuperación efectiva del CDR y la clasificación de un 98 real requieren producción u homologación con credenciales SOL reales, porque beta no admite getStatus sobre tickets de comunicaciones de baja"

patterns-established:
  - "Guarda de ruteo por exclusión de desenlaces confirmados: replicable en DespatchTable/VoucherTable si algún día ganan un tercer estado no enumerado hoy"

requirements-completed: [WEB-02]

coverage:
  - id: D1
    description: "Un ticket vivo con sunat_status en NULL (fila que quedó huérfana en el gap CR-01) ofrece en el listado un botón que lo encamina a resolveTicket(), en vez de recibir el aviso de 'desenlace confirmado'"
    requirement: WEB-02
    verification:
      - kind: unit
        ref: "probeta desechable /tmp/gsd-02-03-ruteo.php — 8/8 casos de la tabla de verdad, incluido NULL+ticket+sunatResponse poblado -> CONSULTAR"
        status: pass
      - kind: manual_procedural
        ref: "human-check Tarea 1 (D-14 reformulado, beta): botón accionable en la fila con sunat_status NULL y ticket vivo, sin el aviso de 'Desenlace confirmado'"
        status: unknown
    human_judgment: true
    rationale: "El automatizado prueba la lógica de ruteo aislada (copiada del fichero editado); confirmar que el botón real de la interfaz reproduce ese ruteo contra una fila viva de beta requiere navegador, según el propio human-check del plan"
  - id: D2
    description: "El aviso 'Desenlace confirmado' sólo lo recibe una anulación Rechazada que conserva ticket"
    requirement: WEB-02
    verification:
      - kind: unit
        ref: "probeta /tmp/gsd-02-03-ruteo.php caso 4 (Rechazada+ticket -> DESENLACE_CONFIRMADO) y greps de acceptance_criteria (VoidedStatus::Rechazada dentro de 12 líneas de 'Desenlace confirmado')"
        status: pass
    human_judgment: false
  - id: D3
    description: "El aviso de pendiente distingue el 'en proceso' real (motivo codigo_98) del fallo de consulta (cualquier otro motivo), nombrando el código devuelto en el segundo caso y sin afirmar procesamiento"
    requirement: WEB-02
    verification:
      - kind: other
        ref: "greps de acceptance_criteria Tarea 2: 'Aún en proceso' == 1, 'No se pudo confirmar el estado' >= 1 con icono warning, \"'En proceso'\" == 0"
        status: pass
      - kind: manual_procedural
        ref: "human-check Tarea 2 (D-14 reformulado, beta): fila cuyo ticket SUNAT beta responde 0127 muestra 'No se pudo confirmar el estado' con el código 0127, no 'sigue procesando'"
        status: unknown
    human_judgment: true
    rationale: "Requiere reproducir en navegador el escenario real de UAT contra beta (respuesta 0127), según el human-check explícito del plan"
  - id: D4
    description: "Un código 98 con ceros a la izquierda (0098) clasifica codigo_98, no fallo_transporte"
    requirement: WEB-02
    verification:
      - kind: unit
        ref: "probeta desechable /tmp/gsd-02-03-codigo.php — 8/8 casos ('98','098','0098' -> codigo_98; '0127','127','0','',null -> fallo_transporte)"
        status: pass
    human_judgment: false

duration: 16min
completed: 2026-08-05
status: complete
---

# Phase 02 Plan 03: Cierre de gaps CR-01/GAP-2/GAP-3 en la reconsulta de anulaciones Summary

**Ruteo de `sendXml()` por exclusión de desenlaces confirmados (no por enumeración), único punto de composición del aviso de pendiente, y clasificación del código SUNAT normalizada por valor numérico.**

## Performance

- **Duration:** 16 min
- **Started:** 2026-08-05T13:47:17-05:00
- **Completed:** 2026-08-05T14:02:47-05:00
- **Tasks:** 2 completed
- **Files modified:** 3

## Accomplishments

- **GAP 1 / CR-01 cerrado.** `VoidedTable::sendXml()` — rama Consultar: la condición dejó de exigir `sunat_status === VoidedStatus::Pendiente` y ahora sólo exige ticket vivo y que el estado no sea `Aceptada` ni `Rechazada`. Una fila con ticket vivo y `sunat_status` en NULL (el caso real que dejó huérfana la fase de UAT) ya no cae en el aviso falso de "desenlace confirmado": entra a `resolveTicket()`. La rama "Avisar — desenlace confirmado" se acotó, en espejo, a `Rechazada` con ticket — el único caso en que esa afirmación sobre el CDR es cierta (D-12).
- **Hoja nueva en la vista.** `response.blade.php` gana una tercera hoja en la rama histórica (`sunat_status` NULL): con ticket vivo, dispara `sendXml()` con un botón distinto (icono `arrow-path` azul, `title` explicativo) del interrogante gris de sólo lectura que sigue usando la hoja "resto" (sin ticket, con `sunatResponse` poblado).
- **GAP 2 cerrado.** Nuevo método privado `VoidedTable::dispatchPendingSwal(Voided $voided): void`, único punto de composición del aviso de fila pendiente. Lee `pending.reason`/`pending.code` de la misma instancia que `markPending()` acaba de persistir. Sólo afirma que SUNAT sigue procesando cuando el motivo es exactamente `codigo_98`; en cualquier otro caso — incluido `fallo_transporte` o motivo ausente — dice "No se pudo confirmar el estado", nombra el código devuelto cuando existe, y deja claro que el ticket sigue guardado. Sustituye las cuatro salidas de pendiente que hoy despachaban el mismo payload duplicado (rama Consultar try/catch, rama de reenvío try/catch), eliminando IN-04 de `02-REVIEW.md`.
- **GAP 3 cerrado.** `VoidedTicketFlowService::resolveTicket()`, bloque "sin CDR": la clasificación del motivo pasó de `$code === '98'` (identidad de cadena, nunca casa porque SUNAT devuelve códigos de cuatro dígitos con ceros a la izquierda) a `(int) $code === 98` (comparación del valor numérico normalizado). `98`, `098` y `0098` clasifican `codigo_98`; `0127`, `127`, `0`, `''` y `null` clasifican `fallo_transporte`. El `$code` que se persiste en `sunatResponse.pending.code` sigue siendo el crudo de SUNAT, sin normalizar — es el dato que el operador ve y busca en la documentación de SUNAT. Ninguna transición de `VoidedStatus` cambió: `markPending()` se invoca igual en ambas ramas.
- Probetas desechables (`/tmp/gsd-02-03-ruteo.php`, `/tmp/gsd-02-03-codigo.php`) verificaron 8/8 casos cada una, copiando literalmente la lógica ya editada. Ambas se borraron al terminar; `git status` no muestra ficheros nuevos.

## Task Commits

Each task was committed atomically:

1. **Tarea 1: Un ticket vivo sin desenlace confirmado siempre tiene vía de reconsulta** - `449a958` (fix)
2. **Tarea 2: El aviso de pendiente dice lo que SUNAT respondió, y el motivo diagnóstico vuelve a ser alcanzable** - `a6bca6e` (fix)

_Sin tareas TDD en este plan; cada tarea llevó un único commit._

## Files Created/Modified

- `app/Livewire/VoidedTable.php` — `sendXml()` con las tres ramas condicionadas por exclusión de estado; `dispatchPendingSwal()` nuevo (privado); `canResendXml()` con comentario actualizado. 9 métodos públicos, sin cambio de recuento.
- `resources/views/voideds/partials/response.blade.php` — hoja nueva en la rama histórica (`sunat_status` NULL) para el ticket vivo; las otras tres ramas de estado no se tocaron.
- `app/Services/Sunat/VoidedTicketFlowService.php` — una sola línea cambiada dentro del bloque "sin CDR" de `resolveTicket()`; el resto del servicio, intacto.

## Decisions Made

Todas registradas en el frontmatter `key-decisions`. La de mayor alcance: **la condición de la rama Consultar se expresa por exclusión de los dos desenlaces confirmados** (`Aceptada`, `Rechazada`), no por enumeración de los estados que sí se consultan — así ningún estado presente ni futuro con ticket vivo queda sin vía de recuperación. Es la corrección literal que exige CR-01 y el criterio de aceptación explícito del plan.

### D-14 reformulado (heredado de este plan, no una decisión nueva)

Registrado aquí porque el plan lo exige explícitamente en su sección `<output>`:

- **Verificable contra beta** (`production = false`): que el envío persiste el ticket, que la fila queda Pendiente con el ticket vivo, que la acción de la columna Sunat rutea hacia la reconsulta (SUNAT responde algo distinto de `2109` — en beta será `0127`), y que con ese `0127` el aviso ya no afirma procesamiento sino que muestra el código devuelto.
- **Requiere producción u homologación con credenciales SOL reales** (no verificable en este plan ni en beta): el recorrido completo de una baja que quedó pendiente por un código `98` real y que, al reconsultar, recupera el CDR y archiva el ZIP; y que ese `98` real llegue como `0098` y quede clasificado `codigo_98` en `sunatResponse.pending.reason`.
- **Propagación pendiente, que este plan no ejecuta:** los ítems D-14 heredados en `01-UAT.md`, y los que la Fase 3 declare para la vía API, arrastran la misma redacción imposible (beta no admite `getStatus` sobre tickets de comunicaciones de baja ni de resúmenes diarios: responde `0127`). Quien planifique esas fases debe reformularlos igual.

### Deuda preexistente deliberadamente excluida de este plan

Para que la re-verificación de la fase no la confunda con un gap nuevo:

- **WR-01** — el ZIP del CDR se nombra `-RC-{fecGeneracion}` mientras el XML de Greenter usa `-RA-{fecComunicacion}`. Anterior a la Fase 2 (commit `35b0633`); corregirlo rompe la correspondencia con los ZIP ya archivados.
- **WR-07** — `VoidedStatus::badgeColor()` devuelve `danger`, color no registrado en WireUI. Enum de la Fase 1, **D-08 lo declara intocable**.
- **WR-02, WR-05, WR-06, WR-09** — doble swal, mensaje crudo de excepción, ausencia de `wire:loading`, `xml_path` sin comprobar en disco. Advertencias legítimas de `02-REVIEW.md`, ninguna es gap de verificación ni de UAT.
- **CR-02 / CR-04** — propiedad multi-tenant y permiso en las acciones del componente. Ya son la **Fase 4** del ROADMAP.
- **`resources/views/voideds/partials/sunatResponse.blade.php:56`** — afirma "SUNAT recibió la comunicación de baja y aún la procesa" para cualquier pendiente. Misma familia de defecto, pero esa rama del modal es hoy inalcanzable desde el listado (tras el plan 02-01 la fila Pendiente ya no ofrece botón que abra `showResponse()`). Corregirla pertenece a WR-08.
- **`VoidedForm.php`** — su swal de envío repite el mismo texto de "en proceso". D-13 es una decisión numerada y bloqueante: la fase toca sólo el listado.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

**Rutas `/tmp/` en el entorno Windows/Git Bash local.** Las probetas desechables que el plan pide en `/tmp/gsd-02-03-ruteo.php` y `/tmp/gsd-02-03-codigo.php` no resuelven a la misma ruta física según quién las invoque: el binario nativo `php.exe` traduce `/tmp` a `%TEMP%` (vía MSYS), mientras que operaciones de fichero directas en Bash resuelven `/tmp` a `D:\tmp`. Se resolvió escribiendo la probeta en `D:\tmp\` y verificando adicionalmente que el comando **literal** del plan (`php /tmp/gsd-02-03-ruteo.php`) funciona con `MSYS_NO_PATHCONV=1`, que fuerza a PHP a resolver `/tmp` como raíz de la unidad actual (`D:\tmp`, coincidente con `D:\xampp3\...` cwd). Ambas probetas se ejecutaron con éxito (8/8 casos cada una) y se borraron al terminar; no quedaron ficheros nuevos en el repositorio.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Los tres defectos de "la interfaz afirma un desenlace que SUNAT no comunicó" que motivaron este plan (CR-01, GAP-2, GAP-3) quedan cerrados en el código y verificados automáticamente (probetas, greps de acceptance_criteria, `pint`, `php -l`, `view:cache`).
- Verificación humana pendiente contra **beta**: los dos `human-check` de este plan (fila NULL+ticket ofrece botón accionable sin aviso de desenlace confirmado; fila con `0127` muestra "No se pudo confirmar el estado" con el código). Se registran como coverage `human_judgment: true` (D1, D3) para que `verify-work` los enrute a UAT.
- Verificación humana pendiente contra **producción/homologación** (fuera del alcance de este plan y de beta): recuperación efectiva del CDR tras un `98` real, y que ese `98` real llegue como `0098` y clasifique `codigo_98`. Debe registrarse como ítem de UAT de la fase.
- Ningún archivo fuera de los tres declarados en el plan cambió; no hay migraciones nuevas; el recuento de métodos públicos de `VoidedTable` se mantiene en 9.

---
*Phase: 02-reconsulta-de-anulaciones-pendientes*
*Completed: 2026-08-05*

## Self-Check: PASSED

- FOUND: `app/Livewire/VoidedTable.php`
- FOUND: `resources/views/voideds/partials/response.blade.php`
- FOUND: `app/Services/Sunat/VoidedTicketFlowService.php`
- FOUND: `.planning/phases/02-reconsulta-de-anulaciones-pendientes/02-03-SUMMARY.md`
- FOUND commit `449a958` (Tarea 1)
- FOUND commit `a6bca6e` (Tarea 2)
- FOUND commit `14adcd4` (docs: summary)
