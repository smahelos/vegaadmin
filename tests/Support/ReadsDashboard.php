<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Analytics\DTO\DashboardDTO;
use App\Application\Analytics\Queries\GetDashboard;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\ValueObjects\UserId;

/**
 * Reads the owner's dashboard through the real gate; the owner needs statistics.view.
 */
trait ReadsDashboard
{
    private function dashboardOf(UserId $owner): DashboardDTO
    {
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::user($owner),
            new GetDashboard($owner),
        );
    }
}
