# Guía de Remisión Transportista (GRE-Transportista) — Documentación técnica

> Módulo nuevo, análogo al de "Guía de Remisión Remitente" (`Despatch`), para emitir
> el documento electrónico SUNAT **tipoDoc `31`** (Catálogo 01) — Guía de Remisión
> Transportista. Implementado porque Greenter (`greenter/lite` `^5.1`, ya instalado en
> el proyecto) **no soporta este tipo de documento** — ver sección
> ["Por qué un puente propio a Greenter"](#por-qué-un-puente-propio-a-greenter).

Fecha de implementación: 2026-07-24.
Base legal: R.S. N.° 007-99/SUNAT (RCP), R.S. N.° 188-2010/SUNAT (arts. 19, 19-A, 19-B),
R.S. N.° 097-2012/SUNAT (Anexos N.° 8, 12, 13, 14, 28), R.S. N.° 255-2015/SUNAT,
modificadas por **R.S. N.° 000108-2026/SUNAT** (vigente desde el 01/06/2026).

---

## 1. Contexto legal (resumen)

- **GRE-Remitente** (tipoDoc `09`): la emite quien traslada bienes bajo transporte
  privado, o el remitente en transporte público.
- **GRE-Transportista** (tipoDoc `31`): la emite el transportista cuando realiza el
  traslado en transporte público, **salvo** que la GRE-Remitente ya consigne los
  datos del transportista + vehículo + choferes (campos 44-49 del Anexo 12) **y** el
  indicador `SUNAT_Envio_IndicadorVehiculoConductoresTransp` (campo 54) — en ese caso
  el transportista queda exceptuado de emitir su propia guía
  (art. 3, num. 3.1, penúltimo párrafo, R.S. 255-2015/SUNAT).
- Este módulo cubre el caso en que el transportista **sí** debe emitir su propia GRE
  (consolidación de varias GRE-Remitente, subcontratación, traslados ZED/ZOFRATACNA
  en transporte público, etc.)

### Diferencias estructurales clave (XML UBL 2.1) frente a la GRE-Remitente

Fuente: **Anexo N.° 14, ANEXO N.° IV de la R.S. N.° 000108-2026/SUNAT** (leído
directamente del PDF oficial de SUNAT — `sunat.gob.pe/legislacion/superin/2026/anexo-000108-2026.pdf`).

| Bloque | GRE-Remitente (`09`) | GRE-Transportista (`31`) |
|---|---|---|
| Emisor | `cac:DespatchSupplierParty` = remitente | `cac:DespatchSupplierParty` = **transportista** |
| Datos del remitente | no aplica (es el emisor) | **`cac:Shipment/cac:Delivery/cac:Despatch/cac:DespatchParty`** (campos 16-17) |
| Empresa subcontratada | no existe | **`cac:Shipment/cac:Consignment/cac:LogisticsOperatorParty`** (campos 60-61) |
| Vehículo / choferes | condicional (`modTraslado='02'` o indicador ausente) | siempre obligatorio |
| Documento relacionado | opcional (DAM/DS/MC/Cita Terminal) | **obligatorio** — el documento del remitente que sustenta el traslado (Catálogo 61, Anexo 28 campo 16) |

---

## 2. Por qué un puente propio a Greenter

Se verificó **empíricamente** (no por documentación de terceros) que Greenter
`5.1.1`, tal como está vendorizado en este proyecto, no puede generar un XML
válido para tipoDoc `31`:

- `vendor/greenter/xml/src/Xml/Templates/despatch2022.xml.twig` **no tiene ninguna
  rama condicional por `tipoDoc`**. Siempre emite `DespatchSupplierParty` +
  `CarrierParty` (transportista embebido) y **nunca** `DespatchParty` ni
  `Consignment/LogisticsOperatorParty`.
- `Greenter\Xml\Builder\DespatchBuilder::build()` solo decide la plantilla por
  `getVersion()` (`'2022'` vs legado), no por `tipoDoc`.
- Si se seteara `tipoDoc = '31'` sobre `Greenter\Model\Despatch\Despatch` sin más,
  solo cambiaría el valor del tag `<cbc:DespatchAdviceTypeCode>` — el resto del XML
  seguiría con la estructura de remitente. **XML no conforme, sería rechazado por SUNAT.**
- Se confirmó además en la comunidad de Greenter
  (`community.greenter.dev/d/223-guia-de-remision-transportista` y un gist de un
  tercero) que la solución habitual de otros desarrolladores es sobreescribir el
  template Twig con un `{% if doc.tipoDoc == '31' %}` manual — exactamente el
  enfoque adoptado aquí, pero de forma más limpia (template y builder propios en
  vez de parchear el vendor).

**Decisión de diseño:** no modificar nada dentro de `vendor/greenter/*` (se
sobrescribiría en cada `composer update`) ni tocar el flujo de la GRE-Remitente
existente. Se creó un namespace propio `App\Services\Sunat\Transportista\*` con:
modelo de documento propio, XML builder propio (basado en `Greenter\Xml\Builder\TwigBuilder`),
template Twig propio, y un `UtilTransportistaService` que **extiende**
`App\Services\Sunat\UtilService` reutilizando todo lo que sí es genérico (envío a
SUNAT, firma, lectura de CDR, credenciales).

---

## 3. Inventario de archivos implementados

### Fase 1 — Catálogo, series y esquema de datos

| Archivo | Qué hace |
|---|---|
| `database/seeders/DocumentSeeder.php` | Agrega `id='31'` ("Guía de Remisión Transportista") al catálogo `documents` (Catálogo 01), usado por el selector de series por sucursal. |
| `database/seeders/CompanySeeder.php` | Agrega serie inicial `V001` (documento `31`) a la sucursal semilla, igual que `T001` para `09`. |
| `database/migrations/2026_07_24_100000_create_despatch_transportistas_table.php` | Crea tabla `despatch_transportistas` (mismo esquema que `despatches` + `remitente`, `subcontratador`, `documentoRelacionado`). |
| `database/migrations/2026_07_24_100100_add_gre_transportista_document_and_series.php` | **Migración de retrofit** para bases de datos ya migradas: inserta `documents.id='31'` si no existe, y crea la serie `V001` en `branch_company_document` para toda sucursal que ya tenga configurada una serie de tipo `09` (idempotente, no duplica). Esto cubre el pedido de "agregar la serie V001 al actual [entorno]" sin depender de volver a correr los seeders completos. |
| `database/migrations/2026_07_24_100200_create_related_documents_table.php` | Crea y siembra `related_documents` (Catálogo 61 SUNAT: "Documentos relacionados aplicables a las GRE" — 01, 03, 04, 09, 12, 31, 48, 49, 50, 52, 80, 81, 82, 91, 92, 93, 94, 95). Distinto de `documents` (Catálogo 01) aunque comparten algunos códigos. |
| `database/seeders/RelatedDocumentSeeder.php` | Mismo seed que la migración, para `db:seed:fresh` / testing. Registrado en `DatabaseSeeder.php`. |
| `app/Models/RelatedDocument.php` | Modelo Eloquent de solo lectura para `related_documents` (PK string, sin autoincrement). |
| `app/Livewire/Branches/ManageVouchers.php` | Se agregó `Rule::when($document == '31', "regex:/^V/")` para que, igual que `T` para remitente, toda serie nueva de transportista deba empezar con `V` (gestión de series ya es genérica en `branches/series`, no requirió UI nueva). |

### Fase 2 — Persistencia del documento emitido

| Archivo | Qué hace |
|---|---|
| `app/Models/DespatchTransportista.php` | Modelo Eloquent (mirror de `Despatch`). Campos JSON: `company`, `destinatario` (nullable), `remitente`, `subcontratador` (nullable), `documentoRelacionado` (nullable), `envio`, `details`, `sunatResponse`. |
| `app/Observers/DespatchTransportistaObserver.php` | En `creating()`, completa `company` desde el modelo `Company` (mismo patrón que `DespatchObserver`). |

### Fase 3 — Puente Greenter (modelo, XML, PDF)

| Archivo | Qué hace |
|---|---|
| `app/Services/Sunat/Transportista/Model/DespatchTransportista.php` | Modelo de documento **propio**, implementa `Greenter\Model\DocumentInterface`. Agrega `remitente`, `subcontratador` (ausentes en `Greenter\Model\Despatch\Despatch`). `getName()` idéntico al de Greenter (`RUC-tipoDoc-serie-correlativo`). |
| `app/Services/Sunat/Transportista/Xml/TransportistaXmlBuilder.php` | Réplica de `Greenter\Xml\Builder\TwigBuilder` (mismos filtros `n_format`/`n_format_limit` de `Greenter\Xml\Filter\FormatFilter`, misma zona horaria) apuntando a un directorio de templates propio. |
| `app/Services/Sunat/Transportista/Templates/despatchTransportista.xml.twig` | **XML UBL 2.1 de la GRE-Transportista.** Basado en `despatch2022.xml.twig` de Greenter, con los bloques nuevos: `cac:DespatchParty` (remitente, dentro de `Shipment/Delivery/Despatch`) y `cac:Consignment/cac:LogisticsOperatorParty` (subcontratador, condicional). Vehículo/choferes/direcciones/ítems reutilizan los mismos tags que la GRE-Remitente porque `Greenter\Model\Despatch\Shipment` ya modela ambos escenarios y los tags coinciden exactamente con los del Anexo 14-a para esos bloques compartidos. |
| `public/custom/despatchTransportista.html.twig` | Template PDF (representación impresa), adaptado de `public/custom/despatch.html.twig` (la versión ya personalizada del proyecto, no la de vendor). Agrega secciones REMITENTE, EMPRESA SUBCONTRATADA y DOCUMENTO(S) RELACIONADO(S). |

### Fase 4 — Servicios de dominio

| Archivo | Qué hace |
|---|---|
| `app/Services/Sunat/Transportista/DocumentTransportistaService.php` | Mapea el array (Request/Form) al modelo Greenter propio. **Reutiliza** `App\Services\Sunat\DocumentService::getCompany()/getClient()/getDirection()/getVehiculo()/getChoferes()/getDespatchDetails()` (inyectado por composición) en vez de duplicarlos, porque esos bloques son idénticos a los de la GRE-Remitente. Solo escribe lógica nueva para `getAddDocs()` (documento relacionado, catálogo 61) y `getEnvio()` (vehículo/choferes siempre obligatorios, sin la rama condicional por `modTraslado` que sí existe en la guía remitente). |
| `app/Services/Sunat/Transportista/UtilTransportistaService.php` | **Extiende** `App\Services\Sunat\UtilService`. Hereda sin cambios: `getSee()`, `getSeeApi()`, `getHashSign()`, `getResponse()`, `getErrorResponse()`, `readCdr()` (ver sección de endpoints abajo). Sobrescribe únicamente `getXmlSigned()` (usa `TransportistaXmlBuilder` en vez de `Greenter\Xml\Builder\DespatchBuilder`), `getReportHtml()` y `getReportPdf()` (usan `despatchTransportista.html.twig` en vez de `Greenter\Report\Resolver\DefaultTemplateResolver`, que no reconoce esta clase de documento y lanzaría `InvalidDocumentException`). |

### Fase 5 — HTTP (API + Web)

| Archivo | Qué hace |
|---|---|
| `app/Http/Requests/DespatchTransportistaRequest.php` | Validación API (`tipoDoc in:31`, `remitente.*` obligatorio, `envio.vehiculo`/`envio.choferes` siempre obligatorios, `documentoRelacionado.tipo exists:related_documents,id`). |
| `app/Http/Controllers/Api/DespatchTransportistaController.php` | `send()/xml()/pdf()` — mismo flujo que `Api\DespatchController` pero usando `DespatchTransportista` (modelo), `DocumentTransportistaService`, `UtilTransportistaService`. |
| `app/Http/Controllers/DespatchTransportistaController.php` | Shell web `index()/create()` (mismo patrón que `DespatchController`), agrega `related_documents` al `create()`. |
| `app/Livewire/Forms/DespatchTransportistaForm.php` | Form object Livewire (mirror de `DespatchForm`). `sendDespatch()` reproduce el mismo flujo de envío (generar XML → firmar → guardar PDF → `sendXml` a la GRE API → `getStatus` con reintento por ticket si SUNAT devuelve código `98` "en proceso"). |

### Fase 6 — UI (Livewire + rappasoft) y rutas

| Archivo | Qué hace |
|---|---|
| `app/Livewire/GenerateDespatchTransportista.php` | Orquestación del formulario (mirror de `GenerateDespatch`). Búsqueda de remitente reutiliza el buscador de `Client` (`api.clients.index`) y el modal `client-create` compartido (evento `clientAdded`, reinterpretado aquí como "remitente agregado"). |
| `app/Livewire/DespatchTransportistaTable.php` | Listado `rappasoft/laravel-livewire-tables` (mirror de `DespatchTable`): columnas F.Emisión, Comprobante, Remitente, PDF/XML/CDR/Sunat, Opciones. Reutiliza los partials genéricos `vouchers.partials.*` (no dependen del modelo). |
| `resources/views/livewire/generate-despatch-transportista.blade.php` | Formulario completo: comprobante, remitente, destinatario (opcional), envío, vehículo/choferes (siempre visibles), subcontratación + documento relacionado (opcionales, con toggles Alpine), productos. |
| `resources/views/despatch-transportistas/index.blade.php`, `create.blade.php`, `partials/action.blade.php`, `partials/modals.blade.php` | Vistas página/partials (mirror exacto de `despatchs/*`). |
| `routes/web.php` | `despatch-transportistas.index` / `.create`, middleware `can:despatch-transportistas.index` / `.create`. |
| `routes/api.php` | `despatch-transportista/send`, `/xml`, `/pdf` → nombres `api.despatch-transportista.*`. |
| `database/seeders/RoleSeeder.php` | Permisos `despatch-transportistas.index` / `.create`, asignados a los mismos roles que `despatches.*` (Super, Admin, Supervisor, Asistente, Facturación, Contabilidad). |
| `resources/views/vouchers/partials/sunatResponse.blade.php` | Se agregó el caso `'31' => 'Guía de remisión transportista'` al `match` existente (archivo compartido, edición mínima no disruptiva). |

---

## 4. Endpoints SUNAT (envío)

**No se agregaron URLs nuevas.** Se reutiliza tal cual `UtilService::getSeeApi()`
(heredado sin cambios por `UtilTransportistaService`):

| Entorno | Auth | CPE (envío) |
|---|---|---|
| Beta / pruebas | `https://gre-test.nubefact.com/v1` | `https://gre-test.nubefact.com/v1` |
| Producción | `https://api-seguridad.sunat.gob.pe/v1` | `https://api-cpe.sunat.gob.pe/v1` |

**Por qué son los mismos que la GRE-Remitente:** el servicio de recepción SUNAT
("GRE API" / `\Greenter\Api` de `greenter/lite`) es un **servicio REST genérico**
para "guía de remisión electrónica" (confirmado en el Anexo N.° 13,
"Aspectos técnicos del servicio de recepción", de la R.S. 000108-2026/SUNAT: recibe
un ZIP con el XML firmado + hash, sin distinguir tipoDoc en el contrato del servicio).
`Api::sendXml($nombre, $xml)` es agnóstico al tipo de documento — solo necesita el
nombre de archivo (`RUC-tipoDoc-serie-correlativo`) y el XML firmado. Las mismas
credenciales de empresa (`sol_user`/`sol_pass`, `client_id`/`client_secret`,
`certificate`, flag `production`) sirven para ambos tipos.

---

## 5. Campos XML: confirmados vs. asumidos

Dos fuentes de evidencia, en orden de fiabilidad:

1. **Un XML real de producción** (tipoDoc 31, empresa transportista ajena a este
   proyecto, aceptado/procesado por SUNAT) que el usuario proporcionó en
   `documentation/20213521447-31-V001-00014483.xml`. Es la evidencia más fuerte:
   es un documento real, no una lectura de anexo.
2. **Pruebas propias contra el ambiente beta SUNAT** (`gre-test.nubefact.com`) con
   el XML generado por este módulo — cada envío devuelve errores XSD reales de
   SUNAT que confirman o descartan la estructura campo por campo.
3. Anexo N.° 14 (b) "Estándar UBL 2.1 - Guía de remisión - Transportista" de la
   R.S. 000108-2026/SUNAT (el PDF oficial, con secciones truncadas `(…)`).

### ✅ Confirmados (XML real de producción + pruebas contra SUNAT beta)

- `cac:DeliveryCustomerParty` (destinatario) es **obligatorio en la secuencia**,
  aunque el campo de negocio sea opcional conceptualmente. Confirmado por rechazo
  real de SUNAT beta (código `0306`, XSD: *"Element 'Shipment' is not expected.
  Expected is (DeliveryCustomerParty)"*) al enviarlo vacío. **Corregido**: el
  template ya no lo condiciona (`app/Services/Sunat/Transportista/Templates/despatchTransportista.xml.twig`), y `destinatario` pasó a ser **obligatorio** en
  `DespatchTransportistaForm`/`DespatchTransportistaRequest`/`DocumentTransportistaService`.
- `cac:Shipment/cac:ShipmentStage/cac:LoadingTransportEvent` **NO existe** en la
  GRE-Transportista (se había copiado por error de `despatch2022.xml.twig`,
  donde sí aplica a la GRE-Remitente). Confirmado por: (a) ausencia total en el
  XML real de producción, y (b) rechazo real de SUNAT beta (código `0306`, XSD:
  *"Element 'LoadingTransportEvent' is not expected. Expected is one of
  (DriverPerson, ...)"*). **Eliminado del template.**
- `cbc:HandlingCode`/`cbc:HandlingInstructions` (motivo de traslado) **NO
  existen** en la GRE-Transportista — ausentes en el XML real de producción.
  Eran una suposición por analogía con la GRE-Remitente; **se retiraron del
  template** (el campo "Motivo de traslado" se sigue capturando en el formulario
  por completitud interna, pero ya no se envía a SUNAT en este documento).
- `cbc:TransportModeCode` **NO existe** en la GRE-Transportista — ausente en el
  XML real de producción. Era una suposición (`"01"` fijo); **se retiró del
  template**.
- `cac:Shipment/cac:ShipmentStage/cac:CarrierParty/cac:PartyLegalEntity/cbc:CompanyID`
  — **N.° de inscripción MTC/RNT propio del transportista emisor**, ausente por
  completo en la implementación original. Confirmado y **agregado** a partir del
  XML real de producción (`<cac:CarrierParty><cac:PartyLegalEntity><cbc:CompanyID>200037CNG</cbc:CompanyID></cac:PartyLegalEntity></cac:CarrierParty>`,
  sin `PartyIdentification` ni `RegistrationName`, a diferencia del `CarrierParty`
  embebido en la GRE-Remitente). Se agregó el campo `envio.mtc` (obligatorio) al
  formulario, Request y `DocumentTransportistaService` (reutiliza el sub-objeto
  `Transportist` de Greenter solo para `nroMtc`).
- `cac:Shipment/cac:Delivery/cac:Despatch/cac:DespatchParty` (remitente) —
  estructura y tags idénticos entre el Anexo 14-b, el XML real de producción y
  la implementación. Sin cambios.
- `cac:TransportHandlingUnit/cac:TransportEquipment` (vehículo/choferes) —
  estructura y tags idénticos entre las tres fuentes. Sin cambios.
- Catálogo 61 (`documentos relacionados`) con código `31` = "Guía de Remisión
  Transportista", `92` = Cita/Orden Terminal Portuario, `93-95` = ZOFRATACNA/ZED
  (nuevos en 2026).

### ✅ Envío end-to-end exitoso confirmado (SUNAT beta)

Guía **V001-3** (sin subcontratador), emitida y enviada tras corregir los dos
errores XSD reales de esta sección, fue **ACEPTADA** por el ambiente beta SUNAT
(`gre-test.nubefact.com`): estado `ACEPTADO`, código `0`, sin observaciones de
rechazo (solo el aviso estándar "CDR de prueba" propio del ambiente beta). Esto
confirma de punta a punta: `DeliveryCustomerParty` obligatorio,
`LoadingTransportEvent` eliminado, ausencia de `HandlingCode`/
`TransportModeCode`, y el orden `TransitPeriod → CarrierParty → DriverPerson`
dentro de `ShipmentStage`.

### ⚠️ Aún no confirmado contra SUNAT

- `cac:Consignment/cac:LogisticsOperatorParty` (subcontratador) — solo basado en
  Anexo 14-b, sin contraste contra un XML real ni contra SUNAT beta (la guía
  V001-3 aceptada no usó subcontratación). Recomendado: repetir una prueba con
  "El transportista subcontrata a otra empresa" activado para confirmar esta
  sección antes de darla por definitiva.

### Novedad de la R.S. 108-2026 no implementada (fuera de alcance)

El Anexo 12 (GRE-Remitente) ahora exige, cuando el remitente reemplaza la
obligación del transportista, consignar la **fecha de entrega de los bienes al
transportista** (campo 39-A, tag
`cac:Shipment/cac:ShipmentStage/cac:LoadingTransportEvent/cbc:OccurrenceDate`
— nótese que este tag SÍ existe en la GRE-Remitente, es exclusivamente en la
GRE-Transportista donde no aplica). `DocumentService`/`despatch2022.xml.twig`
(flujo remitente, **no tocado por este trabajo**) sigue escribiendo
`"now"|date('Y-m-d')` fijo ahí en vez de un campo real del formulario. Pendiente
relacionado, fuera de alcance de este módulo.

### Nota sobre la inestabilidad observada durante las pruebas

Durante las pruebas en navegador, el formulario se reinició por completo varias
veces sin intervención del usuario. La causa identificada en la consola del
navegador fue **`[vite] server connection lost. Polling for restart...`**
seguido de reconexión — el cliente HMR de Vite fuerza un full-reload de la
página al reconectar. **No es un defecto del código de este módulo.** Si vuelve
a ocurrir, verificar que `npm run dev` esté corriendo de forma estable antes de
repetir las pruebas.

---

## 6. Cómo probar

1. Ejecutar migraciones nuevas: `php artisan migrate` (crea `despatch_transportistas`,
   `related_documents`, y aplica el retrofit de documento `31` + serie `V001` a
   sucursales existentes).
2. Si es una base de datos nueva: `php artisan db:seed` ya incluye todo
   (`DocumentSeeder`, `RelatedDocumentSeeder`, `CompanySeeder` con la serie `V001`).
3. **Permisos en bases de datos existentes:** `RoleSeeder` no es re-ejecutable
   (Spatie `Permission::create` falla si el nombre ya existe). En un entorno ya
   sembrado, crear los 2 permisos nuevos manualmente (tinker o script), p. ej.:
   ```php
   $p1 = \Spatie\Permission\Models\Permission::create(['name' => 'despatch-transportistas.index']);
   $p2 = \Spatie\Permission\Models\Permission::create(['name' => 'despatch-transportistas.create']);
   $p1->assignRole(['Super','Admin','Supervisor','Asistente','Facturacion','Contabilidad']);
   $p2->assignRole(['Super','Admin','Supervisor','Asistente','Facturacion','Contabilidad']);
   ```
4. Navegar a `/despatch-transportistas/create`, con `Company.production = false`
   (usa las credenciales MODDATOS + certificado de pruebas + `gre-test.nubefact.com`,
   igual que la guía remitente).
5. Revisar el XML generado (`vouchers.partials.xml` → descarga) contra el Anexo
   14-b antes de dar por buena cualquier respuesta de SUNAT beta.

---

## 7. Mantenimiento futuro — dónde tocar qué

| Si necesitas... | Toca... |
|---|---|
| Cambiar un tag XML de la GRE-Transportista | `app/Services/Sunat/Transportista/Templates/despatchTransportista.xml.twig` |
| Agregar un campo nuevo al formulario | `DespatchTransportistaForm` (Livewire Form) + `DespatchTransportistaRequest` (API) + `DocumentTransportistaService::getDespatch()`/`getEnvio()` (mapeo a Greenter) + el template XML/PDF |
| Cambiar cómo se envía a SUNAT (URLs, credenciales) | `App\Services\Sunat\UtilService::getSeeApi()` — es **compartido** con la guía remitente, cualquier cambio ahí afecta a ambos tipos de documento |
| Agregar un nuevo código al catálogo 61 (documento relacionado) | `database/seeders/RelatedDocumentSeeder.php` + migración de retrofit similar a `2026_07_24_100100_*` |
| Revisar la representación impresa (PDF) | `public/custom/despatchTransportista.html.twig` |
| Ver qué campos son "asumidos" y deben validarse contra SUNAT real | Sección 5 de este documento |

## 8. Resumen ejecutivo de servicios nuevos (para localizar rápido)

- `App\Models\DespatchTransportista` — Eloquent, tabla `despatch_transportistas`.
- `App\Models\RelatedDocument` — Eloquent, tabla `related_documents` (catálogo 61).
- `App\Observers\DespatchTransportistaObserver`
- `App\Services\Sunat\Transportista\Model\DespatchTransportista` — modelo Greenter-compatible propio.
- `App\Services\Sunat\Transportista\Xml\TransportistaXmlBuilder` — builder XML Twig propio.
- `App\Services\Sunat\Transportista\DocumentTransportistaService` — mapeo array → modelo Greenter.
- `App\Services\Sunat\Transportista\UtilTransportistaService` — extiende `UtilService`; firma/envío/PDF propios, resto heredado.
- `App\Http\Requests\DespatchTransportistaRequest`
- `App\Http\Controllers\Api\DespatchTransportistaController` — `send/xml/pdf`.
- `App\Http\Controllers\DespatchTransportistaController` — `index/create` (shell web).
- `App\Livewire\Forms\DespatchTransportistaForm`
- `App\Livewire\GenerateDespatchTransportista`
- `App\Livewire\DespatchTransportistaTable`
