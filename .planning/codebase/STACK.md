# Technology Stack

**Analysis Date:** 2026-07-24

## Languages

**Primary:**
- PHP 8.2+ - Backend (Laravel 11 application)
- HTML/Blade - Server-side templates (views in `resources/views/`)
- JavaScript/TypeScript - Frontend interactivity (via Livewire components)
- CSS/Tailwind - Styling (Tailwind CSS 3.4.0 with forms and typography plugins)

**Secondary:**
- SQL - Database queries (migrations in `database/migrations/`)

## Runtime

**Environment:**
- PHP 8.2+ (configured in `composer.json` as `^8.2`)
- Node.js/npm - Frontend build toolchain

**Package Manager:**
- Composer - PHP dependencies
  - Lockfile: `composer.lock` (present)
- npm - Node.js dependencies
  - Lockfile: `package-lock.json` (present)

## Frameworks

**Core:**
- Laravel 11.9+ - Web framework and ORM (`laravel/framework`)
- Livewire 3.0 - Real-time reactive components (`livewire/livewire`)
- Laravel Jetstream 5.2 - Authentication scaffolding + team management (`laravel/jetstream`)

**UI Components & Styling:**
- WireUI 2.1 - Tailwind UI component library (`wireui/wireui`)
- Tailwind CSS 3.4.0 - Utility-first CSS framework
- Tailwind Forms 0.5.7 - Form styling plugin
- Tailwind Typography 0.5.10 - Prose styling plugin
- Autoprefixer 10.4.16 - CSS vendor prefixes

**Build & Dev Tools:**
- Vite 6.3.6 - Frontend build tool and dev server
- Laravel Vite Plugin 1.0 - Laravel integration for Vite
- PostCSS 8.4.32 - CSS transformations

**Testing:**
- PHPUnit 11.0.1 - Unit and feature testing framework (`phpunit/phpunit`)
- Mockery 1.6 - Object mocking (`mockery/mockery`)
- Faker - Test data generation (`fakerphp/faker` 1.24)
- Laravel Sail 1.26 - Docker dev environment (`laravel/sail`)
- Collision 8.1 - Error formatting (`nunomaduro/collision`)

**Code Quality:**
- Laravel Pint 1.13 - Code style fixer (`laravel/pint`, PSR-12 compliant)

## Key Dependencies

**Critical:**
- Greenter (SUNAT e-invoicing) - Peruvian tax invoice/despatch generation
  - `greenter/lite` 5.1 - Core SUNAT document models
  - `greenter/report` 5.1 - Report/PDF rendering
  - `greenter/htmltopdf` 5.1 - HTML-to-PDF conversion for invoices
- Laravel Sanctum 4.0 - API token authentication and CSRF protection
- Tymon JWT Auth 2.1 - JWT token authentication for mobile/external APIs
- Spatie Laravel Permission 6.22 - Role-based access control
- Rappasoft Laravel Livewire Tables 3.4 - Data table component with Livewire

**Document Generation & Export:**
- barryvdh/laravel-dompdf 3.1 - PDF generation (cash box reports, inspection PDFs)
- Maatwebsite Excel 3.1 - Excel import/export (`maatwebsite/excel`)
- Simple QR Code 4.2 - QR code generation (`simplesoftwareio/simple-qrcode`)
- Numero a Letras 4.0 - Spanish number-to-words conversion

**Infrastructure & Integration:**
- Doctrine DBAL 4.3 - Database abstraction (`doctrine/dbal`)
- League Flysystem AWS S3 v3 - S3/DigitalOcean Spaces adapter (`league/flysystem-aws-s3-v3`)
- Guzzle HTTP (implicit via Laravel) - HTTP client for external APIs

**Frontend:**
- Axios 1.10.0 - HTTP client (in `devDependencies` but used in production)
- Browser Image Compression 2.0.2 - Client-side image compression
- Luxon 3.7.2 - Date/time manipulation

**Development Only:**
- Laravel Lang 6.1 - Multilingual support
- Tinker 2.9 - Interactive shell (`laravel/tinker`)

## Configuration

**Environment:**
- `.env` file (present) - Application environment variables
  - Database connection details
  - Mail configuration
  - API credentials for external services
  - File storage (DigitalOcean Spaces or local)
- `.env.example` (present) - Template for environment setup

**Build:**
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

**Development:**
- PHP 8.2+ CLI
- Composer (PHP dependency manager)
- Node.js 16+ (for Vite and npm)
- npm 7+ (frontend package manager)
- SQLite or MySQL/MariaDB database
- wkhtmltopdf binary (for SUNAT invoice PDF generation) — configured via `WKHTMLTOPDF_BINARIES` env var

**Production:**
- PHP 8.2+ with FPM or similar
- MySQL 5.7+, MariaDB 10.3+, or PostgreSQL 9.6+
- Node.js (for frontend builds, or pre-build assets)
- DigitalOcean Spaces account (for file storage)
- wkhtmltopdf binary installed on server
- SUNAT certificates and credentials (stored on Company model)

---

*Stack analysis: 2026-07-24*
