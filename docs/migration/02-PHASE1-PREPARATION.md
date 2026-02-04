# Phase 1: Preparation

## 🎯 Objectives

1. Fix all code that blocks PHP 8.x upgrade
2. Replace abandoned/deprecated packages
3. Setup staging environment
4. Create backups and tags

---

## 📋 Pre-requisites Checklist

- [ ] Git repository is clean (no uncommitted changes)
- [ ] Full database backup created
- [ ] Staging server available
- [ ] Access to production server confirmed

---

## Step 1: Create Backup Tag

```bash
cd /Users/thomas/ClientProjects/telco

# Ensure all changes are committed
git status

# Create a backup tag before migration
git tag -a v1.0.0-pre-migration -m "Pre-migration backup - Laravel 7, PHP 7.4"
git push origin v1.0.0-pre-migration
```

---

## Step 2: Fix Deprecated Code

### 2.1 Fix `create_function()` in config/title.php

**File**: `core/config/title.php`
**Issue**: `create_function()` was removed in PHP 8.0

```bash
# Find the file and check line 8
cat -n core/config/title.php | head -20
```

**Fix**:
```php
// BEFORE (PHP 7.4 - BROKEN in PHP 8.x):
$callback = create_function('$a', 'return $a;');

// AFTER (PHP 8.x compatible):
$callback = fn($a) => $a;
// OR
$callback = function($a) { return $a; };
```

### 2.2 Check for Other Deprecated Functions

Run this search to find other potential issues:

```bash
cd core

# Search for create_function
grep -rn "create_function" --include="*.php" .

# Search for deprecated each() function
grep -rn "\beach(" --include="*.php" .

# Search for deprecated ereg functions
grep -rn "ereg\|eregi\|ereg_replace\|split\(" --include="*.php" .

# Search for deprecated mysql_ functions
grep -rn "mysql_" --include="*.php" .
```

---

## Step 3: Update composer.json

### 3.1 Replace Abandoned Packages

**File**: `core/composer.json`

```json
{
    "require-dev": {
        // REMOVE THIS (abandoned):
        "fzaninotto/faker": "^1.9.1",
        
        // ADD THIS (replacement):
        "fakerphp/faker": "^1.23"
    }
}
```

### 3.2 Update Outdated Packages

```json
{
    "require": {
        // UPDATE these versions:
        "guzzlehttp/guzzle": "^7.8",
        "barryvdh/laravel-dompdf": "^2.0",
        
        // REMOVE (will be replaced with stripe/stripe-php later):
        // "cartalyst/stripe-laravel": "12.0.*",
        
        // REMOVE (deprecated, will use newer SDK later):
        // "paypal/rest-api-sdk-php": "^1.14",
    }
}
```

### 3.3 Full Updated composer.json (Phase 1 Target)

```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "The Laravel Framework.",
    "keywords": ["framework", "laravel"],
    "license": "MIT",
    "require": {
        "php": "^7.4|^8.0|^8.1|^8.2",
        "anhskohbo/no-captcha": "^3.1",
        "barryvdh/laravel-dompdf": "^0.9.0",
        "cartalyst/stripe-laravel": "12.0.*",
        "fideloper/proxy": "^4.2",
        "fruitcake/laravel-cors": "^1.0",
        "guzzlehttp/guzzle": "^6.3|^7.0",
        "laravel/framework": "^7.0",
        "laravel/tinker": "^2.0",
        "mews/purifier": "^3.3",
        "paypal/rest-api-sdk-php": "^1.14",
        "phpmailer/phpmailer": "^6.1",
        "spatie/laravel-cookie-consent": "^2.12",
        "instamojo/instamojo-php": "^0.4.0",
        "mollie/laravel-mollie": "^2.0",
        "razorpay/razorpay": "2.*",
        "rachidlaasri/laravel-installer": "^4.1"
    },
    "require-dev": {
        "facade/ignition": "^2.0",
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.3.1",
        "nunomaduro/collision": "^4.1",
        "phpunit/phpunit": "^8.5|^9.0"
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "php-http/discovery": true
        }
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        },
        "classmap": [
            "database/seeds",
            "database/factories"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    }
}
```

---

## Step 4: Run Composer Update

