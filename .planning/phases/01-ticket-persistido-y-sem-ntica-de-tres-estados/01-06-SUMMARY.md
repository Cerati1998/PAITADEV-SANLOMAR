---
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
plan: 06
subsystem: sunat
tags: [blade, xss, security, sunat, comunicacion-de-baja, voided]

# Dependency graph
requires:
  - phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
    provides: partial de tres estados creado en el Plan 02 (D-13), selects reales y guarda de reenvío del Plan 05
provides:
  - "sunatResponse.blade.php escapa el mensaje de error de SUNAT en sus dos únicas ramas de salida sin escapar (línea 140, desenlace rechazado; línea 209, fallback histórico)"
affects: [auditoria-de-fase-01]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Escapar en el punto de salida (sintaxis {{ }} de Blade, vía e()/htmlspecialchars) en vez de sanear la entrada, cuando la entrada viaja también a un XML firmado que no debe alterarse"

key-files:
  created: []
  modified:
    - resources/views/voideds/partials/sunatResponse.blade.php

key-decisions:
  - "Se escapó en el punto de salida y no se saneó desMotivoBaja en la entrada, porque ese campo viaja al XML firmado que se envía a SUNAT y sanearlo ahí rompería el comprobante"
  - "Se conservó el mensaje (no se eliminó): las dos ocurrencias de sunatResponse['error']['message'] siguen presentes, solo cambia la sintaxis de salida de {!! !!} a {{ }}"

requirements-completed: [WEB-01]

coverage:
  - id: D1
    description: "Las dos únicas salidas sin escapar del partial de detalle de anulación (líneas 140 y 209) pasan a salida escapada de Blade, cerrando CR-06 de 01-REVIEW.md sin perder el contenido del mensaje"
    requirement: "WEB-01"
    verification:
      - kind: manual_procedural
        ref: "storage/app/verify-01-06.php (script temporal, borrado tras ejecutar) — payload <img src=x onerror=alert(1)> en las dos ramas (Rechazada con cdrResponse ausente, y fallback histórico sunat_status=null) se renderiza solo en forma escapada &lt;img src=x (OK-PAYLOAD-ESCAPADO-EN-AMBAS-RAMAS); un mensaje de texto plano se renderiza íntegro (OK-TEXTO-PLANO-INTACTO)"
        status: pass
      - kind: other
        ref: "grep -c '{!!' → 0; grep -c \"sunatResponse\\['error'\\]\\['message'\\]\" → 2; grep -c '?? null }}' → 2; grep -c '' (líneas) → 216; git diff --numstat → 2 añadidas / 2 eliminadas en un único archivo"
        status: pass
    human_judgment: true
    rationale: "El contrato del plan incluye además una verificación manual contra datos reales (abrir el modal de una anulación rechazada real, una histórica y una aceptada/pendiente) que no se ejecutó en esta sesión — solo la verificación automatizada en runtime con datos en memoria."

# Metrics
duration: 6min
completed: 2026-08-05
status: complete
---

# Phase 01 Plan 06: Cierre de CR-06 — mensaje de SUNAT escapado en el modal de detalle Summary

**Las dos únicas salidas sin escapar de `sunatResponse.blade.php` (líneas 140 y 209) pasan a sintaxis de salida escapada de Blade, cerrando el XSS almacenado de CR-06 sin perder el mensaje que SUNAT devuelve.**

## Performance

- **Duration:** ~6 min
- **Completed:** 2026-08-05T15:53:00Z
- **Tasks:** 1
- **Files modified:** 1

## Accomplishments
- `resources/views/voideds/partials/sunatResponse.blade.php`: las dos ocurrencias de `{!! $document->sunatResponse['error']['message'] ?? null !!}` (línea 140, rama rechazada sin `cdrResponse`; línea 209, fallback histórico con `sunat_status` nulo) pasan a `{{ $document->sunatResponse['error']['message'] ?? null }}`, conservando la expresión y el operador de coalescencia nula.
- Verificado en runtime con un script desechable (`storage/app/verify-01-06.php`, borrado tras ejecutar): un payload `<img src=x onerror=alert(1)>` inyectado como `desMotivoBaja` reflejado por SUNAT se renderiza únicamente en su forma escapada `&lt;img src=x` en las dos ramas, y un mensaje de texto plano se sigue viendo íntegro.
- Ninguna otra línea del archivo cambió: el diff es de exactamente 2 líneas añadidas y 2 eliminadas en un único archivo, y el total de líneas (216) no varió.

