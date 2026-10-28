<?php

use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\UELSController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Clean, organized API routes with proper middleware and permissions
|--------------------------------------------------------------------------
*/

// Admin API endpoints - require authentication and backpack API access
Route::middleware(['api.require.backpack', 'refresh.backpack.session'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function (): void {
        Route::get('/user', [UserController::class, 'getUsersAdmin'])->name('users');
        Route::get('/user/search', [UserController::class, 'searchUsersAdmin'])->name('users.search');
        Route::get('/user/{id}', [UserController::class, 'getUserAdmin'])->whereNumber('id')->name('user');
        Route::get('/invoice/{id}', [InvoiceController::class, 'getInvoice'])->whereNumber('id')->name('invoice');
        Route::get('/invoice', [InvoiceController::class, 'getInvoice'])->name('invoice.query');
    });

// Frontend API endpoints - require authentication and frontend API access
Route::middleware(['api.require.frontend', 'refresh.frontend.session'])
    ->name('api.')
    ->group(function (): void {
        // Invoices
        Route::get('/invoice', [InvoiceController::class, 'getInvoice'])->name('invoice.query');
        Route::get('/invoice/{id}', [InvoiceController::class, 'getInvoice'])->whereNumber('id')->name('invoice');

        // UELS
        Route::get('/uels', [UELSController::class, 'getUELS'])->name('uels');
        Route::get('/uels/{id}', [UELSController::class, 'getUELSById'])->name('uels.id');

        // UELS (Universal Entity Limit System) API endpoints
        Route::get('/uels/{entity}/data', [UELSController::class, 'getEntityData'])->name('uels.entity-data');
        Route::get('/uels/all-entities', [UELSController::class, 'getAllEntitiesData'])->name('uels.all-entities');

        // Dashboard charts
        Route::get('/statistics/monthly-revenue', [StatisticsController::class, 'monthlyRevenue'])->name('statistics.monthly-revenue');
        Route::get('/statistics/client-revenue', [StatisticsController::class, 'clientRevenue'])->name('statistics.client-revenue');
        Route::get('/statistics/invoice-status', [StatisticsController::class, 'invoiceStatus'])->name('statistics.invoice-status');
        Route::get('/statistics/payment-methods', [StatisticsController::class, 'paymentMethods'])->name('statistics.payment-methods');
        Route::get('/statistics/revenue-expenses', [StatisticsController::class, 'revenueExpenses'])->name('statistics.revenue-expenses');
    });

// Public API endpoints - no authentication required (rate limited)
Route::middleware('throttle:60,1')
    ->name('api.')
    ->group(function (): void {
        // Countries and currencies
        Route::get('/countries', [CountryController::class, 'getCountries'])->name('countries');
        Route::get('/countries/{code}', [CountryController::class, 'getCountry'])->name('country');
        Route::get('/currencies/common', [CurrencyController::class, 'getCommonCurrencies'])->name('currencies.common');
        Route::get('/currencies/all', [CurrencyController::class, 'getAllCurrencies'])->name('currencies.all');
        Route::get('/currencies/exchange-rate', [CurrencyController::class, 'getExchangeRate'])->name('exchange-rate');
        Route::get('/currencies/convert', [CurrencyController::class, 'convertCurrency'])->name('convert-currency');

        // UELS - Universal Entity Limit System
        Route::middleware('auth:web')->group(function (): void {
            Route::get('/uels/{entity}', [UELSController::class, 'getEntityData'])->name('uels.entity');
            Route::get('/uels', [UELSController::class, 'getAllEntitiesData'])->name('uels.all');
        });
    });

// Session control API endpoints; the session comes from statefulApi() in bootstrap/app.php
Route::name('api.')
    ->group(function (): void {
        Route::get('/auth-check', [SessionController::class, 'checkAuth'])->name('auth-check');
        Route::post('/session-refresh', [SessionController::class, 'refreshSession'])->name('session-refresh');
    });

// Sanctum route for authenticated users (if needed)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
