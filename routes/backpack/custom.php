<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ArchivePolicyCrudController;
use App\Http\Controllers\Admin\ArtisanCommandCategoryCrudController;
use App\Http\Controllers\Admin\ArtisanCommandCrudController;
use App\Http\Controllers\Admin\BankAccountCrudController;
use App\Http\Controllers\Admin\BankCrudController;
use App\Http\Controllers\Admin\BusinessCrudController;
use App\Http\Controllers\Admin\CacheManagementController;
use App\Http\Controllers\Admin\ContactCrudController;
use App\Http\Controllers\Admin\CronTaskCrudController;
use App\Http\Controllers\Admin\DatabaseDashboardController;
use App\Http\Controllers\Admin\DatabaseMaintenanceLogCrudController;
use App\Http\Controllers\Admin\DocumentPartyCrudController;
use App\Http\Controllers\Admin\EntityLimitCrudController;
use App\Http\Controllers\Admin\EntityUsageCrudController;
use App\Http\Controllers\Admin\ExpenseCategoryCrudController;
use App\Http\Controllers\Admin\ExpenseCrudController;
use App\Http\Controllers\Admin\InvoiceCrudController;
use App\Http\Controllers\Admin\MysqlOptimizationLogCrudController;
use App\Http\Controllers\Admin\PageCategoryCrudController;
use App\Http\Controllers\Admin\PageCrudController;
use App\Http\Controllers\Admin\PaymentMethodCrudController;
use App\Http\Controllers\Admin\PerformanceMetricCrudController;
use App\Http\Controllers\Admin\ProductCategoryCrudController;
use App\Http\Controllers\Admin\ProductCrudController;
use App\Http\Controllers\Admin\StatusCategoryCrudController;
use App\Http\Controllers\Admin\StatusCrudController;
use App\Http\Controllers\Admin\SubscriptionCrudController;
use App\Http\Controllers\Admin\SubscriptionPlanCrudController;
use App\Http\Controllers\Admin\SubscriptionPlanFeatureCrudController;
use App\Http\Controllers\Admin\TaxCrudController;
use App\Http\Controllers\Admin\UELSDashboardController;
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
        (array) config('backpack.base.middleware_key', 'admin'),
    ),
], function (): void { // custom admin routes
    Route::get('document-parties/businesses', [DocumentPartyCrudController::class, 'businesses'])->name('admin.document-parties.businesses');
    Route::get('document-parties/contacts', [DocumentPartyCrudController::class, 'contacts'])->name('admin.document-parties.contacts');
    Route::get('document-parties/accounts', [DocumentPartyCrudController::class, 'accounts'])->name('admin.document-parties.accounts');
    Route::crud('invoice', InvoiceCrudController::class);
    Route::crud('business', BusinessCrudController::class);
    Route::post('business/{id}/archive', [BusinessCrudController::class, 'archive'])->whereNumber('id')->name('admin.businesses.archive');
    Route::post('business/{id}/restore', [BusinessCrudController::class, 'restore'])->whereNumber('id')->name('admin.businesses.restore');
    Route::post('business/{id}/set-default', [BusinessCrudController::class, 'setDefault'])->whereNumber('id')->name('admin.businesses.set-default');
    Route::crud('contact', ContactCrudController::class);
    Route::post('contact/{id}/archive', [ContactCrudController::class, 'archive'])->whereNumber('id')->name('admin.contacts.archive');
    Route::post('contact/{id}/restore', [ContactCrudController::class, 'restore'])->whereNumber('id')->name('admin.contacts.restore');
    Route::crud('bank-account', BankAccountCrudController::class);
    Route::post('bank-account/{id}/archive', [BankAccountCrudController::class, 'archive'])->whereNumber('id')->name('admin.bank-accounts.archive');
    Route::post('bank-account/{id}/restore', [BankAccountCrudController::class, 'restore'])->whereNumber('id')->name('admin.bank-accounts.restore');
    Route::post('bank-account/{id}/set-default', [BankAccountCrudController::class, 'setDefault'])->whereNumber('id')->name('admin.bank-accounts.set-default');
    Route::crud('payment-method', PaymentMethodCrudController::class);
    Route::crud('status', StatusCrudController::class);
    Route::crud('status-category', StatusCategoryCrudController::class);
    Route::crud('cron-task', CronTaskCrudController::class);
    Route::post('cron-task/{id}/run', [CronTaskCrudController::class, 'run'])->whereNumber('id')->name('admin.cron-tasks.run');
    Route::crud('artisan-command-category', ArtisanCommandCategoryCrudController::class);
    Route::crud('artisan-command', ArtisanCommandCrudController::class);
    Route::crud('tax', TaxCrudController::class);
    Route::crud('bank', BankCrudController::class);
    Route::crud('product', ProductCrudController::class);
    Route::crud('product-category', ProductCategoryCrudController::class);
    Route::crud('expense', ExpenseCrudController::class);
    Route::crud('expense-category', ExpenseCategoryCrudController::class);

    // Database Management Routes
    Route::get('database-dashboard', [DatabaseDashboardController::class, 'index'])->name('admin.database.dashboard');
    Route::post('database-dashboard/run-command', [DatabaseDashboardController::class, 'runCommand'])->name('admin.database.run-command');
    Route::crud('database-maintenance-log', DatabaseMaintenanceLogCrudController::class);
    Route::crud('performance-metric', PerformanceMetricCrudController::class);
    Route::crud('archive-policy', ArchivePolicyCrudController::class);
    Route::crud('mysql-optimization-log', MysqlOptimizationLogCrudController::class);
    Route::post('database-dashboard/resolve-alert', [DatabaseDashboardController::class, 'resolveAlert']);
    Route::post('database-dashboard/clean-metrics', [DatabaseDashboardController::class, 'cleanPerformanceMetrics']);
    Route::post('database-dashboard/metrics-stats', [DatabaseDashboardController::class, 'getMetricsStats']);
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('backpack.dashboard');
    Route::crud('page-category', PageCategoryCrudController::class);
    Route::crud('page', PageCrudController::class);
    Route::crud('subscription-plan', SubscriptionPlanCrudController::class);
    Route::crud('subscription', SubscriptionCrudController::class);
    Route::crud('subscription-plan-feature', SubscriptionPlanFeatureCrudController::class);

    // UELS Dashboard and Management Routes
    Route::get('uels-dashboard', [UELSDashboardController::class, 'index'])->name('admin.uels.dashboard');
    Route::post('uels-dashboard/bulk-reset', [UELSDashboardController::class, 'bulkResetUsage'])->name('admin.uels.bulk-reset');
    Route::post('uels-dashboard/user-analysis', [UELSDashboardController::class, 'getUserLimitAnalysis'])->name('admin.uels.user-analysis');
    Route::get('uels-dashboard/export', [UELSDashboardController::class, 'exportUsageData'])->name('admin.uels.export');
    Route::crud('entity-limit', EntityLimitCrudController::class);
    Route::crud('entity-usage', EntityUsageCrudController::class);

    // Cache Management Routes
    Route::post('cache/clear', [CacheManagementController::class, 'clearCache'])->name('admin.cache.clear');
    Route::post('cache/clear-config', [CacheManagementController::class, 'clearConfig'])->name('admin.cache.clear-config');
    Route::post('cache/clear-views', [CacheManagementController::class, 'clearViews'])->name('admin.cache.clear-views');
    Route::post('cache/clear-routes', [CacheManagementController::class, 'clearRoutes'])->name('admin.cache.clear-routes');
    Route::post('cache/clear-all', [CacheManagementController::class, 'clearAll'])->name('admin.cache.clear-all');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
