<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('invoice', 'InvoiceCrudController');
    Route::crud('client', 'ClientCrudController');
    Route::crud('payment-method', 'PaymentMethodCrudController');
    Route::crud('status', 'StatusCrudController');
    Route::crud('status-category', 'StatusCategoryCrudController');
    Route::crud('supplier', 'SupplierCrudController');
    Route::crud('cron-task', 'CronTaskCrudController');
    Route::get('cron-task/{id}/run', 'App\Http\Controllers\Admin\CronTaskCrudController@runCronTask');
    Route::crud('artisan-command-category', 'ArtisanCommandCategoryCrudController');
    Route::crud('artisan-command', 'ArtisanCommandCrudController');
    Route::crud('tax', 'TaxCrudController');
    Route::crud('bank', 'BankCrudController');
    Route::crud('product', 'ProductCrudController');
    Route::crud('product-category', 'ProductCategoryCrudController');
    Route::crud('expense', 'ExpenseCrudController');
    Route::crud('expense-category', 'ExpenseCategoryCrudController');

    // Database Management Routes
    Route::get('database-dashboard', 'DatabaseDashboardController@index')->name('admin.database.dashboard');
    Route::post('database-dashboard/run-command', 'DatabaseDashboardController@runCommand')->name('admin.database.run-command');
    Route::crud('database-maintenance-log', 'DatabaseMaintenanceLogCrudController');
    Route::crud('performance-metric', 'PerformanceMetricCrudController');
    Route::crud('archive-policy', 'ArchivePolicyCrudController');
    Route::crud('mysql-optimization-log', 'MysqlOptimizationLogCrudController');
    Route::post('database-dashboard/resolve-alert', 'DatabaseDashboardController@resolveAlert');
    Route::post('database-dashboard/clean-metrics', 'DatabaseDashboardController@cleanPerformanceMetrics');
    Route::post('database-dashboard/metrics-stats', 'DatabaseDashboardController@getMetricsStats');
    Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
    Route::crud('page-category', 'PageCategoryCrudController');
    Route::crud('page', 'PageCrudController');
    Route::crud('subscription-plan', 'SubscriptionPlanCrudController');
    Route::crud('subscription', 'SubscriptionCrudController');
    Route::crud('subscription-plan-feature', 'SubscriptionPlanFeatureCrudController');

    // UELS Dashboard and Management Routes
    Route::get('uels-dashboard', 'UELSDashboardController@index')->name('admin.uels.dashboard');
    Route::post('uels-dashboard/bulk-reset', 'UELSDashboardController@bulkResetUsage')->name('admin.uels.bulk-reset');
    Route::post('uels-dashboard/user-analysis', 'UELSDashboardController@getUserLimitAnalysis')->name('admin.uels.user-analysis');
    Route::get('uels-dashboard/export', 'UELSDashboardController@exportUsageData')->name('admin.uels.export');
    Route::crud('entity-limit', 'EntityLimitCrudController');
    Route::crud('entity-usage', 'EntityUsageCrudController');

    // Cache Management Routes
    Route::get('cache/clear', 'CacheManagementController@clearCache')->name('admin.cache.clear');
    Route::get('cache/clear-config', 'CacheManagementController@clearConfig')->name('admin.cache.clear-config');
    Route::get('cache/clear-views', 'CacheManagementController@clearViews')->name('admin.cache.clear-views');
    Route::get('cache/clear-routes', 'CacheManagementController@clearRoutes')->name('admin.cache.clear-routes');
    Route::get('cache/clear-all', 'CacheManagementController@clearAll')->name('admin.cache.clear-all');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
