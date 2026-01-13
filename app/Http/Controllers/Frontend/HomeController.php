<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    /**
     * Handle the incoming request and redirect based on authentication status
     *
     * @param  Request $request
     * @return Response|RedirectResponse
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
