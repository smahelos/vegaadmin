<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Analytics\Services\DashboardApplicationService;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardApplicationService $dashboardService) {}

    public function index(Request $request): View
    {
        return view('frontend.dashboard', [
            'dashboard' => $this->dashboardService->getDashboardData(UserId::fromInt($request->user('web')->id)),
        ]);
    }
}
