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
        $this->middleware('guest')->except('logout');
    }

    /**
     * Custom redirect after successful authentication
     */
    protected function authenticated(Request $request, mixed $user): RedirectResponse
    {
        return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()]);
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

            return back()->withInput()->with('error', __('users.auth.failed'));
        }
    }

    /**
     * Log the user out of the application
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home', ['locale' => app()->getLocale()]);
    }

    /**
     * Attempt authentication with web guard
     */
    protected function attemptLogin(Request $request): bool
    {
        return Auth::guard('web')->attempt(
            $this->credentials($request), $request->filled('remember'),
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
