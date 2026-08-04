<!-- refreshed: 2026-07-24 -->
# Architecture

**Analysis Date:** 2026-07-24

## System Overview

This is a Laravel 11 mini-ERP for container terminal logistics (INSPECTEC SERVICES / SANLOMAR). The system handles container discharge/devolution, inspections (EIR/PTI/leak-test), work orders, dispatches, and SUNAT e-invoicing for Peru.

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│                           Web UI Layer (Blade + Livewire 3)                  │
├──────────────────────────────────┬───────────────────────┬──────────────────┤
│   Interactive Tables & Forms     │  Page Controllers    │   PDF Generation │
│  `app/Livewire/Operations/*`     │  (shell rendering)   │  (Reports/Docs)  │
└────────────────────┬─────────────┴───────────┬───────────┴────────────┬──────┘
                     │                          │                        │
                     ▼                          ▼                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│              HTTP Request → Middleware Layer                                  │
│  1. auth (Jetstream/Sanctum)                                                 │
│  2. CheckCompanySelected (Multi-tenant context: session('company/branch'))   │
│  3. Permission gates (can:resource.action via Spatie)                        │
└───────────┬──────────────────────────┬──────────────────────┬────────────────┘
            │                          │                      │
            ▼                          ▼                      ▼
┌───────────────────────┐  ┌─────────────────────┐  ┌──────────────────────┐
│  Web Controllers      │  │  API Controllers    │  │  Admin Controllers   │
│  `app/Http/          │  │  `app/Http/         │  │  `app/Http/         │
│   Controllers/`       │  │   Controllers/Api/` │  │   Controllers/Admin/`│
│  (Session-based)      │  │  (JWT/Sanctum)      │  │  (routes/admin.php)  │
└───────────┬───────────┘  └─────────────────────┘  └──────────────────────┘
            │
            ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                    Domain Services Layer                                      │
│  `app/Services/` - Business logic, e-invoicing, inspections, work orders    │
│  `app/Services/Container/` - Container lifecycle and status tracking         │
│  `app/Services/Sunat/*` - SUNAT e-invoice document generation               │
│  `app/Services/inspections/*` - Photo management services                    │
└───────────┬──────────────────────────────────────────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                      Data Layer (Eloquent Models)                             │
│  `app/Models/` - Domain entities with relationships                          │
│  - Admissions: ShippingLine, Vessel, Port, ContainerType                     │
│  - Containers: Container, ContainerOperationalTrace                          │
│  - Inspections: EirInspection, LeakTest, RoofEvidence                       │
│  - Works: WorkOrder, Worker                                                   │
│  - Dispatches: Dispatch, DispatchPhoto                                       │
│  - Invoicing: Invoice, Despatch, Voided, Detraction                          │
│  - Expenses: ArchingCash, Bill, CashDeposit                                  │
│  - Organization: Company, Branch, User                                        │
└───────────┬──────────────────────────────────────────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                        Database (MySQL)                                       │
│  Schema: Multi-tenant tables scoped by company_id and branch_id              │
│  Migrations: `database/migrations/`                                          │
└──────────────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                  External Integrations & Storage                              │
│  DigitalOcean Spaces (S3-compatible) - Photo/Document storage               │
│  Decolecta API - RUC/DNI lookup, SUNAT exchange rate                         │
│  SUNAT e-invoicing - Electronic document submission (Greenter SDK)          │
│  External Dispatch API - Container dispatch syncing                          │
└──────────────────────────────────────────────────────────────────────────────┘
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

**Overall:** Multi-tenant Monolith with Session-Based Context + Domain-Driven Layers

**Key Characteristics:**
- **Multi-tenant isolation:** Every route enforces `CheckCompanySelected` middleware which injects `company` and `branch` into the session. Business logic queries implicitly scoped via `session('company')` and `session('branch')`.
- **Session-based context over query params:** Rather than filtering by `?company_id=X&branch_id=Y`, the entire request context is derived from session state. This simplifies CRUD operations and cross-entity navigation.
- **Livewire-first interactive UI:** Complex tables and forms live in Livewire components; controllers mostly render Blade shells that mount the components.
- **Service layer for domain logic:** Business rules (container status transitions, e-invoice generation, photo storage) isolated from controllers in `app/Services/`.
- **API as secondary surface:** REST API in `routes/api.php` uses JWT auth and mirrors web functionality for mobile/external integrations—separate from session-based web routes.
- **Permission-based authorization:** Route-level gates via Spatie `laravel-permission` (e.g., `->middleware('can:inspections.eir.create')`), not ability closures.

