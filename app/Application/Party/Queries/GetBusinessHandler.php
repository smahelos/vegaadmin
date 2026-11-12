<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    public function handle(GetBusiness $query, ReadScope $scope): BusinessDTO
    {
        return $this->businesses->findById($query->id, $scope)
            ?? throw new BusinessNotFoundException($query->id);
    }
}
