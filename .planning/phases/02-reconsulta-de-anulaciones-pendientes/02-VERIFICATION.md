---
phase: 02-reconsulta-de-anulaciones-pendientes
verified: 2026-08-05T22:00:00Z
status: gaps_found
score: 11/12 must-haves verificados en código (1 fallido, ver gaps)
behavior_unverified: 0
overrides_applied: 0
re_verification:
  previous_status: gaps_found
  previous_score: 10/11
  gaps_closed:
    - "CR-01 (ruteo): una fila con sunat_ticket vivo y sunat_status en NULL ya no cae en el aviso falso de 'Desenlace confirmado' — la rama Consultar de sendXml() se expresa ahora por exclusión de los dos desenlaces confirmados (Aceptada, Rechazada), no por enumeración del estado Pendiente, y la rama 'Desenlace confirmado' quedó acotada a Rechazada+ticket"
    - "GAP-2 (hallazgo de UAT en vivo): el aviso de fila pendiente ya no afirma 'SUNAT sigue procesando' quando el motivo real fue un fallo de la consulta (0127 en la prueba de navegador) — dispatchPendingSwal() distingue codigo_98 de cualquier otro motivo"
    - "GAP-3 (hallazgo de UAT en vivo): la clasificación del código de SUNAT en resolveTicket() ya no compara la cadena sin normalizar — (int) $code === 98 reconoce '98', '098' y '0098' como codigo_98"
  gaps_remaining: []
  regressions: []
