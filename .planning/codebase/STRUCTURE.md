# Codebase Structure

**Analysis Date:** 2026-07-24

## Directory Layout

```
project-root/
├── app/                              # Application code (PSR-4 autoloaded)
│   ├── Actions/                     # Jetstream/Fortify actions (user management)
│   ├── Console/                     # Console commands and scheduling
│   │   └── Commands/               # Cron tasks (sync, cleanup)
│   ├── Exports/                     # Maatwebsite Excel exports
│   ├── Http/
│   │   ├── Controllers/             # Web page controllers (lowercase operations/)
│   │   │   ├── Admin/              # Admin dashboard controllers
│   │   │   ├── Api/                # API controllers (JSON, JWT/Sanctum)
│   │   │   ├── operations/         # Domain controllers: Inspection, Work, Dispatch
│   │   │   ├── DashboardController.php
│   │   │   ├── VoucherController.php
│   │   │   ├── ReportController.php
│   │   │   └── ...master data controllers (Client, Provider, Port, etc.)
│   │   ├── Middleware/              # HTTP middleware (auth, company/branch, permissions)
│   │   │   └── CheckCompanySelected.php (core multi-tenant middleware)
│   │   └── Requests/                # Form request validation classes
│   ├── Livewire/                    # Interactive UI components (Livewire 3)
│   │   ├── Forms/                  # Jetstream form objects (EirForm, InvoiceForm, etc.)
│   │   ├── Operations/             # Domain-specific components
│   │   │   ├── Inspections/        # EirTable, EirCreate, EirEdit, LeakTestTable, etc.
│   │   │   ├── Works/              # Work order tables/modals
│   │   │   └── Dispatches/         # Dispatch tables/modals
│   │   ├── Branches/               # Branch management modals (BranchEdit, ManageIntegrations)
│   │   ├── Masters/                # Master data tables (Damage, Location, etc.)
│   │   ├── Roles/                  # Role/permission management components
│   │   ├── *Table.php              # Data table components (rappasoft/laravel-livewire-tables)
│   │   ├── *Create.php             # Modal create forms
│   │   ├── *Edit.php               # Modal edit forms
│   │   └── ClientCreate.php, CompanyCreate.php, etc.
│   ├── Mail/                        # Mailable classes (email notifications)
│   ├── Models/                      # Eloquent models (domain entities)
│   │   ├── inspections/            # EirInspection, LeakTest, RoofEvidence, EirInspectionDetail
│   │   ├── works/                  # WorkOrder, WorkOrderPhoto (if nested)
│   │   ├── Dispatches/             # Dispatch, DispatchPhoto
│   │   ├── Company.php             # Tenant root; has branches, users, invoices
│   │   ├── Branch.php              # Branch scoping; has integrations, series
│   │   ├── User.php                # Authenticated user; belongs to companies/branches
│   │   ├── Container.php           # Container identity
│   │   ├── ContainerOperationalTrace.php (core entity for container lifecycle)
│   │   ├── Dischargue.php          # Container admission (discharge)
│   │   ├── Devolution.php          # Container return (devolution)
│   │   ├── Invoice.php, Despatch.php, Voided.php, Detraction.php
│   │   ├── ArchingCash.php, Bill.php, CashDeposit.php (expenses)
│   │   └── Client.php, Provider.php, CustomBroker.php, Transport.php, Driver.php
│   ├── Observers/                   # Eloquent model observers (lifecycle hooks)
│   │   └── CompanyObserver.php
│   ├── Providers/                   # Service providers (bootstrap, bindings)
│   ├── Services/                    # Business logic, domain workflows, external APIs
│   │   ├── Container/              # ContainerOperationalTraceService, ContainerStatusService
│   │   ├── inspections/            # EirPhotoService, RoofEvidencePhotoService
│   │   ├── Sunat/                  # DocumentService, SummaryService, UtilService, VoidedService
│   │   │   ├── Templates/          # XML twig templates sent to SUNAT (repo-owned, NOT vendor)
│   │   │   ├── Xml/                # SunatXmlBuilder (replaces Greenter's XML builders)
│   │   │   └── Transportista/      # Guía transportista: own model, template, builder, service
│   │   ├── WorkOrders/             # WorkLaborPhotoService
│   │   ├── SunatService.php        # Decolecta API (RUC, DNI, exchange rate)
│   │   └── ExternalDispatchApiService.php (sync container dispatches)
│   ├── Traits/                      # Reusable trait code
│   │   └── Sunat/                  # SUNAT-related traits (document helpers)
│   └── View/
│       └── Components/              # Blade component classes (if used)
│
├── bootstrap/
│   └── app.php                      # Laravel app config (routes, middleware, exceptions)
│
├── config/                          # Configuration files
│   ├── app.php, auth.php, database.php, filesystems.php
│   ├── permission.php               # Spatie Laravel Permission config
│   └── ...other framework configs
│
├── database/
│   ├── factories/                   # Model factories for testing
│   │   └── works/                  # Work order factories
│   ├── migrations/                  # Schema migrations (70+ files)
│   │   └── (sorted by date: users, companies, branches, containers, inspections, work_orders, etc.)
│   └── seeders/                     # Database seeding
│       ├── DatabaseSeeder.php      # Main seeder entry point
│       └── RoleSeeder.php          # Roles & permissions seed
│
├── lang/
│   └── es/                         # Spanish translations (validation, messages)
│
├── public/
│   ├── css/, js/, img/             # Frontend assets (built via Vite)
│   └── index.php                    # Entry point
│
├── resources/
│   ├── views/                       # Blade templates
│   │   ├── layouts/                # Master layouts
│   │   ├── livewire/               # Livewire component views (paired with PHP classes)
│   │   │   ├── operations/inspections/  # EIR, leak test forms/tables
│   │   │   ├── operations/works/        # Work order forms/tables
│   │   │   ├── operations/dispatches/   # Dispatch forms/tables
│   │   │   ├── branches/                # Branch modals (series, integrations)
│   │   │   ├── masters/                 # Master data tables (damage, location, etc.)
│   │   │   └── ...CRUD views
│   │   ├── auth/                    # Jetstream auth pages (login, register)
│   │   ├── inspections/            # Controller view shells (eir/create.blade.php mounts Livewire)
│   │   ├── invoices/               # Invoice display/export views
│   │   ├── reports/                # Report templates
│   │   └── dashboard.blade.php     # Main dashboard
│   ├── css/
│   │   └── app.css                 # Tailwind output (generated)
│   ├── js/
│   │   └── app.js                  # Vite entry point (Alpine.js, Livewire JS)
│   └── templates/                  # Excel import templates
│       ├── massive_dischargue.xlsx
│       └── massive_devolution.xlsx
│
├── routes/
│   ├── web.php                      # Main web routes (auth + multi-tenant + domains)
│   ├── api.php                      # API routes (JWT + Sanctum)
│   ├── admin.php                    # Admin routes (loaded separately)
│   └── console.php                  # Console command routes (scheduling)
│
├── storage/
│   ├── app/
│   │   ├── public/                 # Public files (company logos, exported reports)
│   │   └── livewire/               # Livewire temp uploads (cleaned up)
│   └── logs/                        # Application logs (laravel.log)
│
├── tests/
│   ├── Feature/                     # Feature tests (integration)
│   └── Unit/                        # Unit tests
│
├── node_modules/                    # NPM dependencies (npm install)
├── vendor/                          # Composer dependencies (composer install)
│
├── .env                             # Environment config (secrets: DB, SUNAT, DO Spaces, API keys)
├── .env.example                     # Template for .env
├── package.json                     # Frontend dependencies (Vue, Tailwind, Alpine, Vite)
├── composer.json                    # Backend dependencies (Laravel, Livewire, Spatie, Greenter)
├── tailwind.config.js               # Tailwind CSS config
├── vite.config.js                   # Vite build config
│
└── .planning/
    └── codebase/                    # GSD codebase documentation
        └── (this file and others)
```