## Layers

**Presentation (Web & API):**
- Purpose: Accept HTTP requests and render responses (HTML, JSON, PDF)
- Location: `app/Http/Controllers/`, `app/Livewire/`, `resources/views/`
- Contains: Controllers, Livewire components, Blade templates
- Depends on: Models, Services, Middleware
- Used by: Browser clients (web) and mobile/external apps (API)
- Pattern: MVC for web (Blade views), JSON-RPC for API

**Middleware & Routing:**
- Purpose: Route requests, enforce auth & multi-tenant context, apply permissions
- Location: `app/Http/Middleware/`, `routes/web.php`, `routes/api.php`
- Contains: Auth middleware, CheckCompanySelected, permission gates
- Pattern: Middleware stack filters requests; session middleware injects company/branch context

**Business Logic (Services & Observers):**
- Purpose: Encapsulate domain rules and workflows
- Location: `app/Services/`, `app/Observers/`
- Contains: Container status logic, e-invoice generation, photo management, external API calls
- Depends on: Models, external services (Decolecta, SUNAT, DO Spaces)
- Pattern: Service classes with focused responsibilities; Eloquent observers for model lifecycle hooks

**Data Layer (Models & Database):**
- Purpose: Represent domain entities and persist state
- Location: `app/Models/`, `database/migrations/`
- Contains: Eloquent models with relationships, database schema
- Pattern: Relationships reflect domain logic (e.g., `EirInspection->trace->container`, `WorkOrder->eir`)

**External Integration Layer:**
- Purpose: Communicate with third-party systems
- Location: Service classes, `app/Services/SunatService.php`, `app/Services/ExternalDispatchApiService.php`
- Integrations:
  - **Greenter SDK** – SUNAT e-invoicing (invoice, note, despatch, voided, summary documents)
  - **Decolecta API** – RUC/DNI validation, exchange rate lookups
  - **DigitalOcean Spaces** – File storage for photos (S3-compatible)
  - **External Dispatch API** – Syncs container dispatches via `SyncDispatchedContainers` console command

## Data Flow

### Primary Request Path (Web - Interactive CRUD)

1. **Request Entry** (`routes/web.php:48-237`)
   - User clicks link or submits form to `/inspections/eir` (list) or `/inspections/{trace}/create-eir` (create)
   - Route middleware chain: `auth` → `CheckCompanySelected` → permission gate (e.g., `can:inspections.eir.create`)

2. **Middleware - Company/Branch Injection** (`app/Http/Middleware/CheckCompanySelected.php:17-64`)
   - If session lacks 'company': auto-selects if user belongs to exactly one company; otherwise redirects to `companies.index`
   - Auto-selects first branch if user has one assigned to the company
   - Validates on every request that the session company still belongs to the user
   - Sets `session('company')` and `session('branch')` for the entire request

3. **Controller - Page Shell** (e.g., `app/Http/Controllers/operations/InspectionController.php:38-49`)
   - Fetches catalogs (Location, Damage, Component, Method, ContainerType)
   - Renders Blade view with `compact('trace', 'catalogs')`
   - View: `resources/views/inspections/eir/create.blade.php` (mounts Livewire component)

4. **Livewire Component - Interactive UI** (e.g., `app/Livewire/Operations/Inspections/EirCreate.php`)
   - Mounts with `wire:model` bindings to a Form object
   - User fills form → component validates in real-time
   - Submit → Livewire calls `save()` method on the component

5. **Form Object & Validation** (`app/Livewire/Forms/EirForm.php:37-56`)
   - Livewire form object with typed properties and `rules()` method
   - Validation happens via Livewire's built-in validator
   - Custom messages and validation attributes

