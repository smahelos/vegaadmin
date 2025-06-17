<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for Client Model
 * 
 * Tests internal model structure, fillable attributes, casts, accessors, and mutators
 * These tests do not require database interactions and focus on model configuration
 */
class ClientTest extends TestCase
{
    /**
     * Test that client casts are correctly defined.
     *
     * @return void
     */
    #[Test]
    public function client_has_correct_casts()
    {
        $client = new Client();
        $this->assertArrayHasKey('is_default', $client->getCasts());
        $this->assertEquals('boolean', $client->getCasts()['is_default']);
    }

    /**
     * Test getFullAddressAttribute accessor.
     *
     * @return void
     */
    #[Test]
    public function get_full_address_attribute()
    {
        $client = new Client();
        $client->street = '123 Main St';
        $client->zip = '12345';
        $client->city = 'Prague';
        $client->country = 'Czech Republic';
        
        $expected = '123 Main St, 12345 Prague, Czech Republic';
        $this->assertEquals($expected, $client->getFullAddressAttribute());
    }

    /**
     * Test getFullNameAttribute accessor.
     *
     * @return void
     */
    #[Test]
    public function get_full_name_attribute()
    {
        $client = new Client();
        $client->name = 'Test Company';
        $client->shortcut = 'TC';
        
        $expected = 'Test Company (TC)';
        $this->assertEquals($expected, $client->getFullNameAttribute());
    }

    /**
     * Test setIsDefaultAttribute mutator converts values to boolean.
     *
     * @return void
     */
    #[Test]
    public function set_is_default_attribute_mutator()
    {
        $client = new Client();
        
        // Test various truthy values
        $client->setIsDefaultAttribute(1);
        $this->assertTrue($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute('1');
        $this->assertTrue($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute('true');
        $this->assertTrue($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute(true);
        $this->assertTrue($client->getAttributes()['is_default']);
        
        // Test various falsy values
        $client->setIsDefaultAttribute(0);
        $this->assertFalse($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute('0');
        $this->assertFalse($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute('false');
        $this->assertFalse($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute(false);
        $this->assertFalse($client->getAttributes()['is_default']);
        
        $client->setIsDefaultAttribute(null);
        $this->assertFalse($client->getAttributes()['is_default']);
    }

    /**
     * Test that client uses correct traits.
     *
     * @return void
     */
    #[Test]
    public function client_uses_correct_traits()
    {
        $client = new Client();
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

    /**
     * Test that client table name is correctly set.
     *
     * @return void
     */
    #[Test]
    public function client_has_correct_table_name()
    {
        $client = new Client();
        $this->assertEquals('clients', $client->getTable());
    }

    /**
     * Test that client guarded attributes are correctly defined.
     *
     * @return void
     */
    #[Test]
    public function client_has_correct_guarded_attributes()
    {
        $client = new Client();
        $this->assertEquals(['id'], $client->getGuarded());
    }
}
