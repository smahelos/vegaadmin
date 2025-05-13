<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClientController extends ApiBackpackController
{
    /**
     * Get client data by ID
     *
     * @param int $id Client ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClient($id)
    {
        $logContext = $this->getLogContext(['client_id' => $id]);
        
        try {
            $user = $this->getAuthenticatedUser();
            
            if (!$user) {
                return response()->json(['message' => __('auth.unauthenticated')], 401);
            }

            $client = Client::findOrFail($id);
            
            // Admins can see any client
            if ($user->hasRole('admin')) {
                // Admin access
            }
            // Regular users can see only their clients
            else if ($client->user_id !== $user->id) {
                return response()->json(['error' => __('clients.messages.not_found')], 403);
            }
            
            return response()->json($client);
        } catch (\Exception $e) {
            Log::error('API error: Client not found', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));
            
            return response()->json(['error' => __('clients.messages.not_found')], 404);
        }
    }
    
    /**
     * Get list of clients for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClients()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['message' => __('auth.unauthenticated')], 401);
        }
        
        // Admins can see all clients
        if ($user->hasRole('admin')) {
            $clients = Client::all();
        } else {
            $clients = Client::where('user_id', $user->id)->get();
        }
        
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
                    'error' => __('clients.messages.no_clients')
                ], 404);
            }
            
            return response()->json($client);
        } catch (\Exception $e) {
            Log::error('Error getting default client: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'error' => __('clients.messages.error_loading')
            ], 500);
        }
    }
}
