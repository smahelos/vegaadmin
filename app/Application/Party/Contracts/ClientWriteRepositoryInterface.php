<?php

namespace App\Application\Party\Contracts;

use App\Models\Client;

/**
 * Write operations for Client aggregate.
 */
interface ClientWriteRepositoryInterface
{
    public function create(array $data): Client;

    public function unsetOthersDefault(int $currentId, int $userId): void;
    
    public function findByIdForUser(int $id, int $userId): ?Client;

    /** Update client by ID with provided attributes */
    public function updateById(int $id, array $data): bool;

    /** Delete client by ID */
    public function deleteById(int $id): bool;
}
