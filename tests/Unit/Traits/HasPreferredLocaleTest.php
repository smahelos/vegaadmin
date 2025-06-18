<?php

namespace Tests\Unit\Traits;

use App\Traits\HasPreferredLocale;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HasPreferredLocaleTest extends TestCase
{
    #[Test]
    public function trait_exists_and_is_trait(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $this->assertTrue($reflection->isTrait());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function trait_has_get_preferred_locale_method(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $this->assertTrue($reflection->hasMethod('getPreferredLocale'));
        
        $method = $reflection->getMethod('getPreferredLocale');
        $this->assertTrue($method->isPublic());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function trait_has_locale_from_country_method(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $this->assertTrue($reflection->hasMethod('localeFromCountry'));
        
        $method = $reflection->getMethod('localeFromCountry');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function get_preferred_locale_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $method = $reflection->getMethod('getPreferredLocale');
        
        // Should have no parameters
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function locale_from_country_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $method = $reflection->getMethod('localeFromCountry');
        
        // Should have one nullable parameter
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        
        $param = $parameters[0];
        $this->assertEquals('country', $param->getName());
        $this->assertTrue($param->allowsNull());
        
        // Check parameter type
        $paramType = $param->getType();
        $this->assertNotNull($paramType);
        $this->assertEquals('string', $paramType->getName());
        $this->assertTrue($paramType->allowsNull());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        
        // Check getPreferredLocale method docblock
        $getPreferredLocaleMethod = $reflection->getMethod('getPreferredLocale');
        $getPreferredLocaleDocComment = $getPreferredLocaleMethod->getDocComment();
        $this->assertNotFalse($getPreferredLocaleDocComment);
        $this->assertStringContainsString('Determines preferred language', $getPreferredLocaleDocComment);
        $this->assertStringContainsString('@return string', $getPreferredLocaleDocComment);
        
        // Check localeFromCountry method docblock
        $localeFromCountryMethod = $reflection->getMethod('localeFromCountry');
        $localeFromCountryDocComment = $localeFromCountryMethod->getDocComment();
        $this->assertNotFalse($localeFromCountryDocComment);
        $this->assertStringContainsString('Convert country to preferred language', $localeFromCountryDocComment);
        $this->assertStringContainsString('@param string|null $country', $localeFromCountryDocComment);
        $this->assertStringContainsString('@return string', $localeFromCountryDocComment);
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        
        // Check namespace
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
        
        // Check that it's not abstract, final, etc.
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
        $this->assertFalse($reflection->isInstantiable()); // Traits are not instantiable
    }

    #[Test]
    public function trait_has_expected_method_count(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 2 methods
        $this->assertCount(2, $methods);
        
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $this->assertContains('getPreferredLocale', $methodNames);
        $this->assertContains('localeFromCountry', $methodNames);
    }

    #[Test]
    public function locale_from_country_is_static_method(): void
    {
        $reflection = new \ReflectionClass(HasPreferredLocale::class);
        $method = $reflection->getMethod('localeFromCountry');
        
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }
}
