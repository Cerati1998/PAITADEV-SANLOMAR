---
created: 2026-08-04
title: Anulacion no recupera el CDR de la comunicacion de baja
area: sunat
files:
  - app/Livewire/Forms/VoidedForm.php
  - app/Livewire/VoidedTable.php
  - app/Models/Voided.php
  - app/Services/Sunat/VoidedService.php
  - app/Traits/Sunat/UtilTrait.php
  - app/Http/Controllers/Api/VoidedController.php
  - database/migrations/
---

## Problem

Al anular comprobantes, el sistema guarda `sunatResponse.error.message = "Bad Request"`
y pinta la comunicacion de baja en rojo, pero la consulta web de SUNAT muestra el
comprobante **efectivamente dado de baja**. La baja si se registro; lo que fallo fue
la consulta posterior del ticket, y esa respuesta de error es la que quedo persistida.

El flujo en `app/Livewire/Forms/VoidedForm.php:75-78` es:

```php
$ticket = $result->getTicket();
$result  = $see->getStatus($ticket);            // consulta inmediata, sin espera
$voided->sunatResponse = $util->getResponse($result);
```

Cuatro defectos encadenados:

1. **El ticket nunca se persiste.** No hay columna `sunat_ticket` en `voideds`
   (`database/migrations/2024_12_16_111346_create_voideds_table.php`,
   `app/Models/Voided.php:14-29`). Vive solo en la variable local, asi que cuando
   `getStatus()` falla el ticket se pierde y ya no hay forma de reconsultar.

2. **Se consulta el ticket sin espera ni reintento.** SUNAT responde `98`
   (en proceso) o un `SoapFault` HTTP 400. Ese "Bad Request" sale de
   `BaseSunat::getErrorFromFault()` (`vendor/greenter/ws/src/Ws/Services/BaseSunat.php:93-103`):
   como `faultcode`/`faultstring` no traen digitos, el codigo queda vacio y el
   mensaje cae al `faultstring` crudo.

3. **`getResponse()` no distingue pendiente de rechazado**
   (`app/Traits/Sunat/UtilTrait.php:106-132`). Todo lo que no es exito se guarda
   como `success: false` + `error`. `getResponseTicket()` (lineas 134-153) quedo a
   medias: obtiene el ticket, consulta el estado y no retorna nada.

4. **La unica recuperacion de la UI reenvia el XML.** `VoidedTable::sendXml()`
   (`app/Livewire/VoidedTable.php:107`) vuelve a hacer `sendXmlFile()`, lo que en
   SUNAT devuelve `2109` (la comunicacion de baja ya fue informada). Nunca recupera
   el CDR.

## Solution

Replicar el patron que **ya funciona en este mismo repo** para guias de remision:

- `Despatch` tiene columna `sunat_ticket`
  (`database/migrations/2024_11_06_014056_create_despatches_table.php:36`).
- `DespatchForm.php:292-325` persiste el ticket **antes** de consultar, trata el
  codigo `98` como "en proceso" conservando el ticket, y solo lo limpia cuando ya
  tiene el CDR.
- `DespatchTable::sendXml()` (`app/Livewire/DespatchTable.php:186-242`) reconsulta
  con el ticket guardado en vez de reenviar el XML.

Trabajo concreto:

- Migracion: `sunat_ticket` nullable en `voideds`; agregarlo a `$fillable` de `Voided`.
- `VoidedForm::send()`: guardar el ticket apenas SUNAT lo devuelve, antes del
  `getStatus()`. Tratar codigo `98` **y el `SoapFault` de transporte** como pendiente:
  conservar el ticket, no escribir un `sunatResponse` de rechazo, y avisar
  "en proceso" en vez de error.
- `VoidedTable`: accion "Consultar estado" que use el ticket guardado; reservar el
  reenvio solo para cuando no haya ticket. Limpiar el ticket al obtener el CDR y
  recien ahi marcar los `Invoice` como `voided`.
- Diferenciar en `sunatResponse` los tres estados (aceptado / pendiente / rechazado)
  para que la UI no pinte en rojo una baja que SUNAT si acepto.

## Hallazgo aparte (mismo archivo, distinto bug)

`Api/VoidedController::send()` llama `$sunat->getSee()`, `$sunat->getResponse()` y
`$sunat->getReportHtml()`, pero `App\Services\Sunat\VoidedService` **importa**
`UtilTrait` sin hacer `use UtilTrait;` en el cuerpo de la clase, y no tiene
constructor que reciba la company. El endpoint API de anulacion revienta con
`Call to undefined method`. Entra en el mismo alcance del fix.
