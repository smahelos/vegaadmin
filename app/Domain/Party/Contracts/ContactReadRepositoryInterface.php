<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;

interface ContactReadRepositoryInterface
{
    /**
     * Null when the contact does not exist or is outside the scope; archived contacts are found.
     */
    public function findById(ContactId $id, ReadScope $scope): ?ContactDTO;

    /**
     * Without a scope for Invoice, which the gate does not run yet.
     */
    public function findByIdForUser(ContactId $id, UserId $userId): ?ContactDTO;

    /** @return list<ContactDTO> newest first */
    public function listLatestActive(ReadScope $scope, int $limit): array;

    /**
     * @return PaginatedResult<ContactDTO>
     */
    public function search(
        ReadScope $scope,
        PartySearch $search,
        PageRequest $page,
    ): PaginatedResult;

    /**
     * Contacts in the scope, archived included, that share the company number, name or email with the profile;
     * which of them are duplicates is decided by DuplicateFinder.
     *
     * @return list<ContactDTO>
     */
    public function listPossibleDuplicates(
        ReadScope $scope,
        PartyProfile $profile,
        ?ContactId $except,
    ): array;
}