gaps:
  - truth: "Ninguna salida de VoidedTable::sendXml() afirma un desenlace de SUNAT que SUNAT no comunicó — ni siquiera cuando el fallo es puramente local (Phase Goal, 'el listado dice la verdad sobre en qué estado está cada baja'; ROADMAP SC2)"
    status: failed
    reason: >
      En la rama de reenvío de sendXml() (líneas 219-284), la regeneración
      del XML se decide con `empty($voided->xml_path)`, que sólo mira la
      columna y no comprueba que el fichero exista realmente en el disco
      configurado. Si `xml_path` está poblado pero el fichero falta —purga,
      cambio de disco, un Storage::put() que falló en silencio en el envío
      original, o cualquiera de los incidentes de despliegue que CLAUDE.md ya
      documenta para este proyecto—, `$voided->xml` (el accessor que llama a
      Storage::get()) devuelve null en un disco no-throwing o lanza
      UnableToReadFile en uno throwing. Confirmado leyendo el accessor del
      modelo y config/filesystems.php: ambos casos terminan en el
      catch (\Throwable $e) de sendXml(), que llama a
      VoidedTicketFlowService::classifyFailure(). Como esa rama sólo se
      alcanza cuando canResendXml() fue true —sus dos únicos casos exigen
      sunat_ticket nulo—, classifyFailure() entra por is_null(sunat_ticket) y
      ejecuta markRejected() con el mensaje crudo del TypeError/excepción de
      almacenamiento como si fuera la respuesta de SUNAT. Resultado
      persistido: sunat_status = Rechazada, con sunatResponse.error.message
      igual al texto de un fallo de PHP, no de SUNAT. El listado pinta el
      aspa roja y el modal de detalle escribe literalmente "Rechazado por
      SUNAT" — SUNAT nunca vio esta comunicación de baja. Es exactamente la
      clase de defecto que el milestone existe para eliminar (el estado que
      muestra el sistema deja de coincidir con el que SUNAT tiene), ahora en
      la vía del reenvío en vez de la del ticket que 02-03 ya corrigió.
      Confirmado de forma independiente leyendo VoidedTable.php:219-243 y
      VoidedTicketFlowService.php (classifyFailure(), markRejected()); no es
      sólo la narrativa de 02-REVIEW.md — el mismo hallazgo (CR-01 en la
      revisión regenerada del 2026-08-05T21:10:00Z) coincide con la lectura
      directa. Es, además, incoherente con downloadCDR() (misma clase, líneas
      125-138), que sí comprueba Storage::exists() antes de tocar el fichero
      con el razonamiento explícito de que "la ruta puede estar poblada sin
      fichero detrás".
    artifacts:
      - path: "app/Livewire/VoidedTable.php"
        issue: "sendXml() líneas 228-243: la guarda 'if (empty($voided->xml_path))' no comprueba Storage::exists($voided->xml_path); un fichero XML ausente en disco entra a la rama que lee $voided->xml, cuyo fallo se clasifica como rechazo de SUNAT en vez de como error de almacenamiento local"
    missing:
      - "Cambiar la guarda a 'empty($voided->xml_path) || ! Storage::exists($voided->xml_path)' para que un fichero ausente regenere el XML igual que una columna vacía"
      - "Validar que $xml no quede en blanco fuera del try/catch que envuelve sendXmlFile(), para que un fallo de almacenamiento se muestre como error de la aplicación y no entre al catch que classifyFailure() interpreta como respuesta de SUNAT"
      - "Aplicar la misma guarda a downloadPDF()/downloadXML() (WR-04 de 02-REVIEW.md), asimétricas hoy con el cuidado que sí recibió downloadCDR()"
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
  - test: "Abrir el listado con filas en los cuatro estados (ACEPTADA/PENDIENTE/RECHAZADA/SIN ESTADO) y confirmar que cada una muestra su palabra legible a simple vista, sin pasar el ratón ni abrir el modal, que los iconos siguen presentes (incluido el icono azul 'arrow-path' nuevo de la hoja histórica con ticket) y que la fila no se descuadra."
    expected: "Los cuatro/cinco badges y sus iconos se leen a simple vista, incluida la hoja nueva de 02-03."
    why_human: "Legibilidad y maquetación son juicios visuales; sólo se comprobó ACEPTADA/PENDIENTE en el UAT en vivo previo (una sola fila de cada estado disponible)."
  - test: "Punto de atención conocido (WR-07/IN-03, confirmado leyendo vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Solid.php): VoidedStatus::badgeColor() devuelve 'danger', valor no registrado en el pack de colores de WireUI, así que el badge de Rechazada se renderiza sin clase de color de fondo. Confirmar si el resultado visual (icono fa-ban rojo + texto RECHAZADA sin fondo de color) es aceptable o amerita corregir el enum en un ajuste posterior (D-08 lo declara intocable en esta fase)."
    expected: "El operador sigue distinguiendo la fila Rechazada por icono y texto aunque el badge carezca de fondo de color."
    why_human: "Es un juicio de aceptabilidad visual, no un defecto funcional nuevo; el fix del enum queda fuera de alcance de esta fase."
  - test: "En el listado, seleccionar PENDIENTE en el filtro 'Estado Sunat' y confirmar que sólo quedan las filas con badge PENDIENTE; seleccionar SIN ESTADO y confirmar que sólo aparecen las históricas con badge gris (incluidas ahora las de la hoja nueva con ticket vivo); volver a 'Todos' y confirmar que el listado se restablece; confirmar que el filtro no saca filas de otra empresa o sucursal."
    expected: "El filtro reduce el listado correctamente y nunca ensancha el ámbito multi-tenant, con más de una fila por estado disponible (el UAT previo sólo tuvo una)."
    why_human: "Depende de datos reales en distintos estados renderizados en la UI; el UAT anterior sólo cubrió parcialmente por falta de filas variadas."
---

# Fase 2: Reconsulta de anulaciones pendientes — Informe de verificación (re-verificación)

**Objetivo de la fase:** El usuario recupera desde el listado el resultado final de una anulación pendiente, y el listado dice la verdad sobre en qué estado está cada baja
**Verificado:** 2026-08-05
**Estado:** gaps_found
**Re-verificación:** Sí — tras `02-03-PLAN.md` (cierre de gaps CR-01/GAP-2/GAP-3)

## Logro del objetivo

Esta re-verificación confirma que **los tres defectos que dejó abiertos la
verificación anterior están cerrados** en el código actual, con evidencia
independiente (lectura directa, no la narrativa de los SUMMARY):

