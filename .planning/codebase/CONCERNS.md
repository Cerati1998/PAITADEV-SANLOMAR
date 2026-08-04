# Codebase Concerns

**Analysis Date:** 2026-07-24

## Tech Debt

**Debug Mode Enabled in Production Code:**
- Issue: Greenter XML generation library has `'debug' => true` hardcoded for all SUNAT e-invoicing operations
- Files: `app/Services/Sunat/UtilService.php` (line 63), `app/Traits/Sunat/UtilTrait.php` (line 50)
- Impact: Increases memory usage, slows down invoice/despatch generation in production, may output sensitive debug data
- Fix approach: Use env variable `GREENTER_DEBUG` (default false) to control debug mode per environment

**SUNAT Credentials Stored Unencrypted:**
- Issue: SOL credentials (`sol_user`, `sol_pass`) and API credentials (`client_id`, `client_secret`) stored directly as plaintext columns on `Company` model
- Files: `app/Models/Company.php` (SUNAT columns), `app/Services/Sunat/UtilService.php` (lines 41-74)
- Impact: High security risk if database is compromised; credentials directly readable in DB dumps, backups, or via SQL injection
- Fix approach: Encrypt these columns using Laravel's encrypted casting; migrate to encrypted secrets vault (e.g., env vars for active company/branch only)

**Hardcoded Test Credentials in Service Logic:**
- Issue: Test SUNAT credentials hardcoded in production service fallback paths (lines 67-68, 72-73 of `UtilService.php`)
- Files: `app/Services/Sunat/UtilService.php`
- Impact: Test credentials leak if code is exposed; test API calls may interfere with production if production flag misconfigured
- Fix approach: Remove hardcoded test values; pull from environment or config file only; fail fast if production flag doesn't match credentials source

**Monolithic Livewire Components (Code Complexity):**
- Issue: Several Livewire table/form components exceed 900+ lines, bundling table rendering, modals, multiple forms, and business logic
- Files:
  - `app/Livewire/Operations/Works/WorkOrderTable.php` (1085 lines)
  - `app/Livewire/Operations/Inspections/EirEdit.php` (963 lines)
  - `app/Livewire/Forms/WorkOrderForm.php` (772 lines)
  - `app/Livewire/Forms/InvoiceForm.php` (586 lines)
  - `app/Livewire/GenerateInvoice.php` (542 lines)
- Impact: Difficult to test, refactor, or debug; high cognitive load; more prone to hidden bugs in complex conditional flows; slow Livewire hydration cycles
- Fix approach: Extract modal/form logic into separate Livewire components; move business logic to service classes; reduce to <400 lines per component where possible

## Known Bugs

**Operator Precedence Bug in SunatService URL Construction:**
- Symptoms: API calls to external SUNAT/Decolecta services may fail silently if env vars are not set, resulting in malformed URLs like `?numero=12345` instead of full endpoint
- Files: `app/Services/SunatService.php` (lines 18, 23, 28)
- Trigger: When `env('API_RUC')`, `env('API_DNI')`, or `env('API_TC')` return null, the `??` operator is applied AFTER string concatenation due to operator precedence; results in a partial URL being passed to `request()`
- Example: `env('API_RUC') . "?numero={$ruc}" ?? "https://..."` parses as `(env(...) . "?numero=...") ?? "..."`, so null concatenated with string = incomplete URL
- Workaround: Ensure all three API env vars (`API_RUC`, `API_DNI`, `API_TC`) are always set in `.env`; monitor logs for malformed URLs
- Fix approach: Use explicit null checks: `$url = (env('API_RUC') ?? 'https://api.decolecta.com/v1/sunat/ruc/full') . "?numero={$ruc}"`

