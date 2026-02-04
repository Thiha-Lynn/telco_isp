# Phase 4: Code Refactoring

## 🎯 Objectives

1. Refactor `MbtController.php` (5,552 lines → multiple focused controllers)
2. Implement Service Layer pattern
3. Add Repository pattern for data access
4. Create Form Request classes for validation
5. Implement API Resources for consistent responses

---

## 📋 Prerequisites

- [ ] Phase 3 completed (Laravel 10 running)
- [ ] All tests pass
- [ ] Staging environment ready

---

## ⚠️ Important Notes

1. **Incremental Refactoring**: Don't refactor everything at once
2. **Keep Old Code Working**: Maintain backward compatibility
3. **Test Each Change**: Verify functionality after each refactor
4. **Document Changes**: Update API documentation

---

## Step 1: Create New Directory Structure

```bash
cd core

# Create new directories
mkdir -p app/Http/Controllers/API/V2/Auth
mkdir -p app/Http/Controllers/API/V2/User
mkdir -p app/Http/Controllers/API/V2/Payment
mkdir -p app/Http/Controllers/API/V2/Package
mkdir -p app/Http/Controllers/API/V2/Notification
mkdir -p app/Http/Controllers/API/V2/FaultReport
mkdir -p app/Http/Controllers/API/V2/System

mkdir -p app/Services/Payment/Gateways
mkdir -p app/Services/Mbt
mkdir -p app/Services/Notification

mkdir -p app/Repositories/Contracts
mkdir -p app/Repositories/Eloquent

mkdir -p app/Http/Requests/API/V2/Auth
mkdir -p app/Http/Requests/API/V2/Payment
mkdir -p app/Http/Requests/API/V2/User

mkdir -p app/Http/Resources
```

---

## Step 2: Create Base Classes

### 2.1 Base API Controller

```php
// app/Http/Controllers/API/V2/BaseController.php

<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{
    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     */
    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response
     */
    protected function paginated($paginator, $resource = null): JsonResponse
    {
        $data = $resource 
            ? $resource::collection($paginator->items())
            : $paginator->items();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
```

### 2.2 Base Repository Interface

```php
// app/Repositories/Contracts/RepositoryInterface.php

<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
```

### 2.3 Base Repository Implementation

```php
// app/Repositories/Eloquent/BaseRepository.php

<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);
        return $record ? $record->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $record = $this->find($id);
        return $record ? $record->delete() : false;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }
}
```

---

## Step 3: Create Payment Service Layer

### 3.1 Payment Gateway Interface

```php
// app/Services/Payment/Gateways/GatewayInterface.php

<?php

namespace App\Services\Payment\Gateways;

interface GatewayInterface
{
    /**
     * Initialize a payment
     */
    public function initiate(array $data): PaymentResponse;

    /**
     * Check payment status
     */
    public function checkStatus(string $transactionId): PaymentStatus;

    /**
     * Process callback from gateway
     */
    public function handleCallback(array $data): CallbackResult;

    /**
     * Get gateway name
     */
    public function getName(): string;

    /**
     * Check if gateway is enabled
     */
    public function isEnabled(): bool;
}
```

### 3.2 Payment Response DTOs

```php
// app/Services/Payment/Gateways/PaymentResponse.php

<?php

namespace App\Services\Payment\Gateways;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId,
        public readonly ?string $redirectUrl,
        public readonly ?string $qrCode,
        public readonly ?string $message,
        public readonly array $rawResponse = []
    ) {}

    public static function success(
        string $transactionId,
        ?string $redirectUrl = null,
        ?string $qrCode = null,
        array $rawResponse = []
    ): self {
        return new self(
            success: true,
            transactionId: $transactionId,
            redirectUrl: $redirectUrl,
            qrCode: $qrCode,
            message: 'Payment initiated successfully',
            rawResponse: $rawResponse
        );
    }

    public static function failure(string $message, array $rawResponse = []): self
    {
        return new self(
            success: false,
            transactionId: null,
            redirectUrl: null,
            qrCode: null,
            message: $message,
            rawResponse: $rawResponse
        );
    }
}
```

