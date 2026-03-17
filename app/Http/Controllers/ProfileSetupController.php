<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileSetupController extends Controller
{
    //
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tech_stack' => 'required|array',
            'tech_stack.*' => 'exists:tech_stacks,id',
        ]);

        $user = auth()->user();
        $user->techStacks()->sync($validated['tech_stack']);
        $user->has_profile_setup = true;
        $user->save();

        return response()->json([
            'message' => 'Profile setup complete',
            'user' => $user,
        ]);
    }
}