**Session Branch Not Validated Against Company/User:**
- Symptoms: Session can retain a branch_id that no longer belongs to the current company or user, leading to data leakage or unauthorized access
- Files: `app/Http/Middleware/CheckCompanySelected.php` (lines 30-39)
- Trigger: User selects company A, branch X is auto-set. Later, admin removes user from company A in pivot table but session still holds stale branch X reference. If user navigates to a route that checks only `session('branch')` without re-validating, they may access data from the wrong branch
- Workaround: Always include explicit branch ownership checks in queries; use `session('branch')->id` in database WHERE clauses, not as direct references
- Fix approach: After company validation (line 55-61), add matching branch validation: `$branch = auth()->user()->branches()->wherePivot('company_id', $companyId)->find(session('branch')?->id); if (!$branch) session()->forget('branch');`

**Missing Fillable/Guarded on Multiple Models:**
- Symptoms: Models without explicit mass assignment protection can be exploited if request data is passed directly to `create()`/`update()`
- Files: 18 models lack `protected $fillable` or `protected $guarded`:
  - `app/Models/works/Worker.php` uses public $fillable (weaker than protected)
  - `app/Models/Country.php`, `Currency.php`, `District.php`, `Document.php`, `Identity.php`, `Operation.php`, `PaymentMethod.php`, etc.
- Impact: Privilege escalation if hidden fields (e.g., `is_admin`, `role_id`) exist in request payload; unintended data corruption
- Fix approach: Add `protected $fillable = [...]` or `protected $guarded = ['id']` to all models; audit request data handling in controllers

## Security Considerations

**Certificate Handling Without Validation:**
- Risk: SUNAT certificate file_get_contents() called without checking file existence or validity; fallback to public demo certificate on non-production paths
- Files: `app/Services/Sunat/UtilService.php` (lines 38, 75); `app/Traits/Sunat/UtilTrait.php`
- Current mitigation: Env variable check for production vs. beta mode
- Recommendations:
  - Validate certificate file exists and is readable before signing documents
  - Check certificate expiry; alert if expiring within 30 days
  - Store production certificates outside of web root or in encrypted blob storage
  - Add certificate rotation mechanism

**File Upload Without MIME Type Validation:**
- Risk: Livewire file uploads (EIR photos, roof evidence, labor photos, logos) only check file size, not MIME type or content
- Files:
  - `app/Services/inspections/EirPhotoService.php`
  - `app/Services/inspections/RoofEvidencePhotoService.php`
  - `app/Services/WorkOrders/WorkLaborPhotoService.php`
