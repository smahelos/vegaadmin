<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListActiveDocumentBusinessesHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    /** @return list<BusinessDTO> */
    public function handle(ListActiveDocumentBusinesses $query, ReadScope $scope): array
    {
        return $this->businesses->listActive($scope);
    }
}
