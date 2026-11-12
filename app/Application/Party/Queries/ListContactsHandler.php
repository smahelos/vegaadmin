<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

final class ListContactsHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /**
     * @return PaginatedResult<ContactDTO>
     */
    public function handle(ListContacts $query, ReadScope $scope): PaginatedResult
    {
        return $this->contacts->search(
            $scope,
            $query->search,
            $query->page,
        );
    }
}
