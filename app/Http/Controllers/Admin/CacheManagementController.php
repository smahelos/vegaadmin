<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClearCacheRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\Facades\Alert;
use Throwable;

final class CacheManagementController extends Controller
{
    public function clearCache(ClearCacheRequest $request): RedirectResponse
    {
        return $this->clear(
            ['cache:clear'],
            __('admin.cache.cache_cleared_success'),
            __('admin.cache.cache_cleared_error'),
        );
    }

    public function clearConfig(ClearCacheRequest $request): RedirectResponse
    {
        return $this->clear(
            ['config:clear'],
            __('admin.cache.config_cleared_success'),
            __('admin.cache.config_cleared_error'),
        );
    }

    public function clearViews(ClearCacheRequest $request): RedirectResponse
    {
        return $this->clear(
            ['view:clear'],
            __('admin.cache.views_cleared_success'),
            __('admin.cache.views_cleared_error'),
        );
    }

    public function clearRoutes(ClearCacheRequest $request): RedirectResponse
    {
        return $this->clear(
            ['route:clear'],
            __('admin.cache.routes_cleared_success'),
            __('admin.cache.routes_cleared_error'),
        );
    }

    public function clearAll(ClearCacheRequest $request): RedirectResponse
    {
        return $this->clear(
            [
                'cache:clear',
                'config:clear',
                'view:clear',
                'route:clear',
            ],
            __('admin.cache.all_cleared_success'),
            __('admin.cache.all_cleared_error'),
        );
    }

    /**
     * @param  list<string>  $commands
     */
    private function clear(
        array $commands,
        string $successMessage,
        string $errorMessage,
    ): RedirectResponse {
        try {
            foreach ($commands as $command) {
                Artisan::call($command);
            }
            Alert::success($successMessage)->flash();
        } catch (Throwable $e) {
            Log::error('Clearing cache failed', [
                'commands' => $commands,
                'exception' => $e,
            ]);
            Alert::error($errorMessage)->flash();
        }

        return redirect()->back();
    }
}
