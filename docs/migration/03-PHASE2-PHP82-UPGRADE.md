# Phase 2: PHP 8.2 Upgrade

## 🎯 Objectives

1. Install PHP 8.2 on staging server
2. Fix all PHP 8.x compatibility issues
3. Test all features with PHP 8.2
4. Deploy PHP 8.2 to production

---

## 📋 Prerequisites

- [ ] Phase 1 completed (see `02-PHASE1-PREPARATION.md`)
- [ ] Staging environment ready
- [ ] Database backup available
- [ ] Rollback plan documented

---

## Step 1: Install PHP 8.2 on Ubuntu 24.04

### 1.1 Add PHP Repository

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Add Ondřej Surý's PHP PPA (most reliable)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### 1.2 Install PHP 8.2 and Extensions

```bash
# Install PHP 8.2 with all required extensions
sudo apt install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-soap \
    php8.2-redis \
    php8.2-opcache

# Verify installation
php8.2 -v
```

### 1.3 Configure PHP 8.2 FPM

```bash
# Edit PHP-FPM pool config
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Key settings to verify/modify:
user = www-data
group = www-data
listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

### 1.4 Configure PHP.ini for Production

```bash
sudo nano /etc/php/8.2/fpm/php.ini

# Recommended settings:
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
date.timezone = Asia/Yangon

# Security settings:
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php8.2-fpm.log

# OPcache settings (IMPORTANT for performance):
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0  # Set to 1 in development
opcache.save_comments = 1
opcache.fast_shutdown = 1
```

### 1.5 Restart PHP-FPM

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
sudo systemctl status php8.2-fpm
```

---

## Step 2: Update Nginx Configuration

### 2.1 Modify Nginx Site Config

```bash
sudo nano /etc/nginx/sites-available/isp.mlbbshop.app
```

Change PHP-FPM socket from PHP 7.4 to PHP 8.2:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name isp.mlbbshop.app;
    root /var/www/isp.mlbbshop.app/core/public;

    index index.php index.html;

    # Logging
    access_log /var/log/nginx/isp.access.log;
    error_log /var/log/nginx/isp.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration - CHANGE THIS LINE
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;  # Changed from php7.4-fpm
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Security: Block .env, .git, etc.
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security: Block sensitive files
    location ~* \.(env|log|sql)$ {
        deny all;
    }
}
```

### 2.2 Test and Reload Nginx

```bash
# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## Step 3: PHP 8.x Compatibility Fixes

### 3.1 Dynamic Properties Deprecation

PHP 8.2 deprecates dynamic properties. Add `#[AllowDynamicProperties]` attribute or define properties.

**Check for issues**:
```bash
cd core
grep -rn "\$this->" --include="*.php" app/ | head -50
```

**Common fix pattern**:
```php
// BEFORE (PHP 7.4 - allowed dynamic properties):
class MyController extends Controller
{
    public function __construct()
    {
        $this->someUndeclaredProperty = 'value';  // This will warn in PHP 8.2
    }
}

// AFTER (PHP 8.2 compatible) - Option 1: Declare property
class MyController extends Controller
{
    protected $someUndeclaredProperty;
    
    public function __construct()
    {
        $this->someUndeclaredProperty = 'value';
    }
}

// AFTER (PHP 8.2 compatible) - Option 2: Allow dynamic properties
#[\AllowDynamicProperties]
class MyController extends Controller
{
    public function __construct()
    {
        $this->someUndeclaredProperty = 'value';
    }
}
```

### 3.2 MbtController.php - Known Dynamic Properties

The `MbtController.php` uses many dynamic properties in constructor. These need to be declared:

**File**: `core/app/Http/Controllers/API/MbtController.php`

Add property declarations at the top of the class:

