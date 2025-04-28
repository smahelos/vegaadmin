<?php

namespace App\Http\Controllers\Frontend\Auth;

use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, $user)
    {
        return redirect()->route('frontend.dashboard', ['lang' => app()->getLocale()]);
    }

    /**
     * Show the application's login form
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
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
                'ip' => $request->ip()
            ]);
            
            return back()->withInput()->with('error', __('auth.failed'));
        }
    }

    /**
     * Log the user out of the application
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        
        $request->session()->regenerateToken();

        return redirect()->route('home', ['lang' => app()->getLocale()]);
    }

    /**
     * Attempt authentication with web guard
     * 
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return Auth::guard('web')->attempt(
            $this->credentials($request), $request->filled('remember')
        );
    }

    /**
     * Get the authentication guard
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('web');
    }
}