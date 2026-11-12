<?php

declare(strict_types=1);

use App\Application\User\Queries\SearchUsers;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StatisticsController;
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
$registrar = Route::middleware(['api.require.backpack', 'refresh.backpack.session']);
$registrar->prefix('admin');
$registrar->name('api.admin.');
$registrar->group(
    function (): void {
        $route = Route::get('/user/search', [UserController::class, 'search']);
        $route->name('users.search');
        // `allowed` names the use case the action runs through the gate: change them together
        $route->middleware('allowed:' . SearchUsers::class);
        $route = Route::get('/invoice/{id}', [InvoiceController::class, 'getInvoice']);
        $route->whereNumber('id');
        $route->name('invoice');
        $route = Route::get('/invoice', [InvoiceController::class, 'getInvoice']);
        $route->name('invoice.query');
    },
);

// Frontend API endpoints - require authentication and frontend API access
$registrar = Route::middleware(['api.require.frontend', 'refresh.frontend.session']);
$registrar->name('api.');
$registrar->group(
    function (): void {
        // Invoices
        $route = Route::get('/invoice', [InvoiceController::class, 'getInvoice']);
        $route->name('invoice.query');
        $route = Route::get('/invoice/{id}', [InvoiceController::class, 'getInvoice']);
        $route->whereNumber('id');
        $route->name('invoice');

        // Dashboard charts
        $route = Route::get('/statistics/monthly-revenue', [StatisticsController::class, 'monthlyRevenue']);
        $route->name('statistics.monthly-revenue');
        $route = Route::get('/statistics/customer-revenue', [StatisticsController::class, 'customerRevenue']);
        $route->name('statistics.customer-revenue');
        $route = Route::get('/statistics/invoice-status', [StatisticsController::class, 'invoiceStatus']);
        $route->name('statistics.invoice-status');
        $route = Route::get('/statistics/payment-methods', [StatisticsController::class, 'paymentMethods']);
        $route->name('statistics.payment-methods');
        $route = Route::get('/statistics/revenue-expenses', [StatisticsController::class, 'revenueExpenses']);
        $route->name('statistics.revenue-expenses');
    },
);

// Public API endpoints - no authentication required (rate limited)
$registrar = Route::middleware('throttle:60,1');
$registrar->name('api.');
$registrar->group(
    function (): void {
        // Countries and currencies
        $route = Route::get('/countries', [CountryController::class, 'getCountries']);
        $route->name('countries');
        $route = Route::get('/countries/{code}', [CountryController::class, 'getCountry']);
        $route->name('country');
        $route = Route::get('/currencies/common', [CurrencyController::class, 'getCommonCurrencies']);
        $route->name('currencies.common');
        $route = Route::get('/currencies/all', [CurrencyController::class, 'getAllCurrencies']);
        $route->name('currencies.all');
        $route = Route::get('/currencies/exchange-rate', [CurrencyController::class, 'getExchangeRate']);
        $route->name('exchange-rate');
        $route = Route::get('/currencies/convert', [CurrencyController::class, 'convertCurrency']);
        $route->name('convert-currency');
    },
);

// Session control API endpoints; the session comes from statefulApi() in bootstrap/app.php
$registrar = Route::name('api.');
$registrar->group(
    function (): void {
        $route = Route::get('/auth-check', [SessionController::class, 'checkAuth']);
        $route->name('auth-check');
        $route = Route::post('/session-refresh', [SessionController::class, 'refreshSession']);
        $route->name('session-refresh');
    },
);

// Sanctum route for authenticated users (if needed)
$registrar = Route::middleware('auth:sanctum');
$registrar->get('/user', function (Request $request) {
    return $request->user();
});
