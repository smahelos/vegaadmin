<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListActiveBusinessesHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    /**
     * @return list<BusinessDTO> ordered by name
     */
    public function handle(ListActiveBusinesses $query, ReadScope $scope): array
    {
        return $this->businesses->listActive($scope);
    }
}
