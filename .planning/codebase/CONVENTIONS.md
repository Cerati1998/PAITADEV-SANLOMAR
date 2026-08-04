# Coding Conventions

**Analysis Date:** 2026-07-24

## Naming Patterns

**Files:**
- Controllers: PascalCase, suffix with `Controller` (e.g., `ClientController.php`, `InspectionController.php`)
  - Location: `app/Http/Controllers/` with subdirectories for organization (e.g., `operations/`, `Admin/`, `Api/`)
- Models: PascalCase, singular noun (e.g., `Client.php`, `ContainerOperationalTrace.php`)
  - Location: `app/Models/` with subdirectories for related domains (e.g., `inspections/`, `works/`)
- Livewire Components: PascalCase with descriptive names (e.g., `ClientCreate.php`, `ClientTable.php`, `ContainerTraceTable.php`)
  - Location: `app/Livewire/` with subdirectories for organization (e.g., `Operations/`, `Branches/`)
- Services: PascalCase, suffix with `Service` (e.g., `SunatService.php`, `EirPhotoService.php`, `ContainerStatusService.php`)
  - Location: `app/Services/` with subdirectories for domains (e.g., `Sunat/`, `Container/`, `inspections/`, `WorkOrders/`)
- Form Objects: PascalCase, suffix with `Form` (e.g., `ClientForm.php`, `WorkOrderForm.php`, `InvoiceForm.php`)
  - Location: `app/Livewire/Forms/`
- Database Factories: PascalCase, suffix with `Factory` (e.g., `UserFactory.php`, `ClientFactory.php`)
  - Location: `database/factories/` with subdirectories mirroring Models
- Migrations: snake_case with timestamp prefix (Laravel default)

**Functions/Methods:**
- Use snake_case for public methods and action methods (e.g., `uploadHeaderPhoto()`, `searchDocument()`, `create_eir()`)
- Use camelCase for internal/private helper methods (e.g., `request()`, `searchClients()`)
- Controller action methods follow RESTful conventions: `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
- Livewire lifecycle methods: `render()`, `mount()`, `updated*` listeners
- Service methods are typically task-specific: `consultarRUC()`, `consultarDNI()`, `uploadPhoto()`, `deletePhoto()`

**Variables:**
- Local variables and properties use camelCase (e.g., `$identities`, `$openModal`, `$client`, `$rznSocial`)
- Database column names use camelCase for domain-specific fields matching business terminology (e.g., `tipoDoc`, `numDoc`, `rznSocial`, `direccion`)
- Boolean properties/variables use descriptive names without `is` prefix (e.g., `$openModal`, `$quoted_repair`)
- Collection variables use plural nouns (e.g., `$identities`, `$catalogs`, `$locations`)

**Types/Classes:**
- Models (Eloquent): PascalCase singular (e.g., `Client`, `ContainerOperationalTrace`, `EirInspection`)
- Enums: PascalCase (if used)
- Interfaces: PascalCase with `Interface` suffix (if used)
- Traits: PascalCase with `Trait` suffix (if used)

## Code Style

**Formatting:**
- Tool: Laravel Pint 1.13 (PSR-12 standard)
- PSR-12 compliance enforced via `vendor/bin/pint`
- Indentation: 4 spaces
- Line length: No explicit limit enforced; practical limit ~120 characters
- Bracket style: Allman style for control structures (opening brace on same line)

**Linting:**
- Laravel Pint provides automatic code styling
- Run `vendor/bin/pint` before committing code
- No explicit ESLint/Prettier for Frontend (JavaScript/Tailwind CSS) — Vite-only build system

## Import Organization

**Order (PHP):**
1. Namespace declaration
2. Blank line
3. `use` statements grouped as:
   - Laravel/Illuminate facades and classes
   - Third-party packages (Livewire, Spatie, etc.)
   - Application models and services
   - Local imports (same namespace)
4. Blank line before class declaration

**Example from `ClientCreate.php`:**
```php
<?php

namespace App\Livewire;

