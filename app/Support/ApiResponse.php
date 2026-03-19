<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $content = null, string $message = 'successful', int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'content' => $content,
            'status' => $status,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $content = null): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'content' => $content,
            'status' => $status,
        ], $status);
    }
}
