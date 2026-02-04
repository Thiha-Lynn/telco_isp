# Current System Inventory

## 📊 Complete Feature List

This document contains a comprehensive inventory of all features, modules, models, controllers, and APIs in the current ISP Management System.

---

## 1. Eloquent Models (50+ Models)

### Location: `core/app/*.php`

| Model | Table | Purpose |
|-------|-------|---------|
| `User.php` | `users` | Customer accounts |
| `Admin.php` | `admins` | Admin accounts |
| `About.php` | `abouts` | About page content |
| `AyaCallback.php` | `aya_callbacks` | AYA Pay callback logs |
| `Backup.php` | `backups` | Database backup records |
| `BankSetting.php` | `bank_settings` | Payment gateway credentials |
| `Bcategory.php` | `bcategories` | Blog categories |
| `Billpaid.php` | `billpaids` | Bill payment records |
| `Binduser.php` | `bindusers` | User binding data |
| `Blog.php` | `blogs` | Blog posts |
| `Branch.php` | `branches` | Office locations |
| `CborderDetail.php` | `cborder_details` | CB Pay order details |
| `Client.php` | `clients` | Client records |
| `Currency.php` | `currencies` | Currency settings |
| `Daynamicpage.php` | `daynamicpages` | Dynamic CMS pages |
| `Education.php` | `educations` | Education content |
| `EmailTemplate.php` | `email_templates` | Email templates |
| `Emailsetting.php` | `emailsettings` | SMTP configuration |
| `Entertainment.php` | `entertainments` | Entertainment content |
| `ErrorCode.php` | `error_codes` | API error codes |
| `ExtraMonth.php` | `extra_months` | Promotional extra months |
| `Faq.php` | `faqs` | FAQ content |
| `FaultReportQuery.php` | `fault_report_queries` | Fault tickets |
| `Funfact.php` | `funfacts` | Statistics display |
| `Language.php` | `languages` | Multi-language support |
| `MaintenanceSetting.php` | `maintenance_settings` | Maintenance mode |
| `MbtBindUser.php` | `mbt_bind_user` | MBT user binding |
| `Mediazone.php` | `mediazones` | Media gallery |
| `Multimessage.php` | `multimessages` | Bulk messages |
| `Newsletter.php` | `newsletters` | Email subscribers |
| `Notification.php` | `notifications` | User notifications |
| `Offerprovide.php` | `offerprovides` | Special offers |
| `OrderItem.php` | `order_items` | Product order items |
| `Package.php` | `packages` | Internet packages |
| `Packageorder.php` | `packageorders` | Package purchases |
| `PaymentGatewey.php` | `payment_gateweys` | Gateway settings |
| `PaymentNew.php` | `payment_news` | New payment records |
| `PaymentProcess.php` | `payment_processes` | Payment processing |
| `PaymentQuery.php` | `payment_query` | Payment search/history |
| `PendingPayment.php` | `pending_payments` | Pending transactions |
| `PermissionModel.php` | `permissions` | RBAC permissions |
| `PersonalAccessToken.php` | `personal_access_tokens` | API tokens |
| `Portfolio.php` | `portfolios` | Portfolio content |
| `Product.php` | `products` | Physical products |
| `ProductOrder.php` | `product_orders` | Product purchases |
| `Promotion.php` | `promotions` | Promotional campaigns |
| `Role.php` | `roles` | Admin roles |
| `Scategory.php` | `scategories` | Service categories |
| `Sectiontitle.php` | `sectiontitles` | CMS section titles |
| `Service.php` | `services` | Service descriptions |
| `Setting.php` | `settings` | Global settings |
| `Shipping.php` | `shippings` | Shipping methods |
| `Skill.php` | `skills` | Team skills |
| `Slider.php` | `sliders` | Homepage sliders |
| `Social.php` | `socials` | Social media links |
| `Speedtest.php` | `speedtests` | Speed test config |
| `StatusDescription.php` | `status_descriptions` | Status messages |
| `SubCompany.php` | `sub_companies` | Sub-company management |
| `Team.php` | `teams` | Team members |
| `Testimonial.php` | `testimonials` | Customer reviews |
| `UserDevice.php` | `user_devices` | Device tracking |
| `UserQuery.php` | `user_queries` | Support queries |
| `WaveCallback.php` | `wave_callbacks` | Wave Pay callbacks |

