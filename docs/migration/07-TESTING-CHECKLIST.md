# Phase 7: Testing Checklist

## 🎯 Overview

This comprehensive testing checklist covers all phases of the migration to **PHP 8.4 + Laravel 12**. Use this document to verify that every feature works correctly after each phase.

---

## 📋 General Testing Guidelines

### Test Environment Setup

1. **Create staging environment** that mirrors production
2. **Clone production database** to staging (sanitize sensitive data)
3. **Configure separate .env** for staging
4. **Set up separate Redis instance** for staging

### Testing Tools

```bash
# PHPUnit for backend tests
cd /var/www/isp.mlbbshop.app/core
php artisan test

# API testing with curl/Postman
# Browser testing for admin panel
# Mobile app testing on staging API
```

---

## Phase 1: Pre-Migration Testing

### Code Quality Checks

- [ ] Run PHP syntax check on all files
  ```bash
  find app -name "*.php" -exec php -l {} \;
  ```
- [ ] Run PHPStan/Psalm static analysis
  ```bash
  ./vendor/bin/phpstan analyse app --level=5
  ```
- [ ] Check for deprecated function usage
- [ ] Verify all `create_function()` removed
- [ ] Check nullable parameter syntax fixed

### Backup Verification

- [ ] Full database backup completed
- [ ] Full codebase backup completed
- [ ] Backup restoration tested on staging
- [ ] Rollback procedure documented and tested

---

## Phase 2: PHP 8.4 Testing

### Compatibility Testing

- [ ] No errors in error_log after switch
- [ ] All pages load without 500 errors
- [ ] API endpoints return valid JSON
- [ ] File uploads working
- [ ] Image processing working
- [ ] Session handling working
- [ ] Database connections working
- [ ] No "implicitly nullable" deprecation warnings

### PHP 8.4 Specific Checks

```bash
# Check PHP version
php -v
# Expected: PHP 8.4.x

# Check loaded extensions
php -m

# Check JIT is enabled
php -i | grep "opcache.jit"

# Run application console
php artisan --version
```

### PHP 8.4 New Features Available

- [ ] Property hooks work (if used)
- [ ] Asymmetric visibility works (if used)
- [ ] `array_find()` function available
- [ ] `#[\Deprecated]` attribute works

---

## Phase 3: Laravel 12 Upgrade Testing

### After Each Laravel Version (7→8→9→10→11→12)

#### Core Framework

- [ ] `php artisan` commands work
- [ ] `php artisan migrate:status` shows correct status
- [ ] `php artisan config:cache` succeeds
- [ ] `php artisan route:cache` succeeds
- [ ] `php artisan view:cache` succeeds

#### Authentication

- [ ] Admin login works
- [ ] Admin logout works
- [ ] Session persists across pages
- [ ] "Remember me" functionality works
- [ ] Password reset works

#### Database Operations

- [ ] Eloquent queries execute correctly
- [ ] Relationships load properly
- [ ] Migrations run without errors
- [ ] Seeders run without errors

### Laravel 12 Specific Tests

- [ ] Sanctum 4.x tokens work
- [ ] Carbon 3 date operations work correctly
- [ ] `diffInHours()` returns correct values (now float)
- [ ] Local storage disk paths correct
- [ ] Image validation with SVG (if needed)

---

## Phase 4: Refactoring Testing

### Service Layer Testing

```php
// Run these tests after creating services
php artisan test --filter=PaymentServiceTest
php artisan test --filter=UserServiceTest
```

### API V2 Testing

Use these curl commands to test the new V2 API:

#### Authentication

```bash
# Login
curl -X POST https://staging.isp.mlbbshop.app/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "09123456789", "password": "password123"}'

# Expected: {"success": true, "data": {"token": "...", "user": {...}}}
```

#### User Profile

```bash
# Get profile
curl -X GET https://staging.isp.mlbbshop.app/api/v2/user/profile \
  -H "Authorization: Bearer YOUR_TOKEN"

# Expected: {"success": true, "data": {"id": 1, "name": "...", ...}}
```

#### Packages

```bash
# List packages
curl -X GET https://staging.isp.mlbbshop.app/api/v2/packages \
  -H "Authorization: Bearer YOUR_TOKEN"

# Expected: {"success": true, "data": [{"id": 1, "name": "...", ...}]}
```

---

## Phase 5: Security & Performance Testing

### Security Testing

#### HTTPS

- [ ] HTTP redirects to HTTPS
- [ ] SSL certificate valid
- [ ] No mixed content warnings

