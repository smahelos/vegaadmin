<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    /**
     * Handle the incoming request and redirect based on authentication status
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Redirect authenticated user to dashboard
        if (Auth::check()) {
            return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()]);
        }
        
        // Show login page for unauthenticated users
        return response()->view('auth.login');
    }
}
