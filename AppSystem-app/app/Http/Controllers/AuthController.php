<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Password;

use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('Login attempt: ' . json_encode($request->all()));
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            Log::info('Authentication successful for: ' . $request->email);
            $user = Auth::user();
            try {
                $token = $user->createToken('authToken')->accessToken;
                Log::info('Token generated: ' . $token);
                return response()->json(['token' => $token], 200);
            } catch (\Exception $e) {
                Log::error('Token creation failed: ' . $e->getMessage());
                return response()->json(['error' => 'Token generation failed'], 500);
            }
        }
        Log::warning('Authentication failed for: ' . $request->email);
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function forgotPassword(Request $request)
{
   $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {
        // Log the token for testing (remove in production)
        $token = \DB::table('password_reset_tokens')->where('email', $request->email)->first()->token;
        \Log::info('Reset token for ' . $request->email . ': ' . $token);
    }

    return response()->json(['message' => __($status)], $status === Password::RESET_LINK_SENT ? 200 : 400);
}

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return response()->json(['message' => __($status)], $status === Password::PASSWORD_RESET ? 200 : 400);
    }

    public function register(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);


    $validated['password'] = Hash::make($validated['password']);

    $user = User::create($validated);

    return response()->json([
        "message" => 'User created successfully',
       201 => $user]);
}
}
?>
