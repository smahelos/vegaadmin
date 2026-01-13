<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Prologue\Alerts\Facades\Alert;

class CacheManagementController extends Controller
{
    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Alert::success(__('admin.cache.cache_cleared_success'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('admin.cache.cache_cleared_error', ['error' => $e->getMessage()]))->flash();
        }

        return redirect()->back();
    }

    /**
     * Clear configuration cache
     */
    public function clearConfig()
    {
        try {
            Artisan::call('config:clear');
            Alert::success(__('admin.cache.config_cleared_success'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('admin.cache.config_cleared_error', ['error' => $e->getMessage()]))->flash();
        }

        return redirect()->back();
    }

    /**
     * Clear view cache
     */
    public function clearViews()
    {
        try {
            Artisan::call('view:clear');
            Alert::success(__('admin.cache.views_cleared_success'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('admin.cache.views_cleared_error', ['error' => $e->getMessage()]))->flash();
        }

        return redirect()->back();
    }

    /**
     * Clear route cache
     */
    public function clearRoutes()
    {
        try {
            Artisan::call('route:clear');
            Alert::success(__('admin.cache.routes_cleared_success'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('admin.cache.routes_cleared_error', ['error' => $e->getMessage()]))->flash();
        }

        return redirect()->back();
    }

    /**
     * Clear all caches
     */
    public function clearAll()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            
            Alert::success(__('admin.cache.all_cleared_success'))->flash();
        } catch (\Exception $e) {
            Alert::error(__('admin.cache.all_cleared_error', ['error' => $e->getMessage()]))->flash();
        }

        return redirect()->back();
    }
}
