<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotController extends Controller
{
    /**
     * Show the forgot password form
     */
    public function showforgotform()
    {
        return view('user.forgot');
    }

    /**
     * Handle forgot password request
     */
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // For now, just redirect back with a message
        // Full implementation would send password reset email
        return redirect()->back()->with('success', 'If your email exists in our system, you will receive a password reset link.');
    }
}
