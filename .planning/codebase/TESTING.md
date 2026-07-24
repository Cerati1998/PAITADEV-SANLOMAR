# Testing Patterns

**Analysis Date:** 2026-07-24

## Test Framework

**Runner:**
- Framework: PHPUnit 11.0.1
- Config: `phpunit.xml` (in repo root)
- Laravel testing support: `Illuminate\Foundation\Testing\TestCase` base class

**Assertion Library:**
- PHPUnit's built-in assertions
- Laravel testing assertions: `$response->assertStatus()`, `$response->assertRedirect()`, `$this->assertAuthenticated()`, `$this->assertGuest()`

**Run Commands:**
```bash
vendor/bin/phpunit                      # Run all tests
vendor/bin/phpunit tests/Feature/AuthenticationTest.php  # Run specific test file
vendor/bin/phpunit --filter TestName    # Run tests matching filter
```

**Configuration Details:**
- Test environment: `APP_ENV=testing`
- Database: Configured DB connection (SQLite in-memory commented out, can be enabled)
- Cache: `array` driver for tests
- Mail: `array` mailer (no actual mail sent)
- Queue: `sync` connection (synchronous execution)
- Session: `array` driver for tests

## Test File Organization

**Location:**
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`
- Tests co-located with feature they test (e.g., `tests/Feature/AuthenticationTest.php` for login)

**Naming:**
- Test files use PascalCase suffix with `Test` (e.g., `AuthenticationTest.php`, `ApiTokenPermissionsTest.php`)
- Test methods use snake_case prefix with `test_` (e.g., `test_login_screen_can_be_rendered()`, `test_users_can_authenticate_using_the_login_screen()`)
- Test method names are descriptive, reading as English sentences

**Structure:**
```
tests/
├── Feature/
│   ├── ApiTokenPermissionsTest.php
│   ├── AuthenticationTest.php
│   ├── BrowserSessionsTest.php
│   ├── CreateApiTokenTest.php
│   ├── DeleteAccountTest.php
│   ├── DeleteApiTokenTest.php
│   ├── EmailVerificationTest.php
│   ├── ExampleTest.php
│   ├── PasswordConfirmationTest.php
│   ├── PasswordResetTest.php
│   ├── ProfileInformationTest.php
│   ├── RegistrationTest.php
│   ├── TwoFactorAuthenticationSettingsTest.php
│   ├── UpdatePasswordTest.php
│   └── ... (more feature tests)
├── Unit/
│   └── ExampleTest.php
└── TestCase.php
```

## Test Structure

**Suite Organization:**
```php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
```

**Patterns:**
- All Feature tests use `RefreshDatabase` trait to isolate tests (database rolled back after each test)
- Test class extends `Tests\TestCase` (custom base in `tests/TestCase.php`)
- Setup: Test methods create fixtures inline (no separate setUp() methods observed)
- Teardown: Implicit via `RefreshDatabase` trait (database migrations rolled back)
- Assertions: Mix of HTTP response assertions and authentication assertions

## Mocking

**Framework:** 
- Mockery 1.6 (installed as dev dependency)
- Laravel's built-in fake helpers for events, queues, etc.

**Patterns:**
```php
// Event faking (from EmailVerificationTest.php)
Event::fake();
$user = User::factory()->unverified()->create();
// ... perform action ...
Event::assertDispatched(Verified::class);

// Model factory usage for test data
$user = User::factory()->create();
$user = User::factory()->withPersonalTeam()->create();
```

**What to Mock:**
- External API calls (should be mocked with expected responses)
- Events (use `Event::fake()` to prevent side effects)
- Jobs/Queue (use `Queue::fake()` for async tasks)
- Email (configured as `array` in phpunit.xml to prevent actual sending)

**What NOT to Mock:**
- Database interactions (use RefreshDatabase for isolation)
- Eloquent model relationships (test with real models)
- Core business logic (test actual implementation)
- HTTP responses from tested routes (use `$this->post()`, `$this->get()`, etc.)

## Fixtures and Factories

**Test Data:**
```php
// User factory with chaining
$user = User::factory()->create();
$user = User::factory()->withPersonalTeam()->create();
$user = User::factory()->unverified()->create();
$user = User::factory()->withPersonalTeam()->unverified()->create();

// Factory method implementations in Database\Factories\UserFactory
public function unverified(): static
{
    return $this->state(fn (array $attributes) => [
        'email_verified_at' => null,
    ]);
}

