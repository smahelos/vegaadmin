<?php

namespace Tests\Unit\Traits;

use App\Traits\UserFormFields;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserFormFieldsTest extends TestCase
{
    #[Test]
    public function trait_exists_and_is_trait(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        $this->assertTrue($reflection->isTrait());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function trait_has_get_user_fields_method(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        $this->assertTrue($reflection->hasMethod('getUserFields'));
        
        $method = $reflection->getMethod('getUserFields');
        $this->assertTrue($method->isPublic());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function trait_has_get_password_fields_method(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        $this->assertTrue($reflection->hasMethod('getPasswordFields'));
        
        $method = $reflection->getMethod('getPasswordFields');
        $this->assertTrue($method->isPublic());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_user_fields_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        $method = $reflection->getMethod('getUserFields');
        
        // Should have no parameters
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function get_password_fields_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        $method = $reflection->getMethod('getPasswordFields');
        
        // Should have one optional parameter
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        
        $param = $parameters[0];
        $this->assertEquals('isEdit', $param->getName());
        $this->assertTrue($param->isOptional());
        $this->assertFalse($param->getDefaultValue());
        
        // Check parameter type
        $paramType = $param->getType();
        $this->assertNotNull($paramType);
        $this->assertEquals('bool', $paramType->getName());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        
        // Check getUserFields method docblock
        $getUserFieldsMethod = $reflection->getMethod('getUserFields');
        $getUserFieldsDocComment = $getUserFieldsMethod->getDocComment();
        $this->assertNotFalse($getUserFieldsDocComment);
        $this->assertStringContainsString('Get user form fields definitions', $getUserFieldsDocComment);
        $this->assertStringContainsString('@return array', $getUserFieldsDocComment);
        
        // Check getPasswordFields method docblock
        $getPasswordFieldsMethod = $reflection->getMethod('getPasswordFields');
        $getPasswordFieldsDocComment = $getPasswordFieldsMethod->getDocComment();
        $this->assertNotFalse($getPasswordFieldsDocComment);
        $this->assertStringContainsString('Get password field definitions', $getPasswordFieldsDocComment);
        $this->assertStringContainsString('@param bool $isEdit', $getPasswordFieldsDocComment);
        $this->assertStringContainsString('@return array', $getPasswordFieldsDocComment);
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(UserFormFields::class);
        
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
        $reflection = new \ReflectionClass(UserFormFields::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 2 methods
        $this->assertCount(2, $methods);
        
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $this->assertContains('getUserFields', $methodNames);
        $this->assertContains('getPasswordFields', $methodNames);
    }
}