## Directory Purposes

**`app/Http/Controllers/`:**
- Purpose: Route handlers; mostly render Blade page shells that mount Livewire components
- Contains: Page controllers (DashboardController, VoucherController, ReportController) + resource controllers (Client, Provider, etc.)
- Key files: `operations/` subdirectory for domain controllers (Inspection, WorkOrder, Dispatch)

**`app/Http/Controllers/operations/`:**
- Purpose: Domain-specific page controllers (operations = business processes)
- Note: Namespace is `App\Http\Controllers\operations` (lowercase) per `routes/web.php:19-20`
- Contains: InspectionController, WorkOrderController, DispatchController
- Pattern: Each action returns `view('operations.xxx.action', compact(...))` mounting a Livewire component

**`app/Livewire/`:**
- Purpose: Interactive component logic (tables, forms, modals)
- Pattern: Livewire component class (PHP) + paired Blade view (HTML)
- Structure: Subdirectories mirror domain modules (Operations, Branches, Masters, Roles)
- Naming: `*Table` for sortable/filterable tables, `*Create`/`*Edit` for modals, Form classes in `Forms/`

**`app/Livewire/Forms/`:**
- Purpose: Jetstream-style form objects for validation + data binding
- Pattern: Inherit from `Livewire\Form`, define typed properties + `rules()` method
- Usage: Mounted in Livewire component via `#[Validate]` attribute or manual assignment
- Examples: `EirForm`, `InvoiceForm`, `WorkOrderForm` handle validation and submission logic

