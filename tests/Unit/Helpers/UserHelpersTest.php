<?php

namespace Tests\Unit\Helpers;

use App\Helpers\UserHelpers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserHelpersTest extends TestCase
{
    private UserHelpers $helper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->helper = new UserHelpers();
    }

    #[Test]
    public function class_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(UserHelpers::class, $this->helper);
    }

    #[Test]
    public function get_user_name_method_exists_and_is_static(): void
    {
        $this->assertTrue(method_exists(UserHelpers::class, 'getUserName'));
        
        $reflection = new \ReflectionMethod(UserHelpers::class, 'getUserName');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
    }

    #[Test]
    public function get_user_name_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $method = $reflection->getMethod('getUserName');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function get_user_name_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $method = $reflection->getMethod('getUserName');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        
        // First parameter: $default
        $defaultParam = $parameters[0];
        $this->assertEquals('default', $defaultParam->getName());
        $this->assertFalse($defaultParam->hasType()); // No type hint
        $this->assertTrue($defaultParam->isOptional());
        $this->assertEquals('Guest', $defaultParam->getDefaultValue());
    }

    #[Test]
    public function is_backpack_user_method_exists_and_is_static(): void
    {
        $this->assertTrue(method_exists(UserHelpers::class, 'isBackpackUser'));
        
        $reflection = new \ReflectionMethod(UserHelpers::class, 'isBackpackUser');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
    }

    #[Test]
    public function is_backpack_user_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $method = $reflection->getMethod('isBackpackUser');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function is_backpack_user_method_has_no_parameters(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $method = $reflection->getMethod('isBackpackUser');
        $parameters = $method->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function class_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        
        $this->assertEquals('App\Helpers', $reflection->getNamespaceName());
    }

    #[Test]
    public function class_has_correct_method_count(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Should have only getUserName and isBackpackUser methods
        $this->assertCount(2, $publicMethods);
        
        $methodNames = array_map(fn($method) => $method->getName(), $publicMethods);
        $this->assertContains('getUserName', $methodNames);
        $this->assertContains('isBackpackUser', $methodNames);
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        
        // Should not extend any class
        $this->assertFalse($reflection->getParentClass());
        
        // Should not implement any interfaces
        $this->assertEmpty($reflection->getInterfaceNames());
        
        // Should not be abstract
        $this->assertFalse($reflection->isAbstract());
        
        // Should not be final
        $this->assertFalse($reflection->isFinal());
    }

    #[Test]
    public function get_user_name_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $method = $reflection->getMethod('getUserName');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('string', $docComment);
    }

    #[Test]
    public function is_backpack_user_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $method = $reflection->getMethod('isBackpackUser');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('bool', $docComment);
    }

    #[Test]
    public function class_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(UserHelpers::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\Support\Facades\Auth;', $fileContent);
    }
}
