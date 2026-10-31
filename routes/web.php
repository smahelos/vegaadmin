<?php

use App\Http\Controllers\Frontend\Auth\LoginController;
use App\Http\Controllers\Frontend\Auth\RegisterController;
use App\Http\Controllers\Frontend\BusinessController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\InvoiceContactController;
use App\Http\Controllers\Frontend\InvoiceController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\SubscriptionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect to default locale
Route::get('/', function () {
    $defaultLocale = config('app.locale', 'cs');

    return redirect("/{$defaultLocale}");
})->name('root');

// The web group comes from bootstrap/app.php; locale and session refresh run after the session has started
Route::middleware([
    'set.locale',
    'refresh.frontend.session',
])->group(function (): void {

    // Routes with optional locale parameter
    Route::localized(function (): void {
        // Route::get('/', function () {
        //     return view('frontend.invoices.create');
        // })->name('home');

        // Route::get('/dashboard', function () {
        //     return view('frontend.dashboard');
        // })->middleware(['auth', 'verified'])->name('dashboard');

        Route::middleware('auth')->group(function (): void {
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
            Route::put('/invoice/{id}/set-template/{template}', [InvoiceController::class, 'setTemplate'])->name('frontend.invoice.set-template');
            Route::post('/invoice/{id}/actions', [InvoiceController::class, 'batchActions'])->name('frontend.invoice.batch-actions');
            Route::post('/invoices/{invoice}/save-to-contacts', [InvoiceContactController::class, 'saveToContacts'])
                ->whereNumber('invoice')
                ->name('frontend.invoices.save-to-contacts');
            Route::post('/invoices/{invoice}/link-contact', [InvoiceContactController::class, 'linkContact'])
                ->whereNumber('invoice')
                ->name('frontend.invoices.link-contact');

            // Businesses and contacts
            Route::resource('businesses', BusinessController::class)
                ->only([
                    'index',
                    'create',
                    'show',
                    'edit',
                ])
                ->names('frontend.businesses')
                ->whereNumber('business');
            Route::resource('contacts', ContactController::class)
                ->only([
                    'index',
                    'create',
                    'show',
                    'edit',
                ])
                ->names('frontend.contacts')
                ->whereNumber('contact');

            // Profile
            Route::get('/profile', [ProfileController::class, 'edit'])->name('frontend.profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('frontend.profile.update');
            Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('frontend.profile.update.password');

            // Products
            Route::get('/product', [ProductController::class, 'index'])->name('frontend.products');
            Route::get('/product/create', [ProductController::class, 'create'])->name('frontend.product.create');
            Route::post('/product', [ProductController::class, 'store'])->name('frontend.product.store');
            Route::delete('/product/{id}', [ProductController::class, 'destroy'])->name('frontend.product.destroy');
            Route::get('/product/{id}', [ProductController::class, 'show'])->name('frontend.product.show');
            Route::get('/product/{id}/edit', [ProductController::class, 'edit'])->name('frontend.product.edit');
            Route::put('/product/{id}', [ProductController::class, 'update'])->name('frontend.product.update');

            // Subscriptions
            Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('/subscription-plans/{plan}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
            Route::post('/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
            Route::delete('/subscription/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
            Route::get('/my-subscription', [SubscriptionController::class, 'mySubscription'])->name('subscriptions.my-subscription');
        });

        // Frontend Authentication Routes - pouze pro nepřihlášené uživatele
        Route::middleware('guest')->group(function (): void {
            // Registration
            Route::get('/register', [RegisterController::class, 'create'])->name('frontend.register');

            // Login
            Route::get('/login', [LoginController::class, 'showLoginForm'])->name('frontend.login');
            Route::post('/login', [LoginController::class, 'login']);

            // Test route for HomeController (used only in automated tests to verify behavior)
            Route::get('/test-home-controller', [HomeController::class, 'index'])
                ->name('frontend.home.test');
        });

        // Public routes (for guests)
        Route::get('/', [PageController::class, 'homepage'])->name('home');

        // Guest invoice creation - moved from homepage
        Route::get('/create-invoice', [InvoiceController::class, 'createForGuest'])->name('frontend.invoice.create.guest');

        Route::post('/guest-invoices', [InvoiceController::class, 'storeGuest'])
            ->name('frontend.invoice.store.guest')
            ->withoutMiddleware(['auth']);

        // Download invoice with token (for guests)
        Route::get('/invoices/download/{token}', [InvoiceController::class, 'downloadWithToken'])
            ->name('frontend.invoice.download.token')
            ->withoutMiddleware(['auth']);

        // Delete invoice with token (for guests)
        Route::delete('/guest-invoices/{token}', [InvoiceController::class, 'destroyGuest'])
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('frontend.guest-invoices.destroy')
            ->withoutMiddleware(['auth']);

        // Page routes
        Route::get('/pages', [PageController::class, 'index'])->name('frontend.pages.index');
        Route::get('/pages/search', [PageController::class, 'search'])->name('frontend.pages.search');
        Route::get('/pages/sitemap', [PageController::class, 'sitemap'])->name('frontend.pages.sitemap');
        Route::get('/pages/category/{slug}', [PageController::class, 'categoryBySlug'])->name('frontend.pages.category.slug');
        Route::get('/pages/category/id/{categoryId}', [PageController::class, 'category'])->name('frontend.pages.category')->where('categoryId', '[0-9]+');
        Route::get('/pages/{slug}', [PageController::class, 'show'])->name('frontend.pages.show');

        // Logout route
        Route::post('/logout', [LoginController::class, 'logout'])->name('frontend.logout');
    });
});

// Auth routes outside locale group for Laravel auth middleware compatibility
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
});

// Payment callback routes (outside locale and auth middleware)
Route::get('/payment/return', [PaymentController::class, 'return'])->name('payment.return');
Route::post('/payment/notify', [PaymentController::class, 'notify'])->name('payment.notify');
