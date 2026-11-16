<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Analytics\DTO\StatisticsFilter;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<list<array<string, mixed>>> */
final readonly class GetMonthlyRevenue extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public StatisticsFilter $filter,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::STATISTICS_VIEW);
    }
}
