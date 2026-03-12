<?php

use App\Http\Middleware\AuthMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

it('returns 401 when api guard is not authenticated', function (): void {
    $guard = Mockery::mock();
    $guard->shouldReceive('check')->once()->andReturnFalse();

    Auth::shouldReceive('guard')->once()->with('api')->andReturn($guard);

    $middleware = new AuthMiddleware;
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
    expect($response->getContent())->toContain('Unauthenticated.');
});

it('allows request when api guard is authenticated', function (): void {
    $guard = Mockery::mock();
    $guard->shouldReceive('check')->once()->andReturnTrue();

    Auth::shouldReceive('guard')->once()->with('api')->andReturn($guard);

    $middleware = new AuthMiddleware;
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toContain('ok');
});
