<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserById(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return ApiResponse::error('User not found', 404);
        }

        return ApiResponse::success($user);
    }

    public function updateSelfUserInfo(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'name' => 'required|string|max:100',
            'preferred_role' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'github_username' => 'nullable|string|max:100',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'string|max:100|distinct',
        ]);

        $user->update([
            'name' => $request->name ?? $user->name,
            'preferred_role' => $request->preferred_role ?? $user->preferred_role,
            'bio' => $request->bio,
            'github_username' => $request->github_username,
            'tech_stacks' => $request->has('tech_stacks') ? $request->tech_stacks : $user->tech_stacks,
        ]);

        return ApiResponse::success($user->fresh(), 'Profile updated successfully');
    }
}
