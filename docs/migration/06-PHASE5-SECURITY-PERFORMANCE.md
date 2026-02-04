# Phase 5: Security & Performance

## 🎯 Objectives

1. Implement comprehensive security hardening
2. Add Redis caching for performance
3. Implement queue workers for async processing
4. Add logging and monitoring
5. Optimize database queries

---

## 📋 Prerequisites

- [ ] Phase 4 completed (Refactoring done)
- [ ] Redis server available
- [ ] Staging environment ready

---

## Part 1: Security Hardening

### 1.1 Environment Security

#### .env File Security

```bash
# Ensure .env is never committed
echo ".env" >> .gitignore

# Set proper permissions
chmod 600 core/.env
```

#### Production .env Settings

```env
APP_NAME="ISP Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://isp.mlbbshop.app

# Strong key (generate new one)
APP_KEY=base64:GENERATE_NEW_KEY_HERE

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=telco_db
DB_USERNAME=telco_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Session security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Cache
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

### 1.2 Nginx Security Configuration

```nginx
# /etc/nginx/sites-available/isp.mlbbshop.app

server {
    listen 80;
    server_name isp.mlbbshop.app;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name isp.mlbbshop.app;
    root /var/www/isp.mlbbshop.app/core/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/isp.mlbbshop.app/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/isp.mlbbshop.app/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.ipify.org" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Block sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(env|log|sql|bak|config|ini|sh)$ {
        deny all;
    }

    # Block common attack patterns
    location ~* "(eval\()" {
        deny all;
    }

    location ~* "(127\.0\.0\.1)" {
        deny all;
    }

    location ~* "([a-z0-9]{2000})" {
        deny all;
    }

    location ~* "(javascript:)(.*)(;)" {
        deny all;
    }

    location ~* "(base64_encode)(.*)(\()" {
        deny all;
    }

    location ~* "(GLOBALS|REQUEST)(=|\[|\%)" {
        deny all;
    }

    location ~* "(<|%3C).*script.*(>|%3E)" {
        deny all;
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/(login|api/v1/login|api/v2/login) {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/json;
    gzip_disable "msie6";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 1.3 Laravel Security Middleware

#### HTTPS Enforcement

```php
// app/Http/Middleware/ForceHttps.php

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
```

#### Security Headers Middleware

```php
// app/Http/Middleware/SecurityHeaders.php

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

### 1.4 Input Validation Hardening

```php
// app/Http/Requests/API/BaseRequest.php

<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class BaseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Sanitize all string inputs
        $this->merge(
            collect($this->all())->map(function ($value) {
                if (is_string($value)) {
                    // Strip tags and trim
                    return trim(strip_tags($value));
                }
                return $value;
            })->toArray()
        );
    }
}
```

### 1.5 SQL Injection Prevention

Always use Eloquent or Query Builder:

```php
// NEVER do this:
DB::select("SELECT * FROM users WHERE phone = '" . $request->phone . "'");

// ALWAYS do this:
User::where('phone', $request->phone)->first();
// or
DB::table('users')->where('phone', $request->phone)->first();
// or with bindings
DB::select("SELECT * FROM users WHERE phone = ?", [$request->phone]);
```

### 1.6 XSS Prevention

```php
// In Blade templates, use escaped output
{{ $user->name }}  // Escaped (safe)
{!! $user->name !!}  // Unescaped (dangerous)

// For user-generated HTML content, use Purifier
use Mews\Purifier\Facades\Purifier;

$cleanHtml = Purifier::clean($request->content);
```

---

## Part 2: Performance Optimization

### 2.1 Install and Configure Redis

```bash
# Install Redis on Ubuntu
sudo apt install redis-server

# Enable and start
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Test connection
redis-cli ping
# Should return PONG
```

Install PHP Redis extension:

```bash
sudo apt install php8.2-redis
sudo systemctl restart php8.2-fpm
```

Laravel Redis configuration:

```php
// config/database.php

'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],

    'session' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),
    ],
],
```

### 2.2 Implement Caching

#### Cache Packages

```php
// app/Services/PackageService.php

<?php

namespace App\Services;

use App\Models\Package;
use Illuminate\Support\Facades\Cache;

class PackageService
{
    public function getAllActive()
    {
        return Cache::remember('packages:active', 3600, function () {
            return Package::where('status', 1)
                ->orderBy('price')
                ->get();
        });
    }

    public function find(int $id)
    {
        return Cache::remember("packages:{$id}", 3600, function () use ($id) {
            return Package::find($id);
        });
    }

    public function clearCache(): void
    {
        Cache::forget('packages:active');
        // Clear individual package caches if needed
    }
}
```

#### Cache Banners

```php
// app/Services/BannerService.php

public function getActiveBanners()
{
    return Cache::remember('banners:active', 1800, function () {
        return Banner::where('status', 1)
            ->orderBy('order')
            ->get();
    });
}
```

#### Cache Settings

```php
// app/Services/SettingService.php

public function get(string $key, $default = null)
{
    return Cache::remember("settings:{$key}", 86400, function () use ($key, $default) {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    });
}

public function all()
{
    return Cache::remember('settings:all', 86400, function () {
        return Setting::pluck('value', 'key')->toArray();
    });
}
```

### 2.3 Database Query Optimization

#### Add Indexes

```php
// database/migrations/xxxx_add_indexes_for_performance.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment Query indexes
        Schema::table('payment_query', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('transaction_id');
            $table->index('order_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('phone');
            $table->index('bind_user_id');
            $table->index('user_status');
        });

        // MBT Bind User indexes
        Schema::table('mbt_bind_user', function (Blueprint $table) {
            $table->index('phone');
            $table->index('created_at');
        });

        // Fault Report indexes
        Schema::table('fault_report_queries', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        // Remove indexes
    }
};
```

#### Eager Loading

```php
// BEFORE (N+1 problem):
$users = User::all();
foreach ($users as $user) {
    echo $user->packageorder->package_name;  // N queries
}

