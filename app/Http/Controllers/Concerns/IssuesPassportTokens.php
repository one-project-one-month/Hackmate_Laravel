<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait IssuesPassportTokens
{
    protected function issueToken(array $payload): Response
    {
        $subRequest = Request::create('/oauth/token', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ]);

        return app()->handle($subRequest);
    }
}