---
status: partial
phase: 02-reconsulta-de-anulaciones-pendientes
source: [02-01-SUMMARY.md, 02-02-SUMMARY.md]
started: 2026-08-05T18:18:32Z
updated: 2026-08-05T18:33:00Z
---

## Current Test

[testing paused — 2 items bloqueados por el ambiente beta de SUNAT]

## Tests

### 1. La acción de la columna Sunat reconsulta el ticket en vez de reenviar el XML
expected: Con una anulación Pendiente que conserva sunat_ticket, pulsar el botón de la columna Sunat consulta el ticket guardado en vez de reenviar el XML. Señal inequívoca: SUNAT no responde 2109.
coverage_id: 02-01/D1
result: pass
note: "Confirmado en sesión en vivo sobre voided_id 3: SUNAT respondió 0127 (consulta de ticket), no 2109 (reenvío de XML)."

### 2. Cuando SUNAT ya entregó el CDR, se muestra el detalle y la fila toma su estado final
expected: Si al reconsultar SUNAT ya tiene el CDR listo, se abre el modal de detalle con la respuesta completa y la fila queda Aceptada o Rechazada según el código del propio CDR (0 = aceptada). Si quedó Aceptada, el ZIP del CDR es descargable y los comprobantes involucrados aparecen marcados como anulados.
coverage_id: 02-01/D3
result: blocked
blocked_by: third-party
reason: "Inalcanzable en el ambiente beta de SUNAT. Beta no admite getStatus sobre tickets de resúmenes ni de comunicaciones de baja: responde 0127 ('El ticket no existe'). Sin CDR entregado, la rama de desenlace no se puede ejercitar. Requiere producción u homologación con credenciales SOL reales. Comprobado en navegador el 2026-08-05 sobre voided_id 3."

### 3. Recorrido completo contra el ambiente beta de SUNAT (D-14)
expected: Con production = false, dejar una comunicación de baja en Pendiente, pulsar la acción de la columna Sunat y verificar que el CDR se recupera y queda archivado sin que SUNAT responda 2109.
coverage_id: 02-01/D7
result: blocked
blocked_by: third-party
reason: "El propio D-14 es irrealizable tal como está redactado. Pedía verificar la recuperación del CDR contra beta, pero beta no permite consultar el ticket de una comunicación de baja (0127). Reproducido en navegador el 2026-08-05: pulsar la acción de la columna Sunat sobre voided_id 3 devuelve 'Aún en proceso' con code 0127 guardado en sunatResponse.pending. D-14 debe reformularse contra producción/homologación."

### 4. Cada fila del listado muestra el texto de su estado
expected: En el listado de anulaciones, cada fila muestra la palabra de su estado (ACEPTADA, PENDIENTE, RECHAZADA o SIN ESTADO) legible a simple vista, sin pasar el ratón ni abrir el modal de detalle. Los iconos siguen presentes y la fila no se descuadra.
coverage_id: 02-02/D1
result: pass
note: "Verificado en navegador el 2026-08-05 en /voideds. La fila pendiente muestra el badge ámbar PENDIENTE junto al icono de reloj, legible sin hover ni abrir el modal, y la fila no se descuadra. Cobertura parcial: solo había una fila en estado Pendiente, así que las ramas ACEPTADA, RECHAZADA y SIN ESTADO no se vieron renderizadas — en particular queda sin comprobar el punto de atención WR-07 (badge Rechazada sin fondo de color)."

### 5. El filtro "Estado Sunat" aísla las pendientes
expected: El desplegable "Estado Sunat" reduce el listado correctamente — PENDIENTE deja solo las pendientes, SIN ESTADO solo las históricas sin estado, "Todos" restablece el listado — y nunca muestra filas de otra empresa o sucursal.
coverage_id: 02-02/D3
result: pass
note: "Verificado en navegador el 2026-08-05. El desplegable 'Estado Sunat' aparece bajo Filtros con las cuatro opciones. Prueba negativa deliberada: ACEPTADA -> 'Mostrando 0 resultados' con URL ?table-filters[estado_sunat]=aceptada; PENDIENTE -> devuelve la fila, 'Mostrando 1 resultados'. Cobertura parcial: con una sola fila en la base no se pudo ejercitar SIN ESTADO ni comprobar la ausencia de fuga multi-tenant entre empresas/sucursales."

### 6. El swal "Aún en proceso" conserva el ticket para el siguiente intento
expected: Si SUNAT sigue procesando, el usuario ve "Aún en proceso" y el sunat_ticket permanece guardado (D-09)
result: pass
source: automated
coverage_id: 02-01/D2

### 7. Una anulación sin ticket sigue reenviando el XML
expected: Una anulación sin ticket sigue reenviando el XML y guardando el ticket que SUNAT devuelva (WEB-03)
result: pass
source: automated
coverage_id: 02-01/D4

### 8. Una anulación con desenlace confirmado no llama a SUNAT
expected: Una anulación Aceptada, o Rechazada que conserva ticket, no llama a SUNAT y recibe un swal explicativo (D-02 fila 3, D-12)
result: pass
source: automated
coverage_id: 02-01/D5

