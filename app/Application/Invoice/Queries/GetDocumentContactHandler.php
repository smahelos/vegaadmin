<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetDocumentContactHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    public function handle(GetDocumentContact $query, ReadScope $scope): ContactDTO
    {
        return $this->contacts->findById($query->id, $scope)
            ?? throw new ContactNotFoundException($query->id);
    }
}