**`app/Livewire/Operations/Inspections/`:**
- Purpose: Inspection workflow components
- Contains:
  - `EirTable.php` / `EirTable.blade.php` – List all EIR inspections (rappasoft table)
  - `EirCreate.php` / `create.blade.php` – Create inspection form (modal)
  - `EirEdit.php` / `edit.blade.php` – Edit inspection form
  - `LeakTestTable.php` / `LeakTestTable.blade.php` – Leak test listing
  - `LeakTestCreate.php` – Leak test creation
  - `RoofEvidenceModal.php` – Roof evidence photo modal
- Flow: Controller returns view → Livewire component mounts → user interacts → component calls service → response updated

**`app/Services/`:**
- Purpose: Business logic, domain workflows, external integrations
- Subdirectories by domain:
  - `Container/` – Container lifecycle (status transitions, traces)
  - `inspections/` – Photo management (upload/delete to DO Spaces)
  - `Sunat/` – SUNAT e-invoicing (document building, XML, PDF)
  - `WorkOrders/` – Work order photo storage
- Pattern: Service class with focused methods; dependencies injected via constructor or methods

**`app/Services/Container/`:**
- Purpose: Core container domain logic
- `ContainerOperationalTraceService.php` – Updates trace status (box, machine, final)
- `ContainerStatusService.php` – Static methods for status validation and calculation

**`app/Services/Sunat/`:**
- Purpose: SUNAT e-invoicing integration. Greenter provides models, signing, submission and CDR parsing; **XML and report rendering are repo-owned**
- `DocumentService.php` – Builds Greenter Invoice/Despatch/Note/Summary/Voided models from app data
- `SummaryService.php` – Groups invoices into daily/monthly summaries for SUNAT
- `VoidedService.php` – Handles document voiding (notas de crédito/débito)
- `UtilService.php` – Signs and submits documents, reads CDR, renders HTML/PDF reports
- `Xml/SunatXmlBuilder.php` – Renders the XML for every document type; replaces Greenter's `InvoiceBuilder`/`NoteBuilder`/`DespatchBuilder`/`VoidedBuilder`/`SummaryBuilder`, none of which are instantiated anywhere in `app/`
- `Templates/` – The XML twig templates themselves. Never edit `vendor/greenter`: it is gitignored and `composer install` wipes it on every deploy (see CONCERNS.md)
- `Transportista/` – Guía transportista is a document type Greenter does not model; it has its own model, template, builder and service

**`public/custom/pdf/`:**
- Purpose: Report (PDF/RIDE) twig templates, repo-owned, with chained fallback to `greenter/report`
- Holds `invoice`, `despatch`, `voided`, `summary`, `perception`, `retention`, `despatchTransportista` and `assets/style.css`
- Single source for both `getReportPdf()` and `getReportHtml()`; they used to point at different directories and produced different output

**`app/Models/`:**
- Purpose: Domain entities with Eloquent relationships
- Subdirectories by domain:
  - `inspections/` – EirInspection, LeakTest, RoofEvidence, EirInspectionDetail
  - `works/` – WorkOrder (if nested; may also be in root)
  - `Dispatches/` – Dispatch, DispatchPhoto
- Relationships: Models define `hasMany()`, `belongsTo()`, `belongsToMany()` reflecting domain logic

**`app/Models/inspections/EirInspection.php`:**
- Purpose: EIR inspection record
- Relationships: `trace` (ContainerOperationalTrace), `details` (EirInspectionDetail), `inspector`/`closer`/`voider` (User), `workOrder` (WorkOrder)
- Key fields: `status` (borrador, completado, cotizado), photo paths (`path_photo_1`–`path_photo_4`), repair flags (fast_repair, normal_repair, quoted_repair)

