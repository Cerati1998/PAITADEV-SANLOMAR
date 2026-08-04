# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

INSPECTEC SERVICES / SANLOMAR is a Laravel 11 mini-ERP for logistics companies operating in container terminals (unidades menores/terceras). It covers container discharge/devolution tracking, EIR/PTI/leak-test inspections, work orders (structural and machine repairs), dispatches, and SUNAT electronic invoicing (facturas, boletas, notas, guías de remisión) for Peru.

Stack: Laravel 11, Livewire 3 + `rappasoft/laravel-livewire-tables`, Jetstream (Fortify + Sanctum), WireUI, Tailwind, Spatie Laravel Permission, Greenter (SUNAT e-invoicing), Maatwebsite Excel, JWT Auth.

## Commands

Backend (PHP):
```
composer install
php artisan migrate
php artisan db:seed                      # runs DatabaseSeeder (companies, roles, catalogs, etc.)
php artisan serve
vendor/bin/pint                          # code style (Laravel Pint)
vendor/bin/phpunit                       # full test suite
vendor/bin/phpunit --filter TestName     # single test
vendor/bin/phpunit tests/Feature/SomeTest.php
```

Frontend (Vite/Tailwind):
```
npm install
npm run dev                              # Vite dev server
npm run build
```

There is no `phpunit.xml` sqlite/in-memory override enabled by default (those lines are commented out) — tests run against the configured `DB_CONNECTION` unless you uncomment them or set env vars.

## Architecture

### Multi-tenant "company/branch" context

Almost every authenticated route lives behind two middleware layers in `routes/web.php`: `auth`, then `CheckCompanySelected` (`app/Http/Middleware/CheckCompanySelected.php`). This middleware:
- Ensures the user has a `company` selected in the session (users belong to companies via a `belongsToMany` pivot on `Company`/`User`).
- Auto-selects the company and a `branch` in session when the user only belongs to one, otherwise redirects to `companies.index` for manual selection.
- Re-validates on every request that the session's company still belongs to the user (revokes it and redirects otherwise).

Because of this, most business logic (`Company`, `Branch`, invoicing config, series, DO Spaces paths, etc.) is scoped implicitly through `session('company')` / `session('branch')` rather than an explicit tenant column filter — look for `session('company')`/`session('branch')` reads throughout controllers, Livewire components, and services instead of assuming global-scope query scopes.

Permissions use Spatie `laravel-permission` (`config/permission.php`) with route-level `can:` middleware gates named after route names (e.g. `->middleware('can:clients')`, `->middleware('can:branches.series')`) — when adding a route, follow the existing `can:<route.name>` convention and add the matching permission to a seeder (`database/seeders/RoleSeeder.php`) rather than inventing ad-hoc ability names.

### Domain modules

- **Admisión / catalogs**: `ShippingLine`, `Vessel`, `Port`, `ContainerType`, `ReeferTechnology`, `ReeferCondition`, `ContainerLineCategory` — vessel/line/port master data used when discharging containers.
- **Container lifecycle**: `Dischargue` (discharge) and `Devolution` bring containers into the system in bulk (Excel import via `app/Imports/Discharge`, `app/Imports/Devolution`, downloadable templates in `resources/templates/`), tracked per-container via `ContainerOperationalTrace` (`app/Services/Container/ContainerOperationalTraceService.php`, `ContainerStatusService.php`) which drives the "containers-traces" status listing.
- **Inspections** (`app/Models/inspections/*`, `app/Livewire/Operations/Inspections/*`, `app/Http/Controllers/operations/InspectionController.php`): PTI, EIR (`EirInspection`/`EirInspectionDetail`, with detail photos via `EirPhotoService`), and leak tests, all keyed off a `ContainerOperationalTrace`. Roof evidence photos have a dedicated service/model (`RoofEvidencePhotoService`, `RoofEvidence`).
- **Work orders** (`app/Models/works/*`, `app/Livewire/Operations/Works/*`, `app/Http/Controllers/operations/WorkOrderController.php`): structural ("box") and machine ("mach") repair orders, workers/laborers, and labor photo uploads (`WorkLaborPhotoService`).
- **Dispatches** (`app/Models/Dispatches/*`, `app/Livewire/Operations/Dispatches/*`, `app/Http/Controllers/operations/DispatchController.php`): also synced from an external system via `SyncDispatchedContainers` console command and `ExternalDispatchApiService` (`EXTERNAL_DISPATCH_API_URL`/`EXTERNAL_DISPATCH_API_TOKEN` in `.env`).
- **Ventas / SUNAT e-invoicing**: `Invoice`, `Voided`, `Despatch` (guía de remisión), `Detraction`, `Perception` models; `app/Services/Sunat/*` (`DocumentService` builds Greenter model objects for invoices/despatches/notes/summaries, `SummaryService`, `VoidedService`, `UtilService`) plus `app/Traits/Sunat/*`. Company-level SUNAT credentials (`sol_user`, `sol_pass`, `client_id`, `client_secret`, `certificate`, `production` flag) live directly on the `Company` model. Branch-level series and external integrations are managed via `BranchController::series`/`integrations` and the `BranchIntegration`/`Integration` models.
- **Gastos**: `ArchingCash`, `Bill`, `CashDeposit` for cash-box/expense tracking.

