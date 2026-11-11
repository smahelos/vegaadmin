<?php

namespace App\Providers;

use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Http\Controllers\Admin\PermissionCrudController;
use App\Http\Controllers\Admin\RoleCrudController;
use App\Http\Controllers\Admin\UserCrudController;
use App\Http\Support\CurrentActor;
use App\View\Components\ApplicationLogo;
use App\View\Components\CurrencySelect;
use App\View\Components\Dropdown;
use App\View\Components\DropdownLink;
use App\View\Components\NavLink;
use App\View\Components\ResponsiveNavLink;
use App\View\Components\Select;
use Backpack\PermissionManager\app\Http\Controllers\PermissionCrudController as PackagePermissionCrudController;
use Backpack\PermissionManager\app\Http\Controllers\RoleCrudController as PackageRoleCrudController;
use Backpack\PermissionManager\app\Http\Controllers\UserCrudController as PackageUserCrudController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

// Context bindings and observers live in the bounded context providers (ADR D-042)

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     */
    public function register(): void
    {
        // Load Backpack helpers
        require_once app_path('Helpers/BackpackHelpers.php');

        // PermissionManager's routes name its own controllers; these bindings put ours in their place
        $this->app->bind(
            PackageUserCrudController::class,
            UserCrudController::class,
        );
        $this->app->bind(
            PackageRoleCrudController::class,
            RoleCrudController::class,
        );
        $this->app->bind(
            PackagePermissionCrudController::class,
            PermissionCrudController::class,
        );
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // Add Blade directive for checking users with admin access
        Blade::if(
            'backpackUser',
            function () {
                return Auth::check() && function_exists('backpack_user') && backpack_user() !== null;
            },
        );

        // Hides what the signed-in user may not do; the gate is still the check (rules/permissions.md)
        Blade::if(
            'allowed',
            function (string $useCase): bool {
                $currentActor = app(CurrentActor::class);
                $actor = $currentActor->find();
                $useCases = app(AuthorizingUseCaseRunnerInterface::class);

                return $actor !== null && $useCases->allowed($actor, $useCase);
            },
        );

        // Register custom Blade components
        Blade::component('application-logo', ApplicationLogo::class);
        Blade::component('nav-link', NavLink::class);
        Blade::component('responsive-nav-link', ResponsiveNavLink::class);
        Blade::component('dropdown', Dropdown::class);
        Blade::component('dropdown-link', DropdownLink::class);
        Blade::component('select', Select::class);
        Blade::component('currency-select', CurrencySelect::class);
    }
}
