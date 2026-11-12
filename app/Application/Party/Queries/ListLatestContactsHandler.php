<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListLatestContactsHandler
{
    private const LIMIT = 5;

    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /**
     * @return list<ContactDTO> newest first
     */
    public function handle(ListLatestContacts $query, ReadScope $scope): array
    {
        return $this->contacts->listLatestActive($scope, self::LIMIT);
    }
}
