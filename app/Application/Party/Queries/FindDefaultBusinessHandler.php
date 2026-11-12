<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindDefaultBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    public function handle(FindDefaultBusiness $query, ReadScope $scope): ?BusinessId
    {
        return $this->businesses->findDefaultId($scope);
    }
}
