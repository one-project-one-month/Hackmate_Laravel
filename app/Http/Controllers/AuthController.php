<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL()
        ]);
    }

    public function forgotPassword(Request $request)
   {
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    // 1. Generate a random 6-digit OTP
    $otp = rand(100000, 999999);

    // 2. Save or Update the OTP in the table
    // updateOrInsert prevents duplicate rows for the same email
    DB::table('password_reset_otps')->updateOrInsert(
        ['email' => $request->email],
        [
            'otp' => $otp,
            'created_at' => now()
        ]
    );

    // 3. In production, you would Mail::to($request->email)->send(...)
    // For now, we return it in JSON so you can copy-paste it to Postman
    return response()->json([
        'message' => 'OTP generated successfully.',
        'otp' => $otp // REMOVE THIS LINE IN PRODUCTION!
    ]);
    }

    public function resetPassword(Request $request)
    {
    try {
        // 1. Validation
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed', 
        ]);

        // 2. Check if the OTP exists
        $resetData = DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$resetData) {
            return response()->json(['message' => 'Invalid OTP or Email.'], 422);
        }

        // 3. Update the User's password
        $user = User::where('email', $request->email)->first();
        
        // Fix: Use the same name as in your validation ('password')
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // 4. Cleanup
        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully.']);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Catch validation errors specifically (like 422 errors)
        return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Catch system errors (500 errors)
        return response()->json([
            'message' => 'An error occurred while resetting the password.', 
            'error' => $e->getMessage()
        ], 500);
    }   
    }
}