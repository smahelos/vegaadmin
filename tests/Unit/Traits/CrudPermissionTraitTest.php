<?php

namespace Tests\Unit\Traits;

use App\Traits\CrudPermissionTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CrudPermissionTraitTest extends TestCase
{
    #[Test]
    public function trait_exists_and_is_trait(): void
    {
        $reflection = new \ReflectionClass(CrudPermissionTrait::class);
        $this->assertTrue($reflection->isTrait());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function trait_has_operations_property(): void
    {
        $reflection = new \ReflectionClass(CrudPermissionTrait::class);
        $this->assertTrue($reflection->hasProperty('operations'));
        
        $property = $reflection->getProperty('operations');
        $this->assertTrue($property->isPublic());
        
        // Check property type declaration
        $type = $property->getType();
        $this->assertNotNull($type);
        $this->assertEquals('array', $type->getName());
    }

    #[Test]
    public function trait_has_set_access_using_permissions_method(): void
    {
        $reflection = new \ReflectionClass(CrudPermissionTrait::class);
        $this->assertTrue($reflection->hasMethod('setAccessUsingPermissions'));
        
        $method = $reflection->getMethod('setAccessUsingPermissions');
        $this->assertTrue($method->isPublic());
        
        // Require explicit return type annotation
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function set_access_using_permissions_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(CrudPermissionTrait::class);
        $method = $reflection->getMethod('setAccessUsingPermissions');
        
        // Should have no parameters
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass(CrudPermissionTrait::class);
        
        // Check class docblock
        $classDocComment = $reflection->getDocComment();
        $this->assertNotFalse($classDocComment);
        $this->assertStringContainsString('Configure Backpack CRUD access using Spatie Permissions', $classDocComment);
        
        // Check method docblock
        $method = $reflection->getMethod('setAccessUsingPermissions');
        $methodDocComment = $method->getDocComment();
        $this->assertNotFalse($methodDocComment);
        $this->assertStringContainsString('Set CRUD access based on permissions', $methodDocComment);
        $this->assertStringContainsString('@return void', $methodDocComment);
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(CrudPermissionTrait::class);
        
        // Check namespace
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
        
        // Check that it's not abstract, final, etc.
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
        $this->assertFalse($reflection->isInstantiable()); // Traits are not instantiable
    }
}
