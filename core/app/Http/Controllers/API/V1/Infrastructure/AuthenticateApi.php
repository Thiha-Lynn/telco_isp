<?php

namespace App\Http\Middleware;

use App\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Unauthenticated. Please provide a valid access token.',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Invalid access token.',
            ], 401);
        }

        // Check if token is expired
        if ($accessToken->isExpired()) {
            $accessToken->delete();
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Access token has expired. Please login again.',
            ], 401);
        }

        // Get the user
        $user = $accessToken->tokenable;

        // user_status: 0 = Normal (active), 1 = Disabled
        if (!$user || $user->user_status == 1) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User account is disabled or not found.',
            ], 401);
        }

        // Update last used timestamp
        $accessToken->update(['last_used_at' => now()]);

        // Set the user and token on the request
        $request->setUserResolver(function () use ($user, $accessToken) {
            return $user->withAccessToken($accessToken);
        });

        return $next($request);
    }

    /**
     * Get the token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request)
    {
        // Check Authorization header (Bearer token)
        $header = $request->header('Authorization', '');
        
        if (strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }

        // Fallback to query parameter (not recommended for production)
        return $request->query('api_token');
    }
}