### SUNAT output is generated from repo-owned templates — never edit `vendor/greenter`

Everything handed to SUNAT (signed XML) or to the customer (PDF/RIDE) is
rendered from Twig templates that live **in this repository**. Greenter is used
only for its models, XML signing (`SignedXml`), submission and CDR parsing.

| What | Where |
|------|-------|
| XML templates (invoice, notacr, notadb, despatch2022, voided, summary) | `app/Services/Sunat/Templates/` |
| XML builder for every document type | `app/Services/Sunat/Xml/SunatXmlBuilder.php` |
| Report templates + `assets/style.css` | `app/Services/Sunat/Templates/pdf/` |
| Guía transportista (document type Greenter does not model) | `app/Services/Sunat/Transportista/` |

All of them live under `app/` on purpose. The report templates used to sit in
`public/custom/pdf/`, inside the docroot, where the web server handed them out as
plain text to anyone requesting the `.twig` URL — exposing the RIDE structure and
the detraction logic. Do not move templates back into `public/`.

`vendor/` is gitignored and was historically uploaded to the VPS already
hand-patched. The first real Deployer run executed `composer install` and
silently destroyed every patch, causing two production incidents in one day (a
fatal `Invoice::setTipoCambio()` that broke all invoice and note emission, and
the detraction printed in USD when it is always in soles) plus one silent
regression: the `<cac:LoadingTransportEvent>` node vanished from the guía de
remisión remitente XML without any visible symptom, since it never appears on
the PDF.

When you need to change SUNAT output:

- Copy the template from `vendor/greenter/xml/src/Xml/Templates/` (or
  `vendor/greenter/report/src/Report/Templates/`) into the matching repo
  directory and edit the copy. Both loaders chain `[repo, vendor]`, so the repo
  copy wins and untouched templates keep resolving from the package.
- Do not add properties or setters to Greenter models. Data absent from them —
  `tipoCambio` is not on `Greenter\Model\Sale\BaseSale` — travels through report
  params and is read as `params.user.<field>`.
- After changing an XML template, diff the generated output against the previous
  one for real vouchers before shipping. That XML is what SUNAT receives.

Greenter's own builders (`InvoiceBuilder`, `NoteBuilder`, `DespatchBuilder`,
`VoidedBuilder`, `SummaryBuilder`) are no longer instantiated anywhere in
`app/`; `UtilService::getXmlSigned()` delegates to `SunatXmlBuilder`.

`getReportPdf()` and `getReportHtml()` (in `UtilService`, `UtilTrait` and
`UtilTransportistaService`) share the same template directory and the same
params — they used to diverge and produce different output for the same voucher.

### Controllers vs. Livewire split

Controllers (`app/Http/Controllers`) mostly render the outer Blade page (index/create scaffolding, PDF generation, report exports) while the interactive listing/CRUD UI lives in Livewire components (`app/Livewire/**`, paired with `app/Livewire/Forms/**` for Jetstream-style form objects, and Blade views under `resources/views/livewire/**`). When adding a new CRUD feature, expect to touch: a controller action for the page shell, a `*Table` Livewire component (via `rappasoft/laravel-livewire-tables`) for listing, and a `*Create`/`*Edit` Livewire component + Form object for the modal/form.

`app/Http/Controllers/operations/*` (lowercase namespace) hosts inspections/work-orders/dispatches controllers — note the lowercase `operations` segment when importing (`use App\Http\Controllers\operations\...`), which mirrors the routing group.

There is a second route file, `routes/admin.php`, serving a minimal `Admin\DashboardController` — check `bootstrap/app.php`/`RouteServiceProvider`-equivalent registration if extending the admin area, since it is not wired into `routes/web.php`.

### File storage

Photos (EIR detail photos, roof evidence, work-order labor photos) and company logos are stored via Laravel's filesystem abstraction and DigitalOcean Spaces (`DO_SPACES_*` env vars, S3-compatible driver) in production, with `LIVEWIRE_UPLOAD_DISK=local` for Livewire's temporary upload disk. `app/Console/Commands/PurgeLivewireTemp.php` and `PurgeLivewireImports.php` clean up stale Livewire temp uploads/imports.

### External integrations

- Decolecta API for RUC/DNI lookups and SUNAT exchange rate (`app/Services/SunatService.php`, env vars `API_RUC`, `API_DNI`, `API_TC`, `API_TOKEN`).
- `mikehaertl/phpwkhtmltopdf` + `barryvdh/laravel-dompdf` for PDF generation (EIR formats, cash reports, vouchers) — requires `WKHTMLTOPDF_BINARIES` to point at a local wkhtmltopdf binary.
- JWT (`tymon/jwt-auth`) backs `app/Http/Controllers/Api/*` for the mobile/external API surface (auth, despatch, invoice, note, voided, summary, company, phone-code, ubigeo endpoints), separate from the session-based web app.