- Current mitigation: Stored in S3-compatible storage (Spaces) with public/private disk configuration
- Recommendations:
  - Validate MIME type (image/* only) on upload
  - Scan uploaded files with antivirus/malware detection for production
  - Rename files to random UUID to prevent directory traversal
  - Set strict Content-Disposition headers on download

**External API Token Exposure in Logs:**
- Risk: GuzzleHttp exceptions logged with full response body; API tokens in Authorization headers may be logged
- Files: `app/Services/ExternalDispatchApiService.php` (line 48), `app/Console/Commands/SyncDispatchedContainers.php` (lines 92-97)
- Current mitigation: Try-catch with logging only message, not full exception
- Recommendations:
  - Sanitize logged exceptions to remove Authorization headers
  - Use Laravel's built-in sensitive field masking in logs
  - Never log response body containing authentication tokens

**Stored Company Credentials Accessible via API:**
- Risk: If API endpoints expose company data (e.g., `GET /api/company`), SUNAT credentials would be included in JSON response
- Files: Routes in `routes/api.php` (if present); `app/Http/Controllers/Api/*`
- Current mitigation: Unknown; needs audit of API responses
- Recommendations:
  - Create separate API models without sensitive fields (`Company::select(['id', 'name', 'ruc', ...])`)
  - Use transformer/resource classes to explicitly control serialization
  - Never include sol_user, sol_pass, client_secret, or certificate in API responses

## Performance Bottlenecks

**N+1 Queries in Livewire Table Rendering:**
- Problem: Some Livewire table components may execute query-per-row for related data (e.g., accessing `$row->container->code`, `$row->eirInspection->serie` without eager loading)
- Files: `app/Livewire/Operations/Works/WorkOrderTable.php` - checks eager loading with `with()` but also uses filters that may trigger additional queries
- Cause: Filter logic using `whereHas()` on every filter application without caching; row labels calling relationships without guarantee of eager loading
- Improvement path:
  - Audit all column label closures for unguarded relationship access
  - Add query logging in tests to detect N+1s
  - Cache filter options (shipping lines, methods, statuses) using Redis with 30-min TTL
  - Implement query limit of 1000 rows max per page

**Complex Filter Queries with Multiple Joins:**
- Problem: WorkOrderTable shipping line filter executes DB::table() with 4+ table joins and UNION operations on every filter render
- Files: `app/Livewire/Operations/Works/WorkOrderTable.php` (lines 293-320)
- Cause: Recalculating filter options dynamically instead of caching; no pagination limit on results returned to dropdown
- Improvement path:
  - Cache getShippingLinesOptions() result using `Cache::remember()` with branch_id as key (already implemented, good)
  - Consider reducing UNION query to single Eloquent query with proper eager loading
  - Add indexes on foreign key columns: `dischargues(branch_id, shipping_line_id)`, `devolutions(branch_id, shipping_line_id)`

**Invoice Generation Service Without Pagination/Batch Processing:**
- Problem: `GenerateInvoice.php` and `DocumentService.php` load all invoice details into memory when generating Greenter XML objects
- Files: `app/Livewire/GenerateInvoice.php`, `app/Services/Sunat/DocumentService.php` (line 91 loads all details)
- Cause: If invoice has 100+ line items, XML building and PDF generation consume memory and time
- Improvement path:
  - Implement streaming XML generation for large documents
  - Batch PDF generation via queue job instead of synchronous in Livewire
  - Add invoice line limit (e.g., max 500 lines per invoice) with validation

**File Storage Operations Without Concurrent Request Handling:**
- Problem: Livewire file uploads trigger `file_get_contents()` and `Storage::disk()->get()` sequentially; if multiple users upload simultaneously, server I/O becomes bottleneck
- Files: `app/Services/*PhotoService.php` (lines 81, 92-93 doing blocking file I/O)
- Cause: Livewire's synchronous request model with file I/O operations
- Improvement path:
  - Move photo processing to background job queue (Laravel Queue)
  - Use chunked upload (Dropzone.js, Livewire chunked upload) for large files
  - Add database index on `(branch_id, created_at)` for photo queries

## Fragile Areas

**Authorization Relies on Session Company/Branch Without Central Validation:**
- Files: `app/Http/Middleware/CheckCompanySelected.php` + usage throughout codebase (queries filter on `session('branch')->id`)
- Why fragile: If middleware is bypassed (via API route without middleware, or custom controller not using middleware), entire authorization breaks. No centralized "current tenant" resolver exists.
- Safe modification: Before adding new routes/controllers, ensure they use middleware stack or explicitly validate branch ownership. Create Laravel Scope: `class Branch extends Model { protected static function booted() { static::addGlobalScope(fn($q) => $q->where('id', session('branch')?->id)) } }` to enforce at model level.
- Test coverage: Zero tests for multi-tenant isolation; no tests verifying users from company A cannot access company B's data.

**SUNAT e-invoicing State Machine (Draft → Approved → Sent → Voided):**
- Files: `app/Models/Invoice.php`, `app/Livewire/GenerateInvoice.php`, `app/Services/Sunat/DocumentService.php`
- Why fragile: Complex conditional logic for invoice status transitions scattered across Livewire component and service; no formal state machine or event system. Edge cases: what if send-to-SUNAT fails halfway? Can user re-send? Does draft remain locked?
- Safe modification: Create dedicated `InvoiceStateService` or Statemachine package (`spatie/laravel-statemachine`). Write state transition tests.
- Test coverage: Minimal tests for invoice lifecycle; no tests for concurrent invoice generation or SUNAT failure scenarios.

**Photo Storage Paths Stored as Strings (eirPhotoPath, roofEvidencePath, etc.):**
- Files: Models `EirInspection.php`, `RoofEvidence.php`, `WorkOrderLabor.php` store paths as string columns
- Why fragile: If storage disk configuration changes (S3 → local, or S3 bucket renamed), all stored paths become invalid. No migration path to rekey paths. Deletion logic scattered across multiple PhotoService classes.
- Safe modification: Implement photo service with centralized path/disk logic. Create database migration to safely migrate paths if storage changes. Add `softDelete` to photo models to allow recovery.
- Test coverage: No tests for storage path updates or disk configuration changes.

**Dispatch Sync Command External API Dependency:**
- Files: `app/Console/Commands/SyncDispatchedContainers.php`, `app/Services/ExternalDispatchApiService.php`
- Why fragile: If external API is down or rate-limited, command fails silently after timeout. No retry logic, exponential backoff, or notification to admin. Week number calculation is ISO 8601 dependent.
- Safe modification: Wrap sync in retry logic (Spatie/Retry package). Add Slack notification on failure. Implement queue job instead of console command for better error tracking.
- Test coverage: No tests for API failure scenarios or week number edge cases (e.g., year boundary).

**Database Constraints for Cascade Deletes:**
- Files: Migrations with mixed `cascadeOnDelete` and `onDelete('no action')` strategies
- Why fragile: Some tables (e.g., invoices → company) use `onDelete('no action')`, preventing company deletion even if all invoices deleted. Inconsistent strategy across tables makes refactoring risky.
- Safe modification: Audit all foreign key constraints; standardize on either cascade or no-action per relationship type. Add soft deletes to invoice-like documents to prevent accidental hard deletion.
- Test coverage: No tests for cascade/no-action behavior or orphaned records.

## Scaling Limits

**Single Database Connection for Multi-Tenant Queries:**
- Current capacity: Designed for ~50-100 concurrent users per branch; query complexity grows with container count and invoice volume
- Limit: At 10,000+ containers per branch or 50,000+ invoices, table scans on discharge/devolution imports and container trace queries will timeout
- Scaling path:
  - Add database read replicas for reporting/trace queries
  - Implement database indexing strategy: `(branch_id, created_at)`, `(company_id, status)`
  - Partition large tables by year (invoices) or branch (all operational tables)
  - Consider CQRS pattern for read-heavy operations (traces, reports)

**Greenter XML Signing Single-Threaded:**
- Current capacity: ~20 invoices/despatches per minute (varies by CPU and certificate handling)
- Limit: If batch sending (e.g., end-of-day 500 invoices), will take 25+ minutes synchronously; users see loading spinner for extended periods
- Scaling path:
  - Move signing to background job queue (Laravel Queue with multiple workers)
  - Pre-generate XML in Livewire, queue signing/sending
  - Implement bulk SUNAT send endpoint (if available) to send multiple documents in one API call

**File Storage I/O Bottleneck:**
- Current capacity: ~100 photos per minute upload in Livewire (blocking I/O)
- Limit: Concurrent photo uploads from multiple users queue up; disk I/O becomes bottleneck
- Scaling path:
  - Use chunked upload to S3 with multipart API (AWS SDK)
  - Offload thumbnail generation to background job
  - Implement CDN caching (CloudFront) for photo delivery

## Dependencies at Risk

**Greenter Library (SUNAT e-invoicing):**
- Risk: Community-maintained PHP library for SUNAT UBL generation; SUNAT API changes periodically, and Greenter may not update immediately
- Impact: If SUNAT adds new mandatory fields or changes validation rules, invoices will fail to send until Greenter updates
- Migration plan: Monitor Greenter GitHub releases monthly; maintain version lock in composer.json; test with SUNAT beta API quarterly; consider commercial SUNAT provider (Nubefact, Facturación.pe) as fallback
- **Reduced surface (2026-08-03):** Greenter is no longer used to *generate* anything. XML and report templates are repo-owned (`app/Services/Sunat/Templates/`, `public/custom/pdf/`) and rendered by `SunatXmlBuilder`; Greenter now only provides models, signing, submission and CDR parsing. Adapting to a SUNAT UBL change no longer requires waiting for an upstream release — the template can be edited in this repo. See ARCHITECTURE.md, "SUNAT templates are repo-owned".

**Hand-patched `vendor/` — RESOLVED (2026-08-03):**
- Was: SUNAT XML and PDF output depended on files edited by hand inside `vendor/greenter`, which is gitignored. The first Deployer run executed `composer install`, restored pristine code and silently wiped every patch.
- Impact at the time: two production incidents in one day (fatal `Invoice::setTipoCambio()` breaking all invoice/note emission; detraction printed in USD on four invoices) plus one silent regression (`<cac:LoadingTransportEvent>` missing from despatch XML, invisible on the PDF).
- Resolution: all four patched files were audited against pristine `v5.1.1` sources and moved into the repo. Verified with a 40-document byte-for-byte diff against Greenter's builders, plus marker-injection tests proving the system reads repo templates and not `vendor/`.
- Residual risk: **editing `vendor/greenter` again**. There is no automated guard yet — a regression test over the repo templates (well-formed XML + presence of critical nodes such as `LoadingTransportEvent`) is the open follow-up.

**Jetstream (Authentication/Authorization):**
- Risk: Laravel Jetstream deprecated in favor of Breeze; support and community updates may slow
- Impact: Security vulnerabilities in Jetstream may not be patched; future Laravel versions may drop Jetstream support
- Migration plan: Plan migration to Laravel Breeze or custom auth by Laravel 12; audit Jetstream code for custom changes before migration

**Rappasoft Laravel Livewire Tables:**
- Risk: Community package; if abandoned, table rendering and filtering will be broken on Livewire 4 upgrade
- Impact: Cannot easily upgrade to newer Livewire versions without rewriting all table components
- Migration plan: Evaluate Wireui table component (already in stack); gradually migrate large tables to native Livewire v3 components or consider re-implementing with Alpine.js + Tailwind tables

**WireUI (UI Component Library):**
- Risk: Community-maintained; no SLA; if abandoned, theming and component updates stop
- Impact: UI consistency issues on future Tailwind/Livewire updates; custom styling workarounds accumulate
- Migration plan: None currently required; stay on v0.x; monitor GitHub for maintenance; be prepared to fork components if needed

## Missing Critical Features

**Audit Trail / Change Log:**
- Problem: No audit log for changes to invoices, work orders, or dispatch records. Cannot answer "who changed this invoice status and when?"
- Blocks: Compliance audits, dispute resolution, forensic analysis of data integrity issues
- Solution: Implement `spatie/laravel-activitylog` or custom audit table to log all model changes with user ID, timestamp, and old/new values

**Backup & Disaster Recovery Plan:**
- Problem: No documented backup strategy; S3 (DO Spaces) may have auto-backups, but local database backup frequency unknown
- Blocks: Recovery from data corruption, ransomware, or accidental deletion; RTO/RPO undefined
- Solution: Implement daily database backups (mysqldump to S3); test restore procedure monthly; document RTO/RPO SLAs

**Bulk Invoice Re-sending / SUNAT Error Recovery:**
- Problem: If SUNAT API is down or rejects 100 invoices due to validation error, no bulk re-try mechanism exists. Manual re-send of each invoice required.
- Blocks: Quick recovery from SUNAT incidents; delays in revenue recognition if invoices cannot be sent
- Solution: Create admin panel action "Re-send Rejected Invoices" that queues failed invoices for re-sending with exponential backoff

**Rate Limiting on External API Calls:**
- Problem: No rate limiting on SunatService or ExternalDispatchApiService; if code is called in loop, external API may block IP
- Blocks: Scheduled sync jobs may intermittently fail if rate limit hit
- Solution: Implement Guzzle middleware for rate limiting; add retry-after header support

**Localized Error Messages for End Users:**
- Problem: Exceptions and validation errors returned in English or mix of Spanish/English; no i18n strategy
- Blocks: User experience in multi-language deployments; support costs for translation
- Solution: Use Laravel's localization in validation rules and exception messages; define `resources/lang/es/validation.php` for Spanish translations

## Test Coverage Gaps

**Untested Multi-Tenant Isolation:**
- What's not tested: User from company A accessing company B's data through API or direct URL; authorization middleware bypass scenarios; branch validation gaps
- Files: No tests in `tests/Feature/` for tenant isolation; `CheckCompanySelected.php` has zero test coverage
- Risk: Data leakage between customers; regulatory compliance failure if customer data is mixed
- Priority: **High** — Security-critical; add tests:
  - User from company A cannot fetch company B's invoices via API
  - Session branch swap doesn't expose unauthorized data
  - Discharges/devolutions filtered by branch isolation

**Untested SUNAT Integration:**
- What's not tested: Invoice signing with real certificate; sending to SUNAT beta API; handling SUNAT rejection codes; despatch guide generation; credit note state transitions
- Files: Zero tests in `tests/Feature/` for SUNAT features; `DocumentService.php` has zero test coverage
- Risk: Invoice generation silently fails in production; SUNAT state mismatches; invalid documents sent
- Priority: **High** — Revenue-critical; add tests:
  - Generate invoice XML with Greenter, validate structure
  - Send to SUNAT beta API (use test credentials)
  - Handle SUNAT error responses (malformed RUC, invalid dates)
  - Verify PDF generation works without wkhtmltopdf binary crashing

**Untested External API Integrations:**
- What's not tested: ExternalDispatchApiService failure scenarios (timeout, 404, 500, rate limit); retry logic; week number edge cases (year boundary); partial sync failure
- Files: `app/Console/Commands/SyncDispatchedContainers.php`, `app/Services/ExternalDispatchApiService.php` have zero test coverage
- Risk: Silent sync failures; stale dispatch data; no alerting to operations team
- Priority: **High** — Data consistency-critical; add tests:
  - Mock API timeout, verify error logged
  - Week 53/52 boundary calculations correct
  - Partial sync (API returns 50 containers, 2 already exist) handled correctly

**Untested Authorization/Permission System:**
- What's not tested: Role-based access control (RBAC) enforcement; permission middleware; branch-level permissions; user role changes mid-session
- Files: `app/Http/Controllers/RoleController.php`, `database/seeders/RoleSeeder.php` exist but no tests; permission middleware uses `can:` gates with no test coverage
- Risk: User with 'view_invoices' role can create invoices; permission checks inconsistently applied
- Priority: **Medium** — Security; add tests:
  - User without 'invoices.create' permission cannot POST to /vouchers
  - Role changes take effect on next request (not cached indefinitely)
  - Branch permissions enforced (e.g., user_branch A cannot see user_branch B's work orders)

**Untested Photo Upload & Storage:**
- What's not tested: File upload with oversized files; non-image files; S3 disk configuration; photo retrieval and deletion
- Files: `app/Services/inspections/EirPhotoService.php`, `WorkLaborPhotoService.php`, `RoofEvidencePhotoService.php` have zero test coverage
- Risk: Upload accepts executable files; missing files cause 500 errors; storage disk misconfigurations silently fail
- Priority: **Medium** — Data loss risk; add tests:
  - Reject file > 10MB
  - Reject .exe, .zip, .pdf files
  - Store file in S3 with correct path structure
  - Retrieve file from S3 and verify content

**Untested Form Validation:**
- What's not tested: WorkOrderForm, InvoiceForm, DespatchForm validation rules (770+ lines of form code)
- Files: `app/Livewire/Forms/*.php` have zero test coverage for validation
- Risk: Invalid data saved to database (e.g., negative amounts, invalid dates, missing required fields)
- Priority: **Low** — Data quality; add basic validation tests:
  - Invoice amount must be >= 0
  - Dispatch date cannot be in future
  - Container code is required

---

*Concerns audit: 2026-07-24*
