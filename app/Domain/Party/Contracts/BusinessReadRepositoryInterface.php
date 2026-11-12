<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;

interface BusinessReadRepositoryInterface
{
    /**
     * Null when the business does not exist or is outside the scope; archived businesses are found.
     */
    public function findById(BusinessId $id, ReadScope $scope): ?BusinessDTO;

    /**
     * Without a scope for the callers the gate does not run yet: Invoice and BusinessCreator (registration).
     */
    public function findByIdForUser(BusinessId $id, UserId $userId): ?BusinessDTO;

    /**
     * @return list<BusinessDTO> ordered by name
     */
    public function listActive(ReadScope $scope): array;

    /** @return list<BusinessDTO> newest first */
    public function listLatestActive(ReadScope $scope, int $limit): array;

    /**
     * @return PaginatedResult<BusinessDTO>
     */
    public function search(
        ReadScope $scope,
        PartySearch $search,
        PageRequest $page,
    ): PaginatedResult;

    public function countActive(ReadScope $scope): int;

    /** Reads committed rows while the caller holds the user's activation lock, regardless of an earlier snapshot. */
    public function countActiveForUserForUpdate(UserId $userId): int;

    public function findDefaultId(ReadScope $scope): ?BusinessId;

    /**
     * Businesses in the scope, archived included, that share the company number, name or email with the profile;
     * which of them are duplicates is decided by DuplicateFinder.
     *
     * @return list<BusinessDTO>
     */
    public function listPossibleDuplicates(
        ReadScope $scope,
        PartyProfile $profile,
        ?BusinessId $except,
    ): array;
}
