---
phase: 02-reconsulta-de-anulaciones-pendientes
verified: 2026-08-05T00:00:00Z
status: gaps_found
score: 10/11 must-haves verificados en código (1 fallido, ver gaps)
behavior_unverified: 0
overrides_applied: 0
gaps:
  - truth: "Una anulación Aceptada, o Rechazada que conserva ticket, no llama a SUNAT — y el sistema no afirma un desenlace de SUNAT que nunca ocurrió para ningún otro estado de la fila (D-12, Phase Goal 'el listado dice la verdad sobre en qué estado está cada baja')"
    status: failed
    reason: >
      La rama 2 de VoidedTable::sendXml() (líneas 195-207) se condiciona
      únicamente por `! is_null($voided->sunat_ticket)`, sin comprobar el
      valor de `sunat_status`. Existe un estado alcanzable en producción —
      `sunat_ticket` poblado y `sunat_status` en NULL — que cae en esta rama
      y recibe el swal "Desenlace confirmado: SUNAT ya devolvió el CDR
      rechazando esta comunicación de baja", una afirmación falsa: no hay
      CDR ni rechazo, sólo un ticket sin resolver. Confirmado independientemente
      con un script de probeta que reproduce sólo la lógica de guarda (sin BD
      ni red): status=NULL + ticket≠NULL enruta a "DESENLACE_CONFIRMADO" en
      vez de a ninguna rama de reconsulta. El estado es alcanzable porque
      VoidedTicketFlowService::sendAndResolve() (líneas 71-76) persiste el
      ticket con un save() propio y luego invoca resolveTicket() en una
      llamada de red aparte; si el proceso termina entre esos dos puntos
      (fatal de PHP por max_execution_time — no es un \Throwable capturable
      —, OOM, reinicio de php-fpm), la fila queda exactamente en ese estado.
      Es justo la clase de defecto que motivó este milestone (el estado que
      muestra el sistema deja de coincidir con el de SUNAT), y no tiene
      salida por la interfaz: la rama 1 exige `sunat_status === Pendiente`,
      así que el ticket queda huérfano para siempre.
    artifacts:
      - path: "app/Livewire/VoidedTable.php"
        issue: "sendXml() líneas 195-207: la guarda de la rama 'Avisar — desenlace confirmado' se basa solo en la presencia del ticket, no en el estado, capturando también filas con sunat_status=NULL"
    missing:
      - "Condicionar la rama 2 explícitamente por sunat_status === VoidedStatus::Rechazada (no solo por la presencia del ticket), tal como propone CR-01 de 02-REVIEW.md"
      - "Dar una vía de reconsulta a las filas con ticket vivo y sunat_status NULL (ampliar la condición de la rama 1, o una rama nueva), en vez de dejarlas huérfanas"
      - "Corregir en paralelo resources/views/voideds/partials/response.blade.php:58-76, cuya rama histórica ofrece showResponse() (mensaje 'Error al Enviar a SUNAT') para el mismo estado, en vez de la reconsulta que sendXml() necesitaría ofrecer"
deferred:
  - truth: "Las acciones públicas de VoidedTable (sendXml, showResponse, downloadPDF/XML/CDR) comprueban que la fila pertenece a la empresa/sucursal/ambiente de la sesión antes de operar sobre ella"
    addressed_in: "Phase 4"
    evidence: "ROADMAP.md §Phase 4: 'Las acciones de anulación comprueban propiedad multi-tenant y permiso en el servidor... (CR-02 y CR-04 del code review de la Fase 1)'. 02-REVIEW.md CR-02 se registra explícitamente como duplicado de ese hallazgo, y 02-CONTEXT.md §Phase Boundary lo declara fuera de alcance de esta fase."
