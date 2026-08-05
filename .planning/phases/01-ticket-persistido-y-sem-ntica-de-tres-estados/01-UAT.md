---
status: testing
phase: 01-ticket-persistido-y-sem-ntica-de-tres-estados
source: [01-VERIFICATION.md]
started: 2026-08-05T00:00:00Z
updated: 2026-08-05T00:00:00Z
---

## Current Test

number: 1
name: Enviar una comunicación de baja real contra el ambiente beta de SUNAT y observar los tres desenlaces
expected: |
  `sunat_ticket` queda guardado inmediatamente tras el envío.
  En los desenlaces `98` / fallo de transporte: `sunat_status = pendiente`, el ticket se conserva, y
  `sunatResponse` trae la clave `pending` sin clave `error`.
  En el desenlace aceptado: `sunat_status = aceptada`, `sunat_ticket = null`, `cdr_path` apunta a un ZIP
  que existe de verdad en el disco configurado, y las facturas incluidas — y solo las del ambiente
  `production = false` — pasan a `voided = 1`. Verificable contra una factura de producción que comparta
  serie y correlativo: debe seguir en `voided = 0`.
awaiting: user response

## Tests

### 1. Los tres desenlaces contra SUNAT beta

expected: `sunat_ticket` guardado antes de cualquier consulta. Pendiente conserva ticket y escribe `pending`; aceptada limpia el ticket, archiva el ZIP y marca las facturas solo del ambiente correcto.
result: [pending]

### 2. Fallo posterior a un desenlace ya confirmado por SUNAT

expected: La fila permanece en `aceptada` (o `rechazada` si el desenlace previo fue rechazo por CDR con ticket) — nunca se sobrescribe. Aparece un `Log::warning` con `voided_id`, el `sunat_status` preservado y el mensaje de la excepción.
result: [pending]

### 3. Listado con los tres estados y filas históricas

expected: Una fila rechazada con ticket conservado y una histórica con `sunatResponse` poblado NO ofrecen reenvío — muestran el icono de detalle. Una rechazada sin ticket SÍ ofrece reenviar. El botón de la columna CDR sobre una fila aceptada o histórica-con-respuesta responde sin generar tráfico a SUNAT.
result: [pending]

### 4. Modal de detalle con un mensaje real de SUNAT

expected: El código y el mensaje se leen igual que antes del cambio de CR-06, sin entidades HTML visibles (`&lt;`, `&amp;`) en un mensaje de texto plano. Si el `desMotivoBaja` original contuviera marcado, se lee como texto literal en vez de ejecutarse.
result: [pending]

## Summary

total: 4
passed: 0
issues: 0
pending: 4
skipped: 0
blocked: 0

## Gaps
