<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function own(Request $request)
    {
        $projects = Project::where('created_by_user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'title',
                'description',
                'type',
                'created_by_user_id',
                'github_repo',
                'is_active'
            ]);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['required','string'],
            'github_repo' => ['nullable','url'],
            'image_url' => ['nullable','url'],
        ]);

        $project = Project::create([
            ...$data,
            'created_by_user_id' => $request->user()->id,
            'is_active' => true,
        ]);

        return response()->json($project, 201);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if ($project->created_by_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes','string','max:255'],
            'description' => ['sometimes','string'],
            'type' => ['sometimes','string'],
            'github_repo' => ['nullable','url'],
            'is_active' => ['boolean'],
        ]);

        $project->update($data);

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