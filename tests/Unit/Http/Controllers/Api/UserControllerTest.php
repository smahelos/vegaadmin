<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\UserController;
use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    private UserController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        // Provide mocked dependency so we do not rely on Laravel container in pure unit test
        $service = $this->createMock(UserApplicationServiceInterface::class);
        $auth = $this->createMock(UserAuthorizationAdapterInterface::class);
        $this->controller = new UserController($service, $auth);
    }

    // No special teardown needed

    #[Test]
    public function controller_extends_base_controller(): void
    {
        $this->assertInstanceOf(\App\Http\Controllers\Controller::class, $this->controller);
    }

    #[Test]
    public function controller_has_auth_helpers(): void
    {
        $this->assertTrue(method_exists($this->controller, 'getBackpackUser'));
        $this->assertTrue(method_exists($this->controller, 'getFrontendUser'));

        $reflection = new \ReflectionClass($this->controller);
        $backpackMethod = $reflection->getMethod('getBackpackUser');
        $frontendMethod = $reflection->getMethod('getFrontendUser');
        $this->assertTrue($backpackMethod->isProtected());
        $this->assertTrue($frontendMethod->isProtected());
    }

    #[Test]
    public function get_user_admin_method_exists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'getUserAdmin'));
    }

    #[Test]
    public function get_user_admin_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUserAdmin');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
        
        $parameters = $method->getParameters();
        $this->assertEquals('id', $parameters[0]->getName());
    }

    #[Test]
    public function get_users_admin_method_exists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'getUsersAdmin'));
    }

    #[Test]
    public function get_users_admin_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUsersAdmin');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(0, $method->getNumberOfParameters());
    }

    #[Test]
    public function search_users_admin_method_exists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'searchUsersAdmin'));
    }

    #[Test]
    public function search_users_admin_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('searchUsersAdmin');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
        
        $parameters = $method->getParameters();
        $this->assertEquals('request', $parameters[0]->getName());
        $paramType = $parameters[0]->getType();
        $this->assertNotNull($paramType);
        $resolvedName = method_exists($paramType,'getName') ? $paramType->getName() : (string)$paramType;
        $this->assertEquals(Request::class, $resolvedName);
    }

    #[Test]
    public function search_users_admin_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('searchUsersAdmin');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $resolvedReturn = method_exists($returnType,'getName') ? $returnType->getName() : (string)$returnType;
        $this->assertEquals(LengthAwarePaginator::class, $resolvedReturn);
    }

    #[Test]
    public function controller_methods_are_properly_documented(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        
        // Check getUserAdmin method documentation
        $getUserAdminMethod = $reflection->getMethod('getUserAdmin');
        $docComment = $getUserAdminMethod->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('@return', $docComment);
        
        // Check searchUsersAdmin method documentation
        $searchMethod = $reflection->getMethod('searchUsersAdmin');
        $docComment = $searchMethod->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('Request', $docComment);
        $this->assertStringContainsString('LengthAwarePaginator', $docComment);
    }

    #[Test]
    public function controller_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $this->assertEquals('App\Http\Controllers\Api', $reflection->getNamespaceName());
    }

    #[Test]
    public function controller_class_name_follows_convention(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $this->assertEquals('UserController', $reflection->getShortName());
        $this->assertStringEndsWith('Controller', $reflection->getShortName());
    }

    #[Test]
    public function constructor_requires_user_service_and_optional_auth(): void
    {
        $reflection = new \ReflectionClass(UserController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(2, $params);

        // First param: UserApplicationServiceInterface
        $type0 = $params[0]->getType();
        $this->assertNotNull($type0);
        $resolved0 = method_exists($type0,'getName') ? $type0->getName() : (string)$type0;
        $this->assertEquals(UserApplicationServiceInterface::class, $resolved0);

        // Second param: optional UserAuthorizationAdapterInterface with default null
        $type1 = $params[1]->getType();
        $this->assertNotNull($type1);
        $resolved1 = method_exists($type1,'getName') ? $type1->getName() : (string)$type1;
        $this->assertEquals(UserAuthorizationAdapterInterface::class, $resolved1);
        $this->assertTrue($params[1]->isOptional());
    }

    #[Test]
    public function deprecated_getUser_method_does_not_exist(): void
    {
        $this->assertFalse(method_exists($this->controller, 'getUser'));
    }

    #[Test]
    public function public_method_set_matches_expected_contract(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $publicMethods = [];
        foreach ($methods as $m) {
            if ($m->getDeclaringClass()->getName() === UserController::class) {
                $publicMethods[] = $m->getName();
            }
        }
        sort($publicMethods);
        $this->assertEquals([
            '__construct',
            'getUserAdmin',
            'getUsersAdmin',
            'searchUsersAdmin'
        ], $publicMethods);
    }
}
