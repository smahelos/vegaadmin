<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\InvoiceController;
use App\Http\Controllers\Frontend\ClientController;
use App\Http\Controllers\Frontend\SupplierController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\Auth\RegisterController;
use App\Http\Controllers\Frontend\Auth\LoginController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Middleware application for frontend routes
// This middleware will refresh the session for the frontend
// and ensure that the session is always up to date
// with the latest data from the database.
Route::middleware([
    'web', 
    'refresh.frontend.session'
])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('frontend.dashboard');
        
        // Invoices
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('frontend.invoices');
        Route::get('/invoice/create', [InvoiceController::class, 'create'])->name('frontend.invoice.create');
        Route::post('/invoice', [InvoiceController::class, 'store'])->name('frontend.invoice.store');
        Route::get('/invoice/{id}', [InvoiceController::class, 'show'])->name('frontend.invoice.show');
        Route::get('/invoice/{id}/edit', [InvoiceController::class, 'edit'])->name('frontend.invoice.edit');
        Route::put('/invoice/{id}', [InvoiceController::class, 'update'])->name('frontend.invoice.update');
        Route::get('/invoice/{id}/download', [InvoiceController::class, 'download'])->name('frontend.invoice.download');
        Route::put('/invoice/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('frontend.invoice.mark-as-paid');
        
        // Clients
        Route::get('/client', [ClientController::class, 'index'])->name('frontend.clients');
        Route::get('/client/create', [ClientController::class, 'create'])->name('frontend.client.create');
        Route::post('/client', [ClientController::class, 'store'])->name('frontend.client.store');
        Route::get('/client/{id}', [ClientController::class, 'show'])->name('frontend.client.show');
        Route::get('/client/{id}/edit', [ClientController::class, 'edit'])->name('frontend.client.edit');
        Route::put('/client/{id}', [ClientController::class, 'update'])->name('frontend.client.update');
        Route::delete('/client/{id}/delete', [ClientController::class, 'destroy'])->name('frontend.client.destroy');
        
        // Suppliers
        Route::get('/supplier', [SupplierController::class, 'index'])->name('frontend.suppliers');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('frontend.supplier.create');
        Route::post('/supplier', [SupplierController::class, 'store'])->name('frontend.supplier.store');
        Route::get('/supplier/{id}', [SupplierController::class, 'show'])->name('frontend.supplier.show');
        Route::get('/supplier/{id}/edit', [SupplierController::class, 'edit'])->name('frontend.supplier.edit');
        Route::put('/supplier/{id}', [SupplierController::class, 'update'])->name('frontend.supplier.update');
        Route::delete('/supplier/{id}', [SupplierController::class, 'destroy'])->name('frontend.supplier.destroy');
        
        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('frontend.profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('frontend.profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('frontend.profile.update.password');

        // Products
        Route::get('/product', [ProductController::class, 'index'])->name('frontend.products');
        Route::get('/product/create', [ProductController::class, 'create'])->name('frontend.product.create');
        Route::post('/product', [ProductController::class, 'store'])->name('frontend.product.store');
        Route::get('/product/{id}/delete', [ProductController::class, 'destroy'])->name('frontend.product.destroy');
        Route::get('/product/{id}', [ProductController::class, 'show'])->name('frontend.product.show');
        Route::get('/product/{id}/edit', [ProductController::class, 'edit'])->name('frontend.product.edit');
        Route::put('/product/{id}', [ProductController::class, 'update'])->name('frontend.product.update');
    });
});

// Public routes (for guests)
Route::get('/', [InvoiceController::class, 'createForGuest'])->name('home');
Route::post('/guest-invoices', [InvoiceController::class, 'storeGuest'])
    ->name('frontend.invoice.store.guest')
    ->withoutMiddleware(['auth']);

// Download invoice with token (for guests)
Route::get('/invoices/download/{token}', [InvoiceController::class, 'downloadWithToken'])
    ->name('frontend.invoice.download.token')
    ->withoutMiddleware(['auth']);

// Delete invoice with token (for guests)
Route::get('/invoices/delete/{token}', [InvoiceController::class, 'deleteGuestInvoice'])
    ->name('frontend.invoice.delete.token')
    ->withoutMiddleware(['auth']);

// Frontend Authentication Routes - pouze pro nepřihlášené uživatele
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Logout route
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
