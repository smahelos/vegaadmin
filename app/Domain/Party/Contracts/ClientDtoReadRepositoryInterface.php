<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface ClientDtoReadRepositoryInterface
{
    public function getDefaultClient(int $userId): ?ClientDTO;

    public function findById(PartyId $id): ?ClientDTO;

    /** Find by id scoped to a specific user */
    public function findByIdForUser(PartyId $id, UserId $userId): ?ClientDTO;

    public function firstForUser(int $userId): ?ClientDTO;
}
