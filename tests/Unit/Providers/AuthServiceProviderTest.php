<?php

namespace Tests\Unit\Providers;

use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthServiceProviderTest extends TestCase
{
    #[Test]
    public function provider_extends_auth_service_provider(): void
    {
        $reflection = new \ReflectionClass(AuthServiceProvider::class);
        $this->assertTrue($reflection->isSubclassOf(ServiceProvider::class));
    }

    #[Test]
    public function provider_has_policies_property(): void
    {
        $reflection = new \ReflectionClass(AuthServiceProvider::class);
        $this->assertTrue($reflection->hasProperty('policies'));
        
        $property = $reflection->getProperty('policies');
        $this->assertTrue($property->isProtected());
    }

    #[Test]
    public function provider_has_boot_method(): void
    {
        $reflection = new \ReflectionClass(AuthServiceProvider::class);
        $this->assertTrue($reflection->hasMethod('boot'));
        
        $method = $reflection->getMethod('boot');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function boot_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(AuthServiceProvider::class);
        $method = $reflection->getMethod('boot');
        
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function policies_property_is_array(): void
    {
        $provider = new AuthServiceProvider(app());
        $reflection = new \ReflectionClass($provider);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        
        $policies = $property->getValue($provider);
        $this->assertIsArray($policies);
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(AuthServiceProvider::class);
        
        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }
}
