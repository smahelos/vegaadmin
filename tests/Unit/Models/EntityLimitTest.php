<?php

namespace Tests\Unit\Models;

use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit test for EntityLimit model structure and behavior
 * Tests pure business logic without database dependency
 */
class EntityLimitTest extends TestCase
{
    private EntityLimit $entityLimit;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityLimit = new EntityLimit([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'description' => 'Monthly invoice creation limit',
            'is_active' => true
        ]);
    }

    #[Test]
    public function model_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'permission_name',
            'entity_type',
            'limit_value',
            'period_type',
            'metric_type',
            'description',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $this->entityLimit->getFillable());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'is_active' => 'boolean'
        ];

        $casts = $this->entityLimit->getCasts();
        
        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertArrayHasKey($attribute, $casts);
            $this->assertEquals($cast, $casts[$attribute]);
        }
    }

    #[Test]
    public function model_has_entity_types_constant(): void
    {
        $expectedTypes = [
            'invoice',
            'client',
            'supplier',
            'product',
            'expense',
            'user',
            'page'
        ];

        $this->assertEquals($expectedTypes, EntityLimit::ENTITY_TYPES);
    }

    #[Test]
    public function model_has_metric_types_constant(): void
    {
        $expectedTypes = [
            'count' => 'Count-based limit',
            'value' => 'Value-based limit (monetary)', 
            'size' => 'Size-based limit (file size)'
        ];

        $this->assertEquals($expectedTypes, EntityLimit::METRIC_TYPES);
    }

    #[Test]
    public function model_has_period_types_constant(): void
    {
        $expectedTypes = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'lifetime' => 'Lifetime'
        ];

        $this->assertEquals($expectedTypes, EntityLimit::PERIOD_TYPES);
    }

    #[Test]
    public function display_name_accessor_formats_name_correctly(): void
    {
        $this->entityLimit->permission_name = 'can_create_edit_invoice';
        $this->assertEquals('Can create edit invoice', $this->entityLimit->display_name);

        $this->entityLimit->permission_name = 'backpack.access';
        $this->assertEquals('Backpack.access', $this->entityLimit->display_name);
    }

    #[Test]
    public function full_description_accessor_combines_attributes_correctly(): void
    {
        $expected = "Users with 'can_create_edit_invoice' permission can create 10 invoice entities per monthly";
        $this->assertEquals($expected, $this->entityLimit->full_description);

        $this->entityLimit->period_type = 'daily';
        $this->entityLimit->limit_value = 5;
        $this->entityLimit->entity_type = 'client';
        
        $expected = "Users with 'can_create_edit_invoice' permission can create 5 client entities per daily";
        $this->assertEquals($expected, $this->entityLimit->full_description);
    }

    #[Test]
    public function model_defines_usage_relationship(): void
    {
        // In new permission-based system, usage() returns a query builder
        // not a direct Eloquent relationship
        $usage = $this->entityLimit->usage();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $usage);
    }

    #[Test]
    public function model_uses_correct_traits(): void
    {
        $traits = class_uses(EntityLimit::class);
        
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
        $this->assertContains(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
    }

    #[Test]
    public function boolean_cast_works_correctly(): void
    {
        $this->entityLimit->is_active = 1;
        $this->assertTrue($this->entityLimit->is_active);
        
        $this->entityLimit->is_active = 0;
        $this->assertFalse($this->entityLimit->is_active);
        
        $this->entityLimit->is_active = true;
        $this->assertTrue($this->entityLimit->is_active);
        
        $this->entityLimit->is_active = false;
        $this->assertFalse($this->entityLimit->is_active);
    }
}
