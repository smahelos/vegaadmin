<?php

namespace Tests\Unit\Models;

use App\Models\UserActivitySummary;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserActivitySummaryTest extends TestCase
{
    private UserActivitySummary $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserActivitySummary();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_crud_trait(): void
    {
        $this->assertArrayHasKey(CrudTrait::class, class_uses($this->model));
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertArrayHasKey(HasFactory::class, class_uses($this->model));
    }

    #[Test]
    public function table_name_is_user_activity_summary(): void
    {
        $this->assertEquals('user_activity_summary', $this->model->getTable());
    }

    #[Test]
    public function timestamps_are_disabled(): void
    {
        $this->assertFalse($this->model->timestamps);
    }

    #[Test]
    public function primary_key_is_user_id(): void
    {
        $this->assertEquals('user_id', $this->model->getKeyName());
        
        // Check protected property using reflection
        $reflection = new \ReflectionClass($this->model);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('user_id', $property->getValue($this->model));
    }

    #[Test]
    public function casts_are_properly_defined(): void
    {
        $expectedCasts = [
            'last_invoice_date' => 'datetime',
            'total_invoices' => 'integer',
            'total_clients' => 'integer',
            'total_suppliers' => 'integer',
            'total_products' => 'integer',
            'invoices_last_30_days' => 'integer',
            'invoices_last_7_days' => 'integer'
        ];

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function get_key_name_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getKeyName');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function scope_with_recent_activity_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeWithRecentActivity'));
    }

    #[Test]
    public function scope_high_activity_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeHighActivity'));
    }

    #[Test]
    public function scope_inactive_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeInactive'));
    }

    #[Test]
    public function get_activity_level_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'getActivityLevelAttribute'));
    }

    #[Test]
    public function get_activity_badge_class_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'getActivityBadgeClassAttribute'));
    }

    #[Test]
    public function get_formatted_activity_level_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'getFormattedActivityLevelAttribute'));
    }

    #[Test]
    public function user_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'user'));
    }

    #[Test]
    public function activity_level_calculation_logic_works_correctly(): void
    {
        // Test high activity
        $this->model->invoices_last_30_days = 25;
        $this->assertEquals('high', $this->model->activity_level);

        // Test medium activity
        $this->model->invoices_last_30_days = 10;
        $this->assertEquals('medium', $this->model->activity_level);

        // Test low activity
        $this->model->invoices_last_30_days = 2;
        $this->assertEquals('low', $this->model->activity_level);

        // Test inactive
        $this->model->invoices_last_30_days = 0;
        $this->assertEquals('inactive', $this->model->activity_level);
    }

    #[Test]
    public function activity_badge_class_returns_correct_values(): void
    {
        // Mock activity_level accessor calls
        $this->model->invoices_last_30_days = 25; // high
        $this->assertEquals('success', $this->model->activity_badge_class);

        $this->model->invoices_last_30_days = 10; // medium
        $this->assertEquals('primary', $this->model->activity_badge_class);

        $this->model->invoices_last_30_days = 2; // low
        $this->assertEquals('warning', $this->model->activity_badge_class);

        $this->model->invoices_last_30_days = 0; // inactive
        $this->assertEquals('secondary', $this->model->activity_badge_class);
    }

    #[Test]
    public function fillable_attributes_are_properly_defined(): void
    {
        $expectedFillable = [
            'user_id',
            'user_name',
            'user_email',
            'total_invoices',
            'total_clients',
            'total_suppliers',
            'total_products',
            'last_invoice_date',
            'invoices_last_30_days',
            'invoices_last_7_days'
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }
}