human_verification:
  - test: "D-14 (02-01): contra el ambiente beta de SUNAT (production=false), dejar una comunicación de baja en Pendiente forzando el código 98 o el SoapFault de transporte, pulsar la acción de la columna Sunat y comprobar que (a) SUNAT no responde 2109, (b) si ya hay CDR la fila pasa a Aceptada con el ZIP descargable y los comprobantes marcados, (c) si sigue procesando aparece 'Aún en proceso' y el ticket permanece."
    expected: "El ticket se reconsulta sin reenviar el XML; el desenlace final coincide con el que SUNAT reporta."
    why_human: "SUNAT es un tercero real; no hay infraestructura de test con SUNAT simulado (D-14 heredado de la Fase 1)."
  - test: "D-14 (02-01, Tarea 2): con la empresa de sesión igual a la empresa de la fila, enviar una anulación nueva y confirmar que el ZIP del CDR sigue archivándose y descargándose en la misma ruta que antes del plan (sunat/beta/cdr/R-{ruc}-RC-{Ymd}-{correlativo}.zip)."
    expected: "Mover la procedencia del RUC (D-04) no cambia la ruta resultante en el caso normal (sesión = empresa de la fila)."
    why_human: "Requiere un envío real contra el ambiente beta de SUNAT."
  - test: "D-14 (02-02, Tarea 1): abrir el listado con filas en los cuatro estados y confirmar que cada una muestra su palabra (ACEPTADA/PENDIENTE/RECHAZADA/SIN ESTADO) sin pasar el ratón ni abrir el modal, que los iconos siguen presentes y que la fila no se descuadra."
    expected: "Los cuatro badges se leen a simple vista."
    why_human: "Legibilidad y maquetación son juicios visuales."
  - test: "Punto de atención conocido (WR-07, confirmado en este VERIFICATION leyendo vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Solid.php): VoidedStatus::badgeColor() devuelve 'danger' para Rechazada, valor no registrado en el pack de colores de WireUI — ComponentPack::get() hace fallback a la cadena literal, así que el badge de Rechazada se renderiza sin clase de color de fondo mientras Aceptada (primary) y Pendiente (warning) sí lo tienen. Confirmar si el resultado visual (icono fa-ban rojo + texto RECHAZADA sin fondo de color) es aceptable o si amerita corregir el enum en un ajuste posterior."
    expected: "El operador sigue distinguiendo la fila Rechazada por icono y texto aunque el badge carezca de fondo de color."
    why_human: "Es un juicio de aceptabilidad visual, no un defecto funcional; corregir el enum queda fuera de alcance de esta fase (D-08 lo declara intocable)."
  - test: "D-14 (02-02, Tarea 2): en el listado, seleccionar PENDIENTE en el filtro 'Estado Sunat' y confirmar que sólo quedan las filas con badge PENDIENTE; seleccionar SIN ESTADO y confirmar que sólo aparecen las históricas con badge gris; volver a 'Todos' y confirmar que el listado se restablece; confirmar que el filtro no saca filas de otra empresa o sucursal."
    expected: "El filtro reduce el listado correctamente y nunca ensancha el ámbito multi-tenant."
    why_human: "Depende de datos reales en distintos estados renderizados en la UI."
---

# Fase 2: Reconsulta de anulaciones pendientes — Informe de verificación

**Objetivo de la fase:** El usuario recupera desde el listado el resultado final de una anulación pendiente, y el listado dice la verdad sobre en qué estado está cada baja
**Verificado:** 2026-08-05
**Estado:** gaps_found
**Re-verificación:** No — verificación inicial

## Logro del objetivo

Esta fase cumple la mayor parte de su contrato: la ruta feliz de la reconsulta
(Pendiente con ticket → `resolveTicket()`), el reenvío cuando no hay ticket, la
ruta del CDR con el RUC del modelo y el badge/filtro de estado están todos
presentes, cableados y libres de stub. Sin embargo, la revisión de código de
esta misma fase (`02-REVIEW.md`, hallazgo **CR-01**) identifica una grieta en
el árbol de guardas de `sendXml()` que **contradice directamente** el propio
objetivo de la fase — que el listado diga la verdad — para un estado
alcanzable (aunque de baja probabilidad) de la fila. Verifiqué esa grieta de
forma independiente leyendo el código (no la narrativa de `02-REVIEW.md`) y
reproduciendo la lógica de la guarda en un script aislado: **se confirma**.
Por eso el estado de esta verificación es `gaps_found`, no `human_needed`.

### Verdades observables

