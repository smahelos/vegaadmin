<?php

namespace Tests\Unit\Models;

use App\Models\EntityLimitUsage;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit test for EntityLimitUsage model structure and behavior
 * Tests pure business logic without database dependency
 */
class EntityLimitUsageTest extends TestCase
{
    private EntityLimitUsage $usage;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->usage = new EntityLimitUsage([
            'user_id' => 1,
            'entity_type' => 'invoices',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-31',
            'current_value' => 150.50,
            'last_reset_at' => '2025-01-01 00:00:00'
        ]);
    }

    #[Test]
    public function model_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'user_id',
            'entity_type',
            'metric_type',
            'period_type',
            'period_start',
            'period_end',
            'current_value',
            'last_reset_at'
        ];

        $this->assertEquals($expectedFillable, $this->usage->getFillable());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'user_id' => 'integer',
            'current_value' => 'decimal:2',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'last_reset_at' => 'datetime'
        ];

        $casts = $this->usage->getCasts();
        
        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertArrayHasKey($attribute, $casts);
            $this->assertEquals($cast, $casts[$attribute]);
        }
    }

    #[Test]
    public function model_defines_user_relationship(): void
    {
        $relation = $this->usage->user();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    #[Test]
    public function model_uses_correct_traits(): void
    {
        $traits = class_uses(EntityLimitUsage::class);
        
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }

    #[Test]
    public function decimal_cast_works_correctly(): void
    {
        $this->usage->setAttribute('current_value', '123.456');
        $this->assertEquals('123.46', (string) $this->usage->current_value);
        
        $this->usage->setAttribute('current_value', 99.99);
        $this->assertEquals('99.99', (string) $this->usage->current_value);
    }

    #[Test]
    public function integer_casts_work_correctly(): void
    {
        $this->usage->user_id = '7';
        $this->assertIsInt($this->usage->user_id);
        $this->assertEquals(7, $this->usage->user_id);
    }
}
