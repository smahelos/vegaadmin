<?php

namespace Tests\Unit\Providers;

use App\Providers\LocaleServiceProvider;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LocaleServiceProviderTest extends TestCase
{
    #[Test]
    public function provider_extends_service_provider(): void
    {
        $reflection = new \ReflectionClass(LocaleServiceProvider::class);
        $this->assertTrue($reflection->isSubclassOf(ServiceProvider::class));
    }

    #[Test]
    public function provider_has_register_method(): void
    {
        $reflection = new \ReflectionClass(LocaleServiceProvider::class);
        $this->assertTrue($reflection->hasMethod('register'));
        
        $method = $reflection->getMethod('register');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function provider_has_boot_method(): void
    {
        $reflection = new \ReflectionClass(LocaleServiceProvider::class);
        $this->assertTrue($reflection->hasMethod('boot'));
        
        $method = $reflection->getMethod('boot');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function register_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(LocaleServiceProvider::class);
        $method = $reflection->getMethod('register');
        
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function boot_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(LocaleServiceProvider::class);
        $method = $reflection->getMethod('boot');
        
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(LocaleServiceProvider::class);
        
        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }
}
