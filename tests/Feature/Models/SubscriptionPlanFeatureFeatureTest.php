<?php

namespace Tests\Feature\Models;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanFeatureFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The scopes below read the whole table; start without the seeded plan features
        SubscriptionPlanFeature::query()->delete();
    }

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

        $feature = new SubscriptionPlanFeature;
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
