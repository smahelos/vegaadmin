<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Analytics\Presenters\AdminDashboardPresenter;
use App\Application\Analytics\Queries\GetAdminDashboard;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use Backpack\CRUD\app\Http\Controllers\AdminController as BackpackAdminController;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class AdminController extends BackpackAdminController
{
    use ActsAsAdmin;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly AdminDashboardPresenter $presenter,
    ) {
        parent::__construct();
    }

    public function dashboard(): View
    {
        $admin = $this->admin();
        $dashboard = null;
        // The landing page of the admin is for every admin; only its statistics need statistics.view
        if ($this->useCases->allowed($admin, GetAdminDashboard::class)) {
            $dashboard = $this->useCases->run(
                $admin,
                new GetAdminDashboard,
            );
        }

        return view(
            backpack_view('dashboard'),
            [
                'dashboard' => $dashboard,
                'presenter' => $this->presenter,
                'locale' => App::getLocale(),
            ],
        );
    }
}
