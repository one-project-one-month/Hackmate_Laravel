<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function own(Request $request)
    {
        $user = $request->user();
        $projects = Project::where('created_by_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'description', 'type', 'created_by_user_id', 'github_repo', 'is_active']);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->only(['title', 'description', 'type', 'github_repo']);
        $data['created_by_user_id'] = $user->id;
        $data['is_active'] = true;
        $project = Project::create($data);

        return response()->json($project, 201);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        if ($project->created_by_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project->update($request->only(['title', 'description', 'type', 'github_repo', 'is_active']));

        return response()->json($project);
    }

    public function destroy(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        if ($project->created_by_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project->delete();

        return response()->noContent();
    }
}
