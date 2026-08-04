---
created: 2026-08-04T00:00:07.930Z
completed: 2026-08-04
status: complete
title: Migrar generacion SUNAT de invoices y despatches a control local versionable
area: sunat
commits:
  - 88de853  # regenerar el PDF cuando pdf_path es NULL
  - b187645  # invoice PDF deja de depender del vendor parcheado
  - 90912af  # Fase 1: guia remitente (XML) + CSS del logo
  - 689b26e  # el PDF regenerado conserva el nombre de emision
  - 549463f  # Fase 2: todo el XML sale de plantillas del repo
  - b700664  # Fase 3: plantillas de reporte unificadas + quita DIAG-4068
files:
  - app/Services/Sunat/Xml/SunatXmlBuilder.php
  - app/Services/Sunat/Templates/
  - public/custom/pdf/
  - app/Services/Sunat/UtilService.php
  - app/Services/Sunat/DocumentService.php
  - app/Traits/Sunat/UtilTrait.php
  - app/Services/Sunat/Transportista/UtilTransportistaService.php
  - app/Livewire/VoucherTable.php
  - app/Http/Controllers/Api/VoidedController.php
  - app/Http/Controllers/Api/SummaryController.php
---

## Resultado

**Cerrado el 2026-08-04.** Validado por el usuario en el VPS emitiendo factura y
guia de remision **contra SUNAT beta** (no produccion oficial): ambas correctas.

Greenter queda reducido a modelos, firma, envio y lectura de CDR. Ningun builder
de Greenter se instancia ya en `app/`, y ninguna salida a SUNAT o al cliente
depende de un archivo de `vendor/`.

Documentado en `CLAUDE.md`, `.planning/codebase/ARCHITECTURE.md`,
`CONVENTIONS.md`, `CONCERNS.md` y `STRUCTURE.md`.

**Correccion posterior (2026-08-04):** las plantillas de reporte quedaron primero
en `public/custom/pdf/`, que esta dentro del docroot: el servidor entregaba el
`.twig` en crudo a cualquiera que pidiera su URL, dejando a la vista la
estructura del RIDE y la logica de detracciones. Se movieron a
`app/Services/Sunat/Templates/pdf/` y `public/custom/` desaparecio. Comprobado
que la URL responde 404. Las plantillas XML nunca estuvieron expuestas porque ya
vivian bajo `app/`. Commit `a1e296c`, desplegado y verificado en el VPS.

## Estado final

Cerrado y validado en produccion. Ninguna salida a SUNAT o al cliente depende ya
de un archivo de `vendor/`, y ninguna plantilla es accesible desde fuera.

| Que | Donde |
|-----|-------|
| XML de factura, boleta, notas, guia remitente, anulacion, resumen | `app/Services/Sunat/Templates/` |
| XML de guia transportista | `app/Services/Sunat/Transportista/Templates/` |
| Builder de XML | `app/Services/Sunat/Xml/SunatXmlBuilder.php` |
| PDF/RIDE de todos los tipos + `assets/style.css` | `app/Services/Sunat/Templates/pdf/` |

De Greenter se usan modelos, firma, envio y lectura de CDR. Nada mas.

### Seguimiento abierto

Test de regresion sobre las plantillas propias (XML bien formado + nodos
criticos, sobre todo `<cac:LoadingTransportEvent>`). Ver "Guardrail transversal"
mas abajo: el guardrail planteado originalmente quedo obsoleto con la migracion.

## Problem

Todo lo que el sistema le entrega a SUNAT (XML firmado) y al cliente (PDF/RIDE)
se genera hoy con plantillas y modelos que viven dentro de `vendor/greenter`.
`vendor/` esta en `.gitignore` y durante mucho tiempo se subio **a mano y ya
parcheado** al VPS — lo dice el propio comentario en `deploy.php`: *"vendor/
subido manualmente, nunca paso por un composer install real"*.

El primer deploy real con Deployer corrio `composer install` y reemplazo ese
vendor por el pristine de packagist, borrando los parches sin aviso. Resultado:
dos incidentes en produccion el mismo dia.

1. `Call to undefined method Greenter\Model\Sale\Invoice::setTipoCambio()` —
   rompia **toda** emision de factura y nota, no solo el PDF.
2. La detraccion salio impresa en USD en 4 facturas, cuando el monto siempre es
   en soles.

### Auditoria hecha (2026-08-03)

Se clonaron las fuentes pristine de `greenter/core@v5.1.1`,
`greenter/report@v5.1.1` y `greenter/xml@v5.1.1` y se diffearon contra el
vendor local (que todavia conserva los parches) ignorando CRLF. De ~100
archivos, **exactamente 4 estaban parcheados a mano**:

| # | Archivo | Que hacia el parche | Estado |
|---|---------|---------------------|--------|
| 1 | `core/.../Sale/BaseSale.php` | agregaba la propiedad `tipoCambio` + getter/setter (NO existe en upstream) | resuelto en `b187645` |
| 2 | `report/.../Templates/invoice.html.twig` | detraccion con `S/` fijo en vez de `{{ moneda }}`, + fila "Tipo Cambio" | resuelto en `b187645` |
| 3 | `report/.../Templates/assets/style.css` | en `@media print`, logo `img{max-width:100%}` -> `90%` | resuelto en `90912af` |
| 4 | `xml/.../Templates/despatch2022.xml.twig` | agrega `<cac:LoadingTransportEvent><cbc:OccurrenceDate>` a la guia de remision remitente | resuelto en `90912af` |

