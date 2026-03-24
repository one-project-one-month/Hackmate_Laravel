<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
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

        return ApiResponse::success(['url' => $url]);
    }

    /**
     * Updated to include user's id and user's has_profile_setup flag 
     * for redirection to the profile setup page.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            $gh = Socialite::driver('github')->stateless()->user();
        } catch (Throwable $exception) {
            return ApiResponse::error('GitHub login failed or was canceled.', 422, [
                'code' => 'github_auth_failed',
            ]);
        }

        $githubId = $gh->getId();
        if (! $githubId) {
            return ApiResponse::error('GitHub did not return a valid account identifier.', 422, [
                'code' => 'github_profile_incomplete',
            ]);
        }

        $githubToken = $gh->token;
        if (! $githubToken) {
            return ApiResponse::error('GitHub did not return an access token.', 422, [
                'code' => 'github_token_missing',
            ]);
        }

        $githubId = (int) $githubId;
        $githubEmail = $gh->getEmail();
        $displayName = $gh->getNickname() ? $gh->getNickname() : 'GitHub User';

        $user = User::where('github_id', $githubId)->first();

        if (! $user) {
            $email = $githubEmail ?: "github_{$githubId}@users.noreply.local";

            $user = User::create([
                'name' => $displayName,
                'email' => $email,
                'github_id' => $githubId,
                'github_username' => $displayName,
                'github_token' => $githubToken,
                'password' => Str::random(40),
            ]);
        } else {
            $user->fill([
                'github_username' => $displayName,
                'github_token' => $githubToken,
            ]);
            $user->save();
        }

        $token = auth('api')->login($user);

        $frontendCallback = rtrim(env('FRONTEND_GITHUB_CALLBACK_URL', ''), '/');
        if (! $frontendCallback) {
            return ApiResponse::error('Set FRONTEND_GITHUB_CALLBACK_URL in .env.', 500, [
                'code' => 'frontend_callback_not_configured',
            ]);
        }

        return redirect()->away($frontendCallback . '?' . http_build_query([
            'token' => $token,
            'user_id' => $user->id,
            'has_profile_setup' => $user->has_profile_setup,
        ]));
    }
}