### 9. La ruta del CDR usa el RUC de la empresa dueña de la fila
expected: La ruta del ZIP del CDR se arma con el RUC de la empresa dueña de la fila, no con el de la empresa de la sesión (D-04)
result: pass
source: automated
coverage_id: 02-01/D6

### 10. El badge no añade un segundo punto de entrada a la reconsulta
expected: Ninguna etiqueta x-wire-badge lleva wire:click, y los tres wire:click="sendXml" / tres wire:click="showResponse" del plan 02-01 se conservan intactos (D-05)
result: pass
source: automated
coverage_id: 02-02/D2

### 11. Ningún fichero prohibido fue modificado
expected: El enum VoidedStatus sigue con tres casos y ningún fichero prohibido (enum, VoidedTicketFlowService, VoidedForm, vouchers/partials) fue modificado; no hay migraciones nuevas
result: pass
source: automated
coverage_id: 02-02/D4

## Summary

total: 11
passed: 9
issues: 0
pending: 0
skipped: 0
blocked: 2

## Gaps

<!-- Ningún gap de código nuevo salió de esta sesión. Los dos ítems bloqueados no
     son defectos del código de la Fase 2: son una limitación del ambiente beta de
     SUNAT que invalida el plan de verificación D-14 tal como estaba redactado.
     El gap de código de la fase sigue siendo el de 02-VERIFICATION.md (CR-01).
     Se registran aquí como impedimento de verificación para que plan-phase --gaps
     lo tenga en cuenta al reformular D-14. -->

- truth: "El recorrido de la reconsulta queda comprobado manualmente contra el ambiente beta de SUNAT con production = false (D-14)"
  status: blocked
  reason: >
    El ambiente beta de SUNAT no admite getStatus sobre tickets de resúmenes
    diarios ni de comunicaciones de baja: responde 0127 ("El ticket no existe").
    Confirmado en navegador el 2026-08-05 sobre voided_id 3 (production = 0,
    empresa 1 con production = false, ticket 1785940809883): la acción de la
    columna Sunat devuelve el swal "Aún en proceso" y guarda
    sunatResponse.pending = {reason: fallo_transporte, code: "0127"}.
    Fuentes: catálogo thegreenter/xcodes, docs de servicios web de Greenter.
  severity: major
  test: 3
  root_cause: "Limitación del tercero (SUNAT beta), no defecto del código de la Fase 2."
  artifacts: []
  missing:
    - "Reformular D-14 para que la verificación del recorrido completo (CDR recuperado sin 2109) se haga contra producción u homologación con credenciales SOL reales, no contra beta"
    - "Propagar la reformulación a los ítems D-14 heredados de la Fase 1 (01-UAT.md) y a los que la Fase 3 vaya a declarar para la vía API"

- truth: "Cuando la consulta del ticket falla en transporte, el sistema no afirma que SUNAT sigue procesando"
  status: failed
  reason: >
    Hallazgo colateral de esta sesión, confirmado en navegador. VoidedTable::sendXml()
    muestra el mismo swal "Aún en proceso — SUNAT todavía no ha terminado de procesar
    esta comunicación de baja" tanto para un código 98 real como para un
    fallo_transporte. En la prueba SUNAT dijo "el ticket no existe" (0127) y la
    interfaz afirmó que seguía procesando. Es la misma familia de defecto que CR-01
    de 02-REVIEW.md: la UI asegura un desenlace que SUNAT no comunicó.
    Agravante: en VoidedTicketFlowService.php:107 la comparación
    `$code === '98'` nunca casa, porque SUNAT devuelve códigos de cuatro dígitos con
    ceros a la izquierda — la propia fila lo prueba con "0127", no "127". Un "en
    proceso" genuino llega como '0098', así que la rama codigo_98 es inalcanzable y
    todo se clasifica como fallo_transporte, anulando el valor diagnóstico del motivo.
  severity: major
  test: 3
  root_cause: "app/Livewire/VoidedTable.php: el swal de la rama Pendiente no distingue el motivo devuelto por resolveTicket(). app/Services/Sunat/VoidedTicketFlowService.php:107: comparación de código sin normalizar los ceros a la izquierda."
  artifacts:
    - path: "app/Livewire/VoidedTable.php"
      issue: "Los dos bloques que despachan el swal 'Aún en proceso' (rama de reconsulta y catch exterior) no diferencian codigo_98 de fallo_transporte, y afirman un procesamiento en curso que SUNAT no ha confirmado"
    - path: "app/Services/Sunat/VoidedTicketFlowService.php"
      issue: "Línea 107: `$code === '98'` no casa con el '0098' que SUNAT devuelve; la rama codigo_98 es inalcanzable en la práctica"
  missing:
    - "Normalizar el código antes de compararlo (p. ej. ltrim($code, '0') o comparación numérica) para que codigo_98 sea alcanzable"
    - "Diferenciar el mensaje al usuario: 'SUNAT sigue procesando' solo cuando el motivo sea codigo_98; cuando sea fallo_transporte, decir que la consulta del ticket falló e incluir el código devuelto"
