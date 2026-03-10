<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProjectController extends Controller
{
    // Get Projects for Swippable Card
    public function index()
    {
        return $this->feed();
    }

    public function own()
    {
        $projects = Project::query()
            ->where('created_by_user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($projects);
    }

    public function feed()
    {
        // Read recommendation order from the precomputed feed table.
        $projects = Project::with('creator')
            ->where('is_active', true)
            ->leftJoin('feeds', 'projects.id', '=', 'feeds.project_id')
            ->select('projects.*')
            ->orderByRaw('CASE WHEN feeds.rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('feeds.rank')
            ->orderByDesc('projects.created_at')
            ->get();

        // Return the projects as JSON
        return response()->json($projects);
    }

    // update project
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        // Check if the authenticated user owns this project
        if ($project->created_by_user_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
                'debug' => [
                    'project_owner' => $project->created_by_user_id,
                    'logged_in_user' => auth()->id(),
                ],
            ], 403);
        }

        // Validate incoming data
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'type' => 'sometimes|required|string|max:255',
            'github_repo' => 'nullable|url',
            'is_active' => 'sometimes|required|boolean',
        ]);

        // Update project with validated data
        $project->update($validatedData);

        return response()->json($project);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        if ($project->created_by_user_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $project->delete();

        return response()->noContent();
    }

    public function store(Request $request)
    {

        // Validate incoming data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $user = auth()->user();
        $githubToken = $user->github_token;

        $response = Http::withToken($githubToken)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->post('https://api.github.com/user/repos', [
                'name' => $validatedData['title'],
                'description' => $validatedData['description'],
                'private' => true,
            ]);

        $project = Project::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'created_by_user_id' => auth()->id(),
            'github_repo' => $response->json('html_url'),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Project created',
            'data' => $project,
        ], 201);
    }
}
