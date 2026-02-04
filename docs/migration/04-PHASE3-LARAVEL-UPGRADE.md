# Phase 3: Laravel Upgrade (7 → 12)

## 🎯 Objectives

1. Upgrade Laravel 7 → 8 → 9 → 10 → 11 → 12 (incremental)
2. Migrate models to `app/Models/` directory
3. Update factories and seeders
4. Implement Laravel Sanctum for API authentication
5. Preserve all existing functionality

---

## 📋 Prerequisites

- [ ] Phase 2 completed (PHP 8.4 running)
- [ ] All tests pass
- [ ] Full database backup
- [ ] Staging environment ready

---

## ⚠️ Important Notes

1. **Incremental Upgrades**: Laravel must be upgraded one major version at a time
2. **Don't Skip Versions**: 7 → 8 → 9 → 10 → 11 → 12 (not 7 → 12 directly)
3. **Test After Each Step**: Verify functionality after each major version
4. **Keep V1 API Compatible**: Mobile app depends on `/api/v1/*` routes

---

## 📊 Laravel Version Requirements

| Laravel | PHP Requirement | Status |
|---------|-----------------|--------|
| 7.x | 7.2.5 - 8.0 | Current |
| 8.x | 7.3 - 8.1 | Migrate models |
| 9.x | 8.0 - 8.2 | Symfony 6 |
| 10.x | 8.1 - 8.3 | PHP 8.1+ only |
| 11.x | 8.2 - 8.4 | Slim structure |
| **12.x** | **8.2 - 8.5** | **Target** |

---

## Step 1: Laravel 7 → 8 Upgrade

### 1.1 Update composer.json

```json
{
    "require": {
        "php": "^8.0|^8.1|^8.2",
        "laravel/framework": "^8.0",
        "laravel/tinker": "^2.5",
        "fruitcake/laravel-cors": "^2.0",
        "guzzlehttp/guzzle": "^7.0",
        "fideloper/proxy": "^4.4",
        "laravel/sanctum": "^2.11"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.4",
        "nunomaduro/collision": "^5.0",
        "phpunit/phpunit": "^9.5",
        "facade/ignition": "^2.5"
    }
}
```

### 1.2 Run Composer Update

```bash
cd core
rm -rf vendor composer.lock
composer install
```

### 1.3 Create app/Models Directory

```bash
mkdir -p app/Models
```

### 1.4 Move Models to app/Models/

Laravel 8 convention is to put models in `app/Models/`. Create a migration script:

```bash
#!/bin/bash
# File: migrate_models.sh

cd core/app

# List of model files to move
MODELS=(
    "About.php"
    "Admin.php"
    "AyaCallback.php"
    "Backup.php"
    "BankSetting.php"
    "Bcategory.php"
    "Billpaid.php"
    "Binduser.php"
    "Blog.php"
    "Branch.php"
    "CborderDetail.php"
    "Client.php"
    "Currency.php"
    "Daynamicpage.php"
    "Education.php"
    "EmailTemplate.php"
    "Emailsetting.php"
    "Entertainment.php"
    "ErrorCode.php"
    "ExtraMonth.php"
    "Faq.php"
    "FaultReportQuery.php"
    "Funfact.php"
    "Language.php"
    "MaintenanceSetting.php"
    "MbtBindUser.php"
    "Mediazone.php"
    "Multimessage.php"
    "Newsletter.php"
    "Notification.php"
    "Offerprovide.php"
    "OrderItem.php"
    "Package.php"
    "Packageorder.php"
    "PaymentGatewey.php"
    "PaymentNew.php"
    "PaymentProcess.php"
    "PaymentQuery.php"
    "PendingPayment.php"
    "PermissionModel.php"
    "PersonalAccessToken.php"
    "Portfolio.php"
    "Product.php"
    "ProductOrder.php"
    "Promotion.php"
    "Role.php"
    "Scategory.php"
    "Sectiontitle.php"
    "Service.php"
    "Setting.php"
    "Shipping.php"
    "Skill.php"
    "Slider.php"
    "Social.php"
    "Speedtest.php"
    "StatusDescription.php"
    "SubCompany.php"
    "Team.php"
    "Testimonial.php"
    "User.php"
    "UserDevice.php"
    "UserQuery.php"
    "WaveCallback.php"
)

# Move each model
for model in "${MODELS[@]}"; do
    if [ -f "$model" ]; then
        mv "$model" "Models/$model"
        echo "Moved: $model"
    fi
done

echo "Model migration complete!"
```

