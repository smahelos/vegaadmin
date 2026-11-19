<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\System\Commands\ClearCache;
use App\Domain\System\Enums\CacheKind;
use App\Domain\System\Exceptions\CacheClearFailedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\Facades\Alert;

final class CacheManagementController extends Controller
{
    use ActsAsAdmin;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function clearCache(): RedirectResponse
    {
        return $this->clear(
            [CacheKind::APPLICATION],
            __('admin.cache.cache_cleared_success'),
            __('admin.cache.cache_cleared_error'),
        );
    }

    public function clearConfig(): RedirectResponse
    {
        return $this->clear(
            [CacheKind::CONFIG],
            __('admin.cache.config_cleared_success'),
            __('admin.cache.config_cleared_error'),
        );
    }

    public function clearViews(): RedirectResponse
    {
        return $this->clear(
            [CacheKind::VIEWS],
            __('admin.cache.views_cleared_success'),
            __('admin.cache.views_cleared_error'),
        );
    }

    public function clearRoutes(): RedirectResponse
    {
        return $this->clear(
            [CacheKind::ROUTES],
            __('admin.cache.routes_cleared_success'),
            __('admin.cache.routes_cleared_error'),
        );
    }

    public function clearAll(): RedirectResponse
    {
        return $this->clear(
            CacheKind::cases(),
            __('admin.cache.all_cleared_success'),
            __('admin.cache.all_cleared_error'),
        );
    }

    /**
     * @param  list<CacheKind>  $caches
     */
    private function clear(
        array $caches,
        string $successMessage,
        string $errorMessage,
    ): RedirectResponse {
        try {
            $this->useCases->run(
                $this->admin(),
                new ClearCache($caches),
            );
            $alerts = Alert::success($successMessage);
            $alerts->flash();
        } catch (CacheClearFailedException $e) {
            Log::error(
                'Clearing cache failed',
                ['caches' => $caches, 'exception' => $e],
            );
            $alerts = Alert::error($errorMessage);
            $alerts->flash();
        }

        $redirector = redirect();

        return $redirector->back();
    }
}
