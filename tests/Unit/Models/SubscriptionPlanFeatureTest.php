<?php

namespace Tests\Unit\Models;

use App\Models\SubscriptionPlanFeature;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SubscriptionPlanFeatureTest extends TestCase
{
    private SubscriptionPlanFeature $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SubscriptionPlanFeature();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertContains(HasFactory::class, class_uses_recursive($this->model));
    }

    #[Test]
    public function model_uses_crud_trait(): void
    {
        $this->assertContains(CrudTrait::class, class_uses_recursive($this->model));
    }

    #[Test]
    public function model_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'name',
            'description',
            'slug',
            'is_active',
            'sort_order',
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'id' => 'int',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];

        $actualCasts = $this->model->getCasts();
        
        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertArrayHasKey($attribute, $actualCasts);
            $this->assertEquals($expectedCast, $actualCasts[$attribute]);
        }
    }

    #[Test]
    public function model_has_correct_table_name(): void
    {
        $this->assertEquals('subscription_plan_features', $this->model->getTable());
    }

    #[Test]
    public function subscription_plans_method_returns_belongs_to_many_relation(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('subscriptionPlans');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals(BelongsToMany::class, $returnType->getName());
    }

    #[Test]
    public function model_has_scope_active(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeActive'));
    }

    #[Test]
    public function model_has_scope_ordered(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeOrdered'));
    }

    #[Test]
    public function model_has_is_active_method(): void
    {
        $this->assertTrue(method_exists($this->model, 'isActive'));
    }

    #[Test]
    public function is_active_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('isActive');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function model_has_generate_slug_method(): void
    {
        $this->assertTrue(method_exists($this->model, 'generateSlug'));
    }

    #[Test]
    public function generate_slug_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('generateSlug');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }
}
