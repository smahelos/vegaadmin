<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionController extends Controller
{
    /**
     * Check authentication status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAuth(Request $request)
    {
        if (Auth::check()) {
            return response()->json(['status' => 'authenticated']);
        }
        
        return response()->json(['status' => 'unauthenticated'], 401);
    }
    
    /**
     * Refresh session on user activity
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshSession(Request $request)
    {
        Session::migrate(true);
        
        return response()->json(['status' => 'session_refreshed']);
    }
}