## Task Commits

Cada tarea se commiteó de forma atómica:

1. **Tarea única: el mensaje de error de SUNAT se renderiza escapado en las dos ramas del modal** - `1efbea5` (fix)

**Plan metadata:** (pendiente, se genera en el commit final de este plan)

## Files Created/Modified
- `resources/views/voideds/partials/sunatResponse.blade.php` - las dos únicas salidas sin escapar del archivo (líneas 140 y 209) pasan a salida escapada, conservando `?? null`

## Decisions Made
- Se escapó en el punto de salida en vez de sanear `desMotivoBaja` en la entrada: ese campo se valida solo por longitud (`required|min:3|max:250`) en `VoidedForm::send()` y viaja al XML firmado enviado a SUNAT, así que sanearlo ahí alteraría el comprobante. El escape en la salida es la mitigación correcta y no toca el flujo de emisión.
- Se conservó el acceso al mensaje (no se eliminó ni se sustituyó por un texto genérico): el operador sigue viendo la respuesta exacta de SUNAT, solo que como texto literal cuando contiene marcado.
- No se tocaron los dos `<x-wire-badge :label="...['error']['code']">` (líneas 136 y 205): `code` es un identificador numérico de SUNAT y WireUI escapa su propio `label`; ampliar el alcance ahí quedaba fuera de CR-06 (T-01-30, disposición `accept`).
- No se modificaron `app/Livewire/VoidedTable.php` ni `app/Livewire/Forms/VoidedForm.php` (los dos consumidores que inyectan el `->render()` del partial en el swal): la corrección vive en el punto de salida, y `VoidedTable.php` es archivo del Plan 01-05 en la misma wave lógica.

## Deviations from Plan

None - plan ejecutado tal como estaba escrito. El script de verificación en runtime `storage/app/verify-01-06.php` se escribió, ejecutó y borró según lo especificado en `<acceptance_criteria>`; no quedó listado en `git status --porcelain`.

## Issues Encountered
None.

## User Setup Required
None - no se requiere configuración externa.

## Verificación manual pendiente

Este plan no incluye la verificación manual contra datos reales descrita en `<human-check>` de `01-06-PLAN.md`: abrir en el navegador el detalle de una anulación rechazada real, de una fila histórica con `sunat_status` nulo, y de una aceptada/pendiente, para confirmar visualmente que el mensaje se sigue leyendo igual y que las otras ramas no cambiaron. Las verificaciones automatizadas (grep de sintaxis, diff acotado, y el script desechable en runtime con datos en memoria) pasaron íntegras: `OK-SIN-SALIDA-CRUDA` (implícito por `grep -c '{!!' = 0`), `OK-MENSAJE-CONSERVADO`, `OK-COALESCENCIA-CONSERVADA`, `OK-MISMO-NUMERO-DE-LINEAS`, `OK-PAYLOAD-ESCAPADO-EN-AMBAS-RAMAS`, `OK-TEXTO-PLANO-INTACTO`.

**Nota sobre la suite de tests.** Este plan no ejecutó `vendor/bin/phpunit`: la suite del repo corre contra la base de datos configurada (no hay override sqlite habilitado) y es destructiva sin esa configuración. La verificación se hizo con el script desechable descrito arriba, que arranca el kernel de Laravel sin tocar la BD real.

## Next Phase Readiness
- Los tres planes de cierre de gaps (01-04, 01-05, 01-06) quedan completos a nivel de código y verificación automatizada. `T-01-27` pasa de `accept` (registrado en 01-05) a `mitigate` en este plan, sin contradicción entre ambos documentos.
- Falta, para dar la Fase 1 por completamente cerrada, la confirmación manual contra SUNAT beta (D-14, planes 01-04 y 01-05) y la verificación manual del modal descrita arriba (este plan).
- El diff completo del plan lista exactamente un archivo: `resources/views/voideds/partials/sunatResponse.blade.php`. Nada bajo `vendor/`, nada bajo `resources/views/vouchers/`, ni `response.blade.php`, ni `app/Livewire/VoidedTable.php`, ni `app/Services/Sunat/VoidedTicketFlowService.php`.

---
*Phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados*
*Completed: 2026-08-05*

## Self-Check: PASSED

- FOUND: resources/views/voideds/partials/sunatResponse.blade.php
- FOUND: 1efbea5 (Tarea única commit)
- FOUND: .planning/phases/01-ticket-persistido-y-sem-ntica-de-tres-estados/01-06-SUMMARY.md
