<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->has_profile_setup) {
            return response()->json([
                'error' => 'profile_incomplete',
                'message' => 'Please complete your profile setup before proceeding.',
            ], 403);
        }

        return $next($request);
    }
}
