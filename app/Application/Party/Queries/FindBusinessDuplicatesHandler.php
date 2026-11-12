<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindBusinessDuplicatesHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly DuplicateFinder $duplicates,
    ) {}

    /**
     * @return list<DuplicatePartyDTO>
     */
    public function handle(FindBusinessDuplicates $query, ReadScope $scope): array
    {
        return $this->duplicates->find(
            $query->profile,
            $this->businesses->listPossibleDuplicates(
                $scope,
                $query->profile,
                $query->except,
            ),
        );
    }
}
