<!-- GSD:project-start source:PROJECT.md -->

## Project

**INSPECTEC SERVICES / SANLOMAR**

Mini-ERP en Laravel 11 para empresas de logística que operan en terminales de
contenedores (unidades menores/terceras) en Perú. Cubre el ciclo de vida del
contenedor (descarga, devolución, trazas), inspecciones EIR/PTI/leak-test,
órdenes de trabajo, despachos y facturación electrónica ante SUNAT. Lo usan los
operadores de patio y el área administrativa de la empresa.

**Core Value:** Todo comprobante que sale hacia SUNAT — y toda respuesta que SUNAT devuelve —
tiene que quedar registrado fielmente en el sistema. Si el estado que muestra el
sistema no coincide con el que SUNAT tiene, el resto del ERP deja de ser
confiable para contabilidad.

### Constraints

- **Tech stack**: Laravel 11 + Livewire 3 + `rappasoft/laravel-livewire-tables`, Jetstream, WireUI, Tailwind — el fix debe encajar en el patrón Controller (shell) + Livewire (interacción) + Form object ya establecido
- **Dependencias**: Greenter se usa solo para modelos, firma (`SignedXml`), envío y lectura de CDR — no se le agregan propiedades ni setters a sus modelos, ni se edita nada bajo `vendor/`
- **Compatibilidad**: la comunicación de baja viaja por SOAP (`See`), no por la API REST GRE que usan las guías — el manejo de errores debe cubrir tanto el código `98` como el `SoapFault` de transporte
- **Multi-tenant**: todo acceso a datos se ancla en `session('company')` y `session('branch')`, no hay scope global — cualquier consulta nueva debe filtrar por ambos
- **Producción**: SUNAT es un tercero real; la verificación se hace contra el ambiente beta antes de tocar producción

<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->

## Technology Stack

## Languages

- PHP 8.2+ - Backend (Laravel 11 application)
- HTML/Blade - Server-side templates (views in `resources/views/`)
- JavaScript/TypeScript - Frontend interactivity (via Livewire components)
- CSS/Tailwind - Styling (Tailwind CSS 3.4.0 with forms and typography plugins)
- SQL - Database queries (migrations in `database/migrations/`)

## Runtime

- PHP 8.2+ (configured in `composer.json` as `^8.2`)
- Node.js/npm - Frontend build toolchain
- Composer - PHP dependencies
- npm - Node.js dependencies

## Frameworks

- Laravel 11.9+ - Web framework and ORM (`laravel/framework`)
- Livewire 3.0 - Real-time reactive components (`livewire/livewire`)
- Laravel Jetstream 5.2 - Authentication scaffolding + team management (`laravel/jetstream`)
- WireUI 2.1 - Tailwind UI component library (`wireui/wireui`)
- Tailwind CSS 3.4.0 - Utility-first CSS framework
- Tailwind Forms 0.5.7 - Form styling plugin
- Tailwind Typography 0.5.10 - Prose styling plugin
- Autoprefixer 10.4.16 - CSS vendor prefixes
- Vite 6.3.6 - Frontend build tool and dev server
- Laravel Vite Plugin 1.0 - Laravel integration for Vite
- PostCSS 8.4.32 - CSS transformations
- PHPUnit 11.0.1 - Unit and feature testing framework (`phpunit/phpunit`)
- Mockery 1.6 - Object mocking (`mockery/mockery`)
- Faker - Test data generation (`fakerphp/faker` 1.24)
- Laravel Sail 1.26 - Docker dev environment (`laravel/sail`)
- Collision 8.1 - Error formatting (`nunomaduro/collision`)
- Laravel Pint 1.13 - Code style fixer (`laravel/pint`, PSR-12 compliant)

## Key Dependencies

