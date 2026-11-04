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
            $redirector = redirect();
            $application = app();

            return $redirector->route('frontend.dashboard', ['locale' => $application->getLocale()]);
        }

        // Show login page for unauthenticated users
        $responseFactory = response();

        return $responseFactory->view('auth.login');
    }
}
