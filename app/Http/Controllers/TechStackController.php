<?php

namespace App\Http\Controllers;

use App\Models\TechStack;

class TechStackController extends Controller
{
    public function index()
    {
        $techStacks = TechStack::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'successful',
            'content' => $techStacks,
            'status' => 200,
        ]);
    }
}
