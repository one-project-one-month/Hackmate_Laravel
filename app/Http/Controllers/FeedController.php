<?php

namespace App\Http\Controllers;

use App\Models\Project;
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

        return response()->json($projects);
    }

    public function metricLike(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $project->like();

        return response()->noContent();
    }

    public function metricDislike(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $project->dislike();

        return response()->noContent();
    }
}