- Greenter (SUNAT e-invoicing) - Peruvian tax invoice/despatch generation
- Laravel Sanctum 4.0 - API token authentication and CSRF protection
- Tymon JWT Auth 2.1 - JWT token authentication for mobile/external APIs
- Spatie Laravel Permission 6.22 - Role-based access control
- Rappasoft Laravel Livewire Tables 3.4 - Data table component with Livewire
- barryvdh/laravel-dompdf 3.1 - PDF generation (cash box reports, inspection PDFs)
- Maatwebsite Excel 3.1 - Excel import/export (`maatwebsite/excel`)
- Simple QR Code 4.2 - QR code generation (`simplesoftwareio/simple-qrcode`)
- Numero a Letras 4.0 - Spanish number-to-words conversion
- Doctrine DBAL 4.3 - Database abstraction (`doctrine/dbal`)
- League Flysystem AWS S3 v3 - S3/DigitalOcean Spaces adapter (`league/flysystem-aws-s3-v3`)
- Guzzle HTTP (implicit via Laravel) - HTTP client for external APIs
- Axios 1.10.0 - HTTP client (in `devDependencies` but used in production)
- Browser Image Compression 2.0.2 - Client-side image compression
- Luxon 3.7.2 - Date/time manipulation
- Laravel Lang 6.1 - Multilingual support
- Tinker 2.9 - Interactive shell (`laravel/tinker`)

## Configuration

- `.env` file (present) - Application environment variables
- `.env.example` (present) - Template for environment setup
- `config/app.php` - Application settings
- `config/database.php` - Database connection configuration (SQLite, MySQL, MariaDB, PostgreSQL, SQL Server)
- `config/filesystems.php` - Storage disk configuration (local, public, DigitalOcean Spaces via S3)
- `config/mail.php` - Mail driver configuration
- `config/jwt.php` - JWT token settings
- `config/permission.php` - Spatie permission configuration
- `config/jetstream.php` - Jetstream features
- `vite.config.js` (implicit) - Vite build configuration
- `.prettierrc.json` (if present) - Code formatter configuration
- `.eslintrc.*` (if present) - JavaScript linter configuration

## Platform Requirements

- PHP 8.2+ CLI
- Composer (PHP dependency manager)
- Node.js 16+ (for Vite and npm)
- npm 7+ (frontend package manager)
- SQLite or MySQL/MariaDB database
- wkhtmltopdf binary (for SUNAT invoice PDF generation) — configured via `WKHTMLTOPDF_BINARIES` env var
- PHP 8.2+ with FPM or similar
- MySQL 5.7+, MariaDB 10.3+, or PostgreSQL 9.6+
- Node.js (for frontend builds, or pre-build assets)
- DigitalOcean Spaces account (for file storage)
- wkhtmltopdf binary installed on server
- SUNAT certificates and credentials (stored on Company model)

<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->

## Conventions

## Naming Patterns

