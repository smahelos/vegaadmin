<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\SubscriptionPlanFeatureRequest;
use App\Models\SubscriptionPlanFeature;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class SubscriptionPlanFeatureRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    private SubscriptionPlanFeatureRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();

        $this->request = new SubscriptionPlanFeatureRequest;

        // Set up test route
        $route = Route::post(
            '/test-subscription-plan-feature',
            function (SubscriptionPlanFeatureRequest $request) {
                $responseFactory = response();

                return $responseFactory->json(['success' => true]);
            },
        );
        $route->middleware('web');
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
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->postJson('/test-subscription-plan-feature', [
            'name' => 'Test Feature',
            'slug' => 'test-feature',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function validation_fails_when_name_is_missing(): void
    {
        $data = [
            'slug' => 'test-feature',
            'is_active' => true,
            'sort_order' => 1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_name_is_too_long(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // 256 characters
            'slug' => 'test-feature',
            'is_active' => true,
            'sort_order' => 1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_slug_is_missing(): void
    {
        $data = [
            'name' => 'Test Feature',
            'is_active' => true,
            'sort_order' => 1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_slug_is_not_unique(): void
    {
        $subscriptionPlanFeatureFactory = SubscriptionPlanFeature::factory();
        $subscriptionPlanFeatureFactory->create(['slug' => 'existing-feature']);

        $data = [
            'name' => 'Test Feature',
            'slug' => 'existing-feature',
            'is_active' => true,
            'sort_order' => 1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_sort_order_is_negative(): void
    {
        $data = [
            'name' => 'Test Feature',
            'slug' => 'test-feature',
            'is_active' => true,
            'sort_order' => -1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('sort_order', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_is_active_is_not_boolean(): void
    {
        $data = [
            'name' => 'Test Feature',
            'slug' => 'test-feature',
            'is_active' => 'invalid',
            'sort_order' => 1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertArrayHasKey('is_active', $errors->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Feature',
            'description' => 'Test description',
            'slug' => 'test-feature',
            'is_active' => true,
            'sort_order' => 1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $data = [
            'name' => 'Test Feature',
            'slug' => 'test-feature',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_allows_null_description(): void
    {
        $data = [
            'name' => 'Test Feature',
            'slug' => 'test-feature',
            'description' => null,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_allows_boolean_values_for_is_active(): void
    {
        $validValues = [
            true,
            false,
            1,
            0,
            '1',
            '0',
        ];

        foreach ($validValues as $value) {
            $data = [
                'name' => 'Test Feature ' . uniqid(),
                'slug' => 'test-feature-' . uniqid(),
                'is_active' => $value,
            ];

            $validator = Validator::make($data, $this->request->rules());

            $this->assertFalse($validator->fails(), "Value '{$value}' should be valid for is_active");
        }
    }
}
