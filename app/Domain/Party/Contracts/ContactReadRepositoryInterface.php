<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;

interface ContactReadRepositoryInterface
{
    /**
     * Null when the contact does not exist or belongs to another user; archived contacts are found.
     */
    public function findByIdForUser(ContactId $id, UserId $userId): ?ContactDTO;

    /**
     * @return list<ContactDTO> ordered by name
     */
    public function listActiveForUser(UserId $userId): array;

    /**
     * @return list<ContactDTO> ordered by name
     */
    public function listArchivedForUser(UserId $userId): array;

    public function countActiveForUser(UserId $userId): int;

    /**
     * The user's contacts, archived included, that share the company number, name or email with the profile;
     * which of them are duplicates is decided by DuplicateFinder.
     *
     * @return list<ContactDTO>
     */
    public function listPossibleDuplicates(
        UserId $userId,
        PartyProfile $profile,
        ?ContactId $except,
    ): array;
}
