<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Analytics\DTO\DashboardDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\Enums\Permission;

/** @implements QueryInterface<DashboardDTO> */
final readonly class GetDashboard extends OwnedUseCase implements QueryInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::STATISTICS_VIEW);
    }
}
