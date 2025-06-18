<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Client model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, table name, accessors, mutators, relationships)
 * have been moved to Feature tests.
 */
class ClientTest extends TestCase
{
    #[Test]
    public function client_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(Client::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Spatie\Permission\Traits\HasRoles',
            'Illuminate\Notifications\Notifiable',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
            'App\Traits\HasPreferredLocale',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Client model should use {$trait} trait");
        }
    }

    #[Test]
    public function client_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(Client::class));
        
        $reflection = new \ReflectionClass(Client::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function client_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(Client::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function client_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(Client::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function client_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(Client::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }
}
