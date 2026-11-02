<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\SubscriptionRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class SubscriptionRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    private SubscriptionRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();

        $this->request = new SubscriptionRequest;
    }

    #[Test]
    public function authorize_returns_false_without_authentication(): void
    {
        $this->assertFalse($this->request->authorize());
    }

    #[Test]
    public function authorize_returns_false_without_permission(): void
    {
        $this->actingAs($this->regularUser, 'backpack');

        $this->assertFalse($this->request->authorize());
    }

    #[Test]
    public function authorize_returns_true_with_permission(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function validation_fails_when_subscription_plan_id_is_missing(): void
    {
        $data = [
            'user_id' => 1,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('subscription_plan_id', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_subscription_plan_id_does_not_exist(): void
    {
        $data = [
            'subscription_plan_id' => 99999,
            'user_id' => 1,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('subscription_plan_id', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_user_id_is_missing(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('user_id', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_user_id_does_not_exist(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => 99999,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('user_id', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_status_is_invalid(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => $user->id,
            'status' => 'invalid_status',
            'amount' => 99.99,
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('status', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_amount_is_missing(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => $user->id,
            'status' => 'active',
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('amount', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_starts_at_is_invalid_date(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => $user->id,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
            'starts_at' => 'invalid-date',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('starts_at', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_ends_at_is_invalid_date(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => $user->id,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => 'invalid-date',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('ends_at', $errors->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => $user->id,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'user_id' => $user->id,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'CZK',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_passes_with_all_valid_status_values(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $subscriptionPlan = $subscriptionPlanFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $validStatuses = [
            'pending',
            'active',
            'cancelled',
            'expired',
            'past_due',
        ];

        foreach ($validStatuses as $status) {
            $data = [
                'subscription_plan_id' => $subscriptionPlan->id,
                'user_id' => $user->id,
                'status' => $status,
                'amount' => 99.99,
                'currency' => 'CZK',
            ];

            $validator = Validator::make($data, $this->request->rules());

            $this->assertFalse($validator->fails(), "Status '$status' should be valid");
        }
    }
}