---

## 2. Admin Controllers (40 Controllers)

### Location: `core/app/Http/Controllers/Admin/`

| Controller | Features |
|------------|----------|
| `AboutController.php` | About page CRUD, contact info |
| `BackupController.php` | Database backup management |
| `BcategoryController.php` | Blog category CRUD |
| `BlogController.php` | Blog post CRUD |
| `BranchController.php` | Branch location CRUD |
| `CacheController.php` | Cache clearing |
| `CurrencyController.php` | Currency management |
| `DashboardController.php` | Dashboard stats, payment/fault/install queries, user management, roles |
| `DynamicpageController.php` | Dynamic page CRUD |
| `EmailController.php` | Email templates, SMTP config |
| `EntertainmentController.php` | Entertainment content CRUD |
| `ExportController.php` | Excel/PDF exports |
| `FaqController.php` | FAQ CRUD |
| `FooterController.php` | Footer settings |
| `FunfactController.php` | Statistics CRUD |
| `LanguageController.php` | Multi-language management |
| `LoginController.php` | Admin authentication |
| `MaintainanceController.php` | Maintenance mode settings |
| `MbtController.php` | MBT API proxy functions |
| `MediaController.php` | Media gallery CRUD |
| `NewsletterController.php` | Newsletter subscribers |
| `OfferController.php` | Special offers CRUD |
| `OrderController.php` | Package orders management |
| `PackagController.php` | Internet packages CRUD |
| `PaymentController.php` | Payment process management |
| `PaymentGatewayController.php` | Gateway configuration |
| `PendingController.php` | Pending payments (CB/KBZ/Wave) |
| `ProductController.php` | Physical products CRUD |
| `ProductOrderController.php` | Product orders management |
| `ProfileController.php` | Admin profile management |
| `PromotionController.php` | Promotions, extra months |
| `RegisterUserController.php` | User registration management |
| `ServiceController.php` | Services CRUD |
| `SettingController.php` | System settings, bank settings, banners |
| `ShippingMethodController.php` | Shipping methods CRUD |
| `SliderController.php` | Homepage slider CRUD |
| `SocialController.php` | Social links CRUD |
| `TeamController.php` | Team members CRUD |
| `TestimonialController.php` | Testimonials CRUD |

---

## 3. API Controllers

### 3.1 Legacy API (`core/app/Http/Controllers/API/`)

| Controller | Purpose |
|------------|---------|
| `LoginController.php` | User auth, registration, profile |
| `MbtController.php` | **CRITICAL: 5,552 lines, 94 methods** - All MBT integration, payments |

### 3.2 V1 API (`core/app/Http/Controllers/API/V1/`)

| Controller | Purpose |
|------------|---------|
| `AuthController.php` | Authentication endpoints |
| `BindUserController.php` | User binding management |
| `FaultReportController.php` | Fault report CRUD |
| `NotificationController.php` | Notification management |
| `PackageController.php` | Package information |
| `PaymentController.php` | Payment initiation |
| `ProfileController.php` | User profile |
| `SystemController.php` | System info, banners, maintenance |

---

## 4. Payment Gateway Integrations

### 4.1 Myanmar Payment Gateways

