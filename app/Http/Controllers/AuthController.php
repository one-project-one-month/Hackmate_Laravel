<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * GET /v1/auth/login-url
     */
    public function getLoginUrl(Request $request)
    {}

    /**
     * GET /v1/auth/callback
     */
    public function handleCallback(Request $request)
    {}

    /**
     * POST /v1/auth/exchange
     */
    public function exchangeToken(Request $request)
    {}

    /**
     * GET /v1/auth/me
     */
    public function getSelf(Request $request)
    {}

    /**
     * GET /v1/auth/refresh
     */
    public function refreshToken(Request $request)
    {}
}
