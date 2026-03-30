<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getSelfProfile(Request $request)
    {
        $authUserId = $request->user()->id;

        $user = User::query()
            ->with(['techStacks:id,name,category', 'joinedProjects:id,title,created_by_user_id', 'projects:id,title,created_by_user_id'])
            ->withCount([
                'projects as created_projects_count',
                'joinedProjects as joined_projects_count',
            ])
            ->selectSub(function ($query) {
                $query->from('join_requests')
                    ->join('projects', 'projects.id', '=', 'join_requests.project_id')
                    ->selectRaw('count(*)')
                    ->whereColumn('projects.created_by_user_id', 'users.id')
                    ->where('join_requests.status', 'pending');
            }, 'pending_join_requests_count')
            ->selectSub(function ($query) {
                $query->from('join_requests')
                    ->selectRaw('count(*)')
                    ->whereColumn('join_requests.user_id', 'users.id')
                    ->where('join_requests.status', 'approved');
            }, 'approved_join_requests_count')
            ->findOrFail($authUserId);

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'preferred_role' => $user->preferred_role,
            'bio' => $user->bio,
            'github_username' => $user->github_username,
            'profile_image' => $user->profile_image,
            'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
            'has_profile_setup' => $user->has_profile_setup,
            'tech_stacks' => $user->techStacks,
            'metrics' => [
                'created_projects_count' => $user->created_projects_count,
                'joined_projects_count' => $user->joined_projects_count,
                'pending_join_requests_count' => $user->pending_join_requests_count,
                'approved_join_requests_count' => $user->approved_join_requests_count,
            ],
        ]);
    }

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