#### Headers

```bash
# Check security headers
curl -I https://isp.mlbbshop.app

# Should see:
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block
# Strict-Transport-Security: max-age=...
```

#### Rate Limiting

```bash
# Test login rate limit (should block after 5 attempts)
for i in {1..10}; do
  curl -X POST https://isp.mlbbshop.app/api/v1/login \
    -d "phone=test&password=wrong" 2>/dev/null | head -1
done
# Last requests should return 429 Too Many Requests
```

### Performance Testing

#### Response Time

```bash
# Measure API response time
time curl -s https://isp.mlbbshop.app/api/v1/packages > /dev/null

# Target: < 200ms
```

#### Cache Testing

```bash
# Verify Redis caching
redis-cli keys "*"

# Should see cached keys like:
# packages:active
# banners:active
# settings:all
```

---

## 🔴 Critical Feature Testing

### Payment Gateways

**CRITICAL: Test with small amounts on staging first!**

#### CB Pay

```bash
# Initiate payment
curl -X POST https://staging.isp.mlbbshop.app/api/v2/payment/initiate \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"gateway": "cbpay", "package_id": 1, "phone": "09123456789"}'
```

- [ ] Payment initiation returns order_id
- [ ] CB Pay redirect URL works
- [ ] Callback received and processed
- [ ] Payment status updated correctly
- [ ] User package activated after success
- [ ] Failure handled gracefully

#### KBZ Pay

- [ ] QR code generation works
- [ ] Callback URL receives data
- [ ] Payment verification succeeds
- [ ] User notified of payment status

#### AYA Pay

- [ ] Payment initiation works
- [ ] Callback processed correctly
- [ ] Error handling works

#### Wave Pay

- [ ] Transaction ID generated
- [ ] Callback verification works
- [ ] Signature validation works

#### KBZ Direct

- [ ] Bank transfer details provided
- [ ] Manual verification works
- [ ] Admin can confirm payments

---

## 📱 Mobile App API Testing

### Complete API Endpoint Checklist

#### Authentication (/api/v1/ or /api/v2/)

| Endpoint | Method | Test Status | Notes |
|----------|--------|-------------|-------|
| `/login` | POST | [ ] | Phone + Password |
| `/logout` | POST | [ ] | Token required |
| `/register` | POST | [ ] | New user creation |
| `/password/reset` | POST | [ ] | Password reset |
| `/user/profile` | GET | [ ] | Get user info |
| `/user/profile` | PUT | [ ] | Update user info |

#### Packages

| Endpoint | Method | Test Status | Notes |
|----------|--------|-------------|-------|
| `/packages` | GET | [ ] | List all packages |
| `/packages/{id}` | GET | [ ] | Single package |
| `/packages/featured` | GET | [ ] | Featured packages |

#### Payments

| Endpoint | Method | Test Status | Notes |
|----------|--------|-------------|-------|
| `/payment/initiate` | POST | [ ] | Start payment |
| `/payment/status` | GET | [ ] | Check status |
| `/payment/history` | GET | [ ] | Payment history |
| `/cbpay/callback` | POST | [ ] | CB Pay callback |
| `/kbzpay/callback` | POST | [ ] | KBZ Pay callback |
| `/ayapay/callback` | POST | [ ] | AYA Pay callback |
| `/wavepay/callback` | POST | [ ] | Wave Pay callback |

#### User Operations

| Endpoint | Method | Test Status | Notes |
|----------|--------|-------------|-------|
| `/binduser` | POST | [ ] | Bind user |
| `/notifications` | GET | [ ] | Get notifications |
| `/fault-report` | POST | [ ] | Report fault |
| `/fault-report` | GET | [ ] | Get fault reports |

---

## 🖥️ Admin Panel Testing

### Dashboard

- [ ] Dashboard loads without errors
- [ ] Statistics displayed correctly
- [ ] Charts render properly
- [ ] Recent activities shown

### User Management

| Feature | Test Status | Notes |
|---------|-------------|-------|
| User list loads | [ ] | |
| Search users | [ ] | |
| Filter users | [ ] | |
| View user details | [ ] | |
| Edit user | [ ] | |
| Delete user | [ ] | |
| Export users | [ ] | |

### Package Management

| Feature | Test Status | Notes |
|---------|-------------|-------|
| Package list loads | [ ] | |
| Create package | [ ] | |
| Edit package | [ ] | |
| Delete package | [ ] | |
| Activate/Deactivate | [ ] | |
| Image upload | [ ] | |

### Payment Management