### 1.5 Update Model Namespaces

Each model file needs namespace updated from `App` to `App\Models`:

```php
// BEFORE (in app/User.php):
namespace App;

// AFTER (in app/Models/User.php):
namespace App\Models;
```

**Automated namespace update script**:

```bash
#!/bin/bash
# File: update_namespaces.sh

cd core/app/Models

for file in *.php; do
    # Update namespace from App to App\Models
    sed -i 's/^namespace App;/namespace App\\Models;/' "$file"
    echo "Updated namespace in: $file"
done
```

### 1.6 Update All References to Models

Search and replace all model references across the codebase:

```bash
# Find all files referencing old model locations
grep -rn "use App\\\User" --include="*.php" .
grep -rn "use App\\\Admin" --include="*.php" .
grep -rn "use App\\\Package" --include="*.php" .
# ... etc for each model
```

**Global find/replace patterns**:

| Find | Replace |
|------|---------|
| `use App\User;` | `use App\Models\User;` |
| `use App\Admin;` | `use App\Models\Admin;` |
| `use App\Package;` | `use App\Models\Package;` |
| `App\User::` | `App\Models\User::` |
| `App\Admin::` | `App\Models\Admin::` |
| ... | ... |

### 1.7 Update config/auth.php

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,  // Changed from App\User::class
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,  // Changed from App\Admin::class
    ],
],
```

### 1.8 Migrate Seeders

Laravel 8 uses `database/seeders/` instead of `database/seeds/`.

```bash
# Create new directory
mkdir -p database/seeders

