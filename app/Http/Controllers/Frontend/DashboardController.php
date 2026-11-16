<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Analytics\Queries\GetDashboard;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function index(): View
    {
        $owner = UserId::fromInt((int) Auth::id());
        $actor = Actor::user($owner);
        $dashboard = null;
        // The landing page after login is for everyone; only its statistics need statistics.view
        if ($this->useCases->allowed($actor, GetDashboard::class)) {
            $dashboard = $this->useCases->run(
                $actor,
                new GetDashboard($owner),
            );
        }

        return view(
            'frontend.dashboard',
            ['dashboard' => $dashboard],
        );
    }
}
