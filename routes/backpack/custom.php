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
    Route::crud('user', 'UserCrudController');
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
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
