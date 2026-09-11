---
type: quick-summary
slug: note-branch-id
completed: 2026-09-11
status: complete
---

# Summary: la nota de crédito/débito no guardaba `branch_id`

## Qué pasaba

Emitir una nota de crédito en producción moría en el `Invoice::create()`:

```
SQLSTATE[HY000]: General error: 1364 Field 'branch_id' doesn't have a default value
```

El insert listado en el log no incluye `branch_id` en ninguna parte. La nota
nunca se persistía y, por lo tanto, nunca se enviaba a SUNAT: el usuario perdía
todo el formulario y no quedaba rastro del intento.

## Causa

`invoices.branch_id` se declaró como `foreignId()->constrained()`
(`2024_10_29_224224_create_invoices_table.php:110`): NOT NULL, sin default.

Cada emisor llena la columna en el `mount()` de su componente:

| Emisor | Dónde |
|--------|-------|
| Factura / boleta | `GenerateInvoice.php:149` |
| Comunicación de baja | `Forms/VoidedForm.php:59` |
| Guía remitente | `GenerateDespatch.php:104` |
| Guía transportista | `GenerateDespatchTransportista.php:58` |
| **Nota de crédito / débito** | **faltaba** |

`NoteForm` era el único que no declaraba la propiedad, así que
`Invoice::create($this->all())` jamás la enviaba.

## Cambios

- **`app/Livewire/Forms/NoteForm.php`** — `public $branch_id;` junto a
  `$company_id` / `$production`, mismo orden que `InvoiceForm`.
- **`app/Livewire/GenerateNote.php`** — `mount()` asigna
  `$this->note->branch_id = session('branch')->id;` justo después de
  `company_id`.

Commit: `74a5c30`

## No se cierra: misma falla en la API, descartada

`Api\NoteController::send()`, `Api\InvoiceController::send()`,
`Api\DespatchController::send()` y `Api\DespatchTransportistaController::send()`
crean su comprobante solo con lo que trae el request más `company_id` y
`production`, sin `branch_id`, y ninguno de sus FormRequest lo exige. Mismo
error 1364 si un cliente externo no lo manda.

**No se corrigió por decisión del usuario: esa superficie API está inutilizada
actualmente.** Si algún día se reactiva, hay que resolver de dónde sale la
sucursal ahí (no hay `session('branch')`: el token de Sanctum autentica a una
`Company`) — la opción evaluada era deducirla de la serie vía
`branch_company_document`, con `branch_id` explícito del request por encima.
