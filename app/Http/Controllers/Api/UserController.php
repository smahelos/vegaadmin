<?php

namespace App\Http\Controllers\Api;

use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
// Use Application-layer facade instead of Domain in controllers
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private UserApplicationServiceInterface $userService,
        private ?UserAuthorizationAdapterInterface $auth = null,
    ) {
        $this->auth = $this->auth ?? app(UserAuthorizationAdapterInterface::class);
    }

    /**
     * Get User data by ID for admin users
     * Admins can access any user.
     *
     * @param  int  $id  User ID
     */
    public function getUserAdmin(int $id): JsonResponse
    {
        try {
            // Authenticate via backpack guard
            $current = function_exists('backpack_auth') ? backpack_auth()->user() : null;
            if (! $current) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }
            // Permission check via adapter (backpack guard)
            if (! $this->auth->hasBackpackPermission((int) $current->id, 'can_view_user')) {
                return response()->json(['error' => __('users.auth.unauthorized')], 403);
            }

            $user = $this->userService->findUserById((int) $id);

            return response()->json($user);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => __('users.messages.not_found')], 404);
        } catch (\Exception $e) {
            Log::error('UserController@getUserAdmin error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $id,
            ]);

            return response()->json(['error' => __('users.messages.load_failed')], 500);
        }
    }

    /**
     * Get list of users for authenticated user
     */
    public function getUsersAdmin(): JsonResponse
    {
        $user = function_exists('backpack_auth') ? backpack_auth()->user() : null;
        if (! $user) {
            return response()->json(['error' => __('users.auth.unauthenticated'), 'code' => 401], 401);
        }

        // Check if user has permission to view users
        if (! $this->auth->hasBackpackPermission((int) $user->id, 'can_view_user')) {
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
     */
    public function searchUsersAdmin(Request $request): JsonResponse
    {
        $user = function_exists('backpack_auth') ? backpack_auth()->user() : null;
        if (! $user) {
            return response()->json(['error' => __('users.auth.unauthenticated'), 'code' => 401], 401);
        }

        if (! $this->auth->hasBackpackPermission((int) $user->id, 'can_view_user')) {
            return response()->json(['error' => __('users.auth.unauthorized')], 403);
        }

        $search_term = $request->input('q');

        return response()->json($this->userService->searchUsers($search_term, 10, $request->integer('page', 1)));
    }

    /**
     * Get currently authenticated backpack user (for tests, override in subclass)
     */
    protected function getBackpackUser(): ?User
    {
        return function_exists('backpack_auth') && backpack_auth()->check() ? backpack_auth()->user() : null;
    }

    /**
     * Get currently authenticated frontend user (for tests, override in subclass)
     */
    protected function getFrontendUser(): ?User
    {
        return Auth::guard('web')->check() ? Auth::guard('web')->user() : null;
    }
}