| Feature | Test Status | Notes |
|---------|-------------|-------|
| Payment list loads | [ ] | |
| Filter by status | [ ] | |
| Filter by gateway | [ ] | |
| View payment details | [ ] | |
| Manual payment confirmation | [ ] | |
| Export payments | [ ] | |

### Order Management

| Feature | Test Status | Notes |
|---------|-------------|-------|
| Order list loads | [ ] | |
| Filter orders | [ ] | |
| View order details | [ ] | |
| Update order status | [ ] | |

### Fault Reports

| Feature | Test Status | Notes |
|---------|-------------|-------|
| Report list loads | [ ] | |
| View report details | [ ] | |
| Update report status | [ ] | |
| Respond to report | [ ] | |

### Settings

| Feature | Test Status | Notes |
|---------|-------------|-------|
| General settings save | [ ] | |
| Payment gateway settings | [ ] | |
| Email settings | [ ] | |
| SMS settings | [ ] | |
| Notification settings | [ ] | |

### Reports & Analytics

| Feature | Test Status | Notes |
|---------|-------------|-------|
| Sales reports | [ ] | |
| User reports | [ ] | |
| Payment reports | [ ] | |
| Date range filters | [ ] | |
| Export to CSV/PDF | [ ] | |

---

## 🔧 Infrastructure Testing

### Database

```bash
# Check connection
mysql -u telco_user -p -e "SELECT 1"

# Check tables
mysql -u telco_user -p telco_db -e "SHOW TABLES"

# Check data integrity
mysql -u telco_user -p telco_db -e "SELECT COUNT(*) FROM users"
```

### Redis

```bash
# Check connection
redis-cli ping

# Check memory usage
redis-cli info memory

# Check keys
redis-cli keys "*" | head -20
```

### Queue Workers

```bash
# Check Supervisor status
sudo supervisorctl status

# Check queue length
php artisan queue:monitor default,high

# Check failed jobs
php artisan queue:failed
```

### Logs

```bash
# Check Laravel logs
tail -100 storage/logs/laravel.log

# Check payment logs
tail -100 storage/logs/payment.log

# Check Nginx logs
tail -100 /var/log/nginx/error.log
```

---

## 🚨 Rollback Testing

### Prepare Rollback

Before production deployment, verify you can rollback:

1. **Database rollback**
   ```bash
   # Test on staging
   mysql -u root -p telco_db < backup.sql
   ```

2. **Code rollback**
   ```bash
   git checkout PREVIOUS_COMMIT_HASH
   composer install
   ```

3. **Full rollback procedure**
   - [ ] Switch PHP back to 7.4 works
   - [ ] Old Laravel code runs
   - [ ] Database restore completes
   - [ ] Site functional after rollback

---

## 📊 Performance Baseline

Record these metrics BEFORE and AFTER migration:

### API Response Times

| Endpoint | Before | After | Target |
|----------|--------|-------|--------|
| `/api/v1/packages` | ___ms | ___ms | <200ms |
| `/api/v1/user/profile` | ___ms | ___ms | <100ms |
| `/api/v1/login` | ___ms | ___ms | <300ms |
| `/api/v1/payment/history` | ___ms | ___ms | <500ms |

### Page Load Times

| Page | Before | After | Target |
|------|--------|-------|--------|
| Dashboard | ___s | ___s | <2s |
| User List | ___s | ___s | <3s |
| Payment List | ___s | ___s | <3s |
| Reports | ___s | ___s | <5s |

### Resource Usage

| Metric | Before | After |
|--------|--------|-------|
| Memory (PHP) | ___MB | ___MB |
| CPU (idle) | ___% | ___% |
| Disk usage | ___GB | ___GB |

---

## ✅ Final Sign-Off

### Pre-Production Checklist

- [ ] All Phase 1-5 tests pass
- [ ] All payment gateways tested
- [ ] All admin panel features work
- [ ] All API endpoints functional
- [ ] Mobile app works with new API
- [ ] Performance meets targets
- [ ] Security scan passed
- [ ] Backup/rollback tested
- [ ] Monitoring configured
- [ ] Team trained on new features

### Production Deployment Approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Developer | | | |
| QA Engineer | | | |
| Project Manager | | | |
| Client | | | |

---

## 📝 Notes

Use this space to record any issues found during testing:

```
Date: ___________
Issue: 
Steps to reproduce:
Fix applied:
Verified by:
```

---

**Document Version:** 1.0  
**Last Updated:** [Current Date]  
**Next Review:** After each phase completion
