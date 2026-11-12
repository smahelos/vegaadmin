<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListLatestBusinessesHandler
{
    private const LIMIT = 5;

    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    /**
     * @return list<BusinessDTO> newest first
     */
    public function handle(ListLatestBusinesses $query, ReadScope $scope): array
    {
        return $this->businesses->listLatestActive($scope, self::LIMIT);
    }
}
