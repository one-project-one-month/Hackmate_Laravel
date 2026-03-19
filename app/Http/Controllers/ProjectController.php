<?php

namespace App\Http\Controllers;

use App\Models\JoinRequest;
use App\Models\Project;
use App\Models\ProjectRole;
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
                'is_active',
            ]);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'github_repo' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'required_roles' => ['sometimes', 'array'],
            'required_roles.*' => ['string', 'distinct', 'max:255'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('projects', 'public');
        }

        $requiredRoleLabels = $data['required_roles'] ?? null;
        unset($data['required_roles']);

        $project = Project::create([
            ...$data,
            'created_by_user_id' => $request->user()->id,
            'is_active' => true,
        ]);

        if (! empty($requiredRoleLabels)) {
            $requiredRoleIds = ProjectRole::query()
                ->whereIn('label', $requiredRoleLabels)
                ->pluck('id', 'label');

            $missingLabels = array_values(array_diff($requiredRoleLabels, $requiredRoleIds->keys()->all()));
            if (! empty($missingLabels)) {
                $created = collect($missingLabels)
                    ->map(fn ($label) => ProjectRole::create(['label' => $label]));
                $created->each(fn ($role) => $requiredRoleIds->put($role->label, $role->id));
            }

            $project->requiredRoles()->sync($requiredRoleIds->values()->all());
        }

        return response()->json($project, 201);
    }

    public function show($id)
    {
        $project = Project::with(['roles', 'requiredRoles'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $project,
        ], 200);
    }

    public function listJoinRequests($project_id)
    {
        $project = Project::findOrFail($project_id);

        if ($project->created_by_user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized access to this project requests.'], 403);
        }

        $requests = JoinRequest::where('project_id', $project_id)->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if ($project->created_by_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'type' => ['sometimes', 'string'],
            'github_repo' => ['nullable', 'url'],
            'is_active' => ['boolean'],
            'required_roles' => ['sometimes', 'array'],
            'required_roles.*' => ['string', 'distinct', 'max:255'],
        ]);

        $requiredRoleLabels = $data['required_roles'] ?? null;
        unset($data['required_roles']);

        $project->update($data);

        if (! empty($requiredRoleLabels)) {
            $requiredRoleIds = ProjectRole::query()
                ->whereIn('label', $requiredRoleLabels)
                ->pluck('id', 'label');

            $missingLabels = array_values(array_diff($requiredRoleLabels, $requiredRoleIds->keys()->all()));
            if (! empty($missingLabels)) {
                $created = collect($missingLabels)
                    ->map(fn ($label) => ProjectRole::create(['label' => $label]));
                $created->each(fn ($role) => $requiredRoleIds->put($role->label, $role->id));
            }

            $project->requiredRoles()->syncWithoutDetaching($requiredRoleIds->values()->all());
        }

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
