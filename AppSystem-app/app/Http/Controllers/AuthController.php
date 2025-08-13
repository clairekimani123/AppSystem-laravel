<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
}
?>
