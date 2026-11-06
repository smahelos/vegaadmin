<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ArchivePolicyCrudController;
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
use App\Http\Controllers\Admin\InvoiceItemsController;
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

Route::group(
    [
        'prefix' => config('backpack.base.route_prefix', 'admin'),
        'middleware' => array_merge(
            (array) config('backpack.base.web_middleware', 'web'),
            (array) config('backpack.base.middleware_key', 'admin'),
        ),
    ],
    function (): void { // custom admin routes
        $route = Route::get('document-parties/businesses', [DocumentPartyCrudController::class, 'businesses']);
        $route->name('admin.document-parties.businesses');
        $route = Route::get('document-parties/contacts', [DocumentPartyCrudController::class, 'contacts']);
        $route->name('admin.document-parties.contacts');
        $route = Route::get('document-parties/accounts', [DocumentPartyCrudController::class, 'accounts']);
        $route->name('admin.document-parties.accounts');
        $route = Route::get('invoice-items/catalog', [InvoiceItemsController::class, 'catalog']);
        $route->name('admin.invoice-items.catalog');
        $route = Route::post('invoice-items/preview', [InvoiceItemsController::class, 'preview']);
        $route->name('admin.invoice-items.preview');
        Route::crud('invoice', InvoiceCrudController::class);
        Route::crud('business', BusinessCrudController::class);
        $route = Route::post('business/{id}/archive', [BusinessCrudController::class, 'archive']);
        $route->whereNumber('id');
        $route->name('admin.businesses.archive');
        $route = Route::post('business/{id}/restore', [BusinessCrudController::class, 'restore']);
        $route->whereNumber('id');
        $route->name('admin.businesses.restore');
        $route = Route::post('business/{id}/set-default', [BusinessCrudController::class, 'setDefault']);
        $route->whereNumber('id');
        $route->name('admin.businesses.set-default');
        Route::crud('contact', ContactCrudController::class);
        $route = Route::post('contact/{id}/archive', [ContactCrudController::class, 'archive']);
        $route->whereNumber('id');
        $route->name('admin.contacts.archive');
        $route = Route::post('contact/{id}/restore', [ContactCrudController::class, 'restore']);
        $route->whereNumber('id');
        $route->name('admin.contacts.restore');
        Route::crud('bank-account', BankAccountCrudController::class);
        $route = Route::post('bank-account/{id}/archive', [BankAccountCrudController::class, 'archive']);
        $route->whereNumber('id');
        $route->name('admin.bank-accounts.archive');
        $route = Route::post('bank-account/{id}/restore', [BankAccountCrudController::class, 'restore']);
        $route->whereNumber('id');
        $route->name('admin.bank-accounts.restore');
        $route = Route::post('bank-account/{id}/set-default', [BankAccountCrudController::class, 'setDefault']);
        $route->whereNumber('id');
        $route->name('admin.bank-accounts.set-default');
        Route::crud('payment-method', PaymentMethodCrudController::class);
        Route::crud('status', StatusCrudController::class);
        Route::crud('status-category', StatusCategoryCrudController::class);
        Route::crud('cron-task', CronTaskCrudController::class);
        $route = Route::post('cron-task/{id}/run', [CronTaskCrudController::class, 'run']);
        $route->whereNumber('id');
        $route->name('admin.cron-tasks.run');
        Route::crud('tax', TaxCrudController::class);
        Route::crud('bank', BankCrudController::class);
        Route::crud('product', ProductCrudController::class);
        Route::crud('product-category', ProductCategoryCrudController::class);
        Route::crud('expense', ExpenseCrudController::class);
        Route::crud('expense-category', ExpenseCategoryCrudController::class);

        // Database Management Routes
        $route = Route::get('database-dashboard', [DatabaseDashboardController::class, 'index']);
        $route->name('admin.database.dashboard');
        $route = Route::post('database-dashboard/run-command', [DatabaseDashboardController::class, 'runCommand']);
        $route->name('admin.database.run-command');
        Route::crud('database-maintenance-log', DatabaseMaintenanceLogCrudController::class);
        Route::crud('performance-metric', PerformanceMetricCrudController::class);
        Route::crud('archive-policy', ArchivePolicyCrudController::class);
        Route::crud('mysql-optimization-log', MysqlOptimizationLogCrudController::class);
        Route::post('database-dashboard/resolve-alert', [DatabaseDashboardController::class, 'resolveAlert']);
        Route::post(
            'database-dashboard/clean-metrics',
            [DatabaseDashboardController::class, 'cleanPerformanceMetrics'],
        );
        Route::post('database-dashboard/metrics-stats', [DatabaseDashboardController::class, 'getMetricsStats']);
        $route = Route::get('dashboard', [AdminController::class, 'dashboard']);
        $route->name('backpack.dashboard');
        Route::crud('page-category', PageCategoryCrudController::class);
        Route::crud('page', PageCrudController::class);
        Route::crud('subscription-plan', SubscriptionPlanCrudController::class);
        Route::crud('subscription', SubscriptionCrudController::class);
        Route::crud('subscription-plan-feature', SubscriptionPlanFeatureCrudController::class);

        // UELS Dashboard and Management Routes
        $route = Route::get('uels-dashboard', [UELSDashboardController::class, 'index']);
        $route->name('admin.uels.dashboard');
        $route = Route::post('uels-dashboard/bulk-reset', [UELSDashboardController::class, 'bulkResetUsage']);
        $route->name('admin.uels.bulk-reset');
        $route = Route::post('uels-dashboard/user-analysis', [UELSDashboardController::class, 'getUserLimitAnalysis']);
        $route->name('admin.uels.user-analysis');
        $route = Route::get('uels-dashboard/export', [UELSDashboardController::class, 'exportUsageData']);
        $route->name('admin.uels.export');
        Route::crud('entity-limit', EntityLimitCrudController::class);
        Route::crud('entity-usage', EntityUsageCrudController::class);

        // Cache Management Routes
        $route = Route::post('cache/clear', [CacheManagementController::class, 'clearCache']);
        $route->name('admin.cache.clear');
        $route = Route::post('cache/clear-config', [CacheManagementController::class, 'clearConfig']);
        $route->name('admin.cache.clear-config');
        $route = Route::post('cache/clear-views', [CacheManagementController::class, 'clearViews']);
        $route->name('admin.cache.clear-views');
        $route = Route::post('cache/clear-routes', [CacheManagementController::class, 'clearRoutes']);
        $route->name('admin.cache.clear-routes');
        $route = Route::post('cache/clear-all', [CacheManagementController::class, 'clearAll']);
        $route->name('admin.cache.clear-all');
    },
); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