# Move seeder files
mv database/seeds/*.php database/seeders/

# Update namespace in each seeder
# FROM: (no namespace)
# TO: namespace Database\Seeders;
```

Update seeder files:

```php
// BEFORE (database/seeds/DatabaseSeeder.php):
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ...
    }
}

// AFTER (database/seeders/DatabaseSeeder.php):
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ...
    }
}
```

### 1.9 Update Factories (Laravel 8 Class-Based)

Laravel 8 uses class-based factories. Convert existing factories:

```php
// BEFORE (database/factories/UserFactory.php - Laravel 7):
$factory->define(App\User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
    ];
});

// AFTER (database/factories/UserFactory.php - Laravel 8):
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];
    }
}
```

### 1.10 Update composer.json Autoload

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    }
}
```

### 1.11 Update TrustProxies Middleware

```php
// app/Http/Middleware/TrustProxies.php

// BEFORE (Laravel 7):
use Fideloper\Proxy\TrustProxies as Middleware;

// AFTER (Laravel 8):
use Illuminate\Http\Middleware\TrustProxies as Middleware;
```

### 1.12 Run Migrations and Clear Cache

```bash
composer dump-autoload
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 1.13 Test Laravel 8

```bash
php artisan --version
# Should show Laravel Framework 8.x.x

php artisan test
```

---

## Step 2: Laravel 8 → 9 Upgrade

### 2.1 Update composer.json

```json
{
    "require": {
        "php": "^8.0|^8.1|^8.2",
        "laravel/framework": "^9.0",
        "laravel/sanctum": "^3.0",
        "laravel/tinker": "^2.7",
        "guzzlehttp/guzzle": "^7.2"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^6.1",
        "phpunit/phpunit": "^9.5.10",
        "spatie/laravel-ignition": "^1.0"
    }
}
```

### 2.2 Remove Deprecated Packages

```json
// REMOVE these from require:
"fideloper/proxy": "^4.4"      // Now built into Laravel
"fruitcake/laravel-cors": "^2.0"  // Now built into Laravel
"facade/ignition": "^2.5"      // Replaced by spatie/laravel-ignition
```

### 2.3 Update CORS Configuration

Laravel 9 has built-in CORS. Update `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'api/v1/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### 2.4 Update Exception Handler

```php
// app/Exceptions/Handler.php

// BEFORE (Laravel 8):
public function register()
{
    $this->reportable(function (Throwable $e) {
        //
    });
}

// AFTER (Laravel 9 - same, but verify):
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        //
    });
}
```

### 2.5 Run Composer Update

```bash
rm -rf vendor composer.lock
composer install
```

### 2.6 Test Laravel 9

```bash
php artisan --version
# Should show Laravel Framework 9.x.x

php artisan test
```

---

## Step 3: Laravel 9 → 10 Upgrade

### 3.1 Update composer.json

```json
{
    "require": {
        "php": "^8.1|^8.2",
        "laravel/framework": "^10.0",
        "laravel/sanctum": "^3.2",
        "laravel/tinker": "^2.8",
        "guzzlehttp/guzzle": "^7.2"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.5.1",
        "nunomaduro/collision": "^7.0",
        "phpunit/phpunit": "^10.0",
        "spatie/laravel-ignition": "^2.0",
        "laravel/pint": "^1.0"
    }
}
```

### 3.2 Update Service Providers

Laravel 10 changes some provider signatures:

```php
// app/Providers/RouteServiceProvider.php

// BEFORE (Laravel 9):
public function boot()
{
    $this->configureRateLimiting();
    $this->routes(function () {
        // ...
    });
}

// AFTER (Laravel 10):
public function boot(): void
{
    $this->configureRateLimiting();
    $this->routes(function () {
        // ...
    });
}
```

### 3.3 Update Middleware Return Types

Laravel 10 requires return types on middleware:

```php
// app/Http/Middleware/Authenticate.php

// BEFORE:
protected function redirectTo($request)
{
    if (! $request->expectsJson()) {
        return route('login');
    }
}

// AFTER:
protected function redirectTo(Request $request): ?string
{
    return $request->expectsJson() ? null : route('login');
}
```

### 3.4 Run Composer Update

```bash
rm -rf vendor composer.lock
composer install
```

### 3.5 Test Laravel 10

```bash
php artisan --version
# Should show Laravel Framework 10.x.x

php artisan test
```

---

## Step 4: Implement Laravel Sanctum

### 4.1 Install Sanctum (if not already)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 4.2 Update User Model

```php
// app/Models/User.php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;  // Add HasApiTokens
    
    // Remove the custom HasApiTokens trait from App\Traits
}
```

### 4.3 Update API Routes for Sanctum

```php
// routes/api_v1.php

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::prefix('v1')->middleware([
    EnsureFrontendRequestsAreStateful::class,
    'throttle:api'
])->group(function () {
    // ... your routes
});
```

### 4.4 Update AuthController for Sanctum Tokens

```php
// app/Http/Controllers/API/V1/AuthController.php

public function login(Request $request)
{
    $credentials = $request->validate([
        'phone' => 'required',
        'password' => 'required',
    ]);

    $user = User::where('phone', $credentials['phone'])->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Create Sanctum token
    $token = $user->createToken('mobile-app')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user' => $user,
            'token' => $token,
        ],
    ]);
}

public function logout(Request $request)
{
    // Revoke current token
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Logged out successfully',
    ]);
}
```

### 4.5 Backward Compatibility for Legacy API

Keep the old token system working for existing mobile apps:

```php
// app/Http/Middleware/AuthenticateApi.php

public function handle($request, Closure $next)
{
    // Try Sanctum first
    if ($request->bearerToken()) {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        
        if ($token && $token->tokenable) {
            // Sanctum token found
            Auth::login($token->tokenable);
            return $next($request);
        }
        
        // Try legacy token format
        // ... legacy authentication code
    }

    return response()->json(['error' => 'Unauthenticated'], 401);
}
```

---

## Step 5: Final composer.json (Laravel 10)

```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "ISP Management System",
    "keywords": ["isp", "management", "laravel"],
    "license": "MIT",
    "require": {
        "php": "^8.1|^8.2",
        "barryvdh/laravel-dompdf": "^2.0",
        "guzzlehttp/guzzle": "^7.8",
        "laravel/framework": "^10.0",
        "laravel/sanctum": "^3.3",
        "laravel/tinker": "^2.8",
        "mews/purifier": "^3.4",
        "phpmailer/phpmailer": "^6.8",
        "spatie/laravel-cookie-consent": "^3.3",
        "stripe/stripe-php": "^13.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^7.10",
        "phpunit/phpunit": "^10.5",
        "spatie/laravel-ignition": "^2.4"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## Step 6: Testing Checklist

### 6.1 Core Functionality Tests

- [ ] Admin login works
- [ ] User login works
- [ ] Dashboard loads
- [ ] All CRUD operations work

### 6.2 API Tests

```bash
# Test with new Sanctum tokens
BASE_URL="https://isp.mlbbshop.app"

# Login and get token
TOKEN=$(curl -s -X POST "$BASE_URL/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"phone": "test", "password": "test"}' | jq -r '.data.token')

# Use token for authenticated requests
curl -s "$BASE_URL/api/v1/profile" \
  -H "Authorization: Bearer $TOKEN"

# Verify all V1 endpoints work
curl -s "$BASE_URL/api/v1/packages"
curl -s "$BASE_URL/api/v1/notifications" -H "Authorization: Bearer $TOKEN"
```

### 6.3 Payment Gateway Tests

- [ ] CB Pay initiation
- [ ] KBZ Pay initiation
- [ ] AYA Pay authentication
- [ ] Wave Pay initiation
- [ ] All callbacks processed correctly

---

## Step 7: Commit and Tag

```bash
git add .
git commit -m "Phase 3: Laravel 10 upgrade complete

- Upgraded Laravel 7 → 8 → 9 → 10
- Migrated models to app/Models/
- Converted to class-based factories
- Updated seeders namespace
- Implemented Laravel Sanctum
- Updated all deprecated code
- Maintained backward compatibility for V1 API"

git push origin main

git tag -a v2.0.0-laravel10 -m "Laravel 10 upgrade complete"
git push origin v2.0.0-laravel10
```

---

## ✅ Phase 3 Completion Checklist

- [ ] Laravel upgraded to 12.x
- [ ] Models moved to `app/Models/`
- [ ] All model references updated
- [ ] Factories converted to class-based
- [ ] Seeders updated with namespace
- [ ] Laravel Sanctum 4.x implemented
- [ ] Legacy API still works
- [ ] V1 API still works
- [ ] All payment gateways work
- [ ] Admin panel fully functional
- [ ] All tests pass
- [ ] Tag created (v2.0.0-laravel12)

---

## Step 5: Laravel 10 → 11 Upgrade

### 5.1 Understanding Laravel 11's Slim Structure

Laravel 11 introduces a new streamlined application structure:

| Old (Laravel 10) | New (Laravel 11) | Notes |
|------------------|------------------|-------|
| `app/Http/Kernel.php` | `bootstrap/app.php` | Middleware moved |
| `app/Console/Kernel.php` | `bootstrap/app.php` | Schedule moved |
| `app/Exceptions/Handler.php` | `bootstrap/app.php` | Exception handling moved |
| Multiple Service Providers | Single `AppServiceProvider` | Simplified |

**IMPORTANT**: You can keep the Laravel 10 structure! Laravel 11 supports both.

### 5.2 Update composer.json

```json
{
    "require": {
        "php": "^8.2|^8.4",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "guzzlehttp/guzzle": "^7.8"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11.0",
        "laravel/pint": "^1.13"
    }
}
```

### 5.3 Update Sanctum to 4.x

```bash
composer require laravel/sanctum:^4.0
php artisan vendor:publish --tag=sanctum-migrations
```

Update `config/sanctum.php`:

```php
'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
],
```

### 5.4 Option A: Keep Traditional Structure (Recommended)

Laravel 11 still supports the traditional structure. Just update the framework:

```bash
rm -rf vendor composer.lock
composer install
```

Your existing `app/Http/Kernel.php`, `app/Exceptions/Handler.php`, and service providers will continue to work.

### 5.5 Option B: Migrate to Slim Structure

If you want to use the new slim structure:

```php
// bootstrap/app.php (Laravel 11)

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add your middleware here
        $middleware->alias([
            'auth.api' => \App\Http\Middleware\AuthenticateApi::class,
            'api.limit' => \App\Http\Middleware\ApiRateLimiter::class,
        ]);
        
        $middleware->api(append: [
            // Your API middleware
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling
    })
    ->create();
```

### 5.6 Update Password Rehashing (Laravel 11 Feature)

Laravel 11 automatically rehashes passwords. If your User model uses a custom password field:

```php
// app/Models/User.php

class User extends Authenticatable
{
    // If password field is not 'password'
    protected string $authPasswordName = 'password';
}
```

Or disable auto-rehashing in `config/hashing.php`:

```php
'rehash_on_login' => false,
```

### 5.7 Test Laravel 11

```bash
php artisan --version
# Should show Laravel Framework 11.x.x

php artisan test
```

---

## Step 6: Laravel 11 → 12 Upgrade

### 6.1 Update composer.json

```json
{
    "require": {
        "php": "^8.2|^8.4|^8.5",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "guzzlehttp/guzzle": "^7.8"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11.0",
        "pestphp/pest": "^3.0",
        "laravel/pint": "^1.13"
    }
}
```

### 6.2 Key Changes in Laravel 12

**Laravel 12 is a minimal "maintenance release"** - most apps upgrade in 5 minutes.

#### Carbon 3 Required

Carbon 2.x support removed. Update any Carbon-specific code:

```php
// Carbon 3 changes - diffIn* methods return floats
$hours = $date1->diffInHours($date2);  // Now returns float, may be negative
```

#### Local Disk Default Path Changed

```php
// Laravel 11: Storage::disk('local') → storage/app
// Laravel 12: Storage::disk('local') → storage/app/private

// To restore old behavior, explicitly define in config/filesystems.php:
'local' => [
    'driver' => 'local',
    'root' => storage_path('app'),
    'throw' => false,
],
```

#### Image Validation Excludes SVG

```php
// BEFORE (Laravel 11)
'photo' => 'required|image'  // Allowed SVG

// AFTER (Laravel 12) - SVG excluded by default
'photo' => 'required|image:allow_svg'  // Explicitly allow SVG
```

#### UUIDv7 Default for HasUuids

```php
// If you need UUIDv4 instead of UUIDv7
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
```

### 6.3 Run Composer Update

```bash
rm -rf vendor composer.lock
composer install
```

### 6.4 Test Laravel 12

```bash
php artisan --version
# Should show Laravel Framework 12.x.x

# Run all tests
php artisan test

# Check for deprecations
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Step 7: Final composer.json (Laravel 12 + PHP 8.4)

```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "ISP Management System",
    "keywords": ["isp", "management", "laravel"],
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "barryvdh/laravel-dompdf": "^3.0",
        "guzzlehttp/guzzle": "^7.9",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "mews/purifier": "^3.5",
        "phpmailer/phpmailer": "^6.9",
        "spatie/laravel-cookie-consent": "^3.4",
        "stripe/stripe-php": "^15.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.24",
        "laravel/pint": "^1.18",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.5",
        "phpunit/phpunit": "^11.4"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "php-http/discovery": true
        },
        "platform": {
            "php": "8.4"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## Step 8: Testing Checklist

### 8.1 Core Functionality Tests

- [ ] Admin login works
- [ ] User login works
- [ ] Dashboard loads
- [ ] All CRUD operations work

### 8.2 API Tests

```bash
# Test with Sanctum 4.x tokens
BASE_URL="https://isp.mlbbshop.app"

# Login and get token
TOKEN=$(curl -s -X POST "$BASE_URL/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"phone": "test", "password": "test"}' | jq -r '.data.token')

# Use token for authenticated requests
curl -s "$BASE_URL/api/v1/profile" \
  -H "Authorization: Bearer $TOKEN"

# Verify all V1 endpoints work
curl -s "$BASE_URL/api/v1/packages"
curl -s "$BASE_URL/api/v1/notifications" -H "Authorization: Bearer $TOKEN"
```

### 8.3 Payment Gateway Tests

- [ ] CB Pay initiation
- [ ] KBZ Pay initiation
- [ ] AYA Pay authentication
- [ ] Wave Pay initiation
- [ ] All callbacks processed correctly

---

## Step 9: Commit and Tag

```bash
git add .
git commit -m "Phase 3: Laravel 12 + PHP 8.4 upgrade complete

- Upgraded Laravel 7 → 8 → 9 → 10 → 11 → 12
- Upgraded PHP 7.4 → 8.4
- Migrated models to app/Models/
- Converted to class-based factories
- Updated seeders namespace
- Implemented Laravel Sanctum 4.x
- Updated all deprecated code
- Maintained backward compatibility for V1 API
- Carbon 3 compatible
- PHP 8.4 features available"

git push origin main

git tag -a v2.0.0-laravel12 -m "Laravel 12 + PHP 8.4 upgrade complete"
git push origin v2.0.0-laravel12
```

---

## 🆘 Quick Rollback Commands

### Rollback to Laravel 11
```bash
git checkout HEAD~1 -- composer.json composer.lock
composer install
```

### Rollback to Laravel 10
```bash
git checkout v1.x-laravel10 -- composer.json composer.lock
composer install
```

---

## ➡️ Next Step

Once Phase 3 is complete, proceed to **Phase 4: Refactoring** (`05-PHASE4-REFACTORING.md`)
