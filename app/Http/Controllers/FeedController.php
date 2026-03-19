<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function feed()
    {
        $projects = Project::with('creator')
            ->where('is_active', true)
            ->leftJoin('feeds', 'projects.id', '=', 'feeds.project_id')
            ->select('projects.*')
            ->orderByRaw('CASE WHEN feeds.rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('feeds.rank')
            ->orderByDesc('projects.created_at')
            ->get();

        return ApiResponse::success($projects);
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
