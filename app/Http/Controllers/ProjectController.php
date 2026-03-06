<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    //Get Projects for Swippable Card
    public function index()
    {
        // Fetch projects from the database 
        $projects = Project::with('creator')->where('is_active', true)->get();

        // Return the projects as JSON
        return response()->json($projects);
    }

    //update project 
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        // Check if the authenticated user owns this project
        if ($project->created_by_user_id != auth()->id()) {
        return response()->json([
            'message' => 'Unauthorized',
            'debug' => [
                'project_owner' => $project->created_by_user_id,
                'logged_in_user' => auth()->id()
            ]
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

}
