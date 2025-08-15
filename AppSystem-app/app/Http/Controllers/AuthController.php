<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    
    public function login(Request $request)
    {
        Log::info('Login attempt', $request->only('email'));

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            Log::warning('Authentication failed', $request->only('email'));
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        try {

            $tokenResult = $user->createToken('authToken');
            $token = $tokenResult->accessToken;
            $expiresAt = $tokenResult->token->expires_at ?? null;

            return response()->json([
                'token' => $token,
                'token_expires_at' => $expiresAt,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Token creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Token generation failed'], 500);
        }
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required','string','max:255'],
            'last_name'  => ['required','string','max:255'],
            'phone'      => ['nullable','string','max:255'],
            'email'      => ['required','string','email','max:255','unique:users'],
            'password'   => ['required','string','min:8','confirmed'],

        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required','email'],
        ]);

        $status = Password::sendResetLink(['email' => $validated['email']]);
        if ($status === Password::RESET_LINK_SENT) {

            return response()->json(['message' => __($status)], 200);
        }
        return response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token'    => ['required'],
            'email'    => ['required','email'],
            'password' => ['required','confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function ($user) use ($validated) {

                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();


                event(new PasswordReset($user));

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 200);
        }

        return response()->json(['message' => __($status)], 400);
    }
}
