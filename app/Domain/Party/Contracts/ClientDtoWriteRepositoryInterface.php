<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\ClientWriteData;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface ClientDtoWriteRepositoryInterface
{
    public function create(ClientWriteData $data): ClientDTO;
}