| # | Verdad | Estado | Evidencia |
|---|--------|--------|-----------|
| 1 | Una anulación Pendiente con `sunat_ticket` guardado ofrece "Consultar estado" en el listado, que reconsulta ese ticket sin reenviar el XML (ROADMAP SC1, WEB-02) | ✓ VERIFICADO (código) | `sendXml()` líneas 146-193: guarda `sunat_status===Pendiente && !is_null(sunat_ticket)` → `resolveTicket($voided, cdrPathFor($voided))`, sin `sendXmlFile(` ni `markVoidedInvoices(` en el componente. Comportamiento real contra SUNAT → ver Verificación Humana |
| 2 | Si SUNAT sigue procesando, swal "Aún en proceso" y el ticket permanece guardado (D-09) | ✓ VERIFICADO | Literal "Aún en proceso" aparece 2 veces (rama try y rama catch), ninguna limpia `sunat_ticket` |
| 3 | Al llegar el CDR por reconsulta, la fila queda Aceptada o Rechazada según el código del propio CDR, el ZIP se archiva, el ticket se limpia y los comprobantes quedan marcados (ROADMAP SC3) | ✓ VERIFICADO (código, delega en `VoidedTicketFlowService::resolveTicket()` ya probado en Fase 1) | `sendXml()` no contiene ninguna llamada a `sendXmlFile(` ni `markVoidedInvoices(`: el componente no replica esa lógica. Desenlace real → ver Verificación Humana |
| 4 | Una anulación sin ticket guardado sigue reenviando el XML y guarda el ticket que SUNAT devuelva (ROADMAP SC2, WEB-03) | ✓ VERIFICADO | `sendAndResolve` aparece exactamente 1 vez; `canResendXml()` conserva sus dos casos `true` sin incluir `Pendiente` |
| 5 | Una anulación Aceptada, o Rechazada que conserva ticket, no llama a SUNAT — **y el sistema no afirma un desenlace de SUNAT que nunca ocurrió para ningún otro estado** (D-12, objetivo de la fase) | ✗ **FALLIDO** | Ver Gaps abajo (CR-01). La rama se dispara para cualquier fila con ticket, incluida `sunat_status=NULL` |
| 6 | La ruta del ZIP del CDR se arma con el RUC de la empresa dueña de la fila, no el de la sesión (D-04) | ✓ VERIFICADO | `grep -vE '^\s*(//|\*|/\*)' … \| grep -c "session('company')->ruc"` = 0; `cdrPathFor()` lee `$voided->company['ruc']` (1 ocurrencia), usado 4 veces en el fichero (2 en cada rama que archiva CDR) |
| 7 | El listado distingue a simple vista aceptada/pendiente/rechazada mediante badge de texto + icono; una baja aceptada nunca se pinta en rojo (ROADMAP SC4, WEB-04) | ✓ VERIFICADO, con advertencia | 6 `x-wire-badge` en las 6 hojas de la celda; 4 alimentan color/label desde el enum. **Advertencia (WR-07, confirmada leyendo `vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Solid.php`):** `VoidedStatus::badgeColor()` devuelve `'danger'`, que no es una clave del pack de colores de WireUI (los válidos incluyen `positive`/`negative`/`warning`/`primary`, no `danger`) — el badge de Rechazada se renderiza sin fondo de color. El texto RECHAZADA y el icono `fa-ban` rojo siguen distinguiendo la fila, así que el criterio literal ("Aceptada nunca se pinta en rojo") se cumple, pero el defecto es real y ya está anotado para UAT |
| 8 | Las filas históricas (`sunat_status` NULL) muestran un badge gris "SIN ESTADO" resuelto en Blade, sin añadir un cuarto caso al enum (D-08) | ✓ VERIFICADO | Literal "SIN ESTADO" 2 veces, ambas dentro de la rama `@else`; ninguna de esas 2 líneas invoca `badgeColor()`/`label()`; `app/Enums/VoidedStatus.php` sigue con 3 casos (no tocado, confirmado por `git diff --name-only`) |
| 9 | Los iconos preexistentes (check-circle, clock, fa-ban, fa-question-circle, get_cdr.svg) se conservan (D-06) | ✓ VERIFICADO | Los 5 aparecen al menos una vez en `response.blade.php` |
| 10 | El operador puede filtrar el listado por estado y aislar las pendientes (D-07) | ✓ VERIFICADO (código) | `VoidedTable::filters()` declara un `SelectFilter` "Estado Sunat"; la closure compone sobre el `Builder` de `builder()` (ya acotado por `branch_id`/`company_id`/`production`), nunca lo ensancha. Comportamiento real en UI → ver Verificación Humana |
| 11 | El filtro ofrece la opción para las filas sin estado resuelta con `whereNull`, no con un cuarto caso del enum (D-07, D-08) | ✓ VERIFICADO | `whereNull('sunat_status')` exactamente 1 vez, en la rama del token `sin-estado` (no colisiona con `aceptada`/`pendiente`/`rechazada`) |

