# External Integrations

**Analysis Date:** 2026-07-24

## APIs & External Services

**SUNAT e-Invoicing (Peru):**
- Greenter SUNAT integration - Electronic invoicing for Peru (facturas, boletas, notas, guías de remisión)
  - SDK/Client: `greenter/lite`, `greenter/report`, `greenter/htmltopdf`
  - Auth: SOL credentials (Usuario SOL, Contraseña SOL, Client ID, Client Secret)
  - Implementation: `app/Services/Sunat/DocumentService.php`, `app/Services/Sunat/SummaryService.php`, `app/Services/Sunat/VoidedService.php`
  - Certificates: Company's digital certificate (stored in database)
  - Models: `app/Models/Invoice`, `app/Models/Despatch`, `app/Models/Voided`, `app/Models/Detraction`, `app/Models/Perception`
  - Production mode flag: `Company.production` column

**Decolecta API (SUNAT Data):**
- RUC/DNI lookup and tax rate queries
  - SDK/Client: Guzzle HTTP (native in Laravel)
  - Auth: Bearer token via `API_TOKEN` env var
  - Base URL: `API_RUC`, `API_DNI`, `API_TC` env vars
  - Endpoints:
    - RUC lookup: `GET /v1/sunat/ruc/full?numero={ruc}`
    - DNI lookup: `GET /v1/reniec/dni?numero={dni}`
    - Exchange rate: `GET /v1/tipo-cambio-sunat?fecha={fecha}`
  - Implementation: `app/Services/SunatService.php` (methods: `consultarRUC()`, `consultarDNI()`, `consultarTC()`)

**External Dispatch API:**
- Syncs container dispatch data from external logistics system
  - SDK/Client: Guzzle HTTP
  - Auth: Bearer token via `EXTERNAL_DISPATCH_API_TOKEN` env var
  - Base URL: `EXTERNAL_DISPATCH_API_URL` env var
  - Endpoint: `GET /SIGLA/controller/api.php?op=GetPerWeek&week={week}`
  - Implementation: `app/Services/ExternalDispatchApiService.php` (method: `getContainersByWeek()`)
  - Triggered by: `SyncDispatchedContainers` console command (`app/Console/Commands/SyncDispatchedContainers.php`)

## Data Storage

**Databases:**
- Primary: MySQL 5.7+ (production)
  - Configured via `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`
  - Connection type: `DB_CONNECTION=mysql`
  - Charset: `utf8mb4`, Collation: `utf8mb4_unicode_ci` (default, configurable via `DB_CHARSET`, `DB_COLLATION`)
- Alternative: SQLite (development, can be used in production)
  - Default path: `database/database.sqlite`
  - Configured via `DB_DATABASE` env var
- Other supported: MariaDB 10.3+, PostgreSQL 9.6+, SQL Server
- Client: Laravel Eloquent ORM + Laravel Query Builder

**File Storage:**
- DigitalOcean Spaces (production)
  - Disk name: `do`
  - Configuration: `config/filesystems.php`
  - Required env vars: `DO_ACCESS_KEY_ID`, `DO_SECRET_ACCESS_KEY`, `DO_SPACES_REGION`, `DO_SPACES_BUCKET`, `DO_SPACES_URL`, `DO_SPACES_ENDPOINT`
  - S3-compatible driver: `league/flysystem-aws-s3-v3`
  - Used for: EIR photos, roof evidence photos, work order labor photos, company logos
- Local filesystem (development/testing)
  - Disk name: `local`, `public`
  - Root paths: `storage/app/private`, `storage/app/public`
  - Livewire temporary uploads: `LIVEWIRE_UPLOAD_DISK=local` (configured)
  - Cleanup: `app/Console/Commands/PurgeLivewireTemp.php`, `PurgeLivewireImports.php`