### 3.3 CB Pay Gateway Service

```php
// app/Services/Payment/Gateways/CBPayGateway.php

<?php

namespace App\Services\Payment\Gateways;

use App\Models\BankSetting;
use App\Models\PendingPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CBPayGateway implements GatewayInterface
{
    protected BankSetting $settings;

    public function __construct()
    {
        $this->settings = BankSetting::first();
    }

    public function getName(): string
    {
        return 'cbpay';
    }

    public function isEnabled(): bool
    {
        return $this->settings->cb_status == 1;
    }

    public function initiate(array $data): PaymentResponse
    {
        if (!$this->isEnabled()) {
            return PaymentResponse::failure('CB Pay is currently disabled');
        }

        try {
            $orderId = $this->generateOrderId();
            $amount = $data['amount'];
            $userId = $data['user_id'];
            $packageId = $data['package_id'] ?? null;

            // Build request payload
            $payload = [
                'mer_id' => $this->settings->mer_id,
                'sub_mer_id' => $this->settings->sub_mer_id,
                'ecommerce_id' => $this->settings->ecommerce_id,
                'order_id' => $orderId,
                'amount' => $amount,
                'transaction_type' => $this->settings->transaction_type,
                'notify_url' => $this->settings->notifyurl,
                'redirect_url' => $this->settings->cb_redirect,
            ];

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->settings->auth_token,
                'Content-Type' => 'application/json',
            ])->post($this->settings->api_url, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['qr_code'])) {
                // Store pending payment
                PendingPayment::create([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'amount' => $amount,
                    'payment_method' => 'cbpay',
                    'status' => 'pending',
                    'raw_request' => json_encode($payload),
                    'raw_response' => json_encode($result),
                ]);

                return PaymentResponse::success(
                    transactionId: $orderId,
                    qrCode: $result['qr_code'] ?? null,
                    redirectUrl: $result['redirect_url'] ?? null,
                    rawResponse: $result
                );
            }

            Log::error('CB Pay initiation failed', [
                'response' => $result,
                'payload' => $payload,
            ]);

            return PaymentResponse::failure(
                $result['message'] ?? 'Payment initiation failed',
                $result
            );

        } catch (\Exception $e) {
            Log::error('CB Pay exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PaymentResponse::failure('Payment service error: ' . $e->getMessage());
        }
    }

    public function checkStatus(string $transactionId): PaymentStatus
    {
        // Implementation for checking payment status
        // Extract from MbtController::mbtcbpaystatus()
    }

    public function handleCallback(array $data): CallbackResult
    {
        // Implementation for handling callback
        // Extract from MbtController::notify()
    }

    protected function generateOrderId(): string
    {
        return 'CB' . date('YmdHis') . rand(1000, 9999);
    }
}
```

### 3.4 KBZ Pay Gateway Service

