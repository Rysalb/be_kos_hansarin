<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Config;

class PasswordResetController extends Controller
{
    // Untuk pengguna terautentikasi
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Generate token
            $token = Password::createToken($user);
            
            // Buat URL reset
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));

            // Kirim email
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
    
    // Untuk pengguna publik (tidak terautentikasi)
    public function forgotPassword(Request $request)
    {
        \Log::info('Received forgot password request', ['email' => $request->email]);
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for forgot password', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            \Log::info('User not found for password reset', ['email' => $request->email]);
            return response()->json([
                'status' => false,
                'message' => 'Email tidak terdaftar dalam sistem kami'
            ], 404);
        }
        
        try {
            // Generate token
            $token = Password::createToken($user);
            
            // Ubah URL yang dikirim di email
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);
            
            // Log informasi
            \Log::info('Reset URL created', ['url' => $resetUrl]);
            
            // Setup transport dengan opsi SSL
            Config::set('mail.mailers.smtp.stream_options', [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
            
            // Log informasi sebelum mengirim email
            \Log::info('Attempting to send email via: ' . config('mail.default') . ' driver', [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
            ]);
            
            // Kirim email
            Mail::to($user->email)->send(new ResetPasswordMail($resetUrl));
            
            \Log::info('Email sent successfully');
            
            return response()->json([
                'status' => true,
                'message' => 'Link reset password telah dikirim ke email Anda'
            ]);
        } catch (\Exception $e) {
            \Log::error('Forgot password error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                ]
            ]);
            
            // Berikan pesan yang lebih ramah pengguna
            $debugInfo = null;
            if (config('app.debug')) {
                $debugInfo = [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim link reset password. Mohon coba beberapa saat lagi.',
                'debug_info' => $debugInfo
            ], 500);
        }
    }
    
    // Untuk reset password dengan token
    public function resetPassword(Request $request)
    {
        \Log::info('Reset password request received', $request->all());
        
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                
                $user->save();
                
                event(new PasswordReset($user));
                
                \Log::info('Password reset successful for user: ' . $user->email);
            }
        );
        
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => true,
                'message' => 'Password berhasil direset'
            ]);
        }
        
        \Log::error('Password reset failed with status: ' . $status);
        
        return response()->json([
            'status' => false,
            'message' => __($status)
        ], 500);
    }
}