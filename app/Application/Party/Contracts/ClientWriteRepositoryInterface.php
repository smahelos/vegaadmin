<?php

namespace App\Application\Party\Contracts;

use App\Models\Client;

/**
 * Write operations for Client aggregate.
 */
interface ClientWriteRepositoryInterface
{
    public function create(array $data): Client;
}
