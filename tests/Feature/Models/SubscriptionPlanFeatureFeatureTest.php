<?php

namespace Tests\Feature\Models;

use App\Models\SubscriptionPlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanFeatureFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function subscription_plan_feature_can_be_created_with_factory(): void
    {
        $feature = SubscriptionPlanFeature::factory()->create();
        
        $this->assertInstanceOf(SubscriptionPlanFeature::class, $feature);
        $this->assertDatabaseHas('subscription_plan_features', [
            'id' => $feature->id,
            'name' => $feature->name,
            'slug' => $feature->slug,
        ]);
    }

    #[Test]
    public function subscription_plans_relationship_works_correctly(): void
    {
        $feature = SubscriptionPlanFeature::factory()->create();
        
        $relationship = $feature->subscriptionPlans();
        
        $this->assertInstanceOf(BelongsToMany::class, $relationship);
    }

    #[Test]
    public function feature_can_be_attached_to_subscription_plans(): void
    {
        $feature = SubscriptionPlanFeature::factory()->create();
        $plan1 = SubscriptionPlan::factory()->create();
        $plan2 = SubscriptionPlan::factory()->create();
        
        $feature->subscriptionPlans()->attach([$plan1->id, $plan2->id]);
        
        $this->assertEquals(2, $feature->subscriptionPlans()->count());
        $this->assertTrue($feature->subscriptionPlans->contains($plan1));
        $this->assertTrue($feature->subscriptionPlans->contains($plan2));
    }

    #[Test]
    public function scope_active_filters_active_features(): void
    {
        SubscriptionPlanFeature::factory()->create(['is_active' => true, 'name' => 'Active Feature']);
        SubscriptionPlanFeature::factory()->create(['is_active' => false, 'name' => 'Inactive Feature']);
        
        $activeFeatures = SubscriptionPlanFeature::active()->get();
        
        $this->assertEquals(1, $activeFeatures->count());
        $this->assertEquals('Active Feature', $activeFeatures->first()->name);
    }

    #[Test]
    public function scope_ordered_sorts_by_sort_order(): void
    {
        $feature1 = SubscriptionPlanFeature::factory()->create(['sort_order' => 3, 'name' => 'Third']);
        $feature2 = SubscriptionPlanFeature::factory()->create(['sort_order' => 1, 'name' => 'First']);
        $feature3 = SubscriptionPlanFeature::factory()->create(['sort_order' => 2, 'name' => 'Second']);
        
        $orderedFeatures = SubscriptionPlanFeature::ordered()->get();
        
        $this->assertEquals('First', $orderedFeatures[0]->name);
        $this->assertEquals('Second', $orderedFeatures[1]->name);
        $this->assertEquals('Third', $orderedFeatures[2]->name);
    }

    #[Test]
    public function is_active_method_returns_boolean(): void
    {
        $activeFeature = SubscriptionPlanFeature::factory()->create(['is_active' => true]);
        $inactiveFeature = SubscriptionPlanFeature::factory()->create(['is_active' => false]);
        
        $this->assertTrue($activeFeature->isActive());
        $this->assertFalse($inactiveFeature->isActive());
    }

    #[Test]
    public function generate_slug_creates_unique_slug(): void
    {
        $feature = SubscriptionPlanFeature::factory()->create(['name' => 'Test Feature Name']);
        
        $slug = $feature->generateSlug('Test Feature Name');
        
        $this->assertIsString($slug);
        $this->assertStringContainsString('test-feature-name', $slug);
    }

    #[Test]
    public function generate_slug_handles_duplicate_names(): void
    {
        SubscriptionPlanFeature::factory()->create(['slug' => 'test-feature']);
        
        $feature = new SubscriptionPlanFeature();
        $slug = $feature->generateSlug('Test Feature');
        
        $this->assertIsString($slug);
        $this->assertStringStartsWith('test-feature', $slug);
        $this->assertNotEquals('test-feature', $slug);
    }

    #[Test]
    public function feature_can_be_soft_deleted(): void
    {
        $feature = SubscriptionPlanFeature::factory()->create();
        $featureId = $feature->id;
        
        $feature->delete();
        
        $this->assertSoftDeleted('subscription_plan_features', ['id' => $featureId]);
    }

    #[Test]
    public function features_without_explicit_ordering_follow_creation_order(): void
    {
        $first = SubscriptionPlanFeature::factory()->create(['sort_order' => 5, 'name' => 'Last by sort_order']);
        $second = SubscriptionPlanFeature::factory()->create(['sort_order' => 1, 'name' => 'First by sort_order']);
        $third = SubscriptionPlanFeature::factory()->create(['sort_order' => 3, 'name' => 'Middle by sort_order']);
        
        // Without explicit ordering, features follow creation/ID order
        $features = SubscriptionPlanFeature::all();
        
        $this->assertEquals('Last by sort_order', $features[0]->name);
        $this->assertEquals('First by sort_order', $features[1]->name);
        $this->assertEquals('Middle by sort_order', $features[2]->name);
        
        // Verify that ordered scope gives different result
        $orderedFeatures = SubscriptionPlanFeature::ordered()->get();
        $this->assertEquals('First by sort_order', $orderedFeatures[0]->name);
        $this->assertEquals('Middle by sort_order', $orderedFeatures[1]->name);
        $this->assertEquals('Last by sort_order', $orderedFeatures[2]->name);
    }

    #[Test]
    public function feature_has_correct_fillable_fields(): void
    {
        $data = [
            'name' => 'Test Feature',
            'description' => 'Test Description',
            'slug' => 'test-feature',
            'is_active' => true,
            'sort_order' => 1,
        ];
        
        $feature = SubscriptionPlanFeature::create($data);
        
        $this->assertEquals($data['name'], $feature->name);
        $this->assertEquals($data['description'], $feature->description);
        $this->assertEquals($data['slug'], $feature->slug);
        $this->assertTrue($feature->is_active);
        $this->assertEquals($data['sort_order'], $feature->sort_order);
    }

    #[Test]
    public function feature_auto_generates_slug_on_create_if_not_provided(): void
    {
        $feature = SubscriptionPlanFeature::create([
            'name' => 'Auto Slug Feature',
            'is_active' => true,
        ]);
        
        $this->assertNotNull($feature->slug);
        $this->assertStringContainsString('auto-slug-feature', $feature->slug);
    }
}
