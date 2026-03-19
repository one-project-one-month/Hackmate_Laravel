<?php

namespace App\Http\Controllers;

use App\Models\TechStack;
use App\Support\ApiResponse;

class TechStackController extends Controller
{
    public function index()
    {
        $techStacks = TechStack::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return ApiResponse::success($techStacks);
    }
}
