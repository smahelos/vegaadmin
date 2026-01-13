<?php

namespace App\Application\Party\Contracts;

use App\Models\Client;

/**
 * Read-only operations for Client aggregate.
 */
interface ClientReadRepositoryInterface
{
    /** @return array<int,string> */
    public function getClientsForDropdown(int $userId): array;

    public function getDefaultClient(int $userId): ?Client;
    
    public function findById(int $id): ?Client;
    
    /** Find by id without scoping to current user (admin/ownership checks handled at higher layer) */
    public function findByIdAny(int $id): ?Client;

    /** Find by id scoped to a specific user */
    public function findByIdForUser(int $id, int $userId): ?Client;
    
    public function allForAdmin(): array;
    
    public function allForUser(int $userId): array;
    
    public function firstForUser(int $userId): ?Client;
}