| Gateway | Controller Methods | Callback URLs |
|---------|-------------------|---------------|
| **CB Pay** | `mbtcbpay()`, `mbtcbpaystatus()`, `notify()`, `cbredirect()` | `/api/notify`, `/api/cb-redirect` |
| **KBZ Pay** | `mbtkbzpay()`, `mbtkbzpaystatus()`, `kbzredirect()`, `mbtkbzrefund()` | `/api/kbz-redirect`, `/api/kbz-callback-url` |
| **AYA Pay** | `aya_access_token()`, `aya_merchant_login()`, `aya_request_payment()`, `ayacallback()` | `/api/aya-callback` |
| **Wave Pay** | `wave_request_payment()`, `wave_callback_payment()` | `/api/wave-callback-payment` |
| **KBZ Direct** | `mbtmobilesuccess()`, `mbtmobilefailure()`, `decrypt()` | `/api/kbz-mobile-success`, `/api/kbz-mobile-failure` |

### 4.2 International Payment Gateways

| Gateway | Location | Status |
|---------|----------|--------|
| PayPal | `Payment/Package/PaypalController.php` | Active |
| Stripe | `Payment/Package/StripeController.php` | Active |
| PayPal (Bill) | `Payment/Paybill/PaypalController.php` | Active |
| Stripe (Bill) | `Payment/Paybill/StripeController.php` | Active |
| PayPal (Product) | `Payment/Product/PaypalController.php` | Active |
| Stripe (Product) | `Payment/Product/StripeController.php` | Active |

### 4.3 Bank Settings Model Fields

```php
// core/app/BankSetting.php
$fillable = [
    // CB Pay
    'type', 'api_url', 'auth_token', 'ecommerce_id', 'sub_mer_id', 
    'mer_id', 'transaction_type', 'notifyurl', 'cb_status', 'cb_redirect',
    
    // KBZ Pay
    'kbz_type', 'kbz_api_url', 'kbz_m_code', 'kbz_appid', 'kbz_key', 
    'kbz_trade_type', 'kbz_notifyurl', 'kbz_version', 'kbz_redirecct', 'kbz_status',
    
    // AYA Pay
    'aya_paytype', 'aya_api_tokenurl', 'aya_consumer_key', 'aya_consumer_secret',
    'aya_grant_type', 'aya_api_baseurl', 'aya_phone', 'aya_password', 'aya_enc_key', 'aya_status',
    
    // KBZ Direct
    'direct_type', 'direct_apiurl', 'direct_mcode', 'direct_key', 'direct_status',
    
    // Wave Pay
    'wave_live_seconds', 'wave_merchnt_id', 'wave_callback_url', 
    'wave_secret_key', 'wave_base_url', 'wave_status'
];
```

---

## 5. API Routes

### 5.1 Legacy API Routes (`routes/api.php`)

```
GET  /api/get-access-token
GET  /api/store-user
GET  /api/view-user
GET  /api/bound-device
GET  /api/install-broadband-bind-mobile-number
GET  /api/query-ordersed-product
GET  /api/send-notification
GET  /api/product-operators
GET  /api/add-group
GET  /api/view-all-groups
GET  /api/add-billing
GET  /api/create-control
GET  /api/get-package-information
GET  /api/user-super-search
GET  /api/get-notification
GET  /api/get-package
GET  /api/get-new-package
GET  /api/get-new-package-information
GET  /api/get-banner
GET  /api/get-error-code
GET  /api/mbtprofile
GET  /api/check-maintenance
GET  /api/mbt-referer
GET  /api/mbt-return
GET  /api/wave-mbt-return

POST /api/check-validation
POST /api/register
POST /api/login
POST /api/forgot-password
POST /api/apply-install-broadband
POST /api/get-payment-record
POST /api/update-notification
POST /api/language-id
POST /api/get-payment-failed-record
POST /api/user-message
POST /api/get-package-message
POST /api/user-device
POST /api/mbt-cb-pay
POST /api/mbt-cb-pay-status
POST /api/kbz-callback-url
POST /api/cb-redirect
POST /api/mbt-kbz-pay
POST /api/mbt-kbz-pay-status
POST /api/kbz-redirect
POST /api/mbt-kbz-refund-status
POST /api/mbt-kbz-close
POST /api/mbt-kbz-refund
POST /api/check-payment
POST /api/mbt-kbz-pay-check
POST /api/kbz-mobile-success
POST /api/kbz-mobile-decrypt
POST /api/aya-access-token
POST /api/aya-merchant-login
POST /api/aya-request-payment
POST /api/aya-payment-status
POST /api/kbz-payment-status
POST /api/wave-hash-token
POST /api/wave-request-payment
POST /api/wave-authenticate-payment
POST /api/check-user
POST /api/store-failure-reports
POST /api/get-self-profile
POST /api/get-language
POST /api/bind-user
POST /api/change-number
POST /api/change-password
POST /api/profile-image
POST /api/about-page-content
POST /api/insert-message
POST /api/get-message
POST /api/get-apply-query
POST /api/update-apply-query
POST /api/unbind-user
POST /api/store-payment
POST /api/payment-method
POST /api/bindcheck
POST /api/loginotp
POST /api/signupotp
POST /api/update-payment
POST /api/check-app-version
POST /api/remove-user
POST /api/check-new-user
POST /api/check-expire-time
POST /api/get-new-package-message
POST /api/store-new-payment
POST /api/print-invoice
```