- **CR-01 (ruteo, bloqueante de la verificación anterior):** la rama
  "Consultar" de `sendXml()` ya no exige `sunat_status === Pendiente`; exige
  ticket vivo y que el estado **no** sea `Aceptada` ni `Rechazada`. Una fila
  con ticket vivo y `sunat_status` en NULL —el estado exacto que quedaba
  huérfano— ahora entra a `resolveTicket()`. La rama "Desenlace confirmado"
  quedó acotada, en espejo, a `Rechazada` con ticket. `response.blade.php`
  gana la hoja de vista correspondiente (líneas 67-78) para que el botón
  exista en la interfaz. Verificado leyendo `VoidedTable.php:140-207` y
  `response.blade.php` completo.
- **GAP-2 (hallazgo de UAT en vivo):** `dispatchPendingSwal()` (nuevo, privado)
  es el único punto de composición del aviso de pendiente; sólo afirma que
  SUNAT sigue procesando cuando el motivo persistido es exactamente
  `codigo_98`, y en cualquier otro caso —incluido el `fallo_transporte` real
  de la prueba de navegador (`0127`)— dice "No se pudo confirmar el estado" e
  incluye el código.
- **GAP-3 (hallazgo de UAT en vivo):** `VoidedTicketFlowService::resolveTicket()`
  compara `(int) $code === 98`, reconociendo `'98'`, `'098'` y `'0098'` como
  `codigo_98`.

**Pero la revisión de código recién regenerada (`02-REVIEW.md`,
2026-08-05T21:10:00Z) identificó un cuarto defecto de la misma familia**, no
tocado por `02-03` porque estaba deliberadamente fuera de su alcance (era
WR-09, un Warning, en la revisión previa a 02-03): la **rama de reenvío** de
`sendXml()` regenera el XML mirando sólo la columna `xml_path`
(`empty($voided->xml_path)`), no si el fichero existe realmente en disco.
Verifiqué esto de forma independiente leyendo el código —no la narrativa del
review— y **se confirma**: un fichero ausente entra al `catch` de
`sendXml()`, `classifyFailure()` lo clasifica como rechazo porque el ticket es
nulo, y la fila queda `Rechazada` con un mensaje de error de PHP en el lugar
donde debería ir la respuesta de SUNAT. Es la misma clase de mentira que este
milestone existe para eliminar —el listado afirma un desenlace de SUNAT que
SUNAT nunca comunicó— ahora en la vía del reenvío en vez de la del ticket. Por
eso el estado de esta re-verificación sigue siendo `gaps_found`, no `passed`.

### Verdades observables