```php
// app/Services/Payment/Gateways/KBZPayGateway.php

<?php

namespace App\Services\Payment\Gateways;

use App\Models\BankSetting;
use App\Models\PendingPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KBZPayGateway implements GatewayInterface
{
    protected BankSetting $settings;

    public function __construct()
    {
        $this->settings = BankSetting::first();
    }

    public function getName(): string
    {
        return 'kbzpay';
    }

    public function isEnabled(): bool
    {
        return $this->settings->kbz_status == 1;
    }

    public function initiate(array $data): PaymentResponse
    {
        if (!$this->isEnabled()) {
            return PaymentResponse::failure('KBZ Pay is currently disabled');
        }

        try {
            $orderId = $this->generateOrderId();
            $amount = $data['amount'];
            $userId = $data['user_id'];

            // Build signature
            $signString = $this->buildSignString($orderId, $amount);
            $signature = $this->generateSignature($signString);

            $payload = [
                'appid' => $this->settings->kbz_appid,
                'merch_code' => $this->settings->kbz_m_code,
                'trade_type' => $this->settings->kbz_trade_type,
                'nonce_str' => $this->generateNonce(),
                'out_trade_no' => $orderId,
                'total_fee' => $amount,
                'notify_url' => $this->settings->kbz_notifyurl,
                'sign' => $signature,
            ];

            $response = Http::post($this->settings->kbz_api_url, $payload);
            $result = $response->json();

            if ($response->successful() && $result['return_code'] === 'SUCCESS') {
                PendingPayment::create([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'payment_method' => 'kbzpay',
                    'status' => 'pending',
                ]);

                return PaymentResponse::success(
                    transactionId: $orderId,
                    redirectUrl: $result['pay_url'] ?? null,
                    rawResponse: $result
                );
            }

            return PaymentResponse::failure(
                $result['return_msg'] ?? 'Payment initiation failed',
                $result
            );

        } catch (\Exception $e) {
            Log::error('KBZ Pay exception', ['message' => $e->getMessage()]);
            return PaymentResponse::failure('Payment service error');
        }
    }

    public function checkStatus(string $transactionId): PaymentStatus
    {
        // Extract from MbtController::mbtkbzpaystatus()
    }

    public function handleCallback(array $data): CallbackResult
    {
        // Extract from MbtController callback handling
    }

    protected function generateOrderId(): string
    {
        return 'KBZ' . date('YmdHis') . rand(1000, 9999);
    }

    protected function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function buildSignString(string $orderId, float $amount): string
    {
        // Implementation
    }

    protected function generateSignature(string $data): string
    {
        return hash_hmac('sha256', $data, $this->settings->kbz_key);
    }
}
```

### 3.5 Payment Service (Orchestrator)

```php
// app/Services/Payment/PaymentService.php

<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\GatewayInterface;
use App\Services\Payment\Gateways\CBPayGateway;
use App\Services\Payment\Gateways\KBZPayGateway;
use App\Services\Payment\Gateways\AYAPayGateway;
use App\Services\Payment\Gateways\WavePayGateway;
use App\Services\Payment\Gateways\PaymentResponse;
use InvalidArgumentException;

class PaymentService
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerGateways();
    }

    protected function registerGateways(): void
    {
        $this->gateways = [
            'cbpay' => new CBPayGateway(),
            'kbzpay' => new KBZPayGateway(),
            'ayapay' => new AYAPayGateway(),
            'wavepay' => new WavePayGateway(),
        ];
    }

    public function getGateway(string $name): GatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Unknown payment gateway: {$name}");
        }

        return $this->gateways[$name];
    }

    public function initiatePayment(string $gateway, array $data): PaymentResponse
    {
        return $this->getGateway($gateway)->initiate($data);
    }

    public function checkStatus(string $gateway, string $transactionId): PaymentStatus
    {
        return $this->getGateway($gateway)->checkStatus($transactionId);
    }

    public function getAvailableGateways(): array
    {
        return collect($this->gateways)
            ->filter(fn($gateway) => $gateway->isEnabled())
            ->keys()
            ->toArray();
    }
}
```

---

## Step 4: Create Form Request Classes

### 4.1 Login Request

```php
// app/Http/Requests/API/V2/Auth/LoginRequest.php

<?php

namespace App\Http\Requests\API\V2\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
            'device_type' => 'nullable|string|in:android,ios',
            'device_id' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
```

### 4.2 Payment Initiate Request

```php
// app/Http/Requests/API/V2/Payment/InitiatePaymentRequest.php

<?php

namespace App\Http\Requests\API\V2\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => 'required|string|in:cbpay,kbzpay,ayapay,wavepay',
            'amount' => 'required|numeric|min:100',
            'package_id' => 'nullable|integer|exists:packages,id',
            'bind_user_id' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
```

---

## Step 5: Create API Resources

### 5.1 User Resource

