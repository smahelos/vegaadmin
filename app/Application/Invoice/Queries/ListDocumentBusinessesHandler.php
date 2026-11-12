<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\Enums\PartySort;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

final class ListDocumentBusinessesHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    /** @return PaginatedResult<BusinessDTO> */
    public function handle(ListDocumentBusinesses $query, ReadScope $scope): PaginatedResult
    {
        if ($query->id !== null) {
            $business = $this->businesses->findById($query->id, $scope);
            $found = [];
            if ($business !== null && !$business->isArchived()) {
                $found[] = $business;
            }

            return new PaginatedResult(
                $found,
                count($found),
                $query->page->perPage(),
                1,
            );
        }

        return $this->businesses->search(
            $scope,
            new PartySearch(
                $query->term,
                false,
                PartySort::NAME,
                true,
            ),
            $query->page,
        );
    }
}
