<?php

declare(strict_types=1);

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
$route = Route::get(
    '/',
    function () {
        $defaultLocale = config('app.locale', 'cs');

        return redirect("/{$defaultLocale}");
    },
);
$route->name('root');

// The web group comes from bootstrap/app.php; locale and session refresh run after the session has started
$registrar = Route::middleware([
    'set.locale',
    'refresh.frontend.session',
]);
$registrar->group(
    function (): void {

        // Routes with optional locale parameter
        Route::localized(
            function (): void {
                $registrar = Route::middleware('auth');
                $registrar->group(
                    function (): void {
                        // Dashboard
                        $route = Route::get('/dashboard', [DashboardController::class, 'index']);
                        $route->name('frontend.dashboard');

                        // Invoices
                        $route = Route::get('/invoice', [InvoiceController::class, 'index']);
                        $route->name('frontend.invoices');
                        $route = Route::get('/invoice/create', [InvoiceController::class, 'create']);
                        $route->name('frontend.invoice.create');
                        $route = Route::post('/invoice', [InvoiceController::class, 'store']);
                        $route->name('frontend.invoice.store');
                        $route = Route::get('/invoice/{id}', [InvoiceController::class, 'show']);
                        $route->name('frontend.invoice.show');
                        $route = Route::get('/invoice/{id}/edit', [InvoiceController::class, 'edit']);
                        $route->name('frontend.invoice.edit');
                        $route = Route::put('/invoice/{id}', [InvoiceController::class, 'update']);
                        $route->name('frontend.invoice.update');
                        $route = Route::get('/invoice/{id}/download', [InvoiceController::class, 'download']);
                        $route->name('frontend.invoice.download');
                        $route = Route::put('/invoice/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);
                        $route->name('frontend.invoice.mark-as-paid');
                        $route = Route::put('/invoice/{id}/set-template/{template}', [InvoiceController::class, 'setTemplate']);
                        $route->name('frontend.invoice.set-template');
                        $route = Route::post('/invoice/{id}/actions', [InvoiceController::class, 'batchActions']);
                        $route->name('frontend.invoice.batch-actions');
                        $route = Route::post('/invoices/{invoice}/save-to-contacts', [InvoiceContactController::class, 'saveToContacts']);
                        $route->whereNumber('invoice');
                        $route->name('frontend.invoices.save-to-contacts');
                        $route = Route::post('/invoices/{invoice}/link-contact', [InvoiceContactController::class, 'linkContact']);
                        $route->whereNumber('invoice');
                        $route->name('frontend.invoices.link-contact');

                        // Businesses and contacts
                        $resource = Route::resource('businesses', BusinessController::class);
                        $resource->only([
                            'index',
                            'create',
                            'show',
                            'edit',
                        ]);
                        $resource->names('frontend.businesses');
                        $resource->whereNumber('business');
                        $resource->register();
                        $resource = Route::resource('contacts', ContactController::class);
                        $resource->only([
                            'index',
                            'create',
                            'show',
                            'edit',
                        ]);
                        $resource->names('frontend.contacts');
                        $resource->whereNumber('contact');
                        $resource->register();

                        // Profile
                        $route = Route::get('/profile', [ProfileController::class, 'edit']);
                        $route->name('frontend.profile.edit');
                        $route = Route::put('/profile', [ProfileController::class, 'update']);
                        $route->name('frontend.profile.update');
                        $route = Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
                        $route->name('frontend.profile.update.password');

                        // Products
                        $route = Route::get('/product', [ProductController::class, 'index']);
                        $route->name('frontend.products');
                        $route = Route::get('/product/create', [ProductController::class, 'create']);
                        $route->name('frontend.product.create');
                        $route = Route::post('/product', [ProductController::class, 'store']);
                        $route->name('frontend.product.store');
                        $route = Route::delete('/product/{id}', [ProductController::class, 'destroy']);
                        $route->name('frontend.product.destroy');
                        $route = Route::get('/product/{id}', [ProductController::class, 'show']);
                        $route->name('frontend.product.show');
                        $route = Route::get('/product/{id}/edit', [ProductController::class, 'edit']);
                        $route->name('frontend.product.edit');
                        $route = Route::put('/product/{id}', [ProductController::class, 'update']);
                        $route->name('frontend.product.update');

                        // Subscriptions
                        $route = Route::get('/subscriptions', [SubscriptionController::class, 'index']);
                        $route->name('subscriptions.index');
                        $route = Route::get('/subscription-plans/{plan}', [SubscriptionController::class, 'show']);
                        $route->name('subscriptions.show');
                        $route = Route::post('/subscribe/{plan}', [SubscriptionController::class, 'subscribe']);
                        $route->name('subscriptions.subscribe');
                        $route = Route::delete('/subscription/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
                        $route->name('subscriptions.cancel');
                        $route = Route::get('/my-subscription', [SubscriptionController::class, 'mySubscription']);
                        $route->name('subscriptions.my-subscription');
                    },
                );

                // Frontend authentication routes, for guests only
                $registrar = Route::middleware('guest');
                $registrar->group(
                    function (): void {
                        // Registration
                        $route = Route::get('/register', [RegisterController::class, 'create']);
                        $route->name('frontend.register');

                        // Login
                        $route = Route::get('/login', [LoginController::class, 'showLoginForm']);
                        $route->name('frontend.login');
                        Route::post('/login', [LoginController::class, 'login']);

                        // Test route for HomeController (used only in automated tests to verify behavior)
                        $route = Route::get('/test-home-controller', [HomeController::class, 'index']);
                        $route->name('frontend.home.test');
                    },
                );

                // Public routes (for guests)
                $route = Route::get('/', [PageController::class, 'homepage']);
                $route->name('home');

                // Guest invoice creation - moved from homepage
                $route = Route::get('/create-invoice', [InvoiceController::class, 'createForGuest']);
                $route->name('frontend.invoice.create.guest');

                $route = Route::post('/guest-invoices', [InvoiceController::class, 'storeGuest']);
                $route->name('frontend.invoice.store.guest');
                $route->withoutMiddleware(['auth']);

                // Download invoice with token (for guests)
                $route = Route::get('/invoices/download/{token}', [InvoiceController::class, 'downloadWithToken']);
                $route->name('frontend.invoice.download.token');
                $route->withoutMiddleware(['auth']);

                // Delete invoice with token (for guests)
                $route = Route::delete('/guest-invoices/{token}', [InvoiceController::class, 'destroyGuest']);
                $route->where('token', '[A-Za-z0-9]{64}');
                $route->name('frontend.guest-invoices.destroy');
                $route->withoutMiddleware(['auth']);

                // Page routes
                $route = Route::get('/pages', [PageController::class, 'index']);
                $route->name('frontend.pages.index');
                $route = Route::get('/pages/search', [PageController::class, 'search']);
                $route->name('frontend.pages.search');
                $route = Route::get('/pages/sitemap', [PageController::class, 'sitemap']);
                $route->name('frontend.pages.sitemap');
                $route = Route::get('/pages/category/{slug}', [PageController::class, 'categoryBySlug']);
                $route->name('frontend.pages.category.slug');
                $route = Route::get('/pages/category/id/{categoryId}', [PageController::class, 'category']);
                $route->name('frontend.pages.category');
                $route->where('categoryId', '[0-9]+');
                $route = Route::get('/pages/{slug}', [PageController::class, 'show']);
                $route->name('frontend.pages.show');

                // Logout route
                $route = Route::post('/logout', [LoginController::class, 'logout']);
                $route->name('frontend.logout');
            },
        );
    },
);

// Auth routes outside locale group for Laravel auth middleware compatibility
$registrar = Route::middleware('guest');
$registrar->group(
    function (): void {
        $route = Route::get('/login', [LoginController::class, 'showLoginForm']);
        $route->name('login');
    },
);

// Payment callback routes (outside locale and auth middleware)
$route = Route::get('/payment/return', [PaymentController::class, 'return']);
$route->name('payment.return');
$route = Route::post('/payment/notify', [PaymentController::class, 'notify']);
$route->name('payment.notify');