```php
// app/Http/Resources/UserResource.php

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_image' => $this->profile_image 
                ? asset('assets/front/img/user/' . $this->profile_image) 
                : null,
            'bind_user_id' => $this->bind_user_id,
            'sub_company' => $this->sub_company,
            'user_status' => $this->user_status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

### 5.2 Package Resource

```php
// app/Http/Resources/PackageResource.php

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'speed' => $this->speed,
            'features' => $this->features,
            'status' => $this->status,
            'image' => $this->photo 
                ? asset('assets/front/img/package/' . $this->photo) 
                : null,
        ];
    }
}
```

### 5.3 Payment Resource

```php
// app/Http/Resources/PaymentResource.php

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->total_amt,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'invoice_no' => $this->invoice_no,
            'begin_date' => $this->begin_date,
            'expire_date' => $this->expire_date,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

---

## Step 6: Create Refactored V2 Controllers

### 6.1 Auth Controller

```php
// app/Http/Controllers/API/V2/Auth/AuthController.php

<?php

namespace App\Http\Controllers\API\V2\Auth;

use App\Http\Controllers\API\V2\BaseController;
use App\Http\Requests\API\V2\Auth\LoginRequest;
use App\Http\Requests\API\V2\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        if ($user->user_status != 1) {
            return $this->error('Account is disabled', 403);
        }

        // Update device info
        if ($request->device_type || $request->device_id) {
            $user->update([
                'device_type' => $request->device_type,
                'device_id' => $request->device_id,
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user())
        );
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->success(null, 'Password changed successfully');
    }
}
```

### 6.2 Payment Controller

```php
// app/Http/Controllers/API/V2/Payment/PaymentController.php

<?php

namespace App\Http\Controllers\API\V2\Payment;

use App\Http\Controllers\API\V2\BaseController;
use App\Http\Requests\API\V2\Payment\InitiatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\Payment\PaymentService;
use App\Models\PaymentQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function methods(): JsonResponse
    {
        return $this->success([
            'available_gateways' => $this->paymentService->getAvailableGateways(),
        ]);
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $response = $this->paymentService->initiatePayment(
            gateway: $request->gateway,
            data: [
                'amount' => $request->amount,
                'user_id' => $request->user()->id,
                'bind_user_id' => $request->bind_user_id,
                'package_id' => $request->package_id,
                'description' => $request->description,
            ]
        );

        if (!$response->success) {
            return $this->error($response->message, 400);
        }

        return $this->success([
            'transaction_id' => $response->transactionId,
            'redirect_url' => $response->redirectUrl,
            'qr_code' => $response->qrCode,
        ], 'Payment initiated');
    }

    public function history(Request $request): JsonResponse
    {
        $payments = PaymentQuery::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginated($payments, PaymentResource::class);
    }

    public function status(Request $request, string $transactionId): JsonResponse
    {
        $payment = PaymentQuery::where('transaction_id', $transactionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$payment) {
            return $this->error('Payment not found', 404);
        }

        return $this->success(new PaymentResource($payment));
    }
}
```

---

## Step 7: Create V2 API Routes

