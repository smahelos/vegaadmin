<?php

namespace Tests\Unit\Models;

use App\Models\Supplier;
use Tests\TestCase;

/**
 * Unit tests for Supplier Model
 * 
 * Tests internal model structure, fillable attributes, casts, accessors, and business logic methods
 * These tests do not require database interactions and focus on model configuration
 */
class SupplierTest extends TestCase
{
    /**
     * Test that supplier fillable attributes are correctly defined.
     *
     * @return void
     */
    public function test_supplier_has_correct_fillable_attributes()
    {
        $supplier = new Supplier();
        $fillable = [
            'name',
            'email',
            'street',
            'city',
            'zip',
            'country',
            'ico',
            'dic',
            'phone',
            'description',
            'is_default',
            'user_id',
            'account_number',
            'bank_code',
            'iban',
            'swift',
            'bank_name',
            'has_payment_info',
        ];

        $this->assertEquals($fillable, $supplier->getFillable());
    }

    /**
     * Test that supplier casts are correctly defined.
     *
     * @return void
     */
    public function test_supplier_has_correct_casts()
    {
        $supplier = new Supplier();
        $this->assertArrayHasKey('is_default', $supplier->getCasts());
        $this->assertArrayHasKey('has_payment_info', $supplier->getCasts());
        $this->assertEquals('boolean', $supplier->getCasts()['is_default']);
        $this->assertEquals('boolean', $supplier->getCasts()['has_payment_info']);
    }

    /**
     * Test getFullAddressAttribute accessor.
     *
     * @return void
     */
    public function test_get_full_address_attribute()
    {
        $supplier = new Supplier();
        $supplier->street = '456 Business Ave';
        $supplier->zip = '54321';
        $supplier->city = 'Brno';
        $supplier->country = 'Czech Republic';
        
        $expected = '456 Business Ave, 54321 Brno, Czech Republic';
        $this->assertEquals($expected, $supplier->getFullAddressAttribute());
    }

    /**
     * Test hasCompletePaymentInfo method with complete data.
     *
     * @return void
     */
    public function test_has_complete_payment_info_with_complete_data()
    {
        $supplier = new Supplier();
        $supplier->account_number = '123456789';
        $supplier->bank_code = '0800';
        
        $this->assertTrue($supplier->hasCompletePaymentInfo());
    }

    /**
     * Test hasCompletePaymentInfo method with incomplete data.
     *
     * @return void
     */
    public function test_has_complete_payment_info_with_incomplete_data()
    {
        $supplier = new Supplier();
        
        // Test with no payment info
        $this->assertFalse($supplier->hasCompletePaymentInfo());
        
        // Test with only account number
        $supplier->account_number = '123456789';
        $this->assertFalse($supplier->hasCompletePaymentInfo());
        
        // Test with only bank code
        $supplier = new Supplier();
        $supplier->bank_code = '0800';
        $this->assertFalse($supplier->hasCompletePaymentInfo());
    }

    /**
     * Test hasCompletePaymentInfo method with empty strings.
     *
     * @return void
     */
    public function test_has_complete_payment_info_with_empty_strings()
    {
        $supplier = new Supplier();
        $supplier->account_number = '';
        $supplier->bank_code = '';
        
        $this->assertFalse($supplier->hasCompletePaymentInfo());
        
        // Test with one empty string
        $supplier->account_number = '123456789';
        $supplier->bank_code = '';
        
        $this->assertFalse($supplier->hasCompletePaymentInfo());
    }

    /**
     * Test getHasPaymentInfoAttribute accessor.
     *
     * @return void
     */
    public function test_get_has_payment_info_attribute()
    {
        $supplier = new Supplier();
        
        // Test with no payment info
        $this->assertFalse($supplier->getHasPaymentInfoAttribute());
        
        // Test with account number and bank code
        $supplier->account_number = '123456789';
        $supplier->bank_code = '0800';
        $this->assertTrue($supplier->getHasPaymentInfoAttribute());
        
        // Test with only IBAN
        $supplier = new Supplier();
        $supplier->iban = 'CZ6508000000192000145399';
        $this->assertTrue($supplier->getHasPaymentInfoAttribute());
        
        // Test with both account/bank code and IBAN
        $supplier->account_number = '123456789';
        $supplier->bank_code = '0800';
        $this->assertTrue($supplier->getHasPaymentInfoAttribute());
    }

    /**
     * Test setIsDefaultAttribute mutator.
     *
     * @return void
     */
    public function test_set_is_default_attribute_mutator()
    {
        $supplier = new Supplier();
        
        // Test various truthy values
        $supplier->setIsDefaultAttribute(1);
        $this->assertTrue($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute('1');
        $this->assertTrue($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute('true');
        $this->assertTrue($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute(true);
        $this->assertTrue($supplier->getAttributes()['is_default']);
        
        // Test various falsy values
        $supplier->setIsDefaultAttribute(0);
        $this->assertFalse($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute('0');
        $this->assertFalse($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute('false');
        $this->assertFalse($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute(false);
        $this->assertFalse($supplier->getAttributes()['is_default']);
        
        $supplier->setIsDefaultAttribute(null);
        $this->assertFalse($supplier->getAttributes()['is_default']);
    }

    /**
     * Test that supplier uses correct traits.
     *
     * @return void
     */
    public function test_supplier_uses_correct_traits()
    {
        $supplier = new Supplier();
        $traits = class_uses_recursive(Supplier::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Illuminate\Notifications\Notifiable',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
            'App\Traits\HasPreferredLocale',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Supplier model should use {$trait} trait");
        }
    }

    /**
     * Test that supplier table name is correctly set.
     *
     * @return void
     */
    public function test_supplier_has_correct_table_name()
    {
        $supplier = new Supplier();
        $this->assertEquals('suppliers', $supplier->getTable());
    }

    /**
     * Test that validation handles null values correctly.
     *
     * @return void
     */
    public function test_has_complete_payment_info_with_null_values()
    {
        $supplier = new Supplier();
        $supplier->account_number = '123456789';
        $supplier->bank_code = null; // Null value
        
        $this->assertFalse($supplier->hasCompletePaymentInfo());
        
        $supplier->account_number = null;
        $supplier->bank_code = '0800';
        
        $this->assertFalse($supplier->hasCompletePaymentInfo());
    }
}
