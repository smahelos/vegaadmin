<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\Enums\PartySort;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

final class ListDocumentContactsHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /** @return PaginatedResult<ContactDTO> */
    public function handle(ListDocumentContacts $query, ReadScope $scope): PaginatedResult
    {
        if ($query->id !== null) {
            $contact = $this->contacts->findById($query->id, $scope);
            $found = [];
            if ($contact !== null && !$contact->isArchived()) {
                $found[] = $contact;
            }

            return new PaginatedResult(
                $found,
                count($found),
                $query->page->perPage(),
                1,
            );
        }

        return $this->contacts->search(
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
