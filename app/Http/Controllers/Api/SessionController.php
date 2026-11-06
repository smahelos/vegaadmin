<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionController extends Controller
{
    /**
     * Check authentication status
     */
    public function checkAuth(Request $request): JsonResponse
    {
        if (Auth::check()) {
            $responseFactory = response();

            return $responseFactory->json(['status' => 'authenticated']);
        }

        $responseFactory = response();

        return $responseFactory->json(['status' => 'unauthenticated'], 401);
    }

    /**
     * Refresh session on user activity
     */
    public function refreshSession(Request $request): JsonResponse
    {
        Session::migrate(true);

        $responseFactory = response();

        return $responseFactory->json(['status' => 'session_refreshed']);
    }
}
