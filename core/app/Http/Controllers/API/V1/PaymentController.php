<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\PaymentNew;
use App\Models\PaymentGatewey;
use App\Models\MbtBindUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * Get payment history for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $status = $request->input('status'); // pending, success, failed
            
            $query = PaymentNew::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $payments = $query->paginate($perPage);
            
            $formattedPayments = collect($payments->items())->map(function ($payment) {
                return $this->formatPayment($payment);
            });

            return $this->successResponse([
                'payments' => $formattedPayments,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ]
            ], 'Payment history retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get payments failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve payment history.');
        }
    }

    /**
     * Get payment details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $payment = PaymentNew::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$payment) {
                return $this->notFoundResponse('Payment not found.');
            }

            return $this->successResponse([
                'payment' => $this->formatPaymentDetailed($payment)
            ], 'Payment details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get payment details failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve payment details.');
        }
    }

    /**
     * Get available payment methods.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function methods()
    {
        try {
            $methods = PaymentGatewey::where('status', 1)
                ->get()
                ->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'name' => $method->name,
                        'type' => $method->type ?? 'online',
                        'icon' => $method->icon ?? null,
                        'description' => $method->description ?? null,
                    ];
                });

            return $this->successResponse([
                'payment_methods' => $methods
            ], 'Payment methods retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get payment methods failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve payment methods.');
        }
    }

    /**
     * Initiate a payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bind_user_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:kbz,wave,aya,cb',
            'months' => 'integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user = $request->user();
            
            // Verify bind user belongs to this user
            $mbtUser = MbtBindUser::find($request->bind_user_id);
            if (!$mbtUser) {
                return $this->notFoundResponse('Bound account not found.');
            }
            
            // Check user has access
            if ($user->bind_user_id != $request->bind_user_id) {
                $bindRecord = \App\Binduser::where('user_id', $user->id)
                    ->where('bind_user_id', $request->bind_user_id)
                    ->first();
                if (!$bindRecord) {
                    return $this->forbiddenResponse('You do not have access to this account.');
                }
            }
            
            // Generate invoice number
            $invoiceNo = 'INV-' . date('Ymd') . '-' . rand(100000, 999999);
            
            // Create pending payment record
            $payment = PaymentNew::create([
                'user_id' => $user->id,
                'invoice_no' => $invoiceNo,
                'order_id' => $request->bind_user_id,
                'total_amt' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_user_name' => $mbtUser->user_name,
                'phone' => $user->phone,
                'status' => 'pending',
                'admin_status' => 0,
                'trans_date' => now(),
            ]);

            // Return payment initiation data
            // The actual payment gateway redirection would happen in the mobile app
            return $this->successResponse([
                'payment_id' => $payment->id,
                'invoice_no' => $invoiceNo,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'callback_url' => url('/api/v1/payments/callback/' . $request->payment_method),
            ], 'Payment initiated successfully', 201);
        } catch (\Exception $e) {
            Log::error('Payment initiation failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to initiate payment.');
        }
    }

    /**
     * Check payment status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $payment = PaymentNew::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$payment) {
                return $this->notFoundResponse('Payment not found.');
            }

            return $this->successResponse([
                'payment_id' => $payment->id,
                'invoice_no' => $payment->invoice_no,
                'status' => $payment->status,
                'admin_status' => $payment->admin_status,
                'transaction_id' => $payment->transaction_id,
            ], 'Payment status retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get payment status failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve payment status.');
        }
    }

    /**
     * Format payment data for API response.
     */
    protected function formatPayment($payment): array
    {
        return [
            'id' => $payment->id,
            'invoice_no' => $payment->invoice_no,
            'amount' => $payment->total_amt,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'trans_date' => $payment->trans_date,
            'created_at' => $payment->created_at ? $payment->created_at->toISOString() : null,
        ];
    }

    /**
     * Format detailed payment data for API response.
     */
    protected function formatPaymentDetailed($payment): array
    {
        return array_merge($this->formatPayment($payment), [
            'order_id' => $payment->order_id,
            'payment_user_name' => $payment->payment_user_name,
            'phone' => $payment->phone,
            'package_id' => $payment->package_id,
            'begin_date' => $payment->begin_date,
            'expire_date' => $payment->expire_date,
            'pack_expiery_date' => $payment->pack_expiery_date,
            'discount' => $payment->discount,
            'commercial_tax' => $payment->commercial_tax,
            'admin_status' => $payment->admin_status,
        ]);
    }
}