- Controllers: PascalCase, suffix with `Controller` (e.g., `ClientController.php`, `InspectionController.php`)
- Models: PascalCase, singular noun (e.g., `Client.php`, `ContainerOperationalTrace.php`)
- Livewire Components: PascalCase with descriptive names (e.g., `ClientCreate.php`, `ClientTable.php`, `ContainerTraceTable.php`)
- Services: PascalCase, suffix with `Service` (e.g., `SunatService.php`, `EirPhotoService.php`, `ContainerStatusService.php`)
- Form Objects: PascalCase, suffix with `Form` (e.g., `ClientForm.php`, `WorkOrderForm.php`, `InvoiceForm.php`)
- Database Factories: PascalCase, suffix with `Factory` (e.g., `UserFactory.php`, `ClientFactory.php`)
- Migrations: snake_case with timestamp prefix (Laravel default)
- Use snake_case for public methods and action methods (e.g., `uploadHeaderPhoto()`, `searchDocument()`, `create_eir()`)
- Use camelCase for internal/private helper methods (e.g., `request()`, `searchClients()`)
- Controller action methods follow RESTful conventions: `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
- Livewire lifecycle methods: `render()`, `mount()`, `updated*` listeners
- Service methods are typically task-specific: `consultarRUC()`, `consultarDNI()`, `uploadPhoto()`, `deletePhoto()`
- Local variables and properties use camelCase (e.g., `$identities`, `$openModal`, `$client`, `$rznSocial`)
- Database column names use camelCase for domain-specific fields matching business terminology (e.g., `tipoDoc`, `numDoc`, `rznSocial`, `direccion`)
- Boolean properties/variables use descriptive names without `is` prefix (e.g., `$openModal`, `$quoted_repair`)
- Collection variables use plural nouns (e.g., `$identities`, `$catalogs`, `$locations`)
- Models (Eloquent): PascalCase singular (e.g., `Client`, `ContainerOperationalTrace`, `EirInspection`)
- Enums: PascalCase (if used)
- Interfaces: PascalCase with `Interface` suffix (if used)
- Traits: PascalCase with `Trait` suffix (if used)

## Code Style

- Tool: Laravel Pint 1.13 (PSR-12 standard)
- PSR-12 compliance enforced via `vendor/bin/pint`
- Indentation: 4 spaces
- Line length: No explicit limit enforced; practical limit ~120 characters
- Bracket style: Allman style for control structures (opening brace on same line)
- Laravel Pint provides automatic code styling
- Run `vendor/bin/pint` before committing code
- No explicit ESLint/Prettier for Frontend (JavaScript/Tailwind CSS) — Vite-only build system

## Import Organization

- No custom aliases defined (standard Laravel PSR-4 autoloading)
- Use full namespace paths: `App\Models\Client`, `App\Services\SunatService`

## Error Handling

- Try-catch blocks used for external API calls and file operations
- Catches are specific (e.g., `catch (\Exception $e)`, `catch (\Throwable $e)`, `catch (\RuntimeException $e)`)
- Exceptions are re-thrown or converted to user-friendly messages
- Livewire components dispatch `swal` events for user-facing error notifications:
- Controllers return JSON responses for API errors
- Request validation errors handled via Laravel's `validate()` method (automatic 422 response)
- Custom exception messages provide context (e.g., "Tipo de documento no válido", "Error al consultar API")

## Logging

- Log levels used: `info()`, `warning()`, `error()`
- Typical usage in commands and background operations:
- Logs in controller/Livewire operations for debugging file uploads and external API calls
- Context arrays include relevant data for debugging (IDs, messages, counts)
- Configuration in `config/logging.php` with default `stack` channel

## Comments

- Inline comments explain complex business logic (e.g., validation rules for RUC/DNI formats)
- Comments in Spanish for domain-specific logic (matches business terminology)
- Commented-out code preserved for reference (e.g., alternate validation paths)
- No comments for obvious code (naming should be self-documenting)
- PHPDoc comments used for controller action methods and public API methods
- Standard Laravel convention: document parameters and return types
- Example from `InspectionController.php`:

## Function Design

- Methods typically 20-50 lines; larger methods delegate to services
- Services encapsulate complex business logic (e.g., `EirPhotoService`, `ContainerStatusService`)
- Type-hint all parameters (PHP 8.2+ strict types)
- Use dependency injection for services (e.g., `EirPhotoService $photoService`)
- Livewire form properties use `#[Validate('...')]` attributes for validation
- Explicit return types (e.g., `function definition(): array`)
- Controllers typically return View, redirect, or JSON response
- Services return arrays or models
- Livewire components return `view()` from `render()` method

## Module Design

- Models use `$fillable` arrays to define mass-assignable attributes
- Controllers use route model binding (implicit injection of Eloquent models)
- Services are singletons or instantiated via service container
- No barrel/index files used; direct imports of classes
- Each file exports one primary class

## Structural Patterns