```php
// routes/api_v2.php

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v2')->group(function () {
    
    // Public routes
    Route::post('login', [\App\Http\Controllers\API\V2\Auth\AuthController::class, 'login']);
    Route::post('register', [\App\Http\Controllers\API\V2\Auth\AuthController::class, 'register']);
    
    Route::get('packages', [\App\Http\Controllers\API\V2\Package\PackageController::class, 'index']);
    Route::get('banners', [\App\Http\Controllers\API\V2\System\SystemController::class, 'banners']);
    Route::get('maintenance', [\App\Http\Controllers\API\V2\System\SystemController::class, 'maintenance']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('logout', [\App\Http\Controllers\API\V2\Auth\AuthController::class, 'logout']);
        Route::get('profile', [\App\Http\Controllers\API\V2\Auth\AuthController::class, 'profile']);
        Route::put('profile', [\App\Http\Controllers\API\V2\User\ProfileController::class, 'update']);
        Route::post('change-password', [\App\Http\Controllers\API\V2\Auth\AuthController::class, 'changePassword']);

        // Payments
        Route::get('payments/methods', [\App\Http\Controllers\API\V2\Payment\PaymentController::class, 'methods']);
        Route::post('payments/initiate', [\App\Http\Controllers\API\V2\Payment\PaymentController::class, 'initiate']);
        Route::get('payments', [\App\Http\Controllers\API\V2\Payment\PaymentController::class, 'history']);
        Route::get('payments/{id}', [\App\Http\Controllers\API\V2\Payment\PaymentController::class, 'status']);

        // Bind Users
        Route::apiResource('bind-users', \App\Http\Controllers\API\V2\User\BindUserController::class);

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\API\V2\Notification\NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [\App\Http\Controllers\API\V2\Notification\NotificationController::class, 'markAsRead']);

        // Fault Reports
        Route::apiResource('fault-reports', \App\Http\Controllers\API\V2\FaultReport\FaultReportController::class);
    });

    // Payment Callbacks (no auth required)
    Route::prefix('callbacks')->group(function () {
        Route::any('cbpay', [\App\Http\Controllers\API\V2\Payment\CallbackController::class, 'cbpay']);
        Route::any('kbzpay', [\App\Http\Controllers\API\V2\Payment\CallbackController::class, 'kbzpay']);
        Route::any('ayapay', [\App\Http\Controllers\API\V2\Payment\CallbackController::class, 'ayapay']);
        Route::any('wavepay', [\App\Http\Controllers\API\V2\Payment\CallbackController::class, 'wavepay']);
    });
});
```

---

## Step 8: Register Service Provider

```php
// app/Providers/AppServiceProvider.php

<?php

namespace App\Providers;

use App\Services\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register PaymentService as singleton
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });
    }

    public function boot(): void
    {
        //
    }
}
```

---

## Step 9: Testing Refactored Code

### 9.1 Unit Tests

```php
// tests/Unit/Services/PaymentServiceTest.php

<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Payment\PaymentService;

class PaymentServiceTest extends TestCase
{
    public function test_can_get_available_gateways(): void
    {
        $service = new PaymentService();
        $gateways = $service->getAvailableGateways();

        $this->assertIsArray($gateways);
    }

    public function test_throws_exception_for_unknown_gateway(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = new PaymentService();
        $service->getGateway('unknown');
    }
}
```

### 9.2 Feature Tests

```php
// tests/Feature/API/V2/AuthTest.php

<?php

namespace Tests\Feature\API\V2;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'password' => bcrypt('password123'),
            'user_status' => 1,
        ]);

        $response = $this->postJson('/api/v2/login', [
            'phone' => '09123456789',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v2/login', [
            'phone' => '09123456789',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }
}
```

---

## Step 10: Migration Strategy for MbtController

### Gradual Migration Approach

1. **Week 1**: Extract payment gateway methods
2. **Week 2**: Extract user/bind-user methods
3. **Week 3**: Extract notification methods
4. **Week 4**: Extract remaining methods

### Keep Legacy API Working

```php
// app/Http/Controllers/API/MbtController.php

// Add deprecation notices
public function mbtcbpay(Request $request)
{
    // Log deprecation warning
    Log::info('Deprecated: Use /api/v2/payments/initiate instead');
    
    // Forward to new service
    $service = app(PaymentService::class);
    return $service->initiatePayment('cbpay', $request->all());
}
```

---

## ✅ Phase 4 Completion Checklist

- [ ] Directory structure created
- [ ] Base classes implemented
- [ ] Payment Service layer created
- [ ] All payment gateways extracted to services
- [ ] Form Request classes created
- [ ] API Resources created
- [ ] V2 Controllers implemented
- [ ] V2 Routes registered
- [ ] Unit tests passing
- [ ] Feature tests passing
- [ ] Legacy API still works
- [ ] V1 API still works

---

## ➡️ Next Step

Once Phase 4 is complete, proceed to **Phase 5: Security & Performance** (`06-PHASE5-SECURITY-PERFORMANCE.md`)
