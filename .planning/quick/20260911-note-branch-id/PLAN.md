---
type: quick
slug: note-branch-id
created: 2026-09-11
status: planned
---

# Quick: la nota de crédito/débito no guarda `branch_id`

En producción, al emitir una nota de crédito (`07`) el insert en `invoices` revienta:

```
SQLSTATE[HY000]: General error: 1364 Field 'branch_id' doesn't have a default value
```

## Causa

`invoices.branch_id` es `foreignId()->constrained()` — NOT NULL y sin default
(`database/migrations/2024_10_29_224224_create_invoices_table.php:110`).

Todos los demás emisores llenan la columna:

- `app/Livewire/GenerateInvoice.php:149` → `$this->invoice->branch_id = session('branch')->id;`
- `app/Livewire/Forms/VoidedForm.php:59`
- `app/Livewire/GenerateDespatch.php:104`
- `app/Livewire/GenerateDespatchTransportista.php:58`

`NoteForm` es el único que **ni declara la propiedad ni la asigna**, así que
`Invoice::create($this->all())` nunca manda `branch_id`. La nota se pierde antes
de llegar a SUNAT: no queda registro del comprobante ni correlativo consumido.

## Tareas

### 1. `app/Livewire/Forms/NoteForm.php`

- Declarar `public $branch_id;` junto a `$company_id` / `$production`, mismo orden
  que `InvoiceForm.php:99-101`.

### 2. `app/Livewire/GenerateNote.php`

- En `mount()`, asignar `$this->note->branch_id = session('branch')->id;` a
  continuación de `company_id`, replicando `GenerateInvoice::mount()`.

## Fuera de alcance

- `Api\NoteController::send()` y `Api\InvoiceController::send()` tampoco setean
  `branch_id` (dependen de que el request lo traiga, y `InvoiceRequest` no lo
  valida). Mismo bug de raíz, superficie distinta (JWT/Sanctum, sin sesión) —
  se reporta, no se toca aquí.

## Verificación

- La nota se crea con el `branch_id` de la sucursal en sesión y continúa al
  envío SUNAT sin excepción de BD.
