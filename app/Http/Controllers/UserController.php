<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function getUserById(Request $request, $id)
    {
        $user = User::find($id);
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'successful',
            'content' => $user,
            'status' => 200
        ]);
    }

    public function updateSelfUserInfo(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:100',
            'preferred_role' => 'nullable|string|max:100', 
            'bio' => 'nullable|string|max:500',
            'github_username' => 'nullable|string|max:100',
            'tech_stacks' => 'nullable|array', 
            'tech_stacks.*' => 'exists:tech_stacks,id',
        ]);

        $user->update([
            'name' => $request->name,
            'preferred_role' => $request->preferred_role, 
            'bio' => $request->bio,
            'github_username' => $request->github_username,
        ]);

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