6. **Service Layer - Business Logic** (e.g., `app/Services/Container/ContainerOperationalTraceService.php`)
   - Service called from component's `save()` method
   - Validates domain rules (e.g., container status transitions via `ContainerStatusService`)
   - Updates multiple related models atomically (trace status, inspection, photos)

7. **Model Persistence & Observers** (e.g., `app/Models/inspections/EirInspection.php`)
   - Eloquent model saves to `eir_inspections` table
   - Model observers (e.g., `app/Observers/EirInspectionObserver.php`) trigger side effects:
     - Update related container trace status
     - Log audit trail
     - Dispatch jobs (e.g., photo uploads to DO Spaces)

8. **Response** (Livewire → Browser)
   - Livewire refreshes component, updates real-time display
   - Browser shows success message and navigates (e.g., back to list)

### E-Invoicing Flow (Sunat Submission)

1. **Invoice/Despatch/Note Creation**
   - User submits invoice form via Livewire component (e.g., `GenerateInvoice` Livewire)
   - Component calls `DocumentService` to build Greenter document model

2. **Document Service** (`app/Services/Sunat/DocumentService.php`)
   - Reads company credentials (`sol_user`, `sol_pass`, `client_id`, `client_secret`, `certificate`)
   - Reads branch series & number sequence
   - Builds Greenter `Invoice` / `Despatch` / `Note` / `Summary` / `Voided` object
   - Validates document structure

3. **XML Generation** (`app/Services/Sunat/Xml/SunatXmlBuilder.php`)
   - `UtilService::getXmlSigned()` delegates to `SunatXmlBuilder`, **not** to
     Greenter's builders (`InvoiceBuilder`, `NoteBuilder`, `DespatchBuilder`,
     `VoidedBuilder`, `SummaryBuilder` are no longer instantiated anywhere)
   - Templates live in `app/Services/Sunat/Templates/` (versioned in the repo);
     the Twig loader chains `[repo, vendor]`, so anything not overridden still
     resolves from `greenter/xml`
   - See "SUNAT templates are repo-owned" below for why

4. **SUNAT Submission**
   - Greenter signs the XML (`SignedXml`) and submits to the SUNAT API
   - On success: model status → 'enviado', ticket stored
   - On failure: status → 'error', error message logged
   - Response includes ticket or rejection reason

5. **PDF / HTML Generation** (Export)
   - `UtilService::getReportPdf()` and `getReportHtml()`, plus the `UtilTrait`
     and `UtilTransportistaService` variants
   - Uses `mikehaertl/phpwkhtmltopdf` via `Greenter\Report\PdfReport`
   - Templates live in `public/custom/pdf/` (versioned in the repo), with the
     same chained fallback to `greenter/report`
   - `tipoCambio` travels through report params, not on the document model:
     `Greenter\Model\Sale\BaseSale` has no such property

### SUNAT templates are repo-owned

Everything the system hands to SUNAT (signed XML) or to the customer (PDF/RIDE)
is rendered from templates that live **in the repository**, never from
`vendor/greenter`:

| What | Where |
|------|-------|
| XML templates (invoice, notes, despatch, voided, summary) | `app/Services/Sunat/Templates/` |
| XML builder | `app/Services/Sunat/Xml/SunatXmlBuilder.php` |
| Report templates (invoice, despatch, voided, summary, perception, retention, transportista) + `assets/style.css` | `public/custom/pdf/` |
| Transportista XML (own document type) | `app/Services/Sunat/Transportista/` |

**Why:** `vendor/` is gitignored and was historically uploaded to the VPS
already hand-patched. The first real Deployer run executed `composer install`,
replaced it with pristine packagist code and silently destroyed every patch.
That caused two production incidents in one day: a fatal
`Call to undefined method Invoice::setTipoCambio()` that broke *all* invoice and
note emission, and the detraction amount printed in USD on four invoices when it
is always expressed in soles. A third patch — the `<cac:LoadingTransportEvent>`
node in the guía de remisión remitente XML — disappeared with no visible symptom
at all, since it never shows up on the PDF.

**Rule for contributors:** never edit anything under `vendor/greenter`. Copy the
template into the matching repo directory and edit it there. The chained loader
makes the repo copy win.

