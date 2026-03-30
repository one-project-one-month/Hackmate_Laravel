<?php

namespace App\Http\Controllers;

use App\Models\JoinRequest;
use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function send(Request $request, string $project_id)
    {
        $user = $request->user();
        $project = Project::findOrFail($project_id);

        if ($project->created_by_user_id === $user->id) {
            return ApiResponse::error('You cannot send a join request to your own project.', 422);
        }

        if ($project->users()->where('users.id', $user->id)->exists()) {
            return ApiResponse::error('You are already a member of this project.', 422);
        }

        $joinRequest = JoinRequest::firstOrNew([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        if ($joinRequest->exists && $joinRequest->status === 'pending') {
            return ApiResponse::error('A pending join request already exists for this project.', 422);
        }

        $joinRequest->status = 'pending';
        $joinRequest->save();

        return ApiResponse::success([
            'id' => $joinRequest->id,
            'project_id' => $joinRequest->project_id,
            'user_id' => $joinRequest->user_id,
            'status' => $joinRequest->status,
        ], 'Join request sent successfully.', 201);
    }

    public function approve(Request $request, string $id)
    {
        $joinRequest = JoinRequest::findOrFail($id);
        $userId = $request->user()->id;

        if ($joinRequest->approve($userId)) {
            return ApiResponse::success([
                'status' => $joinRequest->status,
            ], 'Join request approved successfully.');
        }

        return ApiResponse::error('You do not have permission to approve this request.', 403);
    }

    public function disapprove(Request $request, string $id)
    {
        $joinRequest = JoinRequest::findOrFail($id);
        $userId = $request->user()->id;

        if ($joinRequest->disapprove($userId)) {
            return ApiResponse::success([
                'status' => $joinRequest->status,
            ], 'Join request disapproved successfully.');
        }

        return ApiResponse::error('You do not have permission to disapprove this request.', 403);
    }

    public function list(Request $request, string $projectId)
    {
        $project = Project::findOrFail($projectId);
        if ($project->created_by_user_id !== $request->user()->id) {
            return ApiResponse::error('You do not have permission to view these requests.', 403);
        }

        $requests = JoinRequest::with('user')
            ->where('project_id', $projectId)
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'user_id' => $req->user->id,
                    'name' => $req->user->name,
                    'status' => $req->status,
                    'created_at' => $req->created_at,
                ];
            });

        return ApiResponse::success($requests);
    }
}