// AFTER (Eager loading):
$users = User::with('packageorder')->get();  // 2 queries only
foreach ($users as $user) {
    echo $user->packageorder->package_name;
}
```

#### Pagination for Large Results

```php
// BEFORE (loads all into memory):
$payments = PaymentQuery::all();

// AFTER (paginated):
$payments = PaymentQuery::paginate(50);

// For processing large datasets:
PaymentQuery::chunk(1000, function ($payments) {
    foreach ($payments as $payment) {
        // Process
    }
});
```

### 2.4 Queue Workers for Async Processing

#### Configure Queue

```php
// config/queue.php

'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],
```

#### Create Payment Notification Job

```php
// app/Jobs/SendPaymentNotification.php

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\PaymentQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPaymentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PaymentQuery $payment,
        public string $type = 'success'
    ) {}

    public function handle(): void
    {
        $user = User::find($this->payment->user_id);
        
        if (!$user) {
            return;
        }

        // Send push notification
        // Send SMS
        // Send email
    }
}
```

#### Dispatch Jobs

```php
// In PaymentService after successful payment
SendPaymentNotification::dispatch($payment, 'success');

// For delayed notifications
SendPaymentNotification::dispatch($payment)->delay(now()->addMinutes(5));
```

#### Run Queue Worker

```bash
# Development
php artisan queue:work

# Production (with Supervisor)
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/isp.mlbbshop.app/core/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/isp.mlbbshop.app/core/storage/logs/worker.log
stopwaitsecs=3600
```

### 2.5 OPcache Configuration

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini

opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Set to 1 in development
opcache.save_comments=1
opcache.fast_shutdown=1
opcache.revalidate_freq=0
```

---

## Part 3: Logging & Monitoring

### 3.1 Configure Logging

```php
// config/logging.php

'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],

    'api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api.log'),
        'level' => 'info',
        'days' => 30,
    ],

    'payment' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payment.log'),
        'level' => 'info',
        'days' => 90,  // Keep payment logs longer
    ],

    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'ISP Bot',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

### 3.2 Payment Logging

```php
// app/Services/Payment/PaymentService.php

use Illuminate\Support\Facades\Log;

public function initiatePayment(string $gateway, array $data): PaymentResponse
{
    $channel = Log::channel('payment');
    
    $channel->info('Payment initiated', [
        'gateway' => $gateway,
        'user_id' => $data['user_id'],
        'amount' => $data['amount'],
    ]);

    try {
        $response = $this->getGateway($gateway)->initiate($data);

        $channel->info('Payment response', [
            'gateway' => $gateway,
            'success' => $response->success,
            'transaction_id' => $response->transactionId,
        ]);

        return $response;
    } catch (\Exception $e) {
        $channel->error('Payment failed', [
            'gateway' => $gateway,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        throw $e;
    }
}
```

### 3.3 API Request Logging Middleware

```php
// app/Http/Middleware/LogApiRequests.php

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $startTime;

        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return $response;
    }
}
```

### 3.4 Health Check Endpoint

```php
// routes/api.php

Route::get('/health', function () {
    $checks = [
        'database' => false,
        'redis' => false,
        'storage' => false,
    ];

    // Check database
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {}

    // Check Redis
    try {
        Redis::ping();
        $checks['redis'] = true;
    } catch (\Exception $e) {}

    // Check storage
    $checks['storage'] = is_writable(storage_path());

    $healthy = !in_array(false, $checks);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toISOString(),
    ], $healthy ? 200 : 503);
});
```

---

## Part 4: Deployment Commands

### 4.1 Production Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "🚀 Starting deployment..."

cd /var/www/isp.mlbbshop.app/core

# Enable maintenance mode
php artisan down --message="Upgrading, back in 2 minutes" --retry=60

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
php artisan queue:restart

# Restart PHP-FPM
sudo systemctl reload php8.2-fpm

# Disable maintenance mode
php artisan up

echo "✅ Deployment complete!"
```

### 4.2 Cache Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild all caches for production
php artisan optimize

# Clear specific caches
php artisan cache:clear    # Application cache
php artisan config:clear   # Config cache
php artisan route:clear    # Route cache
php artisan view:clear     # View cache
```

---

## ✅ Phase 5 Completion Checklist

### Security
- [ ] HTTPS enforced
- [ ] Security headers configured
- [ ] Rate limiting enabled
- [ ] .env file secured
- [ ] Nginx hardened
- [ ] SQL injection prevented
- [ ] XSS prevented
- [ ] CSRF protection verified

### Performance
- [ ] Redis installed and configured
- [ ] Caching implemented for packages, banners, settings
- [ ] Database indexes added
- [ ] Eager loading implemented
- [ ] Query optimization done
- [ ] OPcache configured
- [ ] Gzip enabled
- [ ] Static file caching enabled

### Operations
- [ ] Queue workers running (Supervisor)
- [ ] Logging configured (daily, payment, API)
- [ ] Health check endpoint working
- [ ] Deployment script created
- [ ] Monitoring alerts set up

---

## ➡️ Next Step

Once Phase 5 is complete, proceed to **Testing Checklist** (`07-TESTING-CHECKLIST.md`)
