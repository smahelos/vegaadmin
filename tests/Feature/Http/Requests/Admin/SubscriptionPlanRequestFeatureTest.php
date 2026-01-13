<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class SubscriptionPlanRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected User $adminUser;
    protected User $regularUser;

    private SubscriptionPlanRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();
        
        $this->request = new SubscriptionPlanRequest();
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
    public function validation_fails_when_name_is_missing(): void
    {
        $data = [
            'price' => 99.99,
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_price_is_missing(): void
    {
        $data = [
            'name' => 'Test Plan',
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_price_is_negative(): void
    {
        $data = [
            'name' => 'Test Plan',
            'price' => -10.00,
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_currency_is_invalid(): void
    {
        $data = [
            'name' => 'Test Plan',
            'price' => 99.99,
            'currency' => 'INVALID',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_billing_period_is_invalid(): void
    {
        $data = [
            'name' => 'Test Plan',
            'price' => 99.99,
            'currency' => 'CZK',
            'billing_period' => 'weekly',
            'billing_interval' => 1,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('billing_period', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Plan',
            'description' => 'Test description',
            'price' => 99.99,
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'features' => [], // Empty array for features
            'is_active' => true,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_passes_with_valid_features(): void
    {
        // Create some test features first
        $feature1 = \App\Models\SubscriptionPlanFeature::factory()->create();
        $feature2 = \App\Models\SubscriptionPlanFeature::factory()->create();
        
        $data = [
            'name' => 'Test Plan',
            'description' => 'Test description',
            'price' => 99.99,
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'features' => [$feature1->id, $feature2->id],
            'is_active' => true,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_fails_with_invalid_feature_ids(): void
    {
        $data = [
            'name' => 'Test Plan',
            'description' => 'Test description',
            'price' => 99.99,
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'features' => [999, 1000], // Non-existent feature IDs
            'is_active' => true,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('features.0', $validator->errors()->toArray());
        $this->assertArrayHasKey('features.1', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_non_array_features(): void
    {
        $data = [
            'name' => 'Test Plan',
            'description' => 'Test description',
            'price' => 99.99,
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'features' => 'not an array',
            'is_active' => true,
            'trial_days' => 14,
        ];

        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('features', $validator->errors()->toArray());
    }

    #[Test]
    public function attributes_returns_translation_keys(): void
    {
        $attributes = $this->request->attributes();
        
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('price', $attributes);
        $this->assertArrayHasKey('currency', $attributes);
        // Test returns actual translated values, not translation keys
        $this->assertIsString($attributes['name']);
        $this->assertNotEmpty($attributes['name']);
    }

    #[Test]
    public function messages_returns_custom_error_messages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('price.required', $messages);
        $this->assertArrayHasKey('price.numeric', $messages);
        // Test returns actual translated values, not translation keys
        $this->assertIsString($messages['name.required']);
        $this->assertNotEmpty($messages['name.required']);
    }
}