```php
class MbtController extends Controller
{
    const INIT_VECTOR_LENGTH = 16;
    const CIPHER = 'AES-128-CBC';
    
    public $successStatus = 200;
    public $unauthorisedStatus = 400;
    
    // ADD THESE PROPERTY DECLARATIONS:
    protected $base_url;
    protected $number_days;
    protected $new_user_days;
    protected $new_user_days1;
    protected $discount;
    protected $commercial_tax;
    
    // CB Pay properties
    protected $api_url;
    protected $auth_token;
    protected $ecommerce_id;
    protected $sub_mer_id;
    protected $mer_id;
    protected $transaction_type;
    protected $notifyurl;
    protected $cb_redirect;
    
    // KBZ Pay properties
    protected $kbz_api_url;
    protected $kbz_m_code;
    protected $kbz_appid;
    protected $kbz_key;
    protected $kbz_trade_type;
    protected $kbz_notifyurl;
    protected $kbz_version;
    protected $kbz_redirecct;
    
    // AYA Pay properties
    protected $aya_api_tokenurl;
    protected $aya_consumer_key;
    protected $aya_consumer_secret;
    protected $aya_grant_type;
    protected $aya_api_baseurl;
    protected $aya_phone;
    protected $aya_password;
    protected $aya_enc_key;
    
    // KBZ Direct Pay properties
    protected $direct_apiurl;
    protected $direct_mcode;
    protected $direct_key;
    
    // Wave Pay properties
    protected $wave_live_seconds;
    protected $wave_merchnt_id;
    protected $wave_callback_url;
    protected $wave_secret_key;
    protected $wave_base_url;
    
    public function __construct()
    {
        // ... existing constructor code
    }
}
```

### 3.3 Null Coalescing Changes

PHP 8.x is stricter about null values. Check for potential issues:

```bash
# Find potential null issues
grep -rn "->where\|->find\|->first" --include="*.php" app/ | head -30
```

**Common fix pattern**:
```php
// BEFORE (may throw error if null in PHP 8.x):
$user = User::find($id);
$name = $user->name;  // Error if $user is null

// AFTER (PHP 8.x safe):
$user = User::find($id);
$name = $user?->name;  // Null-safe operator (PHP 8.0+)
// OR
$name = $user ? $user->name : null;
// OR
if ($user) {
    $name = $user->name;
}
```

### 3.4 String Functions with Null

PHP 8.1+ throws TypeError when null is passed to string functions:

```php
// BEFORE (worked in PHP 7.4):
strlen(null);  // Returns 0
trim(null);    // Returns ""

// AFTER (throws TypeError in PHP 8.1+):
strlen(null);  // TypeError
trim(null);    // TypeError

// FIX:
strlen($value ?? '');
trim($value ?? '');
```

**Search for potential issues**:
```bash
grep -rn "strlen\|trim\|strtolower\|strtoupper\|substr\|str_replace" --include="*.php" app/Http/Controllers/ | head -30
```

### 3.5 Return Type Declarations

Add return types for better PHP 8 compatibility:

```php
// BEFORE:
public function index()
{
    return view('admin.dashboard');
}

// AFTER (recommended):
public function index(): \Illuminate\Contracts\View\View
{
    return view('admin.dashboard');
}

// OR for JSON responses:
public function apiIndex(): \Illuminate\Http\JsonResponse
{
    return response()->json(['data' => []]);
}
```

---

## Step 4: Update Composer for PHP 8.2

### 4.1 Update composer.json

```json
{
    "require": {
        "php": "^8.2",
        // ... other packages
    }
}
```

### 4.2 Run Composer Update

```bash
cd core

# Clear old dependencies
rm -rf vendor composer.lock

# Install fresh
composer install

# If there are issues, check compatibility
composer check-platform-reqs
```

---

## Step 5: Clear All Caches

```bash
cd core

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache

# Dump autoloader
composer dump-autoload -o
```

---

## Step 6: Testing

### 6.1 Check PHP Version

```bash
php -v
# Should show PHP 8.2.x

php -m
# List all loaded modules
```

### 6.2 Check Laravel Status

```bash
cd core
php artisan --version
# Should show Laravel Framework 7.x.x

php artisan about
# Shows system information
```

### 6.3 Run Unit Tests

```bash
cd core
php artisan test
# OR
./vendor/bin/phpunit
```

### 6.4 Manual Testing Checklist

#### Admin Panel Tests
- [ ] Login to admin panel
- [ ] Dashboard loads with statistics
- [ ] Payment Query: Search works
- [ ] Payment Query: Filter works
- [ ] Payment Query: Export to Excel works
- [ ] Payment Query: Export to PDF works
- [ ] Fault Query: Search works
- [ ] Fault Query: Update status works
- [ ] Install Query: Search works
- [ ] User Query: Search works
- [ ] User management: List users
- [ ] User management: Edit user
- [ ] User management: Disable user
- [ ] Package management: List packages
- [ ] Package management: Create package
- [ ] Package management: Edit package
- [ ] Settings: Bank settings save
- [ ] Settings: Basic info save
- [ ] Language: Change language

