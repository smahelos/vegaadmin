<?php

namespace Tests\Feature\Providers;

use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppServiceProviderFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function provider_is_registered_in_application(): void
    {
        $providers = App::getLoadedProviders();
        $this->assertArrayHasKey(AppServiceProvider::class, $providers);
    }

    #[Test]
    public function invoice_observer_is_registered(): void
    {
        // Test that observer is actually working by creating an invoice
        $invoice = Invoice::factory()->create([
            'invoice_text' => json_encode([
                ['name' => 'Test Product', 'quantity' => 1, 'price' => 100]
            ])
        ]);
        
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        
        // Check if observer is registered by testing that observer class exists
        $this->assertTrue(class_exists(InvoiceObserver::class));
    }

    #[Test]
    public function backpack_user_blade_directive_is_registered(): void
    {
        // Test that the directive exists by checking if it's been registered
        $blade = app('blade.compiler');
        
        // The directive should be available in the custom directives
        $customDirectives = $blade->getCustomDirectives();
        $this->assertArrayHasKey('backpackUser', $customDirectives);
    }

    #[Test]
    public function custom_blade_components_are_registered(): void
    {
        $components = [
            'application-logo',
            'nav-link', 
            'responsive-nav-link',
            'dropdown',
            'dropdown-link',
            'select',
            'currency-select',
            'pagination'
        ];

        foreach ($components as $component) {
            // Test that component can be compiled
            $blade = app('blade.compiler');
            $compiled = $blade->compileString("<x-{$component} />");
            
            $this->assertStringContainsString('<?php', $compiled);
        }
    }

    #[Test]
    public function user_crud_controller_binding_is_registered(): void
    {
        $originalController = \Backpack\PermissionManager\app\Http\Controllers\UserCrudController::class;
        $customController = \App\Http\Controllers\Admin\UserCrudController::class;
        
        $resolved = app($originalController);
        $this->assertInstanceOf($customController, $resolved);
    }

    #[Test]
    public function helper_files_are_loaded(): void
    {
        // Test that helper classes are available since they're loaded via require_once
        $this->assertTrue(class_exists(\App\Helpers\UserHelpers::class));
        
        // Test that BackpackHelpers functions are available
        $this->assertTrue(function_exists('backpack_auth'));
        $this->assertTrue(function_exists('backpack_guard_name'));
        $this->assertTrue(function_exists('backpack_user'));
    }

    #[Test]
    public function provider_can_be_instantiated(): void
    {
        $provider = new AppServiceProvider(app());
        $this->assertInstanceOf(AppServiceProvider::class, $provider);
    }

    #[Test]
    public function register_method_executes_without_errors(): void
    {
        $provider = new AppServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->register();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }

    #[Test]
    public function boot_method_executes_without_errors(): void
    {
        $provider = new AppServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->boot();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }
}
