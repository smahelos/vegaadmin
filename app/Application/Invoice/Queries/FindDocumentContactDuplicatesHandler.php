<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindDocumentContactDuplicatesHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly DuplicateFinder $duplicates,
    ) {}

    /** @return list<DuplicatePartyDTO> */
    public function handle(FindDocumentContactDuplicates $query, ReadScope $scope): array
    {
        $candidates = $this->contacts->listPossibleDuplicates(
            $scope,
            $query->profile,
            null,
        );

        return $this->duplicates->find($query->profile, $candidates);
    }
}
