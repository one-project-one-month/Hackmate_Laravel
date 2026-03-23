<?php

namespace App\Http\Controllers;

use App\Models\JoinRequest;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function approve(Request $request, string $id)
    {
        $joinRequest = JoinRequest::findOrFail($id);
        $userId = $request->user_id;

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
        $userId = $request->user_id;

        if ($joinRequest->disapprove($userId)) {
            return ApiResponse::success([
                'status' => $joinRequest->status,
            ], 'Join request disapproved successfully.');
        }

        return ApiResponse::error('You do not have permission to disapprove this request.', 403);
    }

    public function list(Request $request, string $projectId)
    {
        $requests = JoinRequest::with('user')
            ->where('project_id', $projectId)
            ->get()
            ->map(function ($req) {
                return [
                    'user_id' => $req->user->id,
                    'name' => $req->user->name,
                    'status' => $req->status,
                ];
            });

        return ApiResponse::success($requests);
    }
}
