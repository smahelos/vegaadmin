<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Analytics\DTO\AdminDashboardDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\User\Enums\Permission;

/**
 * The statistics of the admin dashboard over every owner's records.
 *
 * @implements QueryInterface<AdminDashboardDTO>
 */
final readonly class GetAdminDashboard implements QueryInterface
{
    public static function access(): Access
    {
        return Access::allOwners(Permission::STATISTICS_VIEW);
    }
}