#### API Tests
```bash
BASE_URL="https://isp.mlbbshop.app"

# Test Legacy API endpoints
curl -s "$BASE_URL/api/get-banner"
curl -s "$BASE_URL/api/get-package"
curl -s "$BASE_URL/api/check-maintenance"

# Test V1 API endpoints
curl -s "$BASE_URL/api/v1/packages"
curl -s "$BASE_URL/api/v1/banners"
curl -s "$BASE_URL/api/v1/maintenance-status"
curl -s "$BASE_URL/api/v1/app-version"

# Test authentication (use test credentials)
curl -X POST "$BASE_URL/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"phone": "test_phone", "password": "test_password"}'
```

#### Payment Gateway Tests (CRITICAL)

⚠️ **TEST WITH SMALL AMOUNTS ONLY**

- [ ] CB Pay: Initiate payment
- [ ] CB Pay: Check payment status
- [ ] CB Pay: Callback received
- [ ] KBZ Pay: Initiate payment
- [ ] KBZ Pay: Check payment status
- [ ] KBZ Pay: Callback received
- [ ] AYA Pay: Get access token
- [ ] AYA Pay: Initiate payment
- [ ] AYA Pay: Callback received
- [ ] Wave Pay: Initiate payment
- [ ] Wave Pay: Callback received

---

## Step 7: Monitor Errors

### 7.1 Check Laravel Logs

```bash
# Watch Laravel log
tail -f core/storage/logs/laravel.log

# Check for PHP errors
tail -f /var/log/php8.2-fpm.log
```

### 7.2 Check Nginx Logs

```bash
# Access log
tail -f /var/log/nginx/isp.access.log

# Error log
tail -f /var/log/nginx/isp.error.log
```

### 7.3 Common PHP 8.2 Errors and Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| `Creation of dynamic property is deprecated` | Undeclared class property | Declare property in class |
| `Passing null to parameter of type string` | Null passed to string function | Use `$var ?? ''` |
| `Cannot use object as array` | ArrayAccess not implemented | Use `->` instead of `[]` |
| `Return value must be of type X, null returned` | Missing return type | Add `?X` or `X|null` return type |

---

## Step 8: Commit Changes

```bash
cd /Users/thomas/ClientProjects/telco

git add .
git commit -m "Phase 2: PHP 8.2 compatibility fixes

- Added property declarations to MbtController
- Fixed dynamic property deprecation warnings
- Updated PHP version constraint to ^8.2
- Fixed null coalescing issues
- Updated string function calls with null checks"

git push origin main

git tag -a v1.0.2-php82-ready -m "PHP 8.2 compatible"
git push origin v1.0.2-php82-ready
```

---

## Step 9: Production Deployment

### 9.1 Pre-deployment Checklist

- [ ] All tests pass on staging
- [ ] Payment gateways tested
- [ ] API endpoints verified
- [ ] Admin panel fully functional
- [ ] Database backup taken
- [ ] Maintenance window scheduled

### 9.2 Deployment Steps

```bash
# On production server
cd /var/www/isp.mlbbshop.app

# Enable maintenance mode
php artisan down --message="Upgrading to PHP 8.2, back in 10 minutes"

# Pull latest code
git pull origin main

# Install dependencies
cd core
composer install --no-dev --optimize-autoloader

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Switch Nginx to PHP 8.2
sudo nano /etc/nginx/sites-available/isp.mlbbshop.app
# Change php7.4-fpm.sock to php8.2-fpm.sock

# Test Nginx config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Disable maintenance mode
php artisan up

# Monitor logs
tail -f storage/logs/laravel.log
```

### 9.3 Rollback Plan

If issues occur:

```bash
# Enable maintenance mode
php artisan down

# Switch back to PHP 7.4 in Nginx
sudo nano /etc/nginx/sites-available/isp.mlbbshop.app
# Change php8.2-fpm.sock back to php7.4-fpm.sock

# Reload Nginx
sudo nginx -t && sudo systemctl reload nginx

# Rollback code if needed
git checkout v1.0.1-phase1-complete

# Clear caches
php artisan config:clear
php artisan cache:clear

# Disable maintenance mode
php artisan up
```

---

## ✅ Phase 2 Completion Checklist

- [ ] PHP 8.2 installed and configured
- [ ] All dynamic properties declared
- [ ] All null coalescing issues fixed
- [ ] Composer install works with PHP 8.2
- [ ] All unit tests pass
- [ ] Admin panel fully functional
- [ ] All API endpoints working
- [ ] All payment gateways tested
- [ ] Production deployed with PHP 8.2
- [ ] No errors in logs after 24 hours
- [ ] Phase 2 tag created (v1.0.2-php82-ready)

---

## ➡️ Next Step

Once Phase 2 is complete, proceed to **Phase 3: Laravel Upgrade** (`04-PHASE3-LARAVEL-UPGRADE.md`)
