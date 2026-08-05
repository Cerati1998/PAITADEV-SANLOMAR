---
phase: 02-reconsulta-de-anulaciones-pendientes
verified: 2026-08-05T23:55:00Z
status: gaps_found
score: 12/13 must-haves verificados en código (1 fallido, ver gaps — gap NUEVO, no reapertura del #12)
behavior_unverified: 0
overrides_applied: 0
re_verification:
  previous_status: gaps_found
  previous_score: 11/12
  gaps_closed:
    - "Gap #12 (CR-01, vector de almacenamiento local): en la rama de reenvío de sendXml(), la guarda de regeneración ahora comprueba Storage::exists($voided->xml_path) y no solo la columna; la preparación local del XML (regeneración o lectura del accessor) vive en un try propio cuyo catch llama a dispatchXmlUnavailableSwal() y NUNCA a classifyFailure(); blank($xml) corta antes de cualquier try de envío. Un fichero ausente, vacío o una regeneración que falla ya no se persiste como sunat_status = Rechazada con el mensaje de un TypeError/UnableToReadFile en el lugar de la respuesta de SUNAT."
    - "WR-04 (downloadPDF()/downloadXML() sin guarda): ambos métodos aplican ahora la misma guarda empty(...) || ! Storage::exists(...) que downloadCDR() ya tenía; una fila sin fichero devuelve un swal 'Sin PDF'/'Sin XML' en vez de un error 500 de Livewire."
  gaps_remaining: []
  regressions: []
gaps:
  - truth: "Ninguna salida de VoidedTicketFlowService::sendAndResolve() afirma un rechazo de SUNAT cuando el documento nunca llegó a SUNAT — ni por un XML almacenado corrupto (no vacío) que revienta al parsear antes de abrir socket, ni por un fallo de transporte SOAP (timeout/DNS/TLS) (Phase Goal, 'el listado dice la verdad sobre en qué estado está cada baja'; CR-01 residual de 02-REVIEW.md, hallazgo NUEVO, distinto del gap #12 que 02-04 cerró)"
    status: failed
    reason: >
      Este es un hallazgo DISTINTO del gap #12 que 02-04-PLAN.md cerró. El gap
      #12 estaba acotado, por su propio texto `missing:`, a la preparación
      LOCAL del XML dentro de `VoidedTable::sendXml()` (columna `xml_path`
      poblada sin fichero detrás, lectura vacía, regeneración que falla) — y
      esa vía está verificablemente cerrada en el código actual (ver
      `gaps_closed` arriba). El hallazgo que aquí se abre vive un nivel más
      abajo, dentro de `VoidedTicketFlowService::sendAndResolve()`
      (líneas 34-50), un fichero que 02-04-PLAN.md declaró explícitamente
      intocable con el razonamiento de que "classifyFailure() clasifica
      correctamente lo que recibe" — razonamiento válido para el vector que
      02-04 cerró, pero que no cubre este.

      Verificado independientemente, sin apoyarme en la narrativa de
      `02-REVIEW.md`, leyendo el código de la aplicación y el de
      `vendor/greenter`:

      1. `sendAndResolve()` (VoidedTicketFlowService.php:34-50) envuelve
         `$see->sendXmlFile($xml)` en un único `try`, cuyo `catch (\Throwable $e)`
         llama a `markRejected()` para CUALQUIER excepción, sin distinguir si
         SUNAT llegó a recibir el documento.
      2. `See::sendXmlFile()` (vendor/greenter/lite/src/Greenter/See.php:193-206)
         hace `$doc = new DOMDocument(); $doc->loadXML($xmlContent);` seguido de
         `$resolver->getType($doc)` — TODO esto ocurre antes de que
         `WsSenderResolver`/`SoapClient` abran cualquier conexión.
      3. `XmlTypeResolver::getType()` (vendor/greenter/ws/src/Ws/Resolver/XmlTypeResolver.php:40-43)
         hace `$doc = $this->reader->parseToDocument($value); $name = $doc->documentElement->localName;`.
         Confirmado leyendo `XmlReader::parseToDocument()`
         (vendor/greenter/ws/src/Ws/Reader/XmlReader.php:73-80): como `$value`
         ya es un `DOMDocument`, se devuelve tal cual (rama
         `instanceof DOMDocument`), así que el `documentElement` que se lee es
         el mismo que produjo el `loadXML()` de `See::sendXmlFile()`. Un XML
         almacenado corrupto o truncado —fichero presente y NO vacío, así que
         la guarda `Storage::exists()` y la validación `blank($xml)` que
         cerraron el gap #12 lo dejan pasar íntegro hasta aquí— hace que
         `loadXML()` devuelva `false` sin lanzar, dejando `documentElement` en
         `null`; acceder a `->localName` sobre `null` es un "Attempt to read
         property on null" que el manejador de errores de Laravel convierte en
         `ErrorException` (un `\Throwable`), capturado por el `catch` del
         punto 1 SIN que ninguna llamada de red haya ocurrido.
      2. (bis) Un `SoapFault` de transporte (timeout, DNS caído, TLS,
         mantenimiento de SUNAT) producido dentro de la cadena de envío real
         llega al mismo `catch`: SUNAT no rechazó nada, nunca recibió el
         documento.

      En ambos casos, `markRejected()` persiste
      `sunat_status = Rechazada` con `sunatResponse.error.message` igual al
      texto interno de la excepción, y
      `sunatResponse.blade.php:99-102` imprime literalmente "Rechazado por
      SUNAT" (confirmado leyendo el fichero: la rama `Rechazada` no
      condiciona el texto por ningún indicador de si el envío llegó a
      completarse). Es exactamente la clase de defecto que el Core Value del
      proyecto declara inaceptable ("Si el estado que muestra el sistema no
      coincide con el que SUNAT tiene, el resto del ERP deja de ser
      confiable"), y la misma familia que el gap #12 cerró — pero en una vía
      distinta, un nivel más abajo, que 02-04 dejó fuera de alcance por
      decisión explícita y razonada (no por descuido).

      Adjudicación sobre el alcance: los cuatro Success Criteria de la Fase 2,
      leídos literalmente, no mencionan fallos de transporte SOAP ni XML
      corrupto — SC2 sólo describe el camino feliz del reenvío ("si la
      anulación no tiene ticket guardado... reenvía el XML y guarda el ticket
      que SUNAT devuelva"). Pero el propio gap #12 de la verificación anterior
      etiquetó su hallazgo como "(Phase Goal, ROADMAP SC2)", estableciendo que
      SC2 —y el objetivo de la fase en general— cubre la garantía de que el
      sistema no afirme un desenlace de SUNAT que SUNAT no comunicó cuando el
      envío no llegó a buen puerto. Este hallazgo es la misma garantía, sobre
      la misma acción (reenvío), violada por una causa distinta. No se
      reabre el gap #12 —su alcance original, tal como quedó escrito en su
      campo `missing:`, está genuinamente cerrado y verificado— pero el
      objetivo de la fase ("el listado dice la verdad sobre en qué estado
      está cada baja") sigue siendo demostrablemente falso para este vector,
      así que el veredicto de esta re-verificación no puede ser `passed`.

      Se descarta deliberadamente diferir este gap a la Fase 3: su SC2
      ("Cuando SUNAT responde 98 o la consulta falla por transporte, la
      respuesta indica estado pendiente...") describe el comportamiento de
      `resolveTicket()` (consulta), que YA maneja el fallo de transporte
      correctamente con `markPending()` — no describe `sendAndResolve()`
      (envío), que es donde vive este hallazgo. No hay evidencia específica
      de que la Fase 3 vaya a tocar esta línea; diferirlo sin esa evidencia
      violaría el criterio conservador de la Step 9b.
    artifacts:
      - path: "app/Services/Sunat/VoidedTicketFlowService.php"
        issue: "sendAndResolve() líneas 38-50: el catch (\\Throwable $e) que envuelve $see->sendXmlFile($xml) llama a markRejected() para cualquier excepción, sin distinguir un fallo anterior a cualquier E/S de red (XML corrupto no vacío) de un fallo de transporte (SoapFault) de un rechazo real de SUNAT"
      - path: "resources/views/voideds/partials/sunatResponse.blade.php"
        issue: "líneas 99-102: la rama Rechazada imprime siempre 'Rechazado por SUNAT' sin condicionar por si el envío llegó a completarse"
    missing:
      - "Distinguir, dentro de sendAndResolve(), el catch de $see->sendXmlFile() en dos casos: fallo antes de cualquier E/S de red (validar el XML con DOMDocument::loadXML() antes de entregarlo, o capturar TypeError/Error de forma distinta) y fallo de transporte (SoapFault) — ninguno de los dos es un rechazo de SUNAT"
      - "Marcar en el response persistido (p. ej. una clave delivered => false) que el envío no llegó a completarse, para que sunatResponse.blade.php pueda mostrar un texto distinto de 'Rechazado por SUNAT' cuando el documento nunca llegó al tercero fiscal"
      - "Decidir si este fallo debe clasificarse como Pendiente (recuperable por reconsulta) o como un tercer estado explícito 'no entregado', en vez de Rechazada"
  deferred: []
human_verification:
  - test: "D-14 reformulado (02-03, Tarea 1), verificable contra beta (production = false): sobre una fila con sunat_ticket vivo y sunat_status Pendiente, pulsar la acción de la columna Sunat y comprobar que SUNAT responde algo distinto de 2109 (en beta, 0127), que el ticket permanece guardado, y que — si se provoca en local una fila con sunat_status en NULL y ticket vivo (poniendo el estado a NULL directamente en base de datos) — el listado ofrece un botón accionable en vez del interrogante gris y, al pulsarlo, NO aparece el aviso 'Desenlace confirmado'."
    expected: "El ruteo por exclusión de desenlaces confirmados funciona igual en la interfaz real que en la probeta aislada del plan 02-03."
    why_human: "Requiere navegador contra el ambiente beta de SUNAT y, para el caso NULL+ticket, una fila preparada manualmente en base de datos; no es reproducible con grep."
  - test: "D-14 reformulado (02-03, Tarea 2), verificable contra beta: reproducir el escenario exacto del hallazgo de UAT — la fila pendiente cuyo ticket beta no reconoce (SUNAT responde 0127) — y comprobar que el aviso ya NO dice que SUNAT sigue procesando, sino 'No se pudo confirmar el estado' con el código 0127 incluido, y que la fila sigue Pendiente con su ticket."
    expected: "dispatchPendingSwal() distingue en la interfaz real el fallo de consulta del procesamiento genuino, tal como prueban los greps de acceptance_criteria sobre el código."
    why_human: "Depende de la respuesta real de SUNAT beta sobre un ticket concreto; no reproducible sin red."
  - test: "Requiere producción u homologación con credenciales SOL reales (fuera del alcance de beta, según D-14 reformulado): el recorrido completo de una baja que quedó pendiente por un código 98 real y que, al reconsultar, recupera el CDR, pasa a Aceptada, archiva el ZIP y deja los comprobantes marcados; y que ese 98 real llegue como 0098 y quede clasificado codigo_98 en sunatResponse.pending.reason."
    expected: "El desenlace final coincide con el que SUNAT reporta, y el motivo diagnóstico persistido es el correcto para un 98 real con ceros a la izquierda."
    why_human: "SUNAT beta no admite getStatus sobre tickets de comunicaciones de baja (responde 0127); sólo verificable con credenciales SOL reales."
  - test: "Abrir el listado con filas en los cuatro estados (ACEPTADA/PENDIENTE/RECHAZADA/SIN ESTADO) y confirmar que cada una muestra su palabra legible a simple vista, sin pasar el ratón ni abrir el modal, que los iconos siguen presentes (incluido el icono azul 'arrow-path' de la hoja histórica con ticket) y que la fila no se descuadra."
    expected: "Los cuatro/cinco badges y sus iconos se leen a simple vista, incluida la hoja de 02-03."
    why_human: "Legibilidad y maquetación son juicios visuales; sólo se comprobó ACEPTADA/PENDIENTE en el UAT en vivo previo (una sola fila de cada estado disponible)."
  - test: "Punto de atención conocido (WR-07/IN-03, confirmado leyendo vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Solid.php): VoidedStatus::badgeColor() devuelve 'danger', valor no registrado en el pack de colores de WireUI, así que el badge de Rechazada se renderiza sin clase de color de fondo. Confirmar si el resultado visual (icono fa-ban rojo + texto RECHAZADA sin fondo de color) es aceptable o amerita corregir el enum en un ajuste posterior (D-08 lo declara intocable en esta fase)."
    expected: "El operador sigue distinguiendo la fila Rechazada por icono y texto aunque el badge carezca de fondo de color."
    why_human: "Es un juicio de aceptabilidad visual, no un defecto funcional nuevo; el fix del enum queda fuera de alcance de esta fase."
  - test: "En el listado, seleccionar PENDIENTE en el filtro 'Estado Sunat' y confirmar que sólo quedan las filas con badge PENDIENTE; seleccionar SIN ESTADO y confirmar que sólo aparecen las históricas con badge gris (incluidas ahora las de la hoja con ticket vivo); volver a 'Todos' y confirmar que el listado se restablece; confirmar que el filtro no saca filas de otra empresa o sucursal."
    expected: "El filtro reduce el listado correctamente y nunca ensancha el ámbito multi-tenant, con más de una fila por estado disponible (el UAT previo sólo tuvo una)."
    why_human: "Depende de datos reales en distintos estados renderizados en la UI; el UAT anterior sólo cubrió parcialmente por falta de filas variadas."
  - test: "Human-check de 02-04, Tarea 1: sobre una fila con reenvío ofrecido (Rechazada + ticket NULL, o histórica virgen), Caso A — borrar el fichero al que apunta xml_path bajo storage/app/private/ y pulsar el botón: esperado que el sistema regenere el XML y siga adelante con el envío, sin texto de error de PHP en sunatResponse. Caso B — dejar el fichero de xml_path con tamaño cero y pulsar el botón: esperado el aviso 'No se pudo preparar el XML', sunat_status sin cambiar, y una entrada error en storage/logs/laravel.log con voided_id y xml_path."
    expected: "El comportamiento observado en el entorno local del usuario coincide con la lógica que la probeta desechable de 02-04 ya probó de forma aislada (RED antes del fix, GREEN después)."
    why_human: "Confirmar sunat_status intacto, el texto del swal y la entrada de log exige reproducir el escenario en el entorno local real; la probeta prueba la lógica de decisión, no la interfaz ni el log en disco."
  - test: "Human-check de 02-04, Tarea 2: sobre una fila con pdf_path/xml_path en NULL (o con el fichero borrado del disco), pulsar el icono de la columna PDF y el de la columna XML; comprobar que aparece 'Sin PDF'/'Sin XML' sin error 500 de Livewire. Repetir sobre el icono CDR para confirmar que downloadCDR() no tiene regresión (descarga si hay CDR, aviso 'Sin CDR' si no)."
    expected: "Los tres iconos de descarga se comportan igual en la interfaz real que en la probeta desechable de 02-04 (tres regiones de método, cada una con Storage::exists() antes de Storage::download())."
    why_human: "Confirmar la ausencia de error 500 y la legibilidad del aviso requiere pulsar el icono en el navegador; no reproducible por grep."
---

# Fase 2: Reconsulta de anulaciones pendientes — Informe de verificación (re-verificación tras 02-04)

**Objetivo de la fase:** El usuario recupera desde el listado el resultado final de una anulación pendiente, y el listado dice la verdad sobre en qué estado está cada baja
**Verificado:** 2026-08-05
**Estado:** gaps_found
**Re-verificación:** Sí — tras `02-04-PLAN.md` (cierre del gap #12 / CR-01 vector de almacenamiento, y de WR-04)

## Logro del objetivo

`02-04` cerró exactamente lo que se propuso, verificado leyendo el código
actual y no la narrativa de su `SUMMARY.md`:

- **Gap #12 (único `status: failed` de la verificación anterior) — CERRADO.**
  La rama de reenvío de `VoidedTable::sendXml()` separa ahora la preparación
  local del XML (regeneración o lectura del accessor) del envío a SUNAT en dos
  fases: una fase local con su propio `try`/`catch` que llama a
  `dispatchXmlUnavailableSwal()` — nunca a `classifyFailure()` — y una guarda
  `blank($xml)` que corta antes de cualquier `try` de envío. La guarda de
  regeneración comprueba `Storage::exists($voided->xml_path)`, no sólo la
  columna, replicando el patrón que `downloadCDR()` ya tenía. Confirmado con
  `php -l`, `vendor/bin/pint --test`, y los conteos exactos que fijaba el plan
  (`Storage::exists`=4, `try {`=3, `classifyFailure`=2, `sendAndResolve(`=1,
  `dispatchXmlUnavailableSwal`=3, `public function`=9, `private function`=4,
  `$e->getMessage()`=2, `Log::error`=1).
- **WR-04 — CERRADO.** `downloadPDF()` y `downloadXML()` llevan ahora la misma
  guarda `empty(...) || ! Storage::exists(...)` que `downloadCDR()`.
- **Sin regresión.** El árbol de tres ramas de `sendXml()` (Consultar/Avisar
  desenlace confirmado/Reenviar), `dispatchPendingSwal()`, `cdrPathFor()`,
  `canResendXml()` y el `SelectFilter` de estado siguen intactos, byte a byte,
  con los mismos conteos de la verificación anterior. Los dos commits del plan
  (`bcb69da`, `412c2cd`) tocan exclusivamente `app/Livewire/VoidedTable.php`
  (`git show --stat` confirmado); ningún fichero prohibido por el frontmatter
  de `02-04-PLAN.md` fue tocado (`git status --porcelain` limpio sobre
  `app/Services/`, `app/Enums/`, `app/Livewire/Forms/VoidedForm.php`,
  `resources/views/voideds/`, `resources/views/vouchers/`,
  `database/migrations/`).

**Pero `02-REVIEW.md` (re-revisión posterior a `02-04`, 2026-08-05T23:40:00Z)
identificó un cuarto hallazgo de la misma familia, un nivel más abajo, que
verifiqué de forma independiente leyendo el código de la aplicación y el de
`vendor/greenter` (no la narrativa del review):** dentro de
`VoidedTicketFlowService::sendAndResolve()` (líneas 34-50), el único `try` que
envuelve `$see->sendXmlFile($xml)` clasifica **cualquier** `\Throwable` como
rechazo de SUNAT, sin distinguir un fallo anterior a cualquier E/S de red (un
XML almacenado corrupto pero no vacío, que las guardas de `02-04` dejan pasar
porque no validan que el XML sea *bien formado*, sólo que exista y no esté en
blanco) de un fallo de transporte (`SoapFault` por timeout/DNS/TLS) de un
rechazo real. Confirmé la cadena exacta leyendo
`vendor/greenter/lite/src/Greenter/See.php:193-206`,
`vendor/greenter/ws/src/Ws/Resolver/XmlTypeResolver.php:40-43` y
`vendor/greenter/ws/src/Ws/Reader/XmlReader.php:73-80`: un `loadXML()` fallido
deja `documentElement` en `null`, y `XmlTypeResolver::getType()` accede a
`->localName` sobre ese `null` **antes de que se abra ningún socket**. Ese
error entra al mismo `catch` que un `SoapFault` real, y ambos se persisten
como `sunat_status = Rechazada` con el modal escribiendo literalmente
"Rechazado por SUNAT" (`sunatResponse.blade.php:99-102`, confirmado leyendo el
fichero) sobre una comunicación de baja que SUNAT nunca vio.

**Adjudicación explícita (ver la entrada `gaps:` del frontmatter para el
razonamiento completo):** este hallazgo **no es una reapertura del gap #12**.
El gap #12, tal como quedó escrito en su campo `missing:` en la verificación
anterior, hablaba exclusivamente de la preparación *local* del XML dentro del
componente Livewire — y esa vía está genuinamente cerrada. El nuevo hallazgo
vive en `VoidedTicketFlowService.php`, un fichero que `02-04-PLAN.md` declaró
explícitamente intocable con un razonamiento válido para el vector que sí
cerró (*"classifyFailure() clasifica correctamente lo que recibe"*) pero que
no cubre el `catch` de `sendXmlFile()` dentro de `sendAndResolve()`, un método
distinto del mismo servicio. Los cuatro Success Criteria de la fase, leídos
literalmente, no mencionan fallos de transporte SOAP ni XML corrupto — pero el
propio gap #12 se etiquetó a sí mismo como *"(Phase Goal, ROADMAP SC2)"*,
estableciendo que SC2 cubre la garantía general de no afirmar un desenlace de
SUNAT no comunicado en la vía de reenvío. Este hallazgo es la misma garantía,
violada por una causa distinta. Por eso el veredicto de esta re-verificación
es `gaps_found` con un gap **nuevo**, no la reapertura del anterior. Se
descarta diferirlo a la Fase 3: su SC2 describe el comportamiento de
`resolveTicket()` (consulta), que ya maneja el fallo de transporte
correctamente con `markPending()`, no el de `sendAndResolve()` (envío), que es
donde vive este hallazgo — no hay evidencia específica de que la Fase 3 vaya a
tocar esa línea.

### Verdades observables

| # | Verdad | Estado | Evidencia |
|---|--------|--------|-----------|
| 1 | Una anulación Pendiente con `sunat_ticket` guardado ofrece "Consultar estado" en el listado, que reconsulta ese ticket sin reenviar el XML (ROADMAP SC1, WEB-02) | ✓ VERIFICADO (código) | Sin cambios respecto de la verificación anterior; `sendXml()` líneas 161-212 |
| 2 | Si SUNAT sigue procesando (motivo `codigo_98`), swal "Aún en proceso" y el ticket permanece guardado (D-09) | ✓ VERIFICADO | `dispatchPendingSwal()` líneas 333-367: rama `codigo_98` conserva el literal "Aún en proceso"; `grep -c 'Aún en proceso'` = 1 |
| 3 | Al llegar el CDR por reconsulta, la fila queda Aceptada o Rechazada según el código del propio CDR, el ZIP se archiva, el ticket se limpia y los comprobantes quedan marcados (ROADMAP SC3) | ✓ VERIFICADO (código, sin cambios) | `resolveTicket()` sin cambios; `markVoidedInvoices()` intacto |
| 4 | Una anulación sin ticket guardado reenvía el XML y guarda el ticket que SUNAT devuelva, siempre que el envío realmente llegue a SUNAT (ROADMAP SC2, WEB-03) | ✓ VERIFICADO, con advertencia | `canResendXml()` conserva sus dos casos; `sendAndResolve()` una vez. Camino feliz correcto — ver gap nuevo para el vector de transporte/XML corrupto que aún puede clasificar mal un fallo previo a SUNAT |
| 5 | Una anulación Aceptada, o Rechazada que conserva ticket, no llama a SUNAT y el sistema no afirma un desenlace de SUNAT que nunca ocurrió (D-12, CR-01 original) | ✓ VERIFICADO | `grep -c 'sunat_status === VoidedStatus::Pendiente'` fuera de la ventana; rama "Desenlace confirmado" exige `VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket)` — confirmado en línea 214 |
| 6 | La ruta del ZIP del CDR se arma con el RUC de la empresa dueña de la fila, no el de la sesión (D-04) | ✓ VERIFICADO | Sin cambios; `cdrPathFor()` lee `$voided->company['ruc']` |
| 7 | El listado distingue a simple vista aceptada/pendiente/rechazada mediante badge de texto + icono; una baja aceptada nunca se pinta en rojo (ROADMAP SC4, WEB-04) | ✓ VERIFICADO, con advertencia | `VoidedStatus::badgeColor()`: Aceptada=`primary`, Pendiente=`warning`, Rechazada=`danger` — Aceptada nunca usa rojo. Persiste WR-07/IN-03 (badge Rechazada sin fondo de color, ver human_verification) |
| 8 | Las filas históricas (`sunat_status` NULL) muestran un badge gris "SIN ESTADO" resuelto en Blade, incluida la hoja con ticket vivo | ✓ VERIFICADO | `response.blade.php`: 3 hojas en la rama histórica, todas con `color="gray" label="SIN ESTADO"` |
| 9 | Los iconos preexistentes se conservan y la hoja histórica con ticket usa un icono distinto para no confundirse con el interrogante gris (D-06) | ✓ VERIFICADO | Hoja con ticket usa `arrow-path` azul con `title`; el resto de iconos intactos |
| 10 | El operador puede filtrar el listado por estado y aislar las pendientes, sin fuga multi-tenant (D-07) | ✓ VERIFICADO (código) | `filters()` sin cambios; closure compone sobre `builder()` ya acotado por `branch_id`/`company_id`/`production` |
| 11 | El filtro ofrece la opción para las filas sin estado resuelta con `whereNull` (D-07, D-08) | ✓ VERIFICADO | Sin cambios |
| 12 | **Ninguna salida de la rama de reenvío de `sendXml()` afirma un desenlace de SUNAT no comunicado cuando el fallo es puramente local a la preparación del XML** (gap #12 original, Phase Goal, ROADMAP SC2) | ✓ **VERIFICADO** (regresión cerrada) | `empty($voided->xml_path) || ! Storage::exists($voided->xml_path)` en la guarda de regeneración; `blank($xml)` corta antes del `try` de envío; el `catch` de la fase local llama a `dispatchXmlUnavailableSwal()`, no a `classifyFailure()` — confirmado leyendo `VoidedTable.php:245-287` |
| 13 | **Ninguna salida de `VoidedTicketFlowService::sendAndResolve()` afirma un rechazo de SUNAT cuando el documento nunca llegó a SUNAT (XML corrupto no vacío, o fallo de transporte SOAP)** (Phase Goal — hallazgo NUEVO, distinto de #12) | ✗ **FALLIDO** | Ver Gaps abajo. `sendAndResolve()` líneas 38-50: el `catch` de `sendXmlFile()` llama a `markRejected()` para cualquier `\Throwable`, confirmado alcanzable leyendo `vendor/greenter/lite/src/Greenter/See.php` y `vendor/greenter/ws/src/Ws/Resolver/XmlTypeResolver.php` |

**Puntaje:** 12/13 verdades verificadas en código (1 fallida: #13, hallazgo nuevo
de `02-REVIEW.md` regenerado tras `02-04`, confirmado por lectura directa de
`vendor/greenter`). Las verdades #1, #3, #7 y #10 dependen en su confirmación
final de comportamiento en vivo contra SUNAT/la UI real (ver Verificación
Humana), tal como ya declaraba D-14.

### Artefactos requeridos

| Artefacto | Esperado | Estado | Detalles |
|---|---|---|---|
| `app/Livewire/VoidedTable.php` | Fase local del XML separada del `try` de envío; `dispatchXmlUnavailableSwal()`; guardas `Storage::exists()` en las tres descargas; árbol de tres ramas, `dispatchPendingSwal()`, `cdrPathFor()`, `filters()` intactos | ✓ VERIFICADO | `php -l` limpio, `vendor/bin/pint --test` pasa, 9 métodos públicos / 4 privados, todos los conteos del plan `02-04` coinciden |
| `app/Services/Sunat/VoidedTicketFlowService.php` | Normalización numérica del código en `resolveTicket()`; `sendAndResolve()` no debería clasificar como rechazo un fallo anterior a la respuesta de SUNAT | ⚠️ VERIFICADO con gap nuevo | `(int) $code === 98` confirmado; `sendAndResolve()` (líneas 38-50) sigue clasificando cualquier `\Throwable` de `sendXmlFile()` como `Rechazada` — ver gap #13 |
| `resources/views/voideds/partials/response.blade.php` | Hoja para la fila histórica con ticket vivo; sin cambios de 02-04 | ✓ VERIFICADO | Sin tocar por 02-04 (confirmado por `git status --porcelain`); 3 hojas en la rama histórica intactas |
| `resources/views/voideds/partials/sunatResponse.blade.php` | Sin cambios de 02-04 | ✓ VERIFICADO, con advertencia | Sin tocar por 02-04; rama Rechazada sigue imprimiendo "Rechazado por SUNAT" sin condicionar por si el envío llegó a completarse (efecto visible del gap #13) |

### Verificación de enlaces clave

| Desde | Hacia | Vía | Estado | Detalles |
|---|---|---|---|---|
| `VoidedTable::sendXml()` (fase local, rama Reenviar) | `Storage::exists($voided->xml_path)` antes de leer/regenerar el fichero | llamada directa | ✓ **WIRED** (antes `NOT_WIRED`) | Confirmado en `VoidedTable.php:257`; cierra el enlace que la verificación anterior marcaba roto |
| `VoidedTable::sendXml()` (fase local, catch) | `VoidedTable::dispatchXmlUnavailableSwal()`, NO `VoidedTicketFlowService::classifyFailure()` | llamada privada | ✓ WIRED | `dispatchXmlUnavailableSwal` aparece 3 veces (1 declaración + 2 llamadas); `classifyFailure` aparece 2 veces en todo el fichero, ninguna dentro de la fase local |
| `VoidedTable::downloadPDF()` / `::downloadXML()` | `Storage::exists()` antes de `Storage::download()` | llamada directa | ✓ WIRED | Confirmado en líneas 104 y 119, replicando el patrón de `downloadCDR()` (línea 148) |
| `VoidedTicketFlowService::sendAndResolve()` (catch de `sendXmlFile()`) | distinguir fallo-antes-de-SUNAT de rechazo-de-SUNAT | **no existe esta distinción** | ✗ **NOT_WIRED** | Confirmado por lectura: el `catch` (líneas 40-50) llama a `markRejected()` incondicionalmente para cualquier `\Throwable`; no hay guarda ni marca `delivered`/`success` que distinga un `SoapFault` de transporte o un `Error` de parseo pre-red de un rechazo real |

### Requisitos cubiertos

| Requisito | Plan origen | Descripción | Estado | Evidencia |
|---|---|---|---|---|
| WEB-02 | 02-01, 02-03 | El usuario puede reconsultar el estado de una anulación pendiente desde el listado, sin reenviar el XML, para todo ticket vivo sin desenlace confirmado | ✓ SATISFECHO (código) | Rama Consultar por exclusión de desenlaces confirmados; confirmación de comportamiento real → human_needed (D-14) |
| WEB-03 | 02-01, 02-04 | El reenvío del XML a SUNAT solo ocurre cuando la anulación no tiene ticket guardado, y un fallo que no llegó a SUNAT no se persiste como rechazo de SUNAT | ⚠️ SATISFECHO PARCIALMENTE | `canResendXml()` correcto; el vector local (gap #12) está cerrado por `02-04`; el vector de transporte/XML corrupto dentro de `VoidedTicketFlowService::sendAndResolve()` (gap #13, nuevo) sigue abierto |
| WEB-04 | 02-02 | El usuario distingue en el listado cuáles fueron aceptadas, cuáles siguen pendientes y cuáles rechazó SUNAT | ✓ SATISFECHO, con advertencia | Badge + icono en las 7 hojas; WR-07/IN-03 persiste (color de fondo, human_verification) |

No hay requisitos huérfanos: `REQUIREMENTS.md` mapea exactamente WEB-02, WEB-03
y WEB-04 a la Fase 2; los cuatro planes (`02-01`, `02-02`, `02-03`, `02-04`)
declaran esos IDs en su frontmatter `requirements:` sin faltantes
(`02-01`: WEB-02, WEB-03; `02-02`: WEB-04; `02-03`: WEB-02; `02-04`: WEB-03).

### Anti-patrones encontrados

| Archivo | Línea | Patrón | Severidad | Impacto |
|---|---|---|---|---|
| `app/Services/Sunat/VoidedTicketFlowService.php` | 38-50 | `catch (\Throwable $e)` de `sendXmlFile()` clasifica como rechazo cualquier excepción, incluidas las que ocurren antes de cualquier E/S de red o por fallo de transporte | 🛑 **Blocker** | Gap #13 (nuevo). Confirmado alcanzable leyendo `vendor/greenter`; contradice el core value del proyecto |
| `app/Livewire/VoidedTable.php` | 202-208 | Doble `dispatch('swal', …)` en el catch de la rama Consultar: `showResponse()` seguido de un swal de error que lo sustituye (WR-01 de `02-REVIEW.md`) | ⚠️ Warning | El detalle del comprobante nunca llega a verse; agravado cuando el desenlace ya es Aceptada/Rechazada confirmada — el operador ve "error" sobre un desenlace ya cerrado. Sin cambios de `02-04` (fuera de su alcance) |
| `resources/views/voideds/partials/response.blade.php:13-23`; `sunatResponse.blade.php:47-90` | — | La rama Pendiente del modal de detalle quedó inalcanzable desde la interfaz, y esa misma rama sigue afirmando "SUNAT... aún la procesa" sin condicionar por motivo (WR-02) | ⚠️ Warning | Sin cambios de `02-04` (fuera de su alcance, declarado explícitamente) |
| `app/Livewire/VoidedTable.php:167-238`; `response.blade.php:13-23` | — | Fila `Pendiente` sin ticket (deriva de datos) cae en "No se puede reenviar" con una afirmación categórica sobre SUNAT (WR-03) | ⚠️ Warning | Sin cambios de `02-04` |
| `app/Livewire/VoidedTable.php:186,304`; `VoidedTicketFlowService.php:245-252` | — | Sin `wire:loading`/`wire:target` en los botones de SUNAT; `markRejected()` no respeta el invariante de "desenlace confirmado" que sí aplica `classifyFailure()` (WR-05) | ⚠️ Warning | Doble clic/doble pestaña puede sobrescribir un desenlace ya confirmado. Sin cambios de `02-04` |
| `app/Livewire/VoidedTable.php:207,317` | — | `'text' => $e->getMessage()` en los dos `catch` preexistentes de SUNAT, sin `Log::error()` (WR-06) | ⚠️ Warning | Fuera de alcance de `02-04` por decisión explícita; contrasta con `dispatchXmlUnavailableSwal()`, que sí aplica el patrón correcto — confirmado `$e->getMessage()`=2, `Log::error`=1 |
| `app/Livewire/VoidedTable.php:178,242`; `VoidedTicketFlowService.php` | — | Certificado/endpoint SUNAT siguen anclados a `session('company')` mientras la ruta del CDR ya usa la empresa de la fila (WR-07) | ⚠️ Warning | Divergencia sólo explotable hoy vía el hueco de autorización (IN-01), diferido a Fase 4 |
| `app/Services/Sunat/VoidedTicketFlowService.php:84-166` | — | `resolveTicket()` público sin guarda de entrada propia; el invariante de "desenlace confirmado" sólo vive en el llamador (WR-08) | ⚠️ Warning | Un llamador futuro (Fase 3) que omita la guarda de tres términos puede degradar una Aceptada a Pendiente destruyendo el `cdrResponse` |
| `app/Services/Sunat/VoidedTicketFlowService.php:102-112` | — | El código `0127` de SUNAT ("el ticket no existe") se diagnostica como `fallo_transporte` y deja la fila irrecuperable (WR-10 de `02-REVIEW.md`; documentado como deuda deliberada en `02-04-SUMMARY.md`, hallazgo aportado por el usuario, observado en vivo en `02-UAT.md` sobre `voided_id` 3) | ⚠️ Warning | No escalado a Blocker: en beta `0127` es indistinguible de "beta no soporta esta consulta" (misma razón de D-14); sólo es concluyente en producción/homologación. Fuera de alcance de `02-04` por decisión explícita del usuario; candidato declarado para Fase 3 o ronda de cierre posterior |
| `app/Livewire/VoidedTable.php:245-277` | — | La regeneración del XML encadena en el mismo bloque la firma del XML y la generación del PDF (wkhtmltopdf); un fallo del PDF bloquea el reenvío fiscal y deja el XML recién escrito huérfano porque `save()` va después del PDF (WR-11 de `02-REVIEW.md`) | ⚠️ Warning | No cerrado por `02-04` (no formaba parte de su alcance declarado); hallazgo nuevo de la re-revisión |
| `app/Services/Sunat/VoidedTicketFlowService.php:137` | — | `(string) $cdr->getCode() === '0'` no recibió la normalización numérica que sí recibió el código 98 en la misma función (WR-12 de `02-REVIEW.md`) | ⚠️ Warning | Baja probabilidad (los CDR reales usan `'0'` sin relleno) pero consecuencia irreversible si se dispara: una aceptación de SUNAT quedaría bloqueada como Rechazada de forma permanente |
| `app/Livewire/VoidedTable.php:102,117,146,161,401` | — | Acciones públicas sin comprobación de propiedad multi-tenant/permiso (IN-01, antes CR-02) | ℹ️ Info | **Diferido a Fase 4** por decisión de roadmap explícita; no se cuenta como gap de esta fase |
| `app/Enums/VoidedStatus.php:25` | — | `badgeColor()` devuelve `danger`, no registrado en WireUI (IN-03) | ℹ️ Info | Enum de la Fase 1, D-08 lo declara intocable en esta fase |
| `app/Livewire/VoidedTable.php:161-320` | — | `sendXml()` creció a ~160 líneas con ocho salidas tras `02-04` (IN-06); `$directory` duplicado; triplicación de la guarda `Storage::exists()` entre `downloadPDF`/`downloadXML`/`downloadCDR` (decisión declarada de `02-04-SUMMARY.md` de no extraer un privado común) | ℹ️ Info | Excede la convención de 20-50 líneas del proyecto; no bloquea el objetivo, deuda registrada explícitamente |
| `resources/views/voideds/partials/response.blade.php` (7 botones) | — | Sin `type="button"` ni `title`/`aria-label` en 6 de 7 botones (IN-09) | ℹ️ Info | Accesibilidad; sin cambios de `02-04` |
| `app/Livewire/VoidedTable.php`; `VoidedTicketFlowService.php` | — | Sin cobertura automatizada más allá de las probetas desechables (IN-10) | ℹ️ Info | La suite `phpunit` está rota de base (`UserFactory`) y corre contra la BD configurada; deuda conocida del proyecto. `02-04` no añadió pruebas persistentes pese a corregir un defecto crítico |

No se detectaron marcadores de deuda (`TBD`/`FIXME`/`XXX`) sin referencia en
`app/Livewire/VoidedTable.php` ni en `app/Services/Sunat/VoidedTicketFlowService.php`.

### Gaps

**Gap #13 (NUEVO, no reapertura del #12) — `VoidedTicketFlowService::sendAndResolve()` puede grabar una fila como "Rechazada por SUNAT" por un fallo previo a cualquier respuesta de SUNAT.**

Ver la entrada `gaps:` del frontmatter para el detalle estructurado, la cadena
de código verificada en `vendor/greenter`, y la corrección propuesta. En
resumen: el `catch` que envuelve `$see->sendXmlFile($xml)` en
`sendAndResolve()` clasifica como rechazo cualquier `\Throwable`, incluidos
los que ocurren antes de que se abra un socket (un XML almacenado corrupto
pero no vacío, que las guardas nuevas de `02-04` no detectan porque sólo
comprueban existencia y no-vacío, no buena formación) y los fallos de
transporte SOAP genuinos. Es la misma clase de mentira que el gap #12 cerró,
en una vía distinta y un nivel más abajo, en un fichero que `02-04` declaró
explícitamente intocable por una razón válida para el vector que sí cerró.

**Por qué no es una regresión de `02-04` ni una reapertura del gap #12:** el
gap #12, tal como quedó redactado en su campo `missing:` en la verificación
anterior, hablaba exclusivamente de la preparación local del XML dentro del
componente Livewire (columna vacía, fichero ausente, lectura vacía,
regeneración que falla) — las tres correcciones que pedía están, verificadas
de forma independiente, aplicadas y correctas. Este hallazgo vive en un método
distinto (`sendAndResolve()` contra la fase local de `sendXml()`) y fue
identificado por primera vez en la re-revisión de código regenerada después
de que `02-04` cerrara su alcance declarado.

## Verificación humana requerida

Ver la lista `human_verification` en el frontmatter: los seis ítems heredados
de la verificación anterior (ruteo NULL+ticket y honestidad del aviso contra
el `0127` real de beta, el recorrido completo contra producción/homologación,
legibilidad de badges, el color `danger` del enum, y el filtro `SelectFilter`),
más los dos `human-check` nuevos que introdujo `02-04` (reproducción local del
fichero ausente/vacío en la rama de reenvío, e iconos de descarga sobre filas
sin fichero) — ambos reproducibles en el entorno local sin depender de SUNAT
beta.

## Resumen de gaps

`02-04` cerró exactamente lo que se propuso: el gap #12 (único `status: failed`
de la verificación anterior, vector de almacenamiento local en el componente
Livewire) y WR-04 (descargas sin guarda), ambos verificados de forma
independiente en el código actual, sin regresiones sobre `02-01`/`02-02`/`02-03`.
Sin embargo, la re-revisión de código regenerada tras el cierre encontró un
cuarto defecto de la misma familia, un nivel más abajo en
`VoidedTicketFlowService::sendAndResolve()`, deliberadamente fuera del alcance
declarado de `02-04` (que declaró ese fichero intocable con un razonamiento
válido para el vector que cerró, pero que no cubre este). Verificado de forma
independiente leyendo el código de la aplicación y el de `vendor/greenter`, es
un hallazgo real y alcanzable, no una hipótesis: un `catch` genérico clasifica
como rechazo de SUNAT tanto un XML corrupto que nunca llega a la red como un
fallo de transporte SOAP. Es exactamente la clase de mentira que este
milestone existe para eliminar, y por eso el veredicto de esta re-verificación
sigue siendo `gaps_found` — con un gap **nuevo** (#13), no la reapertura del
anterior. Los hallazgos de autorización (IN-01, antes CR-02) y las demás
advertencias/info de `02-REVIEW.md` permanecen correctamente diferidos a la
Fase 4 o registrados como deuda pre-existente sin bloquear el objetivo de esta
fase; el hallazgo del código `0127` (WR-10) queda registrado como deuda
deliberada, candidato explícito para la Fase 3 o una ronda de cierre
posterior, sin escalar a gap de esta fase por la ambigüedad genuina que
introduce el comportamiento de SUNAT beta.

---

*Verificado: 2026-08-05*
*Verificador: Claude (gsd-verifier)*