### 5.2 V1 API Routes (`routes/api_v1.php`)

```
# Authentication (Rate Limited)
POST /api/v1/register
POST /api/v1/login
POST /api/v1/forgot-password
POST /api/v1/reset-password

# Public Routes
GET  /api/v1/packages
GET  /api/v1/packages/{id}
GET  /api/v1/banners
GET  /api/v1/maintenance-status
GET  /api/v1/app-version
GET  /api/v1/settings

# Protected Read Routes
GET  /api/v1/profile
GET  /api/v1/bind-users
GET  /api/v1/bind-users/{id}
GET  /api/v1/my-packages
GET  /api/v1/payments
GET  /api/v1/payments/methods
GET  /api/v1/payments/{id}
GET  /api/v1/payments/{id}/status
GET  /api/v1/notifications
GET  /api/v1/notifications/unread-count
GET  /api/v1/notifications/{id}
GET  /api/v1/fault-reports
GET  /api/v1/fault-reports/{id}

# Protected Write Routes
POST   /api/v1/logout
POST   /api/v1/logout-all
POST   /api/v1/refresh-token
POST   /api/v1/change-password
POST   /api/v1/device-token
DELETE /api/v1/account
PUT    /api/v1/profile
POST   /api/v1/profile/image
POST   /api/v1/bind-users
DELETE /api/v1/bind-users/{id}
POST   /api/v1/payments/initiate
PUT    /api/v1/notifications/{id}/read
PUT    /api/v1/notifications/read-all
POST   /api/v1/fault-reports
PUT    /api/v1/fault-reports/{id}
DELETE /api/v1/fault-reports/{id}
```

---

## 6. Admin Panel Pages

### 6.1 Dashboard & Queries

- Dashboard with statistics
- Payment Query (search, filter, export)
- Fault Query (search, filter, export)
- Install Query (search, filter, export)
- User Query
- Bind User Query

### 6.2 User Management

- User list
- User details
- User update
- User disable/enable
- Role management
- Permission management
- Register new users

### 6.3 Content Management

- Sliders
- About page
- Services
- Packages
- Offers
- Testimonials
- Entertainment
- Media Zone
- Branches
- Team members
- FAQs
- Blog categories
- Blog posts
- Dynamic pages
- Newsletters

### 6.4 Settings

- Basic info (logo, company details)
- SEO settings
- Social links
- Email templates
- Email configuration
- Bank settings (payment gateways)
- App banners
- Error messages
- Cookie consent
- Scripts (analytics)
- Page visibility
- Custom CSS

### 6.5 Orders & Payments

- Package orders (all, pending, in-progress, completed)
- Bill payments
- Product orders
- Pending payments (CB Pay, KBZ Pay, Wave Pay)
- Payment process management

### 6.6 Promotions

- Promotion campaigns
- Extra months configuration

