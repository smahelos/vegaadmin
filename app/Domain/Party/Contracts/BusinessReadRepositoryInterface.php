<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;

interface BusinessReadRepositoryInterface
{
    /**
     * Null when the business does not exist or belongs to another user; archived businesses are found.
     */
    public function findByIdForUser(BusinessId $id, UserId $userId): ?BusinessDTO;

    /**
     * @return list<BusinessDTO> ordered by name
     */
    public function listActiveForUser(UserId $userId): array;

    /**
     * @return list<BusinessDTO> ordered by name
     */
    public function listArchivedForUser(UserId $userId): array;

    public function countActiveForUser(UserId $userId): int;

    public function findDefaultIdForUser(UserId $userId): ?BusinessId;

    /**
     * The user's businesses, archived included, that share the company number, name or email with the profile;
     * which of them are duplicates is decided by DuplicateFinder.
     *
     * @return list<BusinessDTO>
     */
    public function listPossibleDuplicates(
        UserId $userId,
        PartyProfile $profile,
        ?BusinessId $except,
    ): array;
}