| # | Verdad | Estado | Evidencia |
|---|--------|--------|-----------|
| 1 | Una anulación Pendiente con `sunat_ticket` guardado ofrece "Consultar estado" en el listado, que reconsulta ese ticket sin reenviar el XML (ROADMAP SC1, WEB-02) | ✓ VERIFICADO (código) | Sin cambios respecto de la verificación anterior; regresión comprobada: `sendXml()` líneas 146-193 |
| 2 | Si SUNAT sigue procesando (motivo `codigo_98`), swal "Aún en proceso" y el ticket permanece guardado (D-09) | ✓ VERIFICADO | `dispatchPendingSwal()` líneas 297-321: rama `codigo_98` conserva el literal "Aún en proceso"; `grep -c 'Aún en proceso'` = 1 |
| 3 | Al llegar el CDR por reconsulta, la fila queda Aceptada o Rechazada según el código del propio CDR, el ZIP se archiva, el ticket se limpia y los comprobantes quedan marcados (ROADMAP SC3) | ✓ VERIFICADO (código, sin cambios) | `sendXml()` sigue sin `sendXmlFile(`/`markVoidedInvoices(` propios; delega en `VoidedTicketFlowService::resolveTicket()` |
| 4 | Una anulación sin ticket guardado reenvía el XML y guarda el ticket que SUNAT devuelva, siempre que el envío realmente llegue a SUNAT (ROADMAP SC2, WEB-03) | ✓ VERIFICADO, con advertencia | `canResendXml()` conserva sus dos casos; `sendAndResolve` una vez. Ver gap #12 para el caso local que impide llegar a SUNAT |
| 5 | Una anulación Aceptada, o Rechazada que conserva ticket, no llama a SUNAT y el sistema no afirma un desenlace de SUNAT que nunca ocurrió — **cerrado en esta re-verificación** (D-12, CR-01 de la verificación anterior) | ✓ **VERIFICADO** (regresión cerrada) | `grep -c 'sunat_status === VoidedStatus::Pendiente'` = 0; ventana de `sendXml()` contiene `VoidedStatus::Aceptada` y `VoidedStatus::Rechazada`; rama "Desenlace confirmado" exige `VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket)` — confirmado leyendo líneas 195-207 |
| 6 | La ruta del ZIP del CDR se arma con el RUC de la empresa dueña de la fila, no el de la sesión (D-04) | ✓ VERIFICADO | Sin cambios; `cdrPathFor()` lee `$voided->company['ruc']` |
| 7 | El listado distingue a simple vista aceptada/pendiente/rechazada mediante badge de texto + icono; una baja aceptada nunca se pinta en rojo (ROADMAP SC4, WEB-04) | ✓ VERIFICADO, con advertencia | Sin cambios en el enum ni en los badges; persiste WR-07/IN-03 (badge Rechazada sin fondo de color) |
| 8 | Las filas históricas (`sunat_status` NULL) muestran un badge gris "SIN ESTADO" resuelto en Blade, incluida la hoja nueva con ticket vivo | ✓ VERIFICADO | `grep -c 'SIN ESTADO'` = 3 (antes 2, +1 por la hoja nueva); ninguna invoca `badgeColor()`/`label()`; enum sigue con 3 casos |
| 9 | Los iconos preexistentes se conservan y la hoja nueva usa un icono distinto para no confundirse con el interrogante gris (D-06) | ✓ VERIFICADO | Hoja nueva usa `arrow-path` azul con `title`; el resto de iconos intactos |
| 10 | El operador puede filtrar el listado por estado y aislar las pendientes, sin fuga multi-tenant (D-07) | ✓ VERIFICADO (código) | `filters()` sin cambios; closure compone sobre `builder()` ya acotado |
| 11 | El filtro ofrece la opción para las filas sin estado resuelta con `whereNull` (D-07, D-08) | ✓ VERIFICADO | Sin cambios |
| 12 | **Ninguna salida de `sendXml()` afirma un desenlace de SUNAT que SUNAT no comunicó — ni siquiera cuando el fallo es puramente local** (Phase Goal, ROADMAP SC2) | ✗ **FALLIDO** | Ver Gaps abajo. `empty($voided->xml_path)` no comprueba `Storage::exists()`; un fichero ausente se clasifica como rechazo de SUNAT vía `classifyFailure()` |

