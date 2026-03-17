<?php

namespace App\Http\Controllers;

use App\Models\JoinRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function approve(Request $request, string $id)
    {
        $joinRequest = JoinRequest::findOrFail($id);
        $userId = $request->user_id;

        if ($joinRequest->approve($userId)) {
            return response()->json([
                'message' => 'Join request approved successfully.',
                'status' => $joinRequest->status,
            ]);
        }

        return response()->json([
            'message' => 'You do not have permission to approve this request.',
        ], 403);
    }

    public function disapprove(Request $request, string $id)
    {
        $joinRequest = JoinRequest::findOrFail($id);
        $userId = $request->user_id;

        if ($joinRequest->disapprove($userId)) {
            return response()->json([
                'message' => 'Join request disapproved successfully.',
                'status' => $joinRequest->status,
            ]);
        }

        return response()->json([
            'message' => 'You do not have permission to disapprove this request.',
        ], 403);
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

        return response()->json($requests);
    }
}