**Caching:**
- Redis (optional, for performance)
  - Configuration: `config/database.php` (Redis section)
  - Env vars: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_CLIENT`, `REDIS_DB`, `REDIS_CACHE_DB`
  - Alternative: Database-backed cache, Array (testing)
- Cache driver: `CACHE_STORE` env var (default: `file`, configurable to `redis`, `database`)

## Authentication & Identity

**Web Application Authentication:**
- Provider: Laravel Jetstream + Fortify
  - Implementation: `config/jetstream.php`, `config/fortify.php`
  - Guard: `web` (session-based)
  - Model: `App\Models\User`
  - Session storage: `SESSION_DRIVER` env var
  - Multi-company context: `session('company')`, `session('branch')` (set by `CheckCompanySelected` middleware in `app/Http/Middleware/CheckCompanySelected.php`)

**Mobile/API Authentication:**
- JWT (JSON Web Tokens) for external/mobile APIs
  - Provider: Tymon JWT Auth 2.1 (`tymon/jwt-auth`)
  - Guard: `jwt` (used in `app/Http/Controllers/Api/*`)
  - Configuration: `config/jwt.php`
  - Secret: `JWT_SECRET` env var (or RSA keys via `JWT_PUBLIC_KEY`, `JWT_PRIVATE_KEY`, `JWT_PASSPHRASE`)
  - TTL: `JWT_TTL` (default 60 minutes)
  - Refresh TTL: `JWT_REFRESH_TTL` (default 20160 minutes / 14 days)
  - Algorithm: `JWT_ALGO` (default HS256)
  - Blacklist: `JWT_BLACKLIST_ENABLED=true`
  - Endpoints: `routes/api.php`
    - `POST /login` → JWT token
    - `POST /refresh` → Refresh expired token
    - `POST /logout` → Invalidate token

**API Token Authentication (Sanctum):**
- Provider: Laravel Sanctum 4.0
  - For server-to-server API calls
  - Guard: `sanctum` (used in some API routes)
  - Configuration: `config/sanctum.php`

**Authorization:**
- Role-based access control: Spatie Laravel Permission 6.22
  - Configuration: `config/permission.php`
  - Models: `app/Models/Role`, `app/Models/Permission`
  - Middleware: `can:<permission>` (e.g., `can:clients`, `can:branches.series`)
  - Permission seeding: `database/seeders/RoleSeeder.php`

## Monitoring & Observability

**Error Tracking:**
- Not detected (built-in Laravel logging used)

**Logs:**
- Laravel's default logging stack
  - Configuration: `config/logging.php`
  - Channels: `single`, `daily`, `slack`, `syslog`, `errorlog`
  - Driver: `LOG_CHANNEL` env var
  - Level: `LOG_LEVEL` env var (default `debug`, set to `warning` or `error` in production)
  - Path: `storage/logs/`
- Service-specific logging:
  - External API errors logged via `Illuminate\Support\Facades\Log` in `app/Services/ExternalDispatchApiService.php`, `app/Services/SunatService.php`

## CI/CD & Deployment

**Hosting:**
- Not configured in codebase (deployment handled externally)
- XAMPP local development observed (Windows environment)

**CI Pipeline:**
- None detected (no GitHub Actions, GitLab CI, or similar)

## Environment Configuration

**Required env vars (critical):**
- `APP_KEY` - Laravel encryption key (generated by `php artisan key:generate`)
- `APP_ENV` - Environment mode (`local`, `staging`, `production`)
- `DB_CONNECTION` - Database type (`mysql`, `sqlite`, etc.)
- `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` - Database credentials
- `DO_ACCESS_KEY_ID`, `DO_SECRET_ACCESS_KEY`, `DO_SPACES_REGION`, `DO_SPACES_BUCKET`, `DO_SPACES_ENDPOINT`, `DO_SPACES_URL` - DigitalOcean Spaces
- `JWT_SECRET` - JWT signing secret (or `JWT_PUBLIC_KEY`, `JWT_PRIVATE_KEY` for asymmetric)
- `API_RUC`, `API_DNI`, `API_TC` - Decolecta API endpoints
- `API_TOKEN` - Decolecta API authentication token
- `EXTERNAL_DISPATCH_API_URL`, `EXTERNAL_DISPATCH_API_TOKEN` - External dispatch API
- `WKHTMLTOPDF_BINARIES` - Path to wkhtmltopdf binary (required for SUNAT invoice PDFs)
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` - Email configuration
- `FILESYSTEM_DISK` - Default storage disk (`local` or `do`)
- `LIVEWIRE_UPLOAD_DISK` - Livewire temporary uploads disk (default `local`)

**Secrets location:**
- `.env` file (local) — never committed to git
- Environment variables (production) — injected at deployment

**Company-level SUNAT credentials** (stored in database, not env):
- `Company.sol_user` - SOL username
- `Company.sol_pass` - SOL password (encrypted at rest if using Laravel's encryption)
- `Company.client_id` - SUNAT OAuth client ID
- `Company.client_secret` - SUNAT OAuth client secret (encrypted at rest)
- `Company.certificate` - Digital certificate (encrypted at rest, stored as file path or BLOB)
- `Company.production` - Boolean flag for production vs. staging SUNAT

## Webhooks & Callbacks

**Incoming:**
- None detected

**Outgoing:**
- SUNAT document submissions (via Greenter)
  - Invoice/Despatch/Note/Summary/Voided submissions to SUNAT API (production or staging)
  - Response handling: `app/Http/Controllers/Api/InvoiceController`, `DespatchController`, `NoteController`, `SummaryController`, `VoidedController`

---

*Integration audit: 2026-07-24*
