<?php

namespace App\Http\Controllers\Frontend\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login
     */
    protected string $redirectTo = '/dashboard';

    /**
     * Initialize controller and apply middleware
     *
     * @return void
     */
    public function __construct()
    {
        $middleware = $this->middleware('guest');
        $middleware->except('logout');
    }

    /**
     * Custom redirect after successful authentication
     */
    protected function authenticated(Request $request, mixed $user): RedirectResponse
    {
        $redirector = redirect();
        $application = app();

        return $redirector->route('frontend.dashboard', ['locale' => $application->getLocale()]);
    }

    /**
     * Show the application's login form
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request
     *
     *
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse|Response|JsonResponse
    {
        $this->validateLogin($request);

        try {
            if ($this->attemptLogin($request)) {
                return $this->sendLoginResponse($request);
            }

            return $this->sendFailedLoginResponse($request);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            $redirect = back();
            $redirect->withInput();

            return $redirect->with(
                'error',
                __('users.auth.failed'),
            );
        }
    }

    /**
     * Log the user out of the application
     */
    public function logout(Request $request): RedirectResponse
    {
        $guard = $this->guard();
        $guard->logout();

        $session = $request->session();
        $session->invalidate();
        $session->regenerateToken();

        $redirector = redirect();
        $application = app();

        return $redirector->route('home', ['locale' => $application->getLocale()]);
    }

    /**
     * Attempt authentication with web guard
     */
    protected function attemptLogin(Request $request): bool
    {
        $guard = Auth::guard('web');

        return $guard->attempt(
            $this->credentials($request),
            $request->filled('remember'),
        );
    }

    /**
     * Get the authentication guard
     */
    protected function guard(): StatefulGuard
    {
        return Auth::guard('web');
    }
}
