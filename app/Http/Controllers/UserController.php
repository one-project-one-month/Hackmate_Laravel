<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getUserById(Request $request, $id)
    {
        $user = User::find($id);
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'successful',
            'content' => $user,
            'status' => 200
        ]);
    }

    public function updateSelfUserInfo(Request $request)
    {}
}