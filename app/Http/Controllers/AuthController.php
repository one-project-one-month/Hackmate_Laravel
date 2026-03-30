<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $token = auth('api')->login($user);

            return $this->respondWithToken($token);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, ['errors' => $e->errors()]);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return ApiResponse::error('Unauthorized', 401);
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
        return ApiResponse::success(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return ApiResponse::success(null, 'Successfully logged out');
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
     * Get the token array structure. Updated to include user_id and has_profile_setup
     * for redirection to the profile setup page.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'has_profile_setup' => $user->has_profile_setup,
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        // 1. Generate a random 6-digit OTP
        $otp = rand(100000, 999999);

        // 2. Save or update OTP for this user
        DB::table('password_reset_otps')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'otp_code' => (string) $otp,
                'expires_at' => now()->addMinutes(10),
                'used_at' => null,
                'created_at' => now(),
            ]
        );

        // 3. In production, you would Mail::to($request->email)->send(...)
        // For now, we return it in JSON so you can copy-paste it to Postman
        return ApiResponse::success([
            'otp' => $otp, // REMOVE THIS LINE IN PRODUCTION!
        ], 'OTP generated successfully.');
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

            $user = User::where('email', $request->email)->firstOrFail();

            // 2. Check if the OTP is valid and not expired
            $resetData = DB::table('password_reset_otps')
                ->where('user_id', $user->id)
                ->where('otp_code', $request->otp)
                ->whereNull('used_at')
                ->where('expires_at', '>=', now())
                ->first();

            if (! $resetData) {
                return ApiResponse::error('Invalid or expired OTP.', 422);
            }

            // 3. Update the User's password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // 4. Mark OTP as used
            DB::table('password_reset_otps')
                ->where('id', $resetData->id)
                ->update(['used_at' => now()]);

            return ApiResponse::success(null, 'Password has been reset successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Catch validation errors specifically (like 422 errors)
            return ApiResponse::error('Validation failed', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            // Catch system errors (500 errors)
            return ApiResponse::error('An error occurred while resetting the password.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
