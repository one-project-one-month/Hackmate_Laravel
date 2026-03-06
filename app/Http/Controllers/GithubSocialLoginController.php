<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;
use Throwable;

class GithubSocialLoginController extends Controller
{
    public function getLoginUrl(Request $request)
    {
        $url = Socialite::driver('github')
            ->stateless()
            ->scopes(['repo'])
            ->redirect()
            ->getTargetUrl();
        return response()->json(['url' => $url]);
    }

    public function callback(Request $request)
    {
        try {
            $gh = Socialite::driver('github')->stateless()->user();
        } catch (Throwable $exception) {
            return response()->json([
                'error' => 'github_auth_failed',
                'message' => 'GitHub login failed or was canceled.',
            ], 422);
        }

        $githubId = $gh->getId();
        if (!$githubId) {
            return response()->json([
                'error' => 'github_profile_incomplete',
                'message' => 'GitHub did not return a valid account identifier.',
            ], 422);
        }

        $githubId = (int) $githubId;
        $githubEmail = $gh->getEmail();
        $displayName = $gh->getNickname() ? $gh->getNickname() : 'GitHub User';

        $user = User::where('github_id', $githubId)->first();

        if (!$user) {
            $email = $githubEmail ?: "github_{$githubId}@users.noreply.local";

            $user = User::create([
                'name' => $displayName,
                'email' => $email,
                'github_id' => $githubId,
                'github_username' => $displayName,
                'password' => Str::random(40),
            ]);
        } else {
            $user->fill([
                'github_username' => $displayName,
            ]);
            $user->save();
        }

        $token = auth('api')->login($user);

        $frontendCallback = rtrim(env('FRONTEND_GITHUB_CALLBACK_URL', ''), '/');
        if (!$frontendCallback) {
            return response()->json([
                'error' => 'frontend_callback_not_configured',
                'message' => 'Set FRONTEND_GITHUB_CALLBACK_URL in .env.',
            ], 500);
        }

        return redirect()->away($frontendCallback . '?token=' . $token);
    }
}
