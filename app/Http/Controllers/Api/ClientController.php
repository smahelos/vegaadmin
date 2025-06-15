<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesBackpackApiAuthentication;
use App\Traits\HandlesFrontendApiAuthentication;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    use HandlesFrontendApiAuthentication, 
        HandlesBackpackApiAuthentication;

    /**
     * Get client data by ID for admin users
     * 
     * This method is specifically for admin API endpoints and allows
     * admins to access any client without user-specific restrictions.
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

            $client = Client::findOrFail($id);
            
            return response()->json($client);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => __('clients.messages.not_found')], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ClientController@getClientAdmin error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $id
            ]);
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

            $client = Client::findOrFail($id);
            
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
     */    public function getClientsAdmin()
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
        $clients = Client::all();
        
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
        
        $clients = Client::where('user_id', $user->id)->get();
        
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
            $client = Client::where('user_id', Auth::id())
                ->where('is_default', true)
                ->first();
            
            // If no default client found, get the first one
            if (!$client) {
                $client = Client::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
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
}
