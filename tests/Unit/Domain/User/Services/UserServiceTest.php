<?php

namespace Tests\Unit\Domain\User\Services;

use App\Domain\User\Services\UserService;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private function makeService(): UserService
    {
        $read = $this->createMock(UserReadRepositoryInterface::class);
        $write = $this->createMock(UserWriteRepositoryInterface::class);
        $tx = $this->createMock(TransactionBoundaryInterface::class);
        return new UserService($read, $write, $tx);
    }

    #[Test]
    public function service_implements_user_service_interface(): void
    {
        $service = $this->makeService();
        $this->assertInstanceOf(\App\Domain\User\Contracts\UserServiceInterface::class, $service);
    }

    #[Test]
    public function service_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(UserService::class);
        
        $requiredMethods = [
            'create',
            'findUserByEmail',
            'findUserById',
            'findBySupplierId',
            'findByClientId',
            'updateById',
            'updatePassword',
            'isEmailUnique',
            'getAllUsers',
            'searchUsers',
            'softDeleteUser',
            'restoreUser',
            'updateProfile',
            'changeEmail',
            'getActivitySummary',
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method {$method} should exist");
        }
    }

    #[Test]
    public function create_user_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('data', $parameters[0]->getName());
        
        // Check return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('App\\Domain\\User\\DTO\\UserDTO', (string) $returnType);
    }

    #[Test]
    public function find_by_email_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('findUserByEmail');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('email', $parameters[0]->getName());
        
        // Check return type - should allow null
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    #[Test]
    public function update_password_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('updatePassword');
        $parameters = $method->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('password', $parameters[1]->getName());
        
        // Check return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', (string) $returnType);
    }

    #[Test]
    public function service_method_return_types_are_properly_defined(): void
    {
        $reflection = new \ReflectionClass(UserService::class);
        
        $methodReturnTypes = [
            'create' => 'App\\Domain\\User\\DTO\\UserDTO',
            'findUserByEmail' => '?App\\Domain\\User\\DTO\\UserDTO',
            'findUserById' => '?App\\Domain\\User\\DTO\\UserDTO',
            'findBySupplierId' => '?App\\Domain\\User\\DTO\\UserDTO',
            'findByClientId' => '?App\\Domain\\User\\DTO\\UserDTO',
            'updateById' => 'bool',
            'updatePassword' => 'bool',
            'isEmailUnique' => 'bool',
            'searchUsers' => 'App\\Domain\\Shared\\Pagination\\DTO\\PaginatedResult',
            'softDeleteUser' => 'bool',
            'restoreUser' => 'bool',
            'updateProfile' => 'bool',
            'changeEmail' => 'bool',
            'getActivitySummary' => 'array',
        ];
        
        foreach ($methodReturnTypes as $methodName => $expectedType) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            
            $this->assertNotNull($returnType, "Method {$methodName} should have return type");
            
            $actualType = (string) $returnType;
            
            // Handle nullable types properly
            if ($returnType->allowsNull()) {
                $actualType = '?' . ltrim($actualType, '?');
            }
                
            $this->assertEquals($expectedType, $actualType, "Method {$methodName} return type mismatch");
        }
    }
}
