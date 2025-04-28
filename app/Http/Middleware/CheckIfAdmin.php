<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CheckIfAdmin
{
    /**
     * Answer to unauthorized access request
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            return redirect()->guest(backpack_url('login'));
        }
    }

    /**
     * Check if user is admin and handle unauthorized access
     */
    public function handle($request, Closure $next)
    {
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (!backpack_user()->is_admin()) {
            Log::warning('Non-admin user attempted to access admin area', [
                'user_id' => backpack_user()->id ?? 'unknown',
                'path' => $request->path()
            ]);
            return $this->respondToUnauthorizedRequest($request);
        }

        return $next($request);
    }
}