El #4 es el grave: ese nodo va dentro del **XML que se envia a SUNAT** para la
guia de remision remitente (tipoDoc 09). Produccion lo perdio en el deploy, asi
que las guias que se emiten ahora salen sin la fecha de inicio de traslado.

El #3 solo cambia el tamano del logo en el PDF (cosmetico).

## Solution

Replicar el patron que **ya existe y funciona** para guia transportista, donde
nada critico depende de `vendor/`:

```
app/Services/Sunat/Transportista/
├── Model/DespatchTransportista.php            # modelo propio
├── Templates/despatchTransportista.xml.twig   # XML propio, versionado
├── Xml/TransportistaXmlBuilder.php            # builder propio
├── DocumentTransportistaService.php
└── UtilTransportistaService.php               # extiende UtilService
```

Greenter queda reducido a lo que si conviene reusar y es estable: firma
(`SignedXml`), envio (`See` / GRE API), lectura de CDR y los modelos de datos.

### Fase 1 — Parar la hemorragia (urgente, chica)

- Recuperar el parche #4 bajo control propio: plantilla `despatch2022.xml.twig`
  versionada + builder propio para guia remitente, espejo de transportista.
- Versionar `assets/style.css` en `public/custom/pdf/assets/` (parche #3).
  OJO: hoy `{% include 'assets/style.css' %}` cae por fallback a vendor; al
  crear `public/custom/pdf/assets/` pasa a ganar el propio.
- Verificacion: emitir una guia remitente en beta y diffear el XML generado
  contra uno emitido antes del deploy — debe reaparecer `LoadingTransportEvent`.

### Fase 2 — XML de facturas y notas bajo control  [HECHA]

- `App\Services\Sunat\Templates\` con `invoice2.1.xml.twig`, `notacr2.1`,
  `notadb2.1`, `voided`, `summary` (mas `despatch2022` de la Fase 1).
- En vez de un `InvoiceXmlBuilder` aparte se unifico todo en
  `App\Services\Sunat\Xml\SunatXmlBuilder`, que absorbe tambien al
  `DespatchXmlBuilder` de la Fase 1: los cinco builders de Greenter eran
  envoltorios finos sobre `TwigBuilder` que solo elegian nombre de plantilla.
- Ya no queda ningun builder de Greenter instanciado en `app/`.
- Verificacion cumplida: **40 documentos, diff byte a byte = 0** (24 facturas y
  boletas reales, 13 guias reales, 1 anulacion real, 2 notas fixture), mas smoke
  test del flujo firmado y prueba de independencia con marcador en vendor.

### Fase 3 — Reportes PDF completos  [HECHA]

- `public/custom/pdf/` queda como unico directorio de plantillas de reporte:
  `despatch`, `voided`, `summary`, `perception`, `retention`,
  `despatchTransportista`, `invoice2`, `invoice3` y `assets/style.css` se
  movieron ahi con `git mv`. Se elimino el `public/custom/invoice.html.twig`
  viejo, superado por el de `pdf/`.
- `getReportHtml()` y `getReportPdf()` unificados en los tres servicios
  (`UtilService`, `UtilTrait`, `UtilTransportistaService`): mismo directorio y
  mismo paso de `tipoCambio` por params.
- Dos divergencias adicionales encontradas al probar y corregidas:
  - las tres `getReportHtml()` leian `company->image_path`, columna que **no
    existe** en `companies` (la real es `logo_path`), asi que la vista HTML
    nunca mostraba el logo mientras el PDF si.
  - los endpoints `html` de la API de facturas y notas no pasaban `tipoCambio`.
- Verificacion: las 8 plantillas resuelven desde el repo, 0 referencias a
  `public_path('custom')`, render real de factura (detraccion en S/), guia y
  anulacion, y prueba de independencia con marcador en vendor.

### Guardrail transversal — replantear

El guardrail original (comparar `vendor/greenter` contra las fuentes pristine y
fallar si difiere) **pierde su sentido tras las fases 1-3**: ya no hay ningun
parche en vendor que proteger, porque todo lo que se modificaba vive ahora en el
repo. Lo que si aporta hoy es un test de regresion sobre las plantillas propias:
que generen XML bien formado y conserven los nodos criticos (sobre todo
`<cac:LoadingTransportEvent>` en la guia remitente), para que romper una
plantilla del repo sea un build rojo y no un descubrimiento en produccion.

Los scripts de verificacion usados en las tres fases sirven de base directa.

### Alternativa considerada y descartada

`cweagans/composer-patches`: reaplicaria los parches en cada `composer install`.
Se descarta porque mantiene el acoplamiento a internals de vendor, se rompe en
cada upgrade y no es el estilo ya establecido en el repo (transportista).