**`app/Models/ContainerOperationalTrace.php`:**
- Purpose: Central container lifecycle tracker
- Relationships: `container`, `gateInDetail` (polymorphic to Discharge/Devolution), `inspections`, `workOrders`, `dispatches`
- Key fields: `status_box`, `status_machine`, `status` (final), dates for each status, user IDs for status changes
- Rationale: Single entity to avoid scattering state across inspection/work-order/dispatch tables

**`database/migrations/`:**
- Purpose: Schema definitions
- Structure: 70+ migration files sorted by date
- Key tables: `companies`, `branches`, `users`, `containers`, `container_operational_traces`, `eir_inspections`, `work_orders`, `dispatches`, `invoices`, `voideds`, `arching_cashes`
- Convention: Migration date + description (e.g., `2025_09_18_164302_create_eir_inspections_table.php`)

**`database/seeders/`:**
- Purpose: Initialize database with seed data
- `DatabaseSeeder.php` – Entry point; calls other seeders
- `RoleSeeder.php` – Creates roles (Super, Admin, User) and permissions (can:inspections.eir.create, etc.)
- Usage: `php artisan db:seed` populates roles, permissions, master data

**`resources/views/`:**
- Purpose: Blade templates for HTML rendering
- Structure: Mirrors Laravel convention + Livewire component views
- `livewire/` – Component views paired with `app/Livewire/*` classes
- `layouts/` – Master layout (app.blade.php)
- `auth/` – Jetstream auth pages
- `inspections/`, `invoices/`, `reports/` – Domain-specific shells

**`resources/views/inspections/eir/create.blade.php`:**
- Purpose: EIR creation page shell
- Contains: Title, breadcrumbs, then `<livewire:operations.inspections.eir-create ... />`
- Pattern: Controller passes minimal context (e.g., `$trace`); Livewire component handles form/submission

**`resources/templates/`:**
- Purpose: Excel import templates
- `massive_dischargue.xlsx` – Template for bulk discharge import
- `massive_devolution.xlsx` – Template for bulk devolution import
- Download via route: `Route::get('downloads/dischargue-template', ...)->name('dischargue-template')`

**`routes/web.php`:**
- Purpose: Web application routing
- Structure:
  - Line 46: Redirect `/` to `/dashboard`
  - Lines 48–54: Middleware stack (`auth`, `CheckCompanySelected`)
  - Lines 55–237: Resource routes, prefix groups (inspections, work-orders, etc.)
- Middleware: Every route passes through `auth` + `CheckCompanySelected` + permission gate
- Naming: Routes named with dot notation (e.g., `inspections.eir.list`, `work_orders.box`) for Spatie permission matching

**`routes/api.php`:**
- Purpose: JSON API for external/mobile clients
- Auth: `auth:jwt` for company endpoints, `auth:sanctum` for document operations
- Endpoints: `/api/invoice/send`, `/api/despatch/xml`, `/api/summary/pdf`, etc.
- Pattern: Mostly proxy to web controllers but return JSON instead of Blade

**`bootstrap/app.php`:**
- Purpose: Laravel application configuration
- Contains: Route registration (`web.php`, `api.php`, `admin.php`), middleware setup, exception handling
- Key: Admin routes loaded separately at line 19–22

**`config/permission.php`:**
- Purpose: Spatie Laravel Permission configuration
- Defines: Permission table names, model namespace, cache settings
- Usage: `$user->hasPermissionTo('inspections.eir.create')` checks if user's role has permission

**`storage/app/public/` and `storage/app/livewire/`:**
- Purpose: Local file storage
- `public/` – Company logos, exported reports (symlinked to `public/storage/`)
- `livewire/` – Temporary Livewire uploads; cleaned by `PurgeLivewireTemp` command

## Key File Locations

**Entry Points:**
- `public/index.php` – Web app entry; delegates to `bootstrap/app.php`
- `routes/web.php` – Web route definitions; auth + multi-tenant + domain routes
- `routes/api.php` – API route definitions; JWT + Sanctum auth
- `app/Http/Middleware/CheckCompanySelected.php` – Core multi-tenant middleware

**Configuration:**
- `.env` – Environment variables (DB, SUNAT, DO Spaces, API keys)
- `config/app.php`, `config/auth.php`, `config/database.php` – Framework config
- `config/permission.php` – Spatie permission config
- `config/filesystems.php` – Storage disk config (local, do, public)
- `tailwind.config.js`, `vite.config.js` – Frontend build config