public function withPersonalTeam(?callable $callback = null): static
{
    // ... creates related Team model
}
```

**Location:**
- Factories stored in `database/factories/` (PSR-4 autoloaded as `Database\Factories\*`)
- Each factory class (e.g., `UserFactory.php`, `ClientFactory.php`) corresponds to a model
- Subdirectories in factories mirror model organization (e.g., `database/factories/works/WorkerFactory.php`)

**Base Factory Class:**
- Extends `Illuminate\Database\Eloquent\Factories\Factory<Model>`
- Implements `definition(): array` method returning default attributes
- Can define custom states via chained methods (e.g., `unverified()`, `withPersonalTeam()`)
- Uses `fake()` helper for Faker data generation

## Coverage

**Requirements:** None explicitly enforced (no codecov.yml or coverage configuration)

**View Coverage:**
```bash
# To generate coverage report (if configured):
vendor/bin/phpunit --coverage-html coverage/
# Coverage report would be generated in coverage/ directory
```

**Current Status:** 
- Source code coverage configured in `phpunit.xml` with `<source><include><directory>app</directory></include></source>`
- No external coverage service integration observed
- Tests exist for authentication and Jetstream features; domain-specific tests limited

## Test Types

**Unit Tests:**
- Located in `tests/Unit/`
- Test individual functions/methods in isolation
- Example: `tests/Unit/ExampleTest.php` (template)
- Current codebase has minimal unit tests; focus is on Feature tests

**Integration Tests:**
- Located in `tests/Feature/` (Laravel convention treats "Feature" as integration tests)
- Test full request lifecycle through HTTP routes
- Use `RefreshDatabase` for database isolation
- Test authentication, authorization, and data persistence
- Examples: `AuthenticationTest`, `RegistrationTest`, `PasswordResetTest`

**E2E Tests:**
- Framework: Not used (no Dusk, Playwright, or Cypress configuration)
- Manual testing approach or frontend testing via Livewire components
- Could be added in future with Laravel Dusk or external tool

## Common Patterns

**Async Testing:**
```php
// Queue faking (pattern from testing guidelines)
Queue::fake();
// ... trigger async job ...
Queue::assertPushed(ProcessEmail::class);
```

**Error Testing:**
```php
// Validation error testing (implicit from validation exception handling)
$response = $this->post('/login', [
    'email' => $user->email,
    'password' => 'wrong-password',
]);
// Laravel automatically returns 422 for validation errors
// Assertions can check response status and error messages
```

**HTTP Method Chaining:**
```php
// GET request
$response = $this->get('/login');

// POST request with data
$response = $this->post('/login', [
    'email' => $user->email,
    'password' => 'password',
]);

// Authenticated requests
$this->actingAs($user)
    ->post('/api/endpoint', []);
```

**Assertion Examples:**
```php
// Response status
$response->assertStatus(200);
$response->assertStatus(302);

// Redirects
$response->assertRedirect(route('dashboard', absolute: false));

// Authentication state
$this->assertAuthenticated();
$this->assertGuest();

// View/View data
$response->assertViewIs('view.name');
$response->assertViewHas('variable');
```

## Database Testing

**Isolation:** 
- `RefreshDatabase` trait rolls back database after each test
- Alternatively, `DatabaseTransactions` trait can be used for transaction-based rollback

**Seeding:**
- No explicit seeding in existing tests (factories used instead)
- Could use `seed()` method to run seeders if needed:
  ```php
  $this->seed(DatabaseSeeder::class);
  ```

**Current Status:**
- SQLite in-memory database configuration commented out in phpunit.xml (line 25-26)
- Can be enabled for faster test runs:
  ```xml
  <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
  <!-- <env name="DB_DATABASE" value=":memory:"/> -->
  ```

## Testing Best Practices Observed

1. **Descriptive test names:** `test_login_screen_can_be_rendered()` clearly states what is being tested
2. **Single responsibility:** Each test focuses on one behavior
3. **Arrange-Act-Assert:** Tests follow this pattern implicitly
4. **No test interdependence:** Database isolation via RefreshDatabase ensures tests are independent
5. **Factory usage:** Test data created via factories rather than hardcoded values

## Testing Gaps

**Areas without explicit tests (based on file exploration):**
- Domain-specific features (inspections, work orders, dispatches, invoicing)
- Livewire component logic
- Service classes (SunatService, etc.)
- API endpoints (ApiTokenPermissionsTest exists but domain endpoints may lack tests)
- Excel import/export functionality
- PDF generation
- File upload operations

**Recommendation:** Domain-specific tests should be added following existing patterns in `tests/Feature/` directory.

---

*Testing analysis: 2026-07-24*
