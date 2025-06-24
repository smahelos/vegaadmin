<?php

namespace App\Contracts;

use App\Models\Client;

interface ClientRepositoryInterface
{
    /**
     * Get clients for the current user as dropdown options
     * 
     * @return array
     */
    public function getClientsForDropdown(): array;
    
    /**
     * Find default client for the current user
     * 
     * @return Client|null
     */
    public function getDefaultClient(): ?Client;
    
    /**
     * Find client by ID for the current user
     * 
     * @param int $id
     * @return Client|null
     */
    public function findById(int $id): ?Client;
    
    /**
     * Create a new client from data
     * 
     * @param array $data
     * @return Client
     */
    public function create(array $data): Client;
}
