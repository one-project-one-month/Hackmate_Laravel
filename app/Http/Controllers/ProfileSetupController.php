<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileSetupController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tech_stack' => ['required', 'array'],
            'tech_stack.*' => ['exists:tech_stacks,id'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $user = auth('api')->user();

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile-images', 'public');
            $user->profile_image = $path;
        }

        $user->techStacks()->sync($validated['tech_stack']);
        $user->has_profile_setup = true;
        $user->save();

        return response()->json([
            'message' => 'Profile setup complete',
            'user' => $user->load('techStacks'),
            'profile_image_url' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : null,
        ]);
    }
}