**Core Logic:**
- `app/Services/Container/ContainerOperationalTraceService.php` – Container status management
- `app/Services/Sunat/DocumentService.php` – SUNAT document generation
- `app/Services/inspections/EirPhotoService.php` – EIR photo storage
- `app/Http/Middleware/CheckCompanySelected.php` – Multi-tenant context injection

**Testing:**
- `tests/Feature/` – Feature tests (integration tests)
- `tests/Unit/` – Unit tests
- `database/factories/` – Model factories for test data
- `phpunit.xml` – Test config (commented-out in-memory SQLite config available)

## Naming Conventions

**Files:**
- **Controllers:** `PascalCase` (e.g., `InspectionController.php`, `ClientController.php`)
- **Livewire Components:** `PascalCase` (e.g., `EirTable.php`, `ClientCreate.php`)
- **Models:** `PascalCase` (e.g., `EirInspection.php`, `ContainerOperationalTrace.php`)
- **Services:** `PascalCase` + `Service` suffix (e.g., `ContainerOperationalTraceService.php`)
- **Blade Views:** `snake_case` (e.g., `eir/create.blade.php`, `master_layout.blade.php`)
- **Migrations:** `YYYY_MM_DD_HHMMSS_snake_case_description.php` (e.g., `2025_09_18_164302_create_eir_inspections_table.php`)
- **Seeders:** `PascalCase` + `Seeder` suffix (e.g., `RoleSeeder.php`, `DatabaseSeeder.php`)

**Directories:**
- **Domain modules:** `snake_case` (e.g., `inspections/`, `work_orders/`, but also `works/`)
- **Namespaces:** Match directory structure with `PascalCase` (e.g., `App\Http\Controllers\operations\InspectionController`)
- **Routes prefix:** `snake_case` (e.g., `/inspections`, `/work-orders`, `/branches-choose`)

**Routes:**
- **Naming:** `snake_case` with dots for hierarchy (e.g., `inspections.eir.list`, `work_orders.box.create`, `branches.series`)
- **Middleware gates:** Named after route (e.g., route `inspections.eir.create` → gate `can:inspections.eir.create`)
- **URL params:** `snake_case` in query strings (e.g., `?status=completado&line_id=5`)

**Database:**
- **Tables:** `snake_case`, pluralized (e.g., `users`, `eir_inspections`, `container_operational_traces`)
- **Columns:** `snake_case` (e.g., `user_id`, `status_box`, `started_at`)
- **Foreign keys:** `{table}_id` (e.g., `user_id`, `branch_id`, `trace_id`)
- **Timestamps:** `created_at`, `updated_at` (Eloquent convention)

**Livewire Properties & Methods:**
- **Public properties:** `camelCase`, typed (e.g., `public EirInspection $inspection`)
- **Methods:** `camelCase` (e.g., `saveInspection()`, `uploadPhoto()`)
- **Lifecycle hooks:** `mount()`, `rendering()`, `rendered()`, `hydrate()`, `updating()`, `updated()`
- **Component names in views:** `kebab-case` (e.g., `<livewire:operations.inspections.eir-table />`)

## Where to Add New Code

**New Feature (CRUD) in Existing Module:**

1. **Model:** `app/Models/YourEntity.php` (or `app/Models/domain/YourEntity.php` if domain module exists)
   - Define relationships to Company, Branch if multi-tenant
   - Add fillable, casts, scopes

2. **Primary Code:** `app/Http/Controllers/YourEntityController.php` or `app/Http/Controllers/operations/YourDomainController.php`
   - For admin/master data: place in `app/Http/Controllers/`
   - For domain operations: place in `app/Http/Controllers/operations/` (note lowercase namespace)
   - Actions typically: index (renders shell), show (details), create (shell), edit (shell), destroy (delete)

3. **Interactive Components:** `app/Livewire/YourEntity*` (or `app/Livewire/Operations/Domain/YourEntity*`)
   - `YourEntityTable.php` – Extends `DataTableComponent` from rappasoft
   - `YourEntityCreate.php` – Extends `LivewireComponent`, mounts form
   - `YourEntityEdit.php` – Extends `LivewireComponent`, loads existing record

4. **Forms:** `app/Livewire/Forms/YourEntityForm.php`
   - Extends `Livewire\Form`
   - Define typed properties + `rules()` method

5. **Views:**
   - Controller view: `resources/views/your_entity/index.blade.php` (shell mounting Livewire)
   - Livewire views: `resources/views/livewire/your_entity/table.blade.php`, `create.blade.php`, `edit.blade.php`

