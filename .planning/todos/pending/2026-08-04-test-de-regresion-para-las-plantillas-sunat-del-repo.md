---
created: 2026-08-04
title: Test de regresion para las plantillas SUNAT del repo
area: sunat
files:
  - app/Services/Sunat/Templates/
  - app/Services/Sunat/Xml/SunatXmlBuilder.php
  - app/Services/Sunat/Templates/pdf/
  - tests/
---

## Problem

Tras la migracion (ver `.planning/todos/completed/2026-08-04-migrar-generacion-sunat-*`)
las plantillas de XML y de reporte viven en el repo. Eso resolvio el problema de
que `composer install` las borrara, pero abre uno nuevo: **ahora son codigo del
proyecto y nadie las verifica**.

Romper una plantilla es especialmente silencioso. El caso que ya ocurrio en
produccion lo demuestra: el nodo `<cac:LoadingTransportEvent>` desaparecio del
XML de la guia de remision remitente y **no se noto**, porque ese nodo no
aparece en el PDF. La guia se emitia, el PDF se veia bien, y solo el XML que
recibe SUNAT estaba incompleto.

El guardrail que se habia planteado al principio (descargar las fuentes pristine
de los paquetes greenter pinneados en `composer.lock` y fallar si algun archivo
de `vendor/greenter` difiere) **quedo obsoleto**: ya no hay parches en vendor
que proteger, porque todo lo que se modificaba vive ahora en el repo.

## Solution

Un test de regresion sobre las plantillas propias, que corra en la suite normal:

- Para cada tipo de documento (factura, boleta, nota de credito, nota de debito,
  guia remitente, anulacion, resumen): construir el documento con
  `DocumentService`, generar el XML con `SunatXmlBuilder` y exigir
  - que `DOMDocument::loadXML()` lo acepte,
  - que contenga los nodos criticos, empezando por exactamente **un**
    `<cac:LoadingTransportEvent>` en el namespace CAC para la guia remitente.
- Para los reportes: que cada plantilla que pide `DefaultTemplateResolver`
  resuelva desde `app/Services/Sunat/Templates/pdf` y no desde vendor, y que una factura en
  moneda extranjera con detraccion imprima el monto en `S/`.

Los scripts usados durante la migracion sirven de base directa; estan en el
scratchpad de la sesion del 2026-08-03 y cubren ya equivalencia byte a byte,
smoke del flujo firmado, independencia de vendor por inyeccion de marcador y
render de reportes.

Notas de implementacion:

- No hay `phpunit.xml` con sqlite en memoria habilitado (esas lineas estan
  comentadas), asi que el test corre contra la `DB_CONNECTION` configurada.
  Conviene construir los documentos con fixtures y no depender de que existan
  comprobantes reales en la base.
- No debe firmar ni enviar nada: solo construir el XML en memoria.
