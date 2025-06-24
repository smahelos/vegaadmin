<?php

namespace App\Repositories;

use App\Contracts\ClientRepositoryInterface;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Get clients for the current user as dropdown options
     * 
     * @return array
     */
    public function getClientsForDropdown(): array
    {
        return Client::where('user_id', Auth::id())
            ->pluck('name', 'id')
            ->toArray();
    }
    
    /**
     * Find default client for the current user
     * 
     * @return Client|null
     */
    public function getDefaultClient(): ?Client
    {
        return Client::where('user_id', Auth::id())
            ->where('is_default', true)
            ->first();
    }
    
    /**
     * Find client by ID for the current user
     * 
     * @param int $id
     * @return Client|null
     */
    public function findById(int $id): ?Client
    {
        return Client::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
    }
    
    /**
     * Create a new client from data
     * 
     * @param array $data
     * @return Client
     */
    public function create(array $data): Client
    {
        $data['user_id'] = Auth::id();
        return Client::create($data);
    }
}