### 6.7 Products

- Product management
- Shipping methods
- Currency settings

### 6.8 System

- Language management
- Cache clearing
- Database backup
- Maintenance settings

---

## 7. Middleware

### Location: `core/app/Http/Middleware/`

| Middleware | Purpose |
|------------|---------|
| `ApiRateLimiter.php` | API rate limiting (auth: 5/min, read: 120/min, write: 30/min) |
| `Authenticate.php` | Web authentication |
| `AuthenticateApi.php` | API token authentication |
| `CheckForMaintenanceMode.php` | Maintenance mode check |
| `EncryptCookies.php` | Cookie encryption |
| `RedirectIfAuthenticated.php` | Guest redirect |
| `SetLangMiddleware.php` | Language setting (frontend) |
| `SetLocale.php` | Locale setting (admin) |
| `TrimStrings.php` | Input trimming |
| `TrustProxies.php` | Proxy trust (load balancer) |
| `VerifyCsrfToken.php` | CSRF protection |

---

## 8. Database Migrations

### Location: `core/database/migrations/`

Key migrations include:
- `2020_05_25_122740_create_admins_table.php`
- `2020_05_26_000000_create_users_table.php`
- `2020_10_13_123757_create_settings_table.php`
- `2020_10_27_025201_create_packages_table.php`
- `2020_11_20_064826_create_payment_gateweys_table.php`
- `2020_12_25_122853_create_billpaids_table.php`
- `2020_12_25_123618_create_packageorders_table.php`
- `2025_12_22_000001_create_personal_access_tokens_table.php`

---

## 9. Custom Traits

### Location: `core/app/Traits/`

| Trait | Purpose |
|-------|---------|
| `HasApiTokens.php` | Custom API token management (similar to Sanctum) |

---

## 10. View Templates

### Location: `core/resources/views/`

```
views/
├── admin/
│   ├── layout.blade.php          # Admin layout
│   ├── login.blade.php           # Admin login
│   ├── dashboard.blade.php       # Dashboard
│   ├── about/                    # About CRUD views
│   ├── banner/                   # Banner management
│   ├── bill/                     # Bill management
│   ├── blog/                     # Blog CRUD views
│   ├── branch/                   # Branch CRUD views
│   ├── currency/                 # Currency views
│   ├── dynamicpage/              # Dynamic page views
│   ├── email/                    # Email template views
│   ├── entertainment/            # Entertainment views
│   ├── faq/                      # FAQ views
│   ├── fault/                    # Fault query views
│   ├── footer/                   # Footer settings
│   ├── funfact/                  # Statistics views
│   ├── install/                  # Install query views
│   ├── language/                 # Language views
│   ├── maintainance/             # Maintenance views
│   ├── marketting/               # Marketing views
│   ├── media/                    # Media gallery views
│   ├── message/                  # Message views
│   ├── newsletter/               # Newsletter views
│   ├── offer/                    # Offer views
│   ├── package/                  # Package CRUD views
│   ├── partials/                 # Shared partials
│   ├── payment/                  # Payment views
│   ├── payment_gateway/          # Gateway config views
│   ├── payments/                 # Payment query views
│   ├── pending/                  # Pending payment views
│   ├── product/                  # Product views
│   ├── profile/                  # Profile views
│   ├── promotions/               # Promotion views
│   ├── register_user/            # User registration views
│   ├── role/                     # Role management views
│   ├── service/                  # Service views
│   ├── settings/                 # Settings views
│   ├── shipping/                 # Shipping views
│   ├── slider/                   # Slider views
│   ├── team/                     # Team views
│   ├── testimonial/              # Testimonial views
│   └── user/                     # User management views
├── front/                        # Frontend views
├── user/                         # User portal views
├── pdf/                          # PDF templates
└── errors/                       # Error pages
```

---

*This inventory was generated on February 3, 2026*
*Total Controllers: 50+ | Total Models: 50+ | Total API Endpoints: 80+*
