<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindContactDuplicatesHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly DuplicateFinder $duplicates,
    ) {}

    /**
     * @return list<DuplicatePartyDTO>
     */
    public function handle(FindContactDuplicates $query, ReadScope $scope): array
    {
        return $this->duplicates->find(
            $query->profile,
            $this->contacts->listPossibleDuplicates(
                $scope,
                $query->profile,
                $query->except,
            ),
        );
    }
}