```bash
cd core

# Remove composer.lock and vendor to ensure clean install
rm -rf vendor composer.lock

# Install with updated packages
composer install

# If there are conflicts, run:
composer update --with-all-dependencies
```

---

## Step 5: Test Current Functionality

### 5.1 Run Existing Tests

```bash
cd core
php artisan test
# OR
./vendor/bin/phpunit
```

### 5.2 Manual Testing Checklist

- [ ] Admin login works
- [ ] Dashboard loads
- [ ] Payment query page works
- [ ] Fault query page works
- [ ] User management works
- [ ] API endpoints respond (test with curl)

### 5.3 API Quick Tests

```bash
BASE_URL="https://isp.mlbbshop.app"

# Test legacy API
curl -s "$BASE_URL/api/get-banner" | head

# Test V1 API
curl -s "$BASE_URL/api/v1/packages" | head

# Test V1 maintenance status
curl -s "$BASE_URL/api/v1/maintenance-status" | head
```

---

## Step 6: Setup Staging Environment

### 6.1 Option A: Local Staging with Docker

Create `docker-compose.yml` in project root:

```yaml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - ./core:/var/www/html
    environment:
      - APP_ENV=staging
      - APP_DEBUG=true
    depends_on:
      - db
      
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: telco_staging
      MYSQL_USER: telco_user
      MYSQL_PASSWORD: staging_password
      MYSQL_ROOT_PASSWORD: root_password
    ports:
      - "3307:3306"
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

### 6.2 Option B: Staging on Same Server (Different Directory)

```bash
# On production server
sudo mkdir -p /var/www/staging.isp.mlbbshop.app
sudo chown -R www-data:www-data /var/www/staging.isp.mlbbshop.app

# Clone repository
cd /var/www/staging.isp.mlbbshop.app
git clone https://github.com/Thiha-Lynn/telco_isp.git .

# Create staging database
mysql -u root -p
CREATE DATABASE telco_staging;
GRANT ALL PRIVILEGES ON telco_staging.* TO 'telco_user'@'localhost';
FLUSH PRIVILEGES;

# Copy production database to staging
mysqldump -u telco_user -p telco_db > /tmp/backup.sql
mysql -u telco_user -p telco_staging < /tmp/backup.sql

# Setup staging environment
cd core
cp .env.example .env
# Edit .env with staging settings
```

---

## Step 7: Commit Phase 1 Changes

```bash
cd /Users/thomas/ClientProjects/telco

# Stage changes
git add .

# Commit
git commit -m "Phase 1: Preparation for PHP 8.x upgrade

- Fixed create_function() deprecation in config/title.php
- Replaced fzaninotto/faker with fakerphp/faker
- Updated composer.json PHP version constraint
- Added wider version ranges for guzzle and phpunit"

# Push
git push origin main

# Create phase 1 tag
git tag -a v1.0.1-phase1-complete -m "Phase 1 complete - Ready for PHP 8.x"
git push origin v1.0.1-phase1-complete
```

---

## ✅ Phase 1 Completion Checklist

- [ ] `create_function()` fixed in config/title.php
- [ ] `fzaninotto/faker` replaced with `fakerphp/faker`
- [ ] `composer.json` updated with PHP 8.x support
- [ ] `composer install` runs without errors
- [ ] All existing tests pass
- [ ] Admin panel loads correctly
- [ ] API endpoints respond correctly
- [ ] Backup tag created (v1.0.0-pre-migration)
- [ ] Phase 1 tag created (v1.0.1-phase1-complete)
- [ ] Staging environment ready

---

## 🚨 Troubleshooting

### Issue: Composer can't resolve dependencies

```bash
# Try with ignore platform requirements (temporary)
composer install --ignore-platform-reqs

# Check what's blocking
composer why-not php 8.2
```

### Issue: create_function() not found in config/title.php

The file might have a different name or location. Search for it:

```bash
grep -rn "create_function" --include="*.php" core/
```

### Issue: Tests fail after faker replacement

Update test files that reference Faker:

```php
// BEFORE
use Faker\Generator as Faker;

// AFTER (should work with both packages)
use Faker\Generator as Faker;
```

---

## ➡️ Next Step

Once Phase 1 is complete, proceed to **Phase 2: PHP 8.2 Upgrade** (`03-PHASE2-PHP82-UPGRADE.md`)