6. **Routes:** `routes/web.php`
   - Add resource route: `Route::resource('your_entities', YourEntityController::class)->middleware('can:your_entities');`
   - Middleware gate name should match route (e.g., `can:your_entities`, `can:your_entities.create`)

7. **Permissions:** `database/seeders/RoleSeeder.php`
   - Add permission: `Permission::create(['name' => 'your_entities.index', 'guard_name' => 'web']);`
   - Assign to roles as needed

8. **Tests:** `tests/Feature/YourEntityTest.php` or `tests/Unit/Services/YourEntityServiceTest.php`

**Example: Add new inspection type (e.g., StructuralTest)**

- Model: `app/Models/inspections/StructuralTest.php`
- Controller: `app/Http/Controllers/operations/InspectionController.php` (extend existing)
- Livewire: `app/Livewire/Operations/Inspections/StructuralTestTable.php`, `StructuralTestCreate.php`
- Form: `app/Livewire/Forms/StructuralTestForm.php`
- Views: `resources/views/inspections/structural_test/`
- Routes: Add route in `/inspections` prefix group
- Permissions: Add `inspections.structural.list`, `inspections.structural.create` to seeder

**New Business Logic / Service:**

1. Create service: `app/Services/Domain/YourLogicService.php`
   - Define focused public methods
   - Inject dependencies (models, other services) in constructor or method params
   - Use `session('company')` and `session('branch')` only if HTTP request context available; otherwise pass as params

2. Use in controller/Livewire:
   ```php
   use App\Services\Domain\YourLogicService;
   
   public function save() {
       $service = app(YourLogicService::class);
       $service->doWork($data, session('company'), session('branch'));
   }
   ```

3. Tests: `tests/Unit/Services/Domain/YourLogicServiceTest.php`
   - Mock session or pass company/branch explicitly
   - Test business logic independently of HTTP

**New Module (e.g., Returns Processing):**

1. Create directories:
   - `app/Models/returns/` (e.g., `ReturnRequest.php`, `ReturnInspection.php`)
   - `app/Http/Controllers/operations/ReturnController.php` (if operations domain)
   - `app/Livewire/Operations/Returns/` (if operations UI)
   - `app/Services/Returns/` (if complex logic)
   - `resources/views/livewire/operations/returns/`

2. Follow patterns above for CRUD

3. Update `routes/web.php` with new prefix group:
   ```php
   Route::prefix('returns')->group(function () {
       Route::get('/', [ReturnController::class, 'index'])->name('returns.index');
       // ...
   });
   ```

4. Add permissions to `RoleSeeder.php`

**Utilities (Helpers, Traits):**

- Shared traits: `app/Traits/YourTrait.php` (e.g., `app/Traits/Sunat/HasInvoiceTrait.php`)
- Helpers: `app/Helpers/YourHelper.php` (if used across multiple services)
- Macros: Register in service provider (e.g., `Str::macro(...)` in `AppServiceProvider`)

## Special Directories

**`storage/app/livewire/`:**
- Purpose: Temporary Livewire file uploads
- Generated: Yes (Livewire automatically creates on upload)
- Committed: No (in `.gitignore`)
- Cleanup: `PurgeLivewireTemp` console command (runs daily, deletes files > 24 hours old)

**`bootstrap/cache/`:**
- Purpose: Cached configuration, routes, views
- Generated: Yes (by Laravel during startup)
- Committed: No (in `.gitignore`)
- Clear: `php artisan cache:clear`, `php artisan view:clear`

**`.env` and `.env.example`:**
- Purpose: Environment configuration (secrets, external API keys, DB connection)
- Generated: No (developer creates from `.env.example`)
- Committed: `.env.example` only (in git); `.env` in `.gitignore`
- Contains: `DB_CONNECTION`, `SUNAT` credentials, `DO_SPACES_*`, `API_TOKEN` for Decolecta, external dispatch API

**`node_modules/` and `vendor/`:**
- Purpose: Third-party dependencies
- Generated: Yes (npm/composer install)
- Committed: No (in `.gitignore`)
- Source of truth: `package.json`, `composer.json`

**`.planning/codebase/`:**
- Purpose: GSD codebase documentation (architecture, structure, conventions, testing, concerns)
- Generated: No (manually created by Claude)
- Committed: Yes
- Contents: ARCHITECTURE.md, STRUCTURE.md, CONVENTIONS.md, TESTING.md, CONCERNS.md

---

*Structure analysis: 2026-07-24*
