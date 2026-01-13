<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function __construct(
        private PartyApplicationServiceInterface $partyService,
        private ?UserAuthorizationAdapterInterface $auth = null
    ) {
        $this->auth = $this->auth ?? app(UserAuthorizationAdapterInterface::class);
    }

    /**
     * Get client data by ID for admin users
     * Admins can access any client.
     *
     * @param int $id Client ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientAdmin($id)
    {
        try {
            $user = $this->getBackpackUser();
            if (!$user) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }
            // Check if user has permission to view clients
            if (!$this->backpackUserHasPermission('can_view_client')) {
                return response()->json(['error' => __('users.auth.unauthorized')], 403);
            }
            // Admin can access any client without user scoping
            $client = $this->partyService->findClientById((int)$id);

            return response()->json($client);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => __('clients.messages.not_found')], 404);
        } catch (\Exception $e) {
            Log::error('ClientController@getClientAdmin error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'client_id' => $id]);
            return response()->json(['error' => __('clients.messages.error_loading')], 500);
        }
    }

    /**
     * Get client data by ID
     *
     * @param int $id Client ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClient($id)
    {
        try {
            $user = $this->getFrontendUser();
            if (!$user) {
                return response()->json(['error' => __('users.auth.unauthenticated'), 'code' => 401], 401);
            }

            $client = $this->partyService->findClient($user->id, (int)$id);
            // Regular users can see only their clients
            if ($client->user_id !== $user->id) {
                return response()->json(['error' => __('clients.messages.not_found')], 403);
            }

            return response()->json($client);
        } catch (\Exception $e) {
            return response()->json(['error' => __('clients.messages.not_found')], 404);
        }
    }

    /**
     * Get list of clients for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientsAdmin()
    {
        $user = $this->getBackpackUser();
        if (!$user) {
            return response()->json(['error' => __('users.auth.unauthenticated'), 'code' => 401], 401);
        }
        // Check if user has permission to view clients
        if (!$this->backpackUserHasPermission('can_view_client')) {
            return response()->json(['error' => __('users.auth.unauthorized')], 403);
        }
        // Admins can see all clients
        $clients = $this->partyService->listClients($user);

        return response()->json($clients);
    }

    /**
     * Get list of clients for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClients()
    {
        $user = $this->getFrontendUser();
        if (!$user) {
            return response()->json(['error' => __('users.auth.unauthenticated'), 'code' => 401], 401);
        }
        $clients = $this->partyService->listClients($user);

        return response()->json($clients);
    }

    /**
     * Get default client for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDefaultClient()
    {
        try {
            // Find default client
            $client = $this->partyService->defaultClientOrFirst((int)Auth::id());
            if (!$client) {
                return response()->json([
                    'error' => __('clients.messages.not_found')
                ], 404);
            }
            return response()->json($client);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('clients.messages.error_loading')
            ], 500);
        }
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

    /**
     * Delegate permission check to authorization adapter; tests can override this.
     */
    protected function backpackUserHasPermission(string $permission): bool
    {
        $user = $this->getBackpackUser();
        return $user ? $this->auth?->hasBackpackPermission((int)$user->id, $permission) === true : false;
    }
}