- Component class defines public properties for form state and UI state
- Form object (e.g., `WorkOrderForm`) extends `Livewire\Form` with `#[Validate()]` attributes
- Component calls `$this->form->validate()` before data operations
- Events dispatched via `$this->dispatch()` for parent/sibling communication
- Queries use method chaining with conditional builders:
- Models use `HasFactory` for Eloquent factory support
- Livewire components inherit `Component` base class; no trait-heavy design
- `vendor/` is gitignored and every deploy runs `composer install`, so any change
- To change the XML sent to SUNAT: copy the template from
- To change a PDF/RIDE: same idea, from
- Keep templates under `app/`, never under `public/`. They lived in
- Data that does not exist on the Greenter model (e.g. `tipoCambio`, absent from
- After changing an XML template, diff the generated output against the previous

<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->

## Architecture

## System Overview

```text

```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| **CheckCompanySelected** | Validates & injects company/branch into session on every request | `app/Http/Middleware/CheckCompanySelected.php` |
| **DashboardController** | Renders main dashboard with charts | `app/Http/Controllers/DashboardController.php` |
| **InspectionController** | Page shells for EIR/PTI/leak-test creation/editing + photo uploads | `app/Http/Controllers/operations/InspectionController.php` |
| **WorkOrderController** | Page shells for box/machine repair work orders + labor photos | `app/Http/Controllers/operations/WorkOrderController.php` |
| **DispatchController** | Page shell for dispatch management | `app/Http/Controllers/operations/DispatchController.php` |
| **VoucherController** | Invoice/note/despatch listing and PDF generation | `app/Http/Controllers/VoucherController.php` |
| **ReportController** | Export dispatches, inspections, invoices, work orders | `app/Http/Controllers/ReportController.php` |
| **Livewire Table Components** | Interactive sortable/filterable data tables (rappasoft/laravel-livewire-tables) | `app/Livewire/*Table.php` |
| **Livewire Form Components** | Modal forms for CRUD with Jetstream form objects | `app/Livewire/Operations/*/[Create\|Edit].php` |
| **ContainerOperationalTraceService** | Manages container lifecycle state transitions | `app/Services/Container/ContainerOperationalTraceService.php` |
| **ContainerStatusService** | Validates & calculates final container status from box/machine states | `app/Services/Container/ContainerStatusService.php` |
| **DocumentService** | Builds Greenter SUNAT document models for invoices/despatches/notes | `app/Services/Sunat/DocumentService.php` |
| **SunatXmlBuilder** | Renders the signed-XML payload for every document type from repo-owned Twig templates | `app/Services/Sunat/Xml/SunatXmlBuilder.php` |
| **UtilService** | Signs, submits and reads CDR; renders HTML/PDF reports | `app/Services/Sunat/UtilService.php` |
| **EirPhotoService** | Uploads/deletes EIR header & detail photos to DO Spaces | `app/Services/inspections/EirPhotoService.php` |
| **RoofEvidencePhotoService** | Manages roof evidence photos for damage tracking | `app/Services/inspections/RoofEvidencePhotoService.php` |
| **WorkLaborPhotoService** | Handles work order labor progress photos | `app/Services/WorkOrders/WorkLaborPhotoService.php` |

## Pattern Overview

- **Multi-tenant isolation:** Every route enforces `CheckCompanySelected` middleware which injects `company` and `branch` into the session. Business logic queries implicitly scoped via `session('company')` and `session('branch')`.
- **Session-based context over query params:** Rather than filtering by `?company_id=X&branch_id=Y`, the entire request context is derived from session state. This simplifies CRUD operations and cross-entity navigation.
- **Livewire-first interactive UI:** Complex tables and forms live in Livewire components; controllers mostly render Blade shells that mount the components.
- **Service layer for domain logic:** Business rules (container status transitions, e-invoice generation, photo storage) isolated from controllers in `app/Services/`.
- **API as secondary surface:** REST API in `routes/api.php` uses JWT auth and mirrors web functionality for mobile/external integrations—separate from session-based web routes.
- **Permission-based authorization:** Route-level gates via Spatie `laravel-permission` (e.g., `->middleware('can:inspections.eir.create')`), not ability closures.

## Layers

- Purpose: Accept HTTP requests and render responses (HTML, JSON, PDF)
- Location: `app/Http/Controllers/`, `app/Livewire/`, `resources/views/`
- Contains: Controllers, Livewire components, Blade templates
- Depends on: Models, Services, Middleware
- Used by: Browser clients (web) and mobile/external apps (API)
- Pattern: MVC for web (Blade views), JSON-RPC for API
- Purpose: Route requests, enforce auth & multi-tenant context, apply permissions
- Location: `app/Http/Middleware/`, `routes/web.php`, `routes/api.php`
- Contains: Auth middleware, CheckCompanySelected, permission gates
- Pattern: Middleware stack filters requests; session middleware injects company/branch context
- Purpose: Encapsulate domain rules and workflows
- Location: `app/Services/`, `app/Observers/`
- Contains: Container status logic, e-invoice generation, photo management, external API calls
- Depends on: Models, external services (Decolecta, SUNAT, DO Spaces)
- Pattern: Service classes with focused responsibilities; Eloquent observers for model lifecycle hooks
- Purpose: Represent domain entities and persist state
- Location: `app/Models/`, `database/migrations/`
- Contains: Eloquent models with relationships, database schema
- Pattern: Relationships reflect domain logic (e.g., `EirInspection->trace->container`, `WorkOrder->eir`)
- Purpose: Communicate with third-party systems
- Location: Service classes, `app/Services/SunatService.php`, `app/Services/ExternalDispatchApiService.php`
- Integrations:

## Data Flow

### Primary Request Path (Web - Interactive CRUD)

### E-Invoicing Flow (Sunat Submission)

### SUNAT templates are repo-owned

| What | Where |
|------|-------|
| XML templates (invoice, notes, despatch, voided, summary) | `app/Services/Sunat/Templates/` |
| XML builder | `app/Services/Sunat/Xml/SunatXmlBuilder.php` |
| Report templates (invoice, despatch, voided, summary, perception, retention, transportista) + `assets/style.css` | `app/Services/Sunat/Templates/pdf/` |
| Transportista XML (own document type) | `app/Services/Sunat/Transportista/` |

### Container Lifecycle Flow

- Container trace state is the canonical source of truth (not scattered across inspection/work-order/dispatch tables)
- Session state (company/branch) is injected into every request via middleware
- No global app state; all dependencies injected or retrieved via services/models
- File uploads temporary stored locally (`LIVEWIRE_UPLOAD_DISK=local`), then moved to DO Spaces in a job

## Key Abstractions

- Purpose: Single unified record tracking all container movements, inspections, and repairs
- Examples: `app/Models/ContainerOperationalTrace.php`
- Pattern: One trace per container per admission event (discharge/devolution). Has many: Inspections, WorkOrders, Photos. Belongs to: Container, Discharge/Devolution
- Rationale: Avoids scattering state across inspection/work-order/dispatch tables; all logic queries this central record
- **Admissions:** `ShippingLine`, `Vessel`, `Port`, `ContainerType` - master data
- **Containers:** `Container` (identity), `ContainerOperationalTrace` (lifecycle)
- **Inspections:** `EirInspection`, `EirInspectionDetail`, `LeakTest`, `RoofEvidence` - linked to trace
- **Work Orders:** `WorkOrder`, `Worker`, `WorkLaborPhoto` - repairs keyed to trace
- **Dispatches:** `Dispatch`, `DispatchPhoto` - synced from external system
- **Invoicing:** `Invoice`, `Despatch`, `Voided`, `Detraction`, `Perception` - SUNAT documents
- **Expenses:** `ArchingCash`, `Bill`, `CashDeposit` - cash management
- Purpose: Eliminate global query filters; implicit company/branch scoping via session middleware
- Examples: `session('company')->id`, `session('branch')->id` used throughout
- Pattern: Controllers/services always read session context; queries implicitly constrained
- Rationale: Simpler code; no risk of accidental data bleed between companies; multi-tenant enforced at HTTP level
- Purpose: Authorization layer separate from route definitions
- Pattern: Route defines `->middleware('can:resource.action')`, Spatie gate checks user has role with permission
- Examples: `can:inspections.eir.create`, `can:work_orders.box` defined in `database/seeders/RoleSeeder.php`
- Rationale: Single source of truth for route access; easier to manage permissions per role

## Entry Points

- Location: `routes/web.php:46-237`
- Triggers: Authenticated user requests `/dashboard`, `/inspections/eir`, `/work-orders/box`, etc.
- Responsibilities: Route resolution, middleware chain, controller dispatch
- Key middleware: `auth` (Jetstream), `CheckCompanySelected` (multi-tenant), permission gates
- Location: `routes/api.php:1-164`
- Triggers: External app sends `POST /api/invoice/send`, `GET /api/companies`, etc.
- Responsibilities: JSON request/response, JWT auth, API versioning (implicit—no version in routes)
- Auth: `auth:jwt` for company endpoints, `auth:sanctum` for document operations (invoice, despatch, note)
- Location: `routes/admin.php` (loaded in `bootstrap/app.php:19-22`)
- Triggers: Authenticated admin user requests `/admin/*`
- Responsibilities: Admin-specific routes (minimal; currently only dashboard)
- Middleware: `web`, `auth`
- Location: `app/Console/Commands/`
- Triggers: `php artisan` CLI or scheduled jobs
- Key commands: `SyncDispatchedContainers`, `PurgeLivewireTemp`, `PurgeLivewireImports`
- Responsibilities: Async tasks, periodic syncs, cleanup

## Architectural Constraints

- **Threading:** Single-threaded event loop (Laravel web server). Async jobs via Laravel queue (configured for sync in dev, can switch to Redis/SQS).
- **Global state:** No module-level singletons; dependency injection via service container. Session stores company/branch for request lifecycle. SUNAT credentials stored on Company model (encrypted in DB).
- **Circular imports:** Minimal risk due to namespace organization; Models in `app/Models/`, Services in `app/Services/`. Controllers depend on both; no cross-controller imports.
- **File uploads:** Temporary storage via Livewire's upload disk (`storage/livewire/tmp-*`), then moved to DO Spaces via jobs. Local fallback in dev.
- **Database transactions:** Used selectively in services (e.g., `ContainerOperationalTraceService`) where multiple models updated atomically.
- **Rate limiting:** Not enforced at HTTP level; rely on Laravel's throttle middleware if needed per route.

## Anti-Patterns

### Implicit Session Dependency

### Company/Branch Context Mixing

### Photo Path Coupling to DO Spaces

## Error Handling

- **Services:** Throw domain exceptions with context; catch only specific exceptions, re-throw or log.
- **Controllers:** Catch exceptions, log them, return HTTP response (HTML or JSON).
- **Livewire:** Catch validation errors in form object; display inline via `$errors->get('field')`.
- **API:** Return JSON error response with status code and message.
- **Logging:** `Log::error()` for failures; `Log::warning()` for edge cases (e.g., photo deletion failure in cleanup).
- `ContainerOperationalTraceService::updateStatus()` throws `Exception` with message; controller logs and shows toast.
- `EirPhotoService::uploadHeaderPhoto()` catches storage exceptions, logs, returns success=false to Livewire.
- `DocumentService` throws validation exceptions if SUNAT credentials missing or document invalid.

## Cross-Cutting Concerns

- Framework: Laravel's built-in logging (configured via `config/logging.php`)
- Pattern: Use `Log::channel('single')` for application logs. Photo/document operations log to `storage/logs/laravel.log`.
- Key events: model creates/updates (via observers), service operations, external API calls
- Framework: Laravel's validator (request validation + Livewire form validation)
- Pattern: Request validation in Form objects (`app/Livewire/Forms/*`); database constraints as backup
- Examples: `EirForm` validates trace_id exists, serie is valid for branch, correlativo is numeric
- Providers: Jetstream (web), JWT (API external), Sanctum (API internal)
- Pattern: Jetstream handles user registration/login; guards via `auth:sanctum` or `auth:jwt` per route
- Multi-tenancy: Company membership enforced via `company_user` pivot table; middleware validates on each request
- Framework: Laravel Storage facade + DigitalOcean Spaces S3 driver
- Pattern: Disk alias 'do' for production photos; 'local' for dev/temp. Services handle upload/delete via storage facade.
- Lifecycle: Livewire temp upload → job moves to permanent disk → cleanup via console command

<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->

## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, `.github/skills/`, or `.codex/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->

## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:

- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->

## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
