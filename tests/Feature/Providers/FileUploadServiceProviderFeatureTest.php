<?php

namespace Tests\Feature\Providers;

use App\Providers\FileUploadServiceProvider;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileUploadServiceProviderFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function provider_is_registered_in_application(): void
    {
        $providers = App::getLoadedProviders();
        $this->assertArrayHasKey(FileUploadServiceProvider::class, $providers);
    }

    #[Test]
    public function file_upload_service_is_registered_as_singleton(): void
    {
        $service1 = app(FileUploadService::class);
        $service2 = app(FileUploadService::class);
        
        $this->assertInstanceOf(FileUploadService::class, $service1);
        $this->assertInstanceOf(FileUploadService::class, $service2);
        $this->assertSame($service1, $service2); // Should be the same instance
    }

    #[Test]
    public function file_upload_service_can_be_resolved_from_container(): void
    {
        $service = app(FileUploadService::class);
        $this->assertInstanceOf(FileUploadService::class, $service);
    }

    #[Test]
    public function provider_can_be_instantiated(): void
    {
        $provider = new FileUploadServiceProvider(app());
        $this->assertInstanceOf(FileUploadServiceProvider::class, $provider);
    }

    #[Test]
    public function register_method_executes_without_errors(): void
    {
        $provider = new FileUploadServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->register();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }

    #[Test]
    public function boot_method_executes_without_errors(): void
    {
        $provider = new FileUploadServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->boot();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }

    #[Test]
    public function service_registration_works_correctly(): void
    {
        // Create a new provider instance and manually register
        $provider = new FileUploadServiceProvider(app());
        $provider->register();
        
        // Test that the service can be resolved
        $service = app(FileUploadService::class);
        $this->assertInstanceOf(FileUploadService::class, $service);
    }

    #[Test]
    public function service_has_required_dependencies(): void
    {
        $service = app(FileUploadService::class);
        
        // Test that the service was properly instantiated
        $this->assertInstanceOf(FileUploadService::class, $service);
        
        // Use reflection to check internal properties
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasProperty('imageManager'));
    }
}