Greenter is still used for models, XML signing, submission and CDR parsing.

### Container Lifecycle Flow

1. **Container Import**
   - User uploads discharge/devolution Excel via `DischargueController::store()` or `DevolutionController::store()`
   - Uses `app/Imports/Discharge/` or `app/Imports/Devolution/` import classes
   - Creates bulk `Container` + `Dischargue`/`Devolution` records

2. **Trace Initialization**
   - On container creation, `ContainerOperationalTrace` record auto-created (via observer or explicit call)
   - Trace tracks: `status_box`, `status_machine`, `status` (final), dates, inspector/validator user IDs

3. **Inspection → Status Transition**
   - EIR inspection created for a trace via `InspectionController::create_eir()`
   - Upon closing EIR (status='completado'), `ContainerStatusService::calculateStatus()` updates trace final status
   - Service checks if container is damaged (has EIR details) → marks as needs repair
   - Updates `ContainerOperationalTrace.status_box` if inspection indicates structural damage

4. **Work Order Creation**
   - If inspection indicates repair needed, user creates WorkOrder linked to EIR
   - WorkOrder tracks box vs. machine repairs independently
   - Photos uploaded to track labor progress via `WorkLaborPhotoService`

5. **Dispatch Syncing**
   - External system sends dispatch data via `ExternalDispatchApiService`
   - `SyncDispatchedContainers` console command runs periodically
   - Updates container dispatch status when container leaves terminal
   - Final status → 'despachado'

**State Management:**
- Container trace state is the canonical source of truth (not scattered across inspection/work-order/dispatch tables)
- Session state (company/branch) is injected into every request via middleware
- No global app state; all dependencies injected or retrieved via services/models
- File uploads temporary stored locally (`LIVEWIRE_UPLOAD_DISK=local`), then moved to DO Spaces in a job

## Key Abstractions

**ContainerOperationalTrace:**
- Purpose: Single unified record tracking all container movements, inspections, and repairs
- Examples: `app/Models/ContainerOperationalTrace.php`
- Pattern: One trace per container per admission event (discharge/devolution). Has many: Inspections, WorkOrders, Photos. Belongs to: Container, Discharge/Devolution
- Rationale: Avoids scattering state across inspection/work-order/dispatch tables; all logic queries this central record

**Domain Models by Module:**
- **Admissions:** `ShippingLine`, `Vessel`, `Port`, `ContainerType` - master data
- **Containers:** `Container` (identity), `ContainerOperationalTrace` (lifecycle)
- **Inspections:** `EirInspection`, `EirInspectionDetail`, `LeakTest`, `RoofEvidence` - linked to trace
- **Work Orders:** `WorkOrder`, `Worker`, `WorkLaborPhoto` - repairs keyed to trace
- **Dispatches:** `Dispatch`, `DispatchPhoto` - synced from external system
- **Invoicing:** `Invoice`, `Despatch`, `Voided`, `Detraction`, `Perception` - SUNAT documents
- **Expenses:** `ArchingCash`, `Bill`, `CashDeposit` - cash management

**Session-Scoped Business Logic:**
- Purpose: Eliminate global query filters; implicit company/branch scoping via session middleware
- Examples: `session('company')->id`, `session('branch')->id` used throughout
- Pattern: Controllers/services always read session context; queries implicitly constrained
- Rationale: Simpler code; no risk of accidental data bleed between companies; multi-tenant enforced at HTTP level

**Permission Gates:**
- Purpose: Authorization layer separate from route definitions
- Pattern: Route defines `->middleware('can:resource.action')`, Spatie gate checks user has role with permission
- Examples: `can:inspections.eir.create`, `can:work_orders.box` defined in `database/seeders/RoleSeeder.php`
- Rationale: Single source of truth for route access; easier to manage permissions per role

## Entry Points

**Web Application:**
- Location: `routes/web.php:46-237`
- Triggers: Authenticated user requests `/dashboard`, `/inspections/eir`, `/work-orders/box`, etc.
- Responsibilities: Route resolution, middleware chain, controller dispatch
- Key middleware: `auth` (Jetstream), `CheckCompanySelected` (multi-tenant), permission gates

