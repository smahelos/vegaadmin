<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

final class ListBusinessesHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    /**
     * @return PaginatedResult<BusinessDTO>
     */
    public function handle(ListBusinesses $query, ReadScope $scope): PaginatedResult
    {
        return $this->businesses->search(
            $scope,
            $query->search,
            $query->page,
        );
    }
}
