<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Generate token
            $token = Password::createToken($user);
            
            // Create reset URL
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));

            // Send email
            Mail::to($user->email)->send(new ResetPasswordMail($resetUrl));

            return response()->json([
                'status' => true,
                'message' => 'Link reset password telah dikirim ke email Anda'
            ]);
        } catch (\Exception $e) {
            \Log::error('Reset password error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim link reset password'
            ], 500);
        }
    }
} 