**API (External/Mobile):**
- Location: `routes/api.php:1-164`
- Triggers: External app sends `POST /api/invoice/send`, `GET /api/companies`, etc.
- Responsibilities: JSON request/response, JWT auth, API versioning (implicit—no version in routes)
- Auth: `auth:jwt` for company endpoints, `auth:sanctum` for document operations (invoice, despatch, note)

**Admin Dashboard:**
- Location: `routes/admin.php` (loaded in `bootstrap/app.php:19-22`)
- Triggers: Authenticated admin user requests `/admin/*`
- Responsibilities: Admin-specific routes (minimal; currently only dashboard)
- Middleware: `web`, `auth`

**Console Commands:**
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

**What happens:** Services and controllers read `session('company')` and `session('branch')` directly without parameter injection. Testing these services requires mocking session.

**Why it's wrong:** Reduces testability; makes dependencies invisible; couples business logic to HTTP session.

**Do this instead:** Pass company/branch as parameters to service methods: `$service->createInspection($trace, company: session('company'), branch: session('branch'))`. This makes dependencies explicit and testable.

### Company/Branch Context Mixing

**What happens:** Some code uses `session('company')->id` while other code passes Company model directly. Inconsistent context injection across layers.

**Why it's wrong:** Can lead to context mismatches; harder to trace which company an operation belongs to.

**Do this instead:** Standardize: either always pass session ID/model or always resolve from session at controller level and pass model to services.

### Photo Path Coupling to DO Spaces

**What happens:** Photo paths stored as `do-spaces:/bucket/path/...` hardcoded in migration and service (`EirPhotoService`, `RoofEvidencePhotoService`). Switching storage providers requires code changes.

**Why it's wrong:** Storage provider tightly coupled to business logic; migration path complex.

**Do this instead:** Use Laravel's Storage facade with named disks (`Storage::disk('photos')->put(...)`). Define disk in `config/filesystems.php`. Switch providers by changing disk driver only.

## Error Handling

**Strategy:** Try-catch at service level; logging to Laravel log; user-facing errors via Livewire/API response.

**Patterns:**
- **Services:** Throw domain exceptions with context; catch only specific exceptions, re-throw or log.
- **Controllers:** Catch exceptions, log them, return HTTP response (HTML or JSON).
- **Livewire:** Catch validation errors in form object; display inline via `$errors->get('field')`.
- **API:** Return JSON error response with status code and message.
- **Logging:** `Log::error()` for failures; `Log::warning()` for edge cases (e.g., photo deletion failure in cleanup).

Examples:
- `ContainerOperationalTraceService::updateStatus()` throws `Exception` with message; controller logs and shows toast.
- `EirPhotoService::uploadHeaderPhoto()` catches storage exceptions, logs, returns success=false to Livewire.
- `DocumentService` throws validation exceptions if SUNAT credentials missing or document invalid.

## Cross-Cutting Concerns

**Logging:** 
- Framework: Laravel's built-in logging (configured via `config/logging.php`)
- Pattern: Use `Log::channel('single')` for application logs. Photo/document operations log to `storage/logs/laravel.log`.
- Key events: model creates/updates (via observers), service operations, external API calls

**Validation:**
- Framework: Laravel's validator (request validation + Livewire form validation)
- Pattern: Request validation in Form objects (`app/Livewire/Forms/*`); database constraints as backup
- Examples: `EirForm` validates trace_id exists, serie is valid for branch, correlativo is numeric

**Authentication:**
- Providers: Jetstream (web), JWT (API external), Sanctum (API internal)
- Pattern: Jetstream handles user registration/login; guards via `auth:sanctum` or `auth:jwt` per route
- Multi-tenancy: Company membership enforced via `company_user` pivot table; middleware validates on each request

**File Storage:**
- Framework: Laravel Storage facade + DigitalOcean Spaces S3 driver
- Pattern: Disk alias 'do' for production photos; 'local' for dev/temp. Services handle upload/delete via storage facade.
- Lifecycle: Livewire temp upload → job moves to permanent disk → cleanup via console command

---

*Architecture analysis: 2026-07-24*
