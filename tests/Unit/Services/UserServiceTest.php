<?php

namespace Tests\Unit\Services;

use App\Contracts\UserServiceInterface;
use App\Services\UserService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UserService::class, $this->service);
    }

    #[Test]
    public function service_implements_user_service_interface(): void
    {
        $this->assertInstanceOf(UserServiceInterface::class, $this->service);
    }

    #[Test]
    public function update_profile_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'updateProfile'));
    }

    #[Test]
    public function update_password_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'updatePassword'));
    }

    #[Test]
    public function find_user_by_id_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'findUserById'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertTrue($reflection->hasMethod('updateProfile'));
        $this->assertTrue($reflection->hasMethod('updatePassword'));
        $this->assertTrue($reflection->hasMethod('findUserById'));
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === UserService::class) {
                $this->assertNotNull(
                    $method->getReturnType(),
                    "Method {$method->getName()} should have a return type"
                );
            }
        }
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($method) => $method->getDeclaringClass()->getName() === UserService::class
        );
        
        $this->assertCount(3, $publicMethods, 'UserService should have exactly 3 public methods');
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Check updateProfile method
        $method = $reflection->getMethod('updateProfile');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        $this->assertEquals('array', $parameters[1]->getType()->getName());
        
        // Check updatePassword method
        $method = $reflection->getMethod('updatePassword');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        
        // Check findUserById method
        $method = $reflection->getMethod('findUserById');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }
}