**Puntaje:** 10/11 verdades verificadas en código (1 fallida: #5, hallazgo CR-01). Las verdades #1, #3, #7 y #10 están correctamente cableadas en código pero su confirmación final depende de comportamiento en vivo contra SUNAT/la UI real (ver Verificación Humana), tal como el propio plan ya declaraba con D-14.

### Artefactos requeridos

| Artefacto | Esperado | Estado | Detalles |
|---|---|---|---|
| `app/Livewire/VoidedTable.php` | Árbol de tres ramas en `sendXml()`, `cdrPathFor()`, `filters()` | ✓ VERIFICADO (con el gap de #5) | `php -l` limpio, `vendor/bin/pint --test` pasa, 9 métodos públicos (`configure`, `filters`, `columns`, `downloadPDF`, `downloadXML`, `downloadCDR`, `sendXml`, `showResponse`, `builder`) + 2 privados (`cdrPathFor`, `canResendXml`) |
| `resources/views/voideds/partials/response.blade.php` | Rama Pendiente dispara `sendXml`; badges en las 6 hojas | ✓ VERIFICADO | `php artisan view:cache` termina en 0; 3× `wire:click="sendXml`, 3× `wire:click="showResponse`, 6× `x-wire-badge`, ninguna con `wire:click` |

### Verificación de enlaces clave

| Desde | Hacia | Vía | Estado | Detalles |
|---|---|---|---|---|
| `VoidedTable::sendXml()` (rama Consultar) | `VoidedTicketFlowService::resolveTicket()` | llamada directa, 2 argumentos (sin `$see`) | ✓ WIRED | Confirmado en línea 155 |
| `VoidedTable::sendXml()` (catch exterior, 2 ramas) | `VoidedTicketFlowService::classifyFailure()` | llamada directa | ✓ WIRED | 2 ocurrencias (rama Consultar y rama Reenviar) |
| `VoidedTable::cdrPathFor()` | `$voided->company['ruc']` | lectura de propiedad del modelo (JSON `company`) | ✓ WIRED | `VoidedObserver::creating()` (no tocado en esta fase) puebla ese JSON |
| `response.blade.php` (rama Pendiente) | `VoidedTable::sendXml($row->id)` | `wire:click` | ✓ WIRED | Línea 19 |
| `VoidedTable::filters()` | columna `sunat_status` vía `SelectFilter` | `->where()`/`->whereNull()` sobre el `Builder` de `builder()` | ✓ WIRED | La closure compone sobre el builder ya acotado; no hay fuga multi-tenant a nivel de código |
| `response.blade.php` (badges) | `VoidedStatus::badgeColor()`/`label()` | prop dinámico `:color`/`:label` | ✓ WIRED, con defecto pre-existente | Ver hallazgo WR-07 |

### Requisitos cubiertos

| Requisito | Plan origen | Descripción | Estado | Evidencia |
|---|---|---|---|---|
| WEB-02 | 02-01 | El usuario puede reconsultar el estado de una anulación pendiente desde el listado, sin reenviar el XML | ✓ SATISFECHO (código) | Rama Consultar de `sendXml()`; confirmación de comportamiento real → human_needed (D-14) |
| WEB-03 | 02-01 | El reenvío del XML a SUNAT solo ocurre cuando la anulación no tiene ticket guardado | ✓ SATISFECHO | `canResendXml()` conserva su semántica de lista de permitidos, `Pendiente` queda fuera |
| WEB-04 | 02-02 | El usuario distingue en el listado cuáles fueron aceptadas, cuáles siguen pendientes y cuáles rechazó SUNAT | ✓ SATISFECHO, con advertencia | Badge + icono en las 6 hojas; WR-07 afecta solo el color de fondo del badge Rechazada, no la distinguibilidad textual/icónica |

No hay requisitos huérfanos: `REQUIREMENTS.md` mapea exactamente WEB-02, WEB-03 y WEB-04 a la Fase 2, y ambos planes los declaran en su frontmatter `requirements:`.

### Anti-patrones encontrados

| Archivo | Línea | Patrón | Severidad | Impacto |
|---|---|---|---|---|
| `app/Livewire/VoidedTable.php` | 195-207 | Guarda condicionada solo por presencia de ticket, no por estado (CR-01) | 🛑 **Blocker** | Afirma un rechazo de SUNAT que nunca ocurrió para filas con `sunat_ticket≠null` y `sunat_status=NULL`; sin vía de reconsulta para esas filas — contradice el objetivo central de la fase. Confirmado independientemente (ver Gaps) |
| `app/Livewire/VoidedTable.php` | 183-189 | Doble `dispatch('swal', …)` en la misma respuesta (WR-02) | ⚠️ Warning | `Livewire.on('swal', …)` sólo puede mostrar un `Swal.fire()` a la vez; el segundo evento sustituye el detalle del primero — el usuario nunca ve el detalle del comprobante en ese camino del catch |
| `app/Livewire/VoidedTable.php` | 151, 223, 314-320 | Credenciales SUNAT (`UtilService`) siguen construyéndose con `session('company')` mientras la ruta del CDR ya usa la empresa de la fila (WR-03) | ⚠️ Warning | Divergencia sólo explotable hoy vía CR-02 (autorización, diferida a Fase 4), pero el comentario del código induce a creer que D-04 cerró todo el ámbito multi-tenant de la acción, cuando sólo cerró el nombre del fichero |
| `app/Livewire/VoidedTable.php` | 317 | `$voided->company['ruc']` sin comprobación de existencia (WR-04) | ⚠️ Warning | Si el JSON `company` no trae `ruc`, PHP emite un warning y compone una ruta corrupta (`R--RC-...zip`) sin fallar de forma visible |
| `app/Livewire/VoidedTable.php` | 188, 281 | `'text' => $e->getMessage()` mostrado tal cual al usuario (WR-05) | ⚠️ Warning | Puede filtrar detalles de `SoapFault`/`QueryException`/rutas de almacenamiento a un operador de patio; ninguna de esas ramas registra el error en el log |
| `resources/views/voideds/partials/response.blade.php` | 19, 31, 61; `VoidedTable.php:140` | Sin `wire:loading`/`wire:target` en los botones que llaman a SUNAT (WR-06) | ⚠️ Warning | Confirmado por grep (0 ocurrencias). Doble clic/doble pestaña puede concurrir dos llamadas SOAP sobre la misma fila; `markRejected()` no tiene el guardián de "desenlace confirmado" que sí tiene `classifyFailure()` |
| `resources/views/voideds/partials/response.blade.php` | 34, 44 (vía `VoidedStatus::badgeColor()`) | `danger` no es un color válido de WireUI (WR-07) | ⚠️ Warning | Confirmado leyendo `vendor/wireui/wireui/src/Components/Badge/WireUi/Color/Solid.php`; defecto pre-existente del enum (Fase 1), la Fase 2 amplía su superficie al listado |
| `resources/views/voideds/partials/response.blade.php` | 13-23 | El icono "reloj" cambió de significado (de `showResponse` a `sendXml`) sin aviso visual, y la fila Pendiente pierde acceso de solo lectura al detalle (WR-08) | ⚠️ Warning | Confirmado leyendo el archivo: la rama Pendiente ya no ofrece ningún botón que abra `showResponse` |
| `app/Livewire/VoidedTable.php` | 230-245 | `empty($voided->xml_path)` no comprueba que el fichero exista en disco (WR-09) | ⚠️ Warning | Confirmado por lectura directa; si el fichero falta, `Storage::get()` devuelve `null` (no lanza) y el `TypeError` resultante se clasifica como rechazo por `classifyFailure()` |
| `app/Livewire/VoidedTable.php` | 314-320 | Nombre del ZIP del CDR no coincide con la nomenclatura del XML de Greenter (WR-01) | ℹ️ Info | **Pre-existente** (venía de las líneas que `cdrPathFor()` sustituyó; esta fase sólo cambió la fuente del RUC, no el prefijo `RC`/fecha). No bloquea el objetivo de esta fase; queda como defecto heredado a evaluar aparte |
| `app/Livewire/VoidedTable.php` | 140-284 | `sendXml()` ronda las 145 líneas con textos de swal duplicados literalmente (IN-04) | ℹ️ Info | Excede la convención de 20-50 líneas del proyecto; no bloquea el objetivo pero facilita que los duplicados diverjan |
| `app/Livewire/VoidedTable.php`, `response.blade.php` | 38 / 64,73 | Cadena mágica `sin-estado`/`SIN ESTADO` duplicada entre componente y vista (IN-02) | ℹ️ Info | Si alguien renombra un lado, el otro queda descolgado sin error |

**Sobre CR-02 (autorización, `app/Livewire/VoidedTable.php:101-138,140-284,286-306`):** confirmado que las acciones públicas resuelven el modelo por id sin comprobar `company_id`/`branch_id`/`production` contra la sesión. Es duplicado explícito de CR-02/CR-04 de la revisión de la Fase 1, ya programado para la **Fase 4** (`ROADMAP.md` §Phase 4). Se registra en la sección `deferred` de este informe, no como gap de esta fase.

### Gaps

**CR-01 — la rama "Desenlace confirmado" afirma un rechazo de SUNAT que nunca ocurrió para filas con ticket vivo y estado NULL.**

Ver la entrada `gaps:` en el frontmatter para el detalle estructurado. En resumen: la condición de la rama 2 de `sendXml()` (`! is_null($voided->sunat_ticket)`) no distingue entre "Rechazada con ticket" (D-12, el caso legítimo) y "ticket guardado pero `sunat_status` todavía NULL" (fila que quedó a medio camino entre `VoidedTicketFlowService::sendAndResolve()` guardando el ticket y terminando de resolver el desenlace). Para esta segunda situación, hoy alcanzable en producción, el sistema:

1. Muestra un swal que afirma "SUNAT ya devolvió el CDR rechazando esta comunicación de baja" — falso, no hay CDR ni rechazo.
2. No ofrece ninguna vía de reconsulta: la rama 1 exige `sunat_status === Pendiente`, así que el ticket queda huérfano.
3. En el listado, la misma fila cae en la rama histórica de la vista y muestra "SIN ESTADO" con un botón que abre `showResponse()` — un mensaje distinto e igualmente incorrecto ("Error al Enviar a SUNAT").

Esto contradice directamente el objetivo declarado de la fase — *"el listado dice la verdad sobre en qué estado está cada baja"* — para el estado que precisamente motivó este milestone. No es un hallazgo teórico: confirmé la ruta de código con lectura directa de `VoidedTable.php` y `VoidedTicketFlowService.php`, y reproduje la lógica de la guarda en un script aislado (sin BD ni red) que muestra el enrutamiento incorrecto.

**Este hallazgo ya fue identificado por `02-REVIEW.md` (CR-01, Blocker) con una corrección propuesta.** No requiere trabajo de descubrimiento adicional, sólo que se ejecute el fix antes de considerar la fase completa.

## Verificación humana requerida

Ver la lista `human_verification` en el frontmatter — son los 5 ítems de UAT que las propias SUMMARY.md de los dos planes ya declaraban pendientes (D-14 heredado de la Fase 1: sin infraestructura de test con SUNAT simulado, la ruta feliz y el desenlace final sólo se confirman contra el ambiente beta real), más el punto de atención sobre el color del badge Rechazada (WR-07), que ya estaba anotado por el plan 02-02 para el UAT de la fase.

## Resumen de gaps

Un hallazgo bloqueante (CR-01): la guarda de `sendXml()` no distingue "Rechazada con ticket" de "ticket vivo sin estado resuelto", y para este segundo caso el sistema afirma un desenlace de SUNAT falso sin ofrecer ninguna vía de recuperación — exactamente el tipo de discrepancia que este milestone existe para eliminar. El resto de los must-haves de ambos planes (reconsulta feliz, reenvío, ruta del CDR por RUC de fila, badges, filtro) están correctamente implementados y cableados en código; su confirmación final contra SUNAT/la UI real queda como verificación humana ya prevista por el propio plan (D-14). Los hallazgos de autorización (CR-02) están correctamente diferidos a la Fase 4 y no se cuentan como gap de esta fase.

---

*Verificado: 2026-08-05*
*Verificador: Claude (gsd-verifier)*
