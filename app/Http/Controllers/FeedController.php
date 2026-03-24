<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function feed()
    {
        $perPage = (int) request()->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $paginator = Project::with('creator')
            ->where('is_active', true)
            ->leftJoin('feeds', 'projects.id', '=', 'feeds.project_id')
            ->select('projects.*')
            ->orderByRaw('CASE WHEN feeds.rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('feeds.rank')
            ->orderByDesc('projects.created_at')
            ->paginate($perPage);

        return ApiResponse::success([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function metricLike(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $project->like();

        return ApiResponse::success(null, 'successful');
    }

    public function metricDislike(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $project->dislike();

        return ApiResponse::success(null, 'successful');
    }
}