**Puntaje:** 11/12 verdades verificadas en código (1 fallida: #12, hallazgo nuevo de `02-REVIEW.md` regenerado). Las verdades #1, #3, #7 y #10 dependen en su confirmación final de comportamiento en vivo contra SUNAT/la UI real (ver Verificación Humana), tal como el propio plan ya declaraba con D-14.

### Artefactos requeridos

| Artefacto | Esperado | Estado | Detalles |
|---|---|---|---|
| `app/Livewire/VoidedTable.php` | Árbol de tres ramas en `sendXml()` condicionado por estado (no por presencia de ticket), `dispatchPendingSwal()`, `cdrPathFor()`, `filters()` | ✓ VERIFICADO, con el gap #12 en la rama de reenvío | `php -l` limpio, `vendor/bin/pint --test` pasa, 9 métodos públicos sin cambio de recuento |
| `app/Services/Sunat/VoidedTicketFlowService.php` | Normalización numérica del código en `resolveTicket()` | ✓ VERIFICADO | `(int) $code === 98` confirmado en el bloque "sin CDR"; `markPending` 6 ocurrencias (sin rutas añadidas ni perdidas) |
| `resources/views/voideds/partials/response.blade.php` | Hoja nueva para la fila histórica con ticket vivo | ✓ VERIFICADO | 3 hojas en la rama histórica; `wire:click="sendXml` ×4, `wire:click="showResponse` ×3, `x-wire-badge` ×7, `SIN ESTADO` ×3 |

### Verificación de enlaces clave

| Desde | Hacia | Vía | Estado | Detalles |
|---|---|---|---|---|
| `VoidedTable::sendXml()` (rama Consultar) | `VoidedTicketFlowService::resolveTicket()` | llamada directa, todo ticket vivo sin desenlace confirmado | ✓ WIRED | Condición por exclusión confirmada en líneas 146-150 |
| `VoidedTable::sendXml()` (4 salidas de pendiente) | `VoidedTable::dispatchPendingSwal()` | llamada privada | ✓ WIRED | `dispatchPendingSwal` aparece 5 veces (1 declaración + 4 llamadas) |
| `VoidedTicketFlowService::resolveTicket()` | `markPending($voided, 'codigo_98', ...)` | código normalizado | ✓ WIRED | Alcanzable con `0098` |
| `response.blade.php` (hoja histórica con ticket) | `VoidedTable::sendXml($row->id)` | `wire:click` | ✓ WIRED | Línea 74 |
| `VoidedTable::sendXml()` (rama Reenviar) | `Storage::exists($voided->xml_path)` antes de leer el fichero | **no existe esta llamada** | ✗ **NOT_WIRED** | Confirmado por grep: 0 ocurrencias de `Storage::exists` dentro de `sendXml()`; sólo `downloadCDR()` la usa (línea 125) |

### Requisitos cubiertos

| Requisito | Plan origen | Descripción | Estado | Evidencia |
|---|---|---|---|---|
| WEB-02 | 02-01, 02-03 | El usuario puede reconsultar el estado de una anulación pendiente desde el listado, sin reenviar el XML, para todo ticket vivo sin desenlace confirmado | ✓ SATISFECHO (código) | Rama Consultar por exclusión de desenlaces confirmados; confirmación de comportamiento real → human_needed (D-14) |
| WEB-03 | 02-01 | El reenvío del XML a SUNAT solo ocurre cuando la anulación no tiene ticket guardado | ✓ SATISFECHO, con advertencia | `canResendXml()` correcto en su condición de entrada; el gap #12 está *dentro* de esa rama (el envío correcto se dispara, pero un fallo local puede impedir que llegue a SUNAT y aun así clasificarse como rechazo de SUNAT) |
| WEB-04 | 02-02 | El usuario distingue en el listado cuáles fueron aceptadas, cuáles siguen pendientes y cuáles rechazó SUNAT | ✓ SATISFECHO, con advertencia | Badge + icono en las 7 hojas; WR-07/IN-03 persiste (color de fondo) |

No hay requisitos huérfanos: `REQUIREMENTS.md` mapea exactamente WEB-02, WEB-03 y WEB-04 a la Fase 2; los tres planes (02-01, 02-02, 02-03) declaran esos IDs en su frontmatter `requirements:` sin faltantes.

### Anti-patrones encontrados

| Archivo | Línea | Patrón | Severidad | Impacto |
|---|---|---|---|---|
| `app/Livewire/VoidedTable.php` | 228-243 | Guarda de regeneración del XML mira sólo la columna `xml_path`, no `Storage::exists()` (CR-01 de la revisión regenerada, antes WR-09) | 🛑 **Blocker** | Un fichero ausente en disco se clasifica como rechazo de SUNAT sin que SUNAT haya respondido nada — contradice el objetivo central de la fase. Confirmado independientemente (ver Gaps) |
| `app/Livewire/VoidedTable.php` | 181-189 | Doble `dispatch('swal', …)` en el catch de la rama Consultar: `showResponse()` seguido de un swal de error que lo sustituye (WR-01 de la revisión regenerada) | ⚠️ Warning | El detalle del comprobante nunca llega a verse; agravado cuando el desenlace ya es Aceptada/Rechazada confirmada (invariante de `classifyFailure()`) — el operador ve "error" sobre un desenlace ya cerrado |
| `resources/views/voideds/partials/response.blade.php:13-23`; `sunatResponse.blade.php:47-90` | — | La rama Pendiente del modal de detalle quedó inalcanzable desde la interfaz (el reloj ahora dispara `sendXml`), y esa misma rama sigue afirmando "SUNAT... aún la procesa" sin condicionar por motivo (WR-02) | ⚠️ Warning | No hay forma de leer el ticket/motivo de una pendiente sin llamar a SUNAT; el criterio de honestidad que `dispatchPendingSwal()` aplica al toast no se replicó en el modal |
| `app/Livewire/VoidedTable.php:146-217`; `response.blade.php:13-23` | — | Fila `Pendiente` sin ticket (deriva de datos, no alcanzable por flujo normal) cae en "No se puede reenviar" con una afirmación categórica sobre SUNAT (WR-03) | ⚠️ Warning | Baja probabilidad (los tres escritores de Pendiente siempre conservan el ticket), pero la salida elegida repite el patrón de afirmar de más |
| `app/Livewire/VoidedTable.php:101-109` | — | `downloadPDF()`/`downloadXML()` sin la guarda `Storage::exists()` que sí tiene `downloadCDR()` (WR-04) | ⚠️ Warning | Fila con `pdf_path`/`xml_path` en NULL (fallo en el envío original) revienta con error 500 al pulsar el icono |
| `app/Livewire/VoidedTable.php:186,271`; `VoidedTicketFlowService.php:245-252` | — | Sin `wire:loading`/`wire:target` en los botones de SUNAT; `markRejected()` no respeta el invariante de "desenlace confirmado" que sí aplica `classifyFailure()` (WR-05) | ⚠️ Warning | Doble clic/doble pestaña puede sobrescribir un desenlace ya confirmado por un rechazo espurio de la segunda llamada |
| `app/Livewire/VoidedTable.php:186,271` | — | `'text' => $e->getMessage()` sin `Log::error()` (WR-06) | ⚠️ Warning | Filtra detalle interno al operador y no deja rastro en logs si cierra el modal |
| `app/Livewire/VoidedTable.php:157,221-222,345-357` | — | Certificado/endpoint SUNAT siguen anclados a `session('company')` mientras la ruta del CDR ya usa la empresa de la fila (WR-07) | ⚠️ Warning | Divergencia sólo explotable hoy vía el hueco de autorización (IN-01/CR-02, diferido a Fase 4) |
| `app/Services/Sunat/VoidedTicketFlowService.php:84-166` | — | `resolveTicket()` público sin guarda de entrada propia; el invariante de "desenlace confirmado" sólo vive en el llamador (WR-08) | ⚠️ Warning | Un llamador futuro (Fase 3) que omita la guarda de tres términos puede degradar una Aceptada a Pendiente destruyendo el `cdrResponse` |
| `app/Livewire/VoidedTable.php:101,106,125,140,323` | — | Acciones públicas sin comprobación de propiedad multi-tenant/permiso (CR-02/IN-01) | ℹ️ Info | **Diferido a Fase 4** por decisión de roadmap explícita; no se cuenta como gap de esta fase |
| `app/Livewire/VoidedTable.php:351-357` | — | Nombre del ZIP del CDR (`RC`/`fecGeneracion`) no coincide con la nomenclatura Greenter del XML (`RA`/`fecComunicacion`) (WR-01/IN-02) | ℹ️ Info | Pre-existente a la Fase 2; corregirlo requiere migración de datos, fuera de alcance |
| `app/Enums/VoidedStatus.php:20-27` | — | `badgeColor()` devuelve `danger`, no registrado en WireUI (WR-07/IN-03) | ℹ️ Info | Enum de la Fase 1, D-08 lo declara intocable en esta fase |
| `app/Livewire/VoidedTable.php:41-43` | — | Rama muerta `$value === ''` en el filtro (IN-04) | ℹ️ Info | El paquete ya descarta ese valor antes de invocar la closure |
| `app/Livewire/VoidedTable.php:140-274` | — | `sendXml()` sigue por encima de 130 líneas (IN-06); `$directory` duplicado | ℹ️ Info | Excede la convención de 20-50 líneas; no bloquea el objetivo |
| `app/Livewire/VoidedTable.php:354` | — | `$voided->company['ruc']` sin comprobación de existencia (IN-07) | ℹ️ Info | Columna `company` es `json NOT NULL` poblada siempre por el observer; no hay vector real hoy |
| `app/Services/Sunat/VoidedTicketFlowService.php:102-112` | — | Código `99` ("proceso con errores", desenlace terminal) se clasifica como `fallo_transporte` (IN-08) | ℹ️ Info | La fila quedaría Pendiente indefinidamente atribuyendo a la red algo que SUNAT sí respondió; no observado en la UAT en vivo |
| `resources/views/voideds/partials/response.blade.php` (7 botones) | — | Sin `type="button"` ni `title`/`aria-label` en 6 de 7 botones (IN-09) | ℹ️ Info | Accesibilidad; la fase sólo añadió `title` a la hoja nueva |
| `app/Livewire/VoidedTable.php`; `VoidedTicketFlowService.php` | — | Sin cobertura automatizada más allá de las probetas desechables (IN-10) | ℹ️ Info | La suite `phpunit` está rota de base (`UserFactory`) y corre contra la BD configurada; deuda conocida del proyecto |

No se detectaron marcadores de deuda (`TBD`/`FIXME`/`XXX`) sin referencia en los tres ficheros modificados por `02-03`.

### Gaps

**CR-01 (revisión regenerada) — la rama de reenvío puede grabar una fila como "Rechazada por SUNAT" por un fallo puramente local.**

Ver la entrada `gaps:` del frontmatter para el detalle estructurado y la
corrección propuesta. En resumen: la guarda de regeneración del XML en la rama
de reenvío de `sendXml()` (`empty($voided->xml_path)`) no comprueba que el
fichero exista en el disco configurado — a diferencia de `downloadCDR()`, que
sí lo hace en la misma clase, con el razonamiento explícito de que "la ruta
puede estar poblada sin fichero detrás". Cuando el fichero falta, la excepción
resultante se clasifica por `classifyFailure()` como rechazo de SUNAT
(`is_null(sunat_ticket)` es verdadero en toda fila que llega a esta rama), y la
fila queda `Rechazada` con el mensaje crudo de un `TypeError`/`UnableToReadFile`
en el lugar donde debería ir la respuesta de SUNAT.

Este hallazgo **no formaba parte del alcance de `02-03`** — era `WR-09`, un
Warning, en la revisión previa al cierre de gaps, y `02-03-PLAN.md` lo excluyó
explícitamente de su lista de "qué entra" con esa misma razón. La revisión de
código regenerada tras `02-03` lo eleva a Blocker/Crítico precisamente porque,
con el resto del árbol de decisión ya honesto, esta es ahora la única vía
restante por la que el listado puede afirmar un desenlace de SUNAT que SUNAT
nunca comunicó — la contradicción exacta que el objetivo de la fase declara
eliminar. Confirmé el hallazgo de forma independiente leyendo
`VoidedTable.php:228-243` y `VoidedTicketFlowService.php` (`classifyFailure()`,
`markRejected()`), sin apoyarme en la narrativa de `02-REVIEW.md`.

## Verificación humana requerida

Ver la lista `human_verification` en el frontmatter — los dos `human-check`
nuevos que introdujo `02-03` (ruteo NULL+ticket y honestidad del aviso contra
el `0127` real de beta), los ítems D-14 heredados que exigen producción u
homologación, y los dos ítems de legibilidad/filtro que el UAT en vivo anterior
sólo cubrió parcialmente por falta de filas variadas.

## Resumen de gaps

Los tres defectos que dejó abiertos la verificación anterior (CR-01 de ruteo,
el aviso de pendiente que mentía sobre el progreso, y la comparación de código
sin normalizar) están cerrados y verificados de forma independiente en el
código actual — no hay regresiones. Sin embargo, la revisión de código
regenerada tras el cierre de gaps descubrió un cuarto defecto de la misma
familia, deliberadamente fuera del alcance de `02-03`: la rama de reenvío
puede convertir un fallo de almacenamiento local en un falso rechazo de SUNAT.
Es la misma clase de mentira que la fase existe para eliminar, ahora en la vía
opuesta a la que corrigió `02-03`, y por eso el veredicto de esta
re-verificación sigue siendo `gaps_found`. Los hallazgos de autorización
(CR-02/IN-01) y las demás advertencias/info de `02-REVIEW.md` permanecen
correctamente diferidos a la Fase 4 o registrados como deuda pre-existente sin
bloquear el objetivo de esta fase.

---

*Verificado: 2026-08-05*
*Verificador: Claude (gsd-verifier)*
