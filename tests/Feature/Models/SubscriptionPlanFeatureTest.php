<?php

namespace Tests\Feature\Models;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function subscription_plan_can_be_created_with_valid_data(): void
    {
        $planData = [
            'name' => 'Professional Plan',
            'description' => 'Professional features for growing businesses',
            'price' => 29.99,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'is_active' => true,
            'trial_days' => 14,
        ];

        $plan = SubscriptionPlan::create($planData);

        $this->assertInstanceOf(SubscriptionPlan::class, $plan);
        $this->assertEquals('Professional Plan', $plan->name);
        $this->assertEquals(29.99, $plan->price);
        $this->assertEquals('EUR', $plan->currency);
        $this->assertTrue($plan->is_active);
    }

    #[Test]
    public function subscription_plan_has_subscriptions_relationship(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $plan->subscriptions());
    }

    #[Test]
    public function subscription_plan_has_features_relationship(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $plan->features());
    }

    #[Test]
    public function subscription_plan_can_attach_and_detach_features(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $feature1 = \App\Models\SubscriptionPlanFeature::factory()->create(['name' => 'Feature 1']);
        $feature2 = \App\Models\SubscriptionPlanFeature::factory()->create(['name' => 'Feature 2']);

        // Attach features to plan
        $plan->features()->attach([$feature1->id, $feature2->id]);

        $this->assertEquals(2, $plan->features()->count());
        $this->assertTrue($plan->features->contains($feature1));
        $this->assertTrue($plan->features->contains($feature2));

        // Detach one feature
        $plan->features()->detach($feature1->id);

        $plan->refresh();
        $this->assertEquals(1, $plan->features()->count());
        $this->assertFalse($plan->features->contains($feature1));
        $this->assertTrue($plan->features->contains($feature2));
    }

    #[Test]
    public function subscription_plan_active_scope_returns_only_active_plans(): void
    {
        SubscriptionPlan::factory()->create(['is_active' => true]);
        SubscriptionPlan::factory()->create(['is_active' => false]);

        $activePlans = SubscriptionPlan::active()->get();

        $this->assertCount(1, $activePlans);
        $this->assertTrue($activePlans->first()->is_active);
    }

    #[Test]
    public function subscription_plan_has_trial_period_returns_correct_boolean(): void
    {
        $planWithTrial = SubscriptionPlan::factory()->create(['trial_days' => 14]);
        $planWithoutTrial = SubscriptionPlan::factory()->withoutTrial()->create();

        $this->assertTrue($planWithTrial->hasTrialPeriod());
        $this->assertFalse($planWithoutTrial->hasTrialPeriod());
    }

    #[Test]
    public function subscription_plan_formatted_price_includes_currency(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price' => 29.99,
            'currency' => 'EUR'
        ]);

        $formattedPrice = $plan->getFormattedPriceAttribute();

        $this->assertIsString($formattedPrice);
        $this->assertStringContainsString('29.99', $formattedPrice);
        $this->assertStringContainsString('EUR', $formattedPrice);
    }

    #[Test]
    public function subscription_plan_casts_attributes_correctly(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price' => '29.99',
            'is_active' => '1',
            'trial_days' => '14',
            'billing_interval' => '1',
        ]);

        // Test that Laravel's casts work correctly
        $this->assertIsString($plan->price); // decimal cast returns string
        $this->assertEquals('29.99', $plan->price);
        $this->assertIsBool($plan->is_active); // boolean cast
        $this->assertTrue($plan->is_active);
        $this->assertIsInt($plan->trial_days); // integer cast
        $this->assertEquals(14, $plan->trial_days);
        $this->assertIsInt($plan->billing_interval); // integer cast
        $this->assertEquals(1, $plan->billing_interval);
    }
}
