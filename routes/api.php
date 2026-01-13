<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\AresLookupController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\UELSController;

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
    ->group(function () {
        Route::get('/user', [UserController::class, 'getUsersAdmin'])->name('users');
        Route::get('/user/search', [UserController::class, 'searchUsersAdmin'])->name('users.search');
        Route::get('/user/{id}', [UserController::class, 'getUserAdmin'])->whereNumber('id')->name('user');
        Route::get('/client', [ClientController::class, 'getClientsAdmin'])->name('clients');
        Route::get('/client/{id}', [ClientController::class, 'getClientAdmin'])->whereNumber('id')->name('client');
        Route::get('/supplier', [SupplierController::class, 'getSuppliersAdmin'])->name('suppliers');
        Route::get('/supplier/{id}', [SupplierController::class, 'getSupplierAdmin'])->whereNumber('id')->name('supplier');
        Route::get('/invoice/{id}', [InvoiceController::class, 'getInvoice'])->whereNumber('id')->name('invoice');
        Route::get('/invoice', [InvoiceController::class, 'getInvoice'])->name('invoice.query');
    });

// Frontend API endpoints - require authentication and frontend API access
Route::middleware(['api.require.frontend', 'refresh.frontend.session'])
    ->name('api.')
    ->group(function () {
        // Clients
        Route::get('/client', [ClientController::class, 'getClients'])->name('clients');
        Route::get('/client/default', [ClientController::class, 'getDefaultClient'])->name('client.default');
        Route::get('/client/{id}', [ClientController::class, 'getClient'])->whereNumber('id')->name('client');

        // Suppliers
        Route::get('/supplier', [SupplierController::class, 'getSuppliers'])->name('suppliers');
        Route::get('/supplier/default', [SupplierController::class, 'getDefaultSupplier'])->name('supplier.default');
        Route::get('/supplier/{id}', [SupplierController::class, 'getSupplier'])->whereNumber('id')->name('supplier');

        // Invoices
        Route::get('/invoice', [InvoiceController::class, 'getInvoice'])->name('invoice.query');
        Route::get('/invoice/{id}', [InvoiceController::class, 'getInvoice'])->whereNumber('id')->name('invoice');

        // UELS
        Route::get('/uels', [UELSController::class, 'getUELS'])->name('uels');
        Route::get('/uels/{id}', [UELSController::class, 'getUELSById'])->name('uels.id');

        // UELS (Universal Entity Limit System) API endpoints
        Route::get('/uels/{entity}/data', [UELSController::class, 'getEntityData'])->name('uels.entity-data');
        Route::get('/uels/all-entities', [UELSController::class, 'getAllEntitiesData'])->name('uels.all-entities');
    });

// Public API endpoints - no authentication required (rate limited)
Route::middleware(['web','throttle:60,1'])
    ->name('api.')
    ->group(function () {
        // Countries and currencies
        Route::get('/countries', [CountryController::class, 'getCountries'])->name('countries');
        Route::get('/countries/{code}', [CountryController::class, 'getCountry'])->name('country');
        Route::get('/currencies/common', [CurrencyController::class, 'getCommonCurrencies'])->name('currencies.common');
        Route::get('/currencies/all', [CurrencyController::class, 'getAllCurrencies'])->name('currencies.all');
        Route::get('/currencies/exchange-rate', [CurrencyController::class, 'getExchangeRate'])->name('exchange-rate');
        Route::get('/currencies/convert', [CurrencyController::class, 'convertCurrency'])->name('convert-currency');

        // ARES lookup
        Route::get('/ares-lookup', [AresLookupController::class, 'lookup'])->name('ares-lookup');

        // UELS - Universal Entity Limit System
        Route::middleware('auth:web')->group(function () {
            Route::get('/uels/{entity}', [UELSController::class, 'getEntityData'])->name('uels.entity');
            Route::get('/uels', [UELSController::class, 'getAllEntitiesData'])->name('uels.all');
        });

        // Statistics (public access)
        Route::get('/monthly-revenue', [StatisticsController::class, 'monthlyRevenue'])->name('statistics.monthly-revenue');
        Route::get('/client-revenue', [StatisticsController::class, 'clientRevenue'])->name('statistics.client-revenue');
        Route::get('/invoice-status', [StatisticsController::class, 'invoiceStatus'])->name('statistics.invoice-status');
        Route::get('/payment-methods', [StatisticsController::class, 'paymentMethods'])->name('statistics.payment-methods');
        Route::get('/revenue-expenses', [StatisticsController::class, 'revenueExpenses'])->name('statistics.revenue-expenses');
    });

// Session control API endpoints - minimal middleware
Route::middleware(['web'])
    ->name('api.')
    ->group(function () {
        Route::get('/auth-check', [SessionController::class, 'checkAuth'])->name('auth-check');
        Route::post('/session-refresh', [SessionController::class, 'refreshSession'])->name('session-refresh');
    });

// Sanctum route for authenticated users (if needed)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
