<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\AresLookupController;

// Backpack admin API endpointy - přístupné přes jednotný autentizační middleware
Route::middleware(['web', 'api.auth', 'refresh.backpack.session'])->prefix('admin')->group(function () {
    // Admin API endpointy
    Route::get('/client/{id}', [ClientController::class, 'getClient'])->name('api.admin.client');
    Route::get('/supplier/{id}', [SupplierController::class, 'getSupplier'])->name('api.admin.supplier');
});

// Frontend API endpointy - přístupné s běžnou autentizací
Route::middleware(['web', 'api.auth', 'refresh.frontend.session'])->group(function () {
    // Klienti
    Route::get('/client', [ClientController::class, 'getClients'])->name('api.clients');
    Route::get('/client/default', [ClientController::class, 'getDefaultClient'])->name('api.client.default');
    Route::get('/client/{id}', [ClientController::class, 'getClient'])->name('api.client');
    
    // Dodavatelé
    Route::get('/supplier', [SupplierController::class, 'getSuppliers'])->name('api.suppliers');
    Route::get('/supplier/default', [SupplierController::class, 'getDefaultSupplier'])->name('api.supplier.default');
    Route::get('/supplier/{id}', [SupplierController::class, 'getSupplier'])->name('api.supplier');
});

// Veřejné API endpointy - bez autentizace ale s web middleware pro session
Route::middleware(['web', 'refresh.frontend.session'])->group(function () {
    Route::get('/countries', [CountryController::class, 'getCountries'])->name('api.countries');
    Route::get('/countries/{code}', [CountryController::class, 'getCountry'])->name('api.country');
    Route::get('/currencies/common', [CurrencyController::class, 'getCommonCurrencies']);
    Route::get('/currencies/all', [CurrencyController::class, 'getAllCurrencies']);
    Route::get('/currencies/exchange-rate', [CurrencyController::class, 'getExchangeRate'])->name('api.exchange-rate');
    Route::get('/currencies/convert', [CurrencyController::class, 'convertCurrency'])->name('api.convert-currency');
    // ARES lookup route
    Route::get('/ares-lookup', [AresLookupController::class, 'lookup']);
});

// Session kontrolní API endpointy
Route::middleware(['web'])->group(function () {
    Route::get('/auth-check', [SessionController::class, 'checkAuth']);
    Route::post('/session-refresh', [SessionController::class, 'refreshSession']);
});

// Sanctum route pro autentizované uživatele
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
