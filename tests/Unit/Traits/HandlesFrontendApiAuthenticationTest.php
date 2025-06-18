<?php

namespace Tests\Unit\Traits;

use App\Traits\HandlesFrontendApiAuthentication;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HandlesFrontendApiAuthenticationTest extends TestCase
{
    #[Test]
    public function trait_exists_and_is_trait(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $this->assertTrue($reflection->isTrait());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function trait_has_get_frontend_user_method(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $this->assertTrue($reflection->hasMethod('getFrontendUser'));
        
        $method = $reflection->getMethod('getFrontendUser');
        $this->assertTrue($method->isProtected());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        
        // For nullable types, check both the type name and allowsNull property
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('App\Models\User', $returnType->getName());
            $this->assertTrue($returnType->allowsNull());
        } else {
            // For union types or other complex types
            $this->assertEquals('?App\Models\User', (string) $returnType);
        }
    }

    #[Test]
    public function trait_has_frontend_user_has_permission_method(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $this->assertTrue($reflection->hasMethod('frontendUserHasPermission'));
        
        $method = $reflection->getMethod('frontendUserHasPermission');
        $this->assertTrue($method->isProtected());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function trait_has_frontend_user_has_any_permission_method(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $this->assertTrue($reflection->hasMethod('frontendUserHasAnyPermission'));
        
        $method = $reflection->getMethod('frontendUserHasAnyPermission');
        $this->assertTrue($method->isProtected());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function trait_has_get_frontend_api_log_context_method(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $this->assertTrue($reflection->hasMethod('getFrontendApiLogContext'));
        
        $method = $reflection->getMethod('getFrontendApiLogContext');
        $this->assertTrue($method->isProtected());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function trait_has_frontend_unauthorized_response_method(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $this->assertTrue($reflection->hasMethod('frontendUnauthorizedResponse'));
        
        $method = $reflection->getMethod('frontendUnauthorizedResponse');
        $this->assertTrue($method->isProtected());
        
        // Check method return type - union type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType instanceof \ReflectionUnionType);
    }

    #[Test]
    public function all_methods_are_protected(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $methods = $reflection->getMethods();
        
        foreach ($methods as $method) {
            $this->assertTrue($method->isProtected(), "Method {$method->getName()} should be protected");
            $this->assertFalse($method->isPublic(), "Method {$method->getName()} should not be public");
            $this->assertFalse($method->isPrivate(), "Method {$method->getName()} should not be private");
        }
    }

    #[Test]
    public function method_signatures_are_correct(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        
        // Check getFrontendUser method signature
        $getFrontendUserMethod = $reflection->getMethod('getFrontendUser');
        $this->assertCount(0, $getFrontendUserMethod->getParameters());
        
        // Check frontendUserHasPermission method signature
        $hasPermissionMethod = $reflection->getMethod('frontendUserHasPermission');
        $this->assertCount(1, $hasPermissionMethod->getParameters());
        $param = $hasPermissionMethod->getParameters()[0];
        $this->assertEquals('permission', $param->getName());
        $this->assertEquals('string', $param->getType()->getName());
        
        // Check frontendUserHasAnyPermission method signature
        $hasAnyPermissionMethod = $reflection->getMethod('frontendUserHasAnyPermission');
        $this->assertCount(1, $hasAnyPermissionMethod->getParameters());
        $param = $hasAnyPermissionMethod->getParameters()[0];
        $this->assertEquals('permissions', $param->getName());
        $this->assertEquals('array', $param->getType()->getName());
        
        // Check getFrontendApiLogContext method signature
        $getLogContextMethod = $reflection->getMethod('getFrontendApiLogContext');
        $this->assertCount(1, $getLogContextMethod->getParameters());
        $param = $getLogContextMethod->getParameters()[0];
        $this->assertEquals('additionalContext', $param->getName());
        $this->assertTrue($param->isOptional());
        $this->assertEquals('array', $param->getType()->getName());
        
        // Check frontendUnauthorizedResponse method signature
        $unauthorizedResponseMethod = $reflection->getMethod('frontendUnauthorizedResponse');
        $this->assertCount(2, $unauthorizedResponseMethod->getParameters());
        $params = $unauthorizedResponseMethod->getParameters();
        $this->assertEquals('message', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertEquals('statusCode', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertEquals('int', $params[1]->getType()->getName());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        
        // Check class docblock
        $classDocComment = $reflection->getDocComment();
        $this->assertNotFalse($classDocComment);
        $this->assertStringContainsString('Frontend API authentication trait', $classDocComment);
        $this->assertStringContainsString('Only works with web guard', $classDocComment);
        
        // Check method docblocks
        $methods = ['getFrontendUser', 'frontendUserHasPermission', 'frontendUserHasAnyPermission', 'getFrontendApiLogContext', 'frontendUnauthorizedResponse'];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();
            $this->assertNotFalse($docComment, "Method {$methodName} should have docblock");
            $this->assertStringContainsString('@return', $docComment, "Method {$methodName} should have @return annotation");
        }
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        
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
        $reflection = new \ReflectionClass(HandlesFrontendApiAuthentication::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 5 methods
        $this->assertCount(5, $methods);
        
        $expectedMethods = [
            'getFrontendUser',
            'frontendUserHasPermission', 
            'frontendUserHasAnyPermission',
            'getFrontendApiLogContext',
            'frontendUnauthorizedResponse'
        ];
        
        $actualMethods = array_map(fn($method) => $method->getName(), $methods);
        
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $actualMethods);
        }
    }
}
