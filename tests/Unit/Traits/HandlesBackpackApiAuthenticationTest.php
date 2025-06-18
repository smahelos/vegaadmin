<?php

namespace Tests\Unit\Traits;

use App\Traits\HandlesBackpackApiAuthentication;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandlesBackpackApiAuthenticationTest extends TestCase
{
    use HandlesBackpackApiAuthentication;

    #[Test]
    public function trait_can_be_used(): void
    {
        $this->assertTrue(method_exists($this, 'getBackpackUser'));
    }

    #[Test]
    public function get_backpack_user_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'getBackpackUser'));
    }

    #[Test]
    public function backpack_user_has_permission_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'backpackUserHasPermission'));
    }

    #[Test]
    public function backpack_user_has_any_permission_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'backpackUserHasAnyPermission'));
    }

    #[Test]
    public function get_backpack_api_log_context_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'getBackpackApiLogContext'));
    }

    #[Test]
    public function backpack_unauthorized_response_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'backpackUnauthorizedResponse'));
    }

    #[Test]
    public function all_methods_are_protected(): void
    {
        $reflection = new \ReflectionClass($this);
        
        $methods = [
            'getBackpackUser',
            'backpackUserHasPermission',
            'backpackUserHasAnyPermission', 
            'getBackpackApiLogContext',
            'backpackUnauthorizedResponse'
        ];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isProtected(), "Method {$methodName} should be protected");
        }
    }

    #[Test]
    public function backpack_user_has_permission_method_signature(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('backpackUserHasPermission');
        $parameters = $method->getParameters();
        $returnType = $method->getReturnType();

        $this->assertCount(1, $parameters);
        $this->assertEquals('permission', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function backpack_user_has_any_permission_method_signature(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('backpackUserHasAnyPermission');
        $parameters = $method->getParameters();
        $returnType = $method->getReturnType();

        $this->assertCount(1, $parameters);
        $this->assertEquals('permissions', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function get_backpack_api_log_context_method_signature(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getBackpackApiLogContext');
        $parameters = $method->getParameters();
        $returnType = $method->getReturnType();

        $this->assertCount(1, $parameters);
        $this->assertEquals('additionalContext', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function backpack_unauthorized_response_method_signature(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('backpackUnauthorizedResponse');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('message', $parameters[0]->getName());
        $this->assertEquals('statusCode', $parameters[1]->getName());
        
        // Both parameters should have default values
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals(401, $parameters[1]->getDefaultValue());
    }

    #[Test]
    public function trait_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HandlesBackpackApiAuthentication');
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
    }

    #[Test]
    public function trait_has_class_docblock(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HandlesBackpackApiAuthentication');
        $docComment = $reflection->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Backpack API authentication trait', $docComment);
    }

    #[Test]
    public function methods_have_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass($this);
        
        $methods = [
            'getBackpackUser',
            'backpackUserHasPermission',
            'backpackUserHasAnyPermission',
            'getBackpackApiLogContext',
            'backpackUnauthorizedResponse'
        ];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();
            
            $this->assertNotFalse($docComment, "Method {$methodName} should have docblock");
            $this->assertStringContainsString('@', $docComment, "Method {$methodName} docblock should contain annotations");
        }
    }
}
