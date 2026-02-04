# Phase 2: PHP 8.4 Upgrade

## 🎯 Objectives

Upgrade from **PHP 7.4** to **PHP 8.4** while maintaining all functionality.

---

## 📋 Prerequisites

- [ ] Phase 1 completed (code fixes applied)
- [ ] Full database backup
- [ ] Staging environment ready
- [ ] Git tag created: `pre-php84-upgrade`

---

## Part 1: PHP 8.4 Breaking Changes to Address

### 1.1 Implicitly Nullable Parameters (DEPRECATED)

**Issue**: PHP 8.4 deprecates implicitly nullable parameter types.

```php
// DEPRECATED in PHP 8.4 (generates warning)
function example(string $value = null) {}

// CORRECT - explicit nullable
function example(?string $value = null) {}
```

**Files to Fix**:

| File | Line | Current | Fix |
|------|------|---------|-----|
| `app/Traits/HasApiTokens.php` | 35 | `$expiresAt = null` | Add `?\DateTimeInterface` |
| `app/Traits/ApiResponse.php` | 17 | `$data = null` | Add `mixed` type |
| `app/Http/Middleware/RedirectIfAuthenticated.php` | 18 | `$guard = null` | Add `?string` |
| `app/Http/Controllers/API/V1/BindUserController.php` | 236 | `$serviceType = null` | Add `?string` |
| `app/Http/Controllers/API/V1/Infrastructure/*.php` | Various | Multiple | Add nullable types |

### 1.2 Example Fixes

#### HasApiTokens.php
```php
// BEFORE
public function createToken(string $name, array $abilities = ['*'], $expiresAt = null)

// AFTER (PHP 8.4 compatible)
public function createToken(string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array
```

#### ApiResponse.php
```php
// BEFORE
protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse

// AFTER
protected function successResponse(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
```

---

## Part 2: PHP 7.4 → 8.0 Breaking Changes

### 2.1 Removed Functions

| Function | Replacement |
|----------|-------------|
| `create_function()` | Arrow functions `fn() =>` |
| `each()` | `foreach` loop |
| `money_format()` | `NumberFormatter` |

### 2.2 Type Changes

```php
// PHP 8.0+ uses mixed instead of no type
function process($value) {}       // PHP 7.4
function process(mixed $value) {} // PHP 8.0+

// Union types (PHP 8.0+)
function getId(): int|string {}

// Named arguments (PHP 8.0+)
htmlspecialchars($string, double_encode: false);
```

---

## Part 3: PHP 8.0 → 8.1 Changes

### 3.1 New Features to Leverage

```php
// Readonly properties (PHP 8.1+)
class User {
    public readonly string $id;
}

// Enums (PHP 8.1+)
enum PaymentStatus: string {
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}

// First-class callable syntax
$callback = $this->handlePayment(...);
```

---

## Part 4: PHP 8.1 → 8.2 Changes

### 4.1 Deprecated Dynamic Properties

```php
// DEPRECATED in PHP 8.2
class User {
    // ...
}
$user = new User();
$user->undeclaredProperty = 'value'; // Deprecated!

// FIX: Use #[AllowDynamicProperties] or declare properties
#[AllowDynamicProperties]
class User {
    // ...
}
```

**Note**: Laravel Eloquent models handle this automatically with `__get`/`__set`.

### 4.2 Readonly Classes

```php
// PHP 8.2+
readonly class PaymentRequest {
    public function __construct(
        public string $gateway,
        public float $amount,
        public string $userId,
    ) {}
}
```

---

## Part 5: PHP 8.2 → 8.3 Changes

### 5.1 Key Features

```php
// Typed class constants (PHP 8.3+)
class PaymentGateway {
    public const string CBPAY = 'cbpay';
    public const string KBZPAY = 'kbzpay';
}

// json_validate() function
if (json_validate($jsonString)) {
    $data = json_decode($jsonString);
}

// Deep cloning of readonly properties
readonly class Order {
    public function __construct(
        public DateTimeImmutable $createdAt,
    ) {}
}
```

---

## Part 6: PHP 8.3 → 8.4 Changes

### 6.1 Property Hooks (New!)

```php
// Old way (PHP 7.4 - 8.3)
class User {
    private string $phone;
    
    public function getPhone(): string {
        return $this->phone;
    }
    
    public function setPhone(string $phone): void {
        $this->phone = $this->normalizePhone($phone);
    }
}

// New way (PHP 8.4+)
class User {
    public string $phone {
        get => $this->phone;
        set (string $value) {
            $this->phone = $this->normalizePhone($value);
        }
    }
}
```

### 6.2 Asymmetric Visibility

```php
// PHP 8.4+ - Property can be read publicly but only set privately
class Payment {
    public private(set) string $transactionId;
    public protected(set) float $amount;
    
    public function __construct(float $amount) {
        $this->transactionId = Str::uuid();
        $this->amount = $amount;
    }
}

$payment = new Payment(100.00);
echo $payment->transactionId;  // ✅ Works
$payment->transactionId = 'x'; // ❌ Error
```

### 6.3 #[\Deprecated] Attribute

```php
// PHP 8.4+
class MbtController {
    #[\Deprecated(
        message: "Use PaymentController::initiatePayment() instead",
        since: "2.0"
    )]
    public function cbpayRequest(Request $request) {
        return $this->paymentController->initiatePayment($request, 'cbpay');
    }
}
```

