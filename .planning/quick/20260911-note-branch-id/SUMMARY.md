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

## Pendiente (fuera de alcance, reportado)

`Api\NoteController::send()` y `Api\InvoiceController::send()` construyen el
`Invoice::create($data)` solo con lo que trae el request más `company_id` y
`production`. `InvoiceRequest` no valida ni exige `branch_id`, y esos endpoints
corren sin sesión (Sanctum/JWT), así que un cliente externo que no mande la
columna choca con el mismo error 1364. Requiere decidir de dónde sale la
sucursal en esa superficie (campo del request, sucursal por defecto de la
empresa, o columna nullable) — no se tocó aquí.