use App\Models\Client;
use App\Services\SunatService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
```

**Path Aliases:**
- No custom aliases defined (standard Laravel PSR-4 autoloading)
- Use full namespace paths: `App\Models\Client`, `App\Services\SunatService`

## Error Handling

**Patterns:**
- Try-catch blocks used for external API calls and file operations
- Catches are specific (e.g., `catch (\Exception $e)`, `catch (\Throwable $e)`, `catch (\RuntimeException $e)`)
- Exceptions are re-thrown or converted to user-friendly messages
- Livewire components dispatch `swal` events for user-facing error notifications:
  ```php
  $this->dispatch('swal', [
      'icon' => 'error',
      'title' => 'Error',
      'text'  => $e->getMessage(),
  ]);
  ```
- Controllers return JSON responses for API errors
- Request validation errors handled via Laravel's `validate()` method (automatic 422 response)
- Custom exception messages provide context (e.g., "Tipo de documento no válido", "Error al consultar API")

## Logging

**Framework:** Laravel's Monolog integration via `Illuminate\Support\Facades\Log`

**Patterns:**
- Log levels used: `info()`, `warning()`, `error()`
- Typical usage in commands and background operations:
  ```php
  Log::info('Sincronización de despachos completada', [
      'count' => $count,
      'timestamp' => now(),
  ]);
  Log::error('Error en SyncDispatchedContainers', [
      'exception' => $e->getMessage(),
  ]);
  ```
- Logs in controller/Livewire operations for debugging file uploads and external API calls
- Context arrays include relevant data for debugging (IDs, messages, counts)
- Configuration in `config/logging.php` with default `stack` channel

## Comments

**When to Comment:**
- Inline comments explain complex business logic (e.g., validation rules for RUC/DNI formats)
- Comments in Spanish for domain-specific logic (matches business terminology)
- Commented-out code preserved for reference (e.g., alternate validation paths)
- No comments for obvious code (naming should be self-documenting)

**JSDoc/TSDoc:**
- PHPDoc comments used for controller action methods and public API methods
- Standard Laravel convention: document parameters and return types
- Example from `InspectionController.php`:
  ```php
  /**
   * Subir foto de cabecera (posiciones 1-4)
   */
  public function uploadHeaderPhoto(Request $request, EirPhotoService $photoService)
  ```

## Function Design

**Size:** 
- Methods typically 20-50 lines; larger methods delegate to services
- Services encapsulate complex business logic (e.g., `EirPhotoService`, `ContainerStatusService`)

**Parameters:** 
- Type-hint all parameters (PHP 8.2+ strict types)
- Use dependency injection for services (e.g., `EirPhotoService $photoService`)
- Livewire form properties use `#[Validate('...')]` attributes for validation

**Return Values:** 
- Explicit return types (e.g., `function definition(): array`)
- Controllers typically return View, redirect, or JSON response
- Services return arrays or models
- Livewire components return `view()` from `render()` method

## Module Design

**Exports:**
- Models use `$fillable` arrays to define mass-assignable attributes
- Controllers use route model binding (implicit injection of Eloquent models)
- Services are singletons or instantiated via service container

**Barrel Files:**
- No barrel/index files used; direct imports of classes
- Each file exports one primary class

## Structural Patterns

**Livewire Components with Forms:**
- Component class defines public properties for form state and UI state
- Form object (e.g., `WorkOrderForm`) extends `Livewire\Form` with `#[Validate()]` attributes
- Component calls `$this->form->validate()` before data operations
- Events dispatched via `$this->dispatch()` for parent/sibling communication

**Query Builder Fluent Interface:**
- Queries use method chaining with conditional builders:
  ```php
  Client::query()
    ->select(DB::raw('id,rznSocial'))
    ->when(
        $request->search,
        fn(Builder $query) => $query->where('rznSocial', 'like', "%{$request->search}%")
    )
    ->get();
  ```

**Trait Usage:**
- Models use `HasFactory` for Eloquent factory support
- Livewire components inherit `Component` base class; no trait-heavy design

**SUNAT templates — never edit `vendor/`:**
- `vendor/` is gitignored and every deploy runs `composer install`, so any change
  made there is silently destroyed on the next release. This already caused two
  production incidents (see CONCERNS.md).
- To change the XML sent to SUNAT: copy the template from
  `vendor/greenter/xml/src/Xml/Templates/` into `app/Services/Sunat/Templates/`
  and edit the copy. `SunatXmlBuilder` chains the loader `[repo, vendor]`, so the
  repo copy wins and untouched templates keep resolving from the package.
- To change a PDF/RIDE: same idea, from
  `vendor/greenter/report/src/Report/Templates/` into `app/Services/Sunat/Templates/pdf/`.
- Keep templates under `app/`, never under `public/`. They lived in
  `public/custom/pdf/` until 2026-08-04, inside the docroot, where the server
  served the raw `.twig` to anyone who requested its URL.
- Data that does not exist on the Greenter model (e.g. `tipoCambio`, absent from
  `BaseSale`) is passed through report params and read as
  `params.user.<field>` — do not add setters to vendor models.
- After changing an XML template, diff the generated output against the previous
  builder for real vouchers before shipping: this is what SUNAT receives.

---

*Convention analysis: 2026-07-24*
