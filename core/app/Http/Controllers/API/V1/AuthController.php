<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Token expiration time in days
     */
    protected $tokenExpirationDays = 30;

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|unique:users|min:7|max:15',
            'name' => 'required|string|min:2|max:100',
            'password' => 'required|string|min:6|max:50',
            'confirm_password' => 'required|same:password',
        ], [
            'phone.unique' => 'This phone number is already registered.',
            'password.min' => 'Password must be at least 6 characters.',
            'confirm_password.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'phone' => $request->phone,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'user_status' => 0, // 0 = Normal (active)
            ]);

            // Generate API token
            $tokenData = $user->createToken(
                'mobile_app',
                ['*'],
                now()->addDays($this->tokenExpirationDays)
            );

            DB::commit();

            return $this->successResponse([
                'user' => $this->formatUserData($user),
                'token' => $tokenData['plainTextToken'],
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays($this->tokenExpirationDays)->toISOString(),
            ], 'Registration successful', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Registration failed. Please try again.');
        }
    }

    /**
     * Login user and create token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:7|max:15',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->errorResponse('Invalid phone number or password.', 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid phone number or password.', 401);
        }

        // user_status: 0 = Normal (active), 1 = Disabled
        if ($user->user_status == 1) {
            return $this->errorResponse('Your account is disabled. Please contact support.', 403);
        }

        // Revoke old tokens (optional - for single device login)
        // $user->revokeAllTokens();

        // Generate new API token
        $tokenData = $user->createToken(
            'mobile_app',
            ['*'],
            now()->addDays($this->tokenExpirationDays)
        );

        return $this->successResponse([
            'user' => $this->formatUserData($user),
            'token' => $tokenData['plainTextToken'],
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays($this->tokenExpirationDays)->toISOString(),
        ], 'Login successful');
    }

    /**
     * Logout user (revoke current token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user && $user->currentAccessToken()) {
                $user->revokeCurrentToken();
            }

            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Logout failed.');
        }
    }

    /**
     * Logout from all devices (revoke all tokens).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                $user->revokeAllTokens();
            }

            return $this->successResponse(null, 'Logged out from all devices successfully');
        } catch (\Exception $e) {
            Log::error('Logout all failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Logout failed.');
        }
    }

    /**
     * Get current user profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => $this->formatUserData($user),
        ], 'Profile retrieved successfully');
    }

    /**
     * Refresh the access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $user->revokeCurrentToken();

            // Generate new token
            $tokenData = $user->createToken(
                'mobile_app',
                ['*'],
                now()->addDays($this->tokenExpirationDays)
            );

            return $this->successResponse([
                'token' => $tokenData['plainTextToken'],
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays($this->tokenExpirationDays)->toISOString(),
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Token refresh failed.');
        }
    }

    /**
     * Change user password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|max:50',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect.', 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Optionally revoke all tokens after password change
            // $user->revokeAllTokens();

            return $this->successResponse(null, 'Password changed successfully');
        } catch (\Exception $e) {
            Log::error('Password change failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Password change failed.');
        }
    }

    /**
     * Format user data for API response.
     *
     * @param  \App\User  $user
     * @return array
     */
    protected function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'photo' => $user->photo,
            'user_status' => $user->user_status,
            'bind_user_id' => $user->bind_user_id,
            'created_at' => $user->created_at ? $user->created_at->toISOString() : null,
        ];
    }

    /**
     * Get all active packages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function packages()
    {
        try {
            $packages = \App\Package::where('status', 1)->get();
            return $this->successResponse($packages, 'Packages retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Packages fetch failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to fetch packages.');
        }
    }

    /**
     * Get all active banners/sliders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function banners()
    {
        try {
            $banners = \App\Slider::where('status', 1)->get();
            return $this->successResponse($banners, 'Banners retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Banners fetch failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to fetch banners.');
        }
    }

    /**
     * Request password reset (Forgot Password).
     * Sends OTP to phone number.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:7|max:15',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                // Don't reveal if phone exists or not for security
                return $this->successResponse([
                    'otp_sent' => true,
                    'expires_in' => 300, // 5 minutes
                ], 'If this phone number is registered, you will receive an OTP.');
            }

            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            
            // Store OTP in cache/database (expires in 5 minutes)
            \Illuminate\Support\Facades\Cache::put(
                'password_reset_otp_' . $request->phone,
                [
                    'otp' => $otp,
                    'user_id' => $user->id,
                    'attempts' => 0,
                ],
                now()->addMinutes(5)
            );

            // TODO: Send OTP via SMS gateway
            // For now, log it (remove in production)
            Log::info("Password reset OTP for {$request->phone}: {$otp}");

            return $this->successResponse([
                'otp_sent' => true,
                'expires_in' => 300,
                // Remove this in production - only for testing
                'debug_otp' => app()->environment('local') ? $otp : null,
            ], 'OTP sent to your phone number.');
        } catch (\Exception $e) {
            Log::error('Forgot password failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to process request.');
        }
    }

    /**
     * Verify OTP and reset password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:7|max:15',
            'otp' => 'required|digits:6',
            'new_password' => 'required|string|min:6|max:50',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $cacheKey = 'password_reset_otp_' . $request->phone;
            $otpData = \Illuminate\Support\Facades\Cache::get($cacheKey);

            if (!$otpData) {
                return $this->errorResponse('OTP expired or invalid. Please request a new one.', 400);
            }

            // Check max attempts (3)
            if ($otpData['attempts'] >= 3) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                return $this->errorResponse('Too many invalid attempts. Please request a new OTP.', 429);
            }

            // Verify OTP
            if ($otpData['otp'] != $request->otp) {
                $otpData['attempts']++;
                \Illuminate\Support\Facades\Cache::put($cacheKey, $otpData, now()->addMinutes(5));
                return $this->errorResponse('Invalid OTP. ' . (3 - $otpData['attempts']) . ' attempts remaining.', 400);
            }

            // Reset password
            $user = User::find($otpData['user_id']);
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Clear OTP
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            // Revoke all existing tokens
            $user->revokeAllTokens();

            return $this->successResponse(null, 'Password reset successfully. Please login with your new password.');
        } catch (\Exception $e) {
            Log::error('Reset password failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to reset password.');
        }
    }

    /**
     * Update device token for push notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|max:500',
            'device_type' => 'required|in:android,ios',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user = $request->user();
            
            // Store device token (you may want a separate table for multiple devices)
            DB::table('user_devices')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'device_type' => $request->device_type,
                ],
                [
                    'device_token' => $request->device_token,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return $this->successResponse(null, 'Device token updated successfully.');
        } catch (\Exception $e) {
            Log::error('Update device token failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to update device token.');
        }
    }

    /**
     * Delete user account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user = $request->user();

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Invalid password.', 400);
            }

            // Log deletion reason
            Log::info("User account deletion requested", [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'reason' => $request->reason ?? 'No reason provided',
            ]);

            // Revoke all tokens
            $user->revokeAllTokens();

            // Soft delete - set status to disabled instead of hard delete
            $user->update([
                'user_status' => 1, // 1 = Disabled
                'phone' => $user->phone . '_deleted_' . time(), // Prevent phone reuse issues
            ]);

            return $this->successResponse(null, 'Account deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Delete account failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to delete account.');
        }
    }
}