### 6.4 New Array Functions

```php
// array_find() - Find first matching element
$gateway = array_find(['cbpay', 'kbzpay', 'ayapay'], 
    fn($g) => str_starts_with($g, 'kbz')
); // 'kbzpay'

// array_find_key() - Find key of first match
$index = array_find_key(['cb' => 'cbpay', 'kbz' => 'kbzpay'], 
    fn($g) => $g === 'kbzpay'
); // 'kbz'

// array_any() - Check if any element matches
$hasKbz = array_any($gateways, fn($g) => str_contains($g, 'kbz'));

// array_all() - Check if all elements match
$allActive = array_all($payments, fn($p) => $p->status === 'active');
```

### 6.5 Deprecated Features in PHP 8.4

| Deprecated | Replacement |
|------------|-------------|
| `E_STRICT` constant | Remove usage |
| Implicitly nullable params | Use explicit `?Type` |
| `mysqli_ping()` | Connection retry logic |
| `mysqli_kill()` | N/A |
| `mysqli_refresh()` | N/A |

---

## Part 7: Server Installation

### 7.1 Install PHP 8.4 on Ubuntu 24.04

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP 8.4 and extensions
sudo apt install php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml \
    php8.4-bcmath php8.4-curl php8.4-gd php8.4-zip php8.4-intl \
    php8.4-readline php8.4-redis php8.4-opcache

# Verify installation
php8.4 -v
# PHP 8.4.x (cli)
```

### 7.2 Update Nginx Configuration

```nginx
# /etc/nginx/sites-available/isp.mlbbshop.app

# Update PHP-FPM socket
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;  # Changed from 7.4
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 7.3 PHP 8.4 php.ini Configuration

```ini
; /etc/php/8.4/fpm/php.ini

; Memory
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300

; Error handling
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log

; OPcache for production
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.jit = 1255
opcache.jit_buffer_size = 128M

; Session
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Lax

; Date
date.timezone = Asia/Yangon
```

### 7.4 Enable JIT Compilation (PHP 8.4)

```ini
; /etc/php/8.4/fpm/conf.d/10-opcache.ini

; Enable OPcache
opcache.enable=1
opcache.enable_cli=1

; JIT Configuration (PHP 8.0+)
opcache.jit=1255
opcache.jit_buffer_size=128M

; Cache settings
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

---

## Part 8: Composer Updates

### 8.1 Update composer.json

```json
{
    "require": {
        "php": "^8.4",
        "guzzlehttp/guzzle": "^7.8",
        "laravel/framework": "^12.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "phpunit/phpunit": "^11.0",
        "nunomaduro/collision": "^8.1"
    },
    "config": {
        "platform": {
            "php": "8.4"
        }
    }
}
```

### 8.2 Run Composer Update

```bash
cd /var/www/isp.mlbbshop.app/core

# Update dependencies
composer update --with-all-dependencies

# If conflicts, try:
composer update --with-all-dependencies --prefer-stable

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Part 9: Testing PHP 8.4 Compatibility

### 9.1 Syntax Check

```bash
# Check all PHP files for syntax errors
find app -name "*.php" -exec php8.4 -l {} \;

# Should output:
# No syntax errors detected in ...
```

### 9.2 Deprecation Check

```bash
# Run with deprecation notices visible
php8.4 -d error_reporting=E_ALL artisan --version

# Check error log for deprecation warnings
tail -100 storage/logs/laravel.log | grep -i deprecat
```

### 9.3 Run Application Tests

```bash
# If PHPUnit tests exist
php8.4 artisan test

# Manual test key endpoints
curl -X GET http://localhost/api/v1/packages
curl -X POST http://localhost/api/v1/login -d "phone=09xxx&password=xxx"
```

---

## ✅ Phase 2 Completion Checklist

### Code Fixes
- [ ] All implicitly nullable parameters fixed
- [ ] No `create_function()` usage
- [ ] No deprecated function calls
- [ ] All syntax errors resolved

### Server
- [ ] PHP 8.4 installed
- [ ] PHP 8.4-FPM running
- [ ] Nginx updated to use PHP 8.4
- [ ] OPcache/JIT configured
- [ ] Error logging configured

### Testing
- [ ] No syntax errors
- [ ] No fatal errors on page load
- [ ] API endpoints responding
- [ ] Admin panel accessible
- [ ] No deprecation warnings in production

### Backup
- [ ] Git commit/tag created: `php84-upgrade-complete`
- [ ] Database backup taken

---

## 🆘 Rollback Procedure

If PHP 8.4 causes issues:

```bash
# Switch back to PHP 7.4 (if still installed)
sudo systemctl stop php8.4-fpm
sudo systemctl start php7.4-fpm

# Update Nginx
sudo sed -i 's/php8.4-fpm.sock/php7.4-fpm.sock/g' \
    /etc/nginx/sites-available/isp.mlbbshop.app
sudo systemctl reload nginx

# Restore composer.lock
git checkout composer.lock
composer install
```

---

## ➡️ Next Step

Once PHP 8.4 is working, proceed to **Phase 3: Laravel Upgrade** (`04-PHASE3-LARAVEL-UPGRADE.md`)
