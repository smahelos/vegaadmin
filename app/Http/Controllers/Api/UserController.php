<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
// Use Application-layer facade instead of Domain in controllers
use App\Application\User\Contracts\UserApplicationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    public function __construct(
        private UserApplicationServiceInterface $userService,
        private ?UserAuthorizationAdapterInterface $auth = null
    ) {
        $this->auth = $this->auth ?? app(\App\Application\User\Contracts\UserAuthorizationAdapterInterface::class);
    }

    /**
     * Get User data by ID for admin users
     * Admins can access any user.
     *
     * @param int $id User ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserAdmin($id)
    {
        try {
            // Authenticate via backpack guard
            $current = function_exists('backpack_auth') ? backpack_auth()->user() : null;
            if (!$current) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }
            // Permission check via adapter (backpack guard)
            if (!$this->auth->hasBackpackPermission((int)$current->id, 'can_view_client')) {
                return response()->json(['error' => __('users.auth.unauthorized')], 403);
            }

            $user = $this->userService->findUserById((int)$id);
            return response()->json($user);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => __('users.messages.not_found')], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController@getUserAdmin error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $id
            ]);
            return response()->json(['error' => __('users.messages.error_loading')], 500);
        }
    }
    
    /**
     * Get list of users for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsersAdmin()
    {
        $user = function_exists('backpack_auth') ? backpack_auth()->user() : null;
        if (!$user) {
            return response()->json(['error' => __('users.auth.unauthenticated'), 'code' => 401], 401);
        }

        // Check if user has permission to view clients
        if (!$this->auth->hasBackpackPermission((int)$user->id, 'can_view_client')) {
            return response()->json(['error' => __('users.auth.unauthorized')], 403);
        }

        // Admins can see all users via service abstraction
        $users = $this->userService->getAllUsers();

        return response()->json($users);
    }

    /**
     * Search users for admin
     *
     * This method allows admins to search for users by name.
     * It returns paginated results.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function searchUsersAdmin(Request $request): LengthAwarePaginator
    {
        $search_term = $request->input('q');
        return $this->userService->searchUsers($search_term, 10);
    }

    /**
     * Get currently authenticated backpack user (for tests, override in subclass)
     */
    protected function getBackpackUser(): ?\App\Models\User
    {
        return function_exists('backpack_auth') && backpack_auth()->check() ? backpack_auth()->user() : null;
    }

    /**
     * Get currently authenticated frontend user (for tests, override in subclass)
     */
    protected function getFrontendUser(): ?\App\Models\User
    {
        return Auth::guard('web')->check() ? Auth::guard('web')->user() : null;
    }
}
