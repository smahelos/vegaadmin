<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Handle the incoming request and redirect based on authentication status
     */
    public function index(Request $request): RedirectResponse|Response
    {
        // Redirect authenticated user to dashboard
        if (Auth::check()) {
            return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()]);
        }

        // Show login page for unauthenticated users
        return response()->view('auth.login');
    }
}
