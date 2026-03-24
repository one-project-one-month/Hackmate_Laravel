<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserById(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'successful',
            'content' => $user,
            'status' => 200,
        ]);
    }

    public function updateSelfUserInfo(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'name' => 'nullable|string|max:100|required_without_all:preferred_role,bio,github_username,tech_stacks',
            'preferred_role' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'github_username' => 'nullable|string|max:100',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
        ]);

        $updateData = $request->only(['name', 'preferred_role', 'bio', 'github_username']);
        
        $user->update(array_filter($updateData));

        if ($request->has('tech_stacks')) {
            $user->techStacks()->sync($request->tech_stacks);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'content' => $user->load('techStacks'),
            'status' => 200
        ]);
    }
}