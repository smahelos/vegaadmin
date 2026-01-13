<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\EntityLimitRequest;
use App\Models\EntityLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class EntityLimitRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up permissions, roles, users
        $this->setUpAdminTestEnvironment();

        // Define a temporary route using the request for testing (bypassing Backpack CRUD stack)
        Route::post('/admin/entity-limit-test', function (EntityLimitRequest $request) {
                return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function authorization_requires_can_configure_system_permission(): void
    {
        $payload = [
            'permission_name' => 'test_limit_' . uniqid(),
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 5,
        ];

        // Unauthorized
        $this->withoutMiddleware();
        $this->actingAs($this->regularUser, 'backpack'); // regular user lacks can_configure_system
        $response = $this->postJson('/admin/entity-limit-test', $payload);
        $response->assertStatus(403);

        // Authorized
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/entity-limit-test', $payload);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->postJson('/admin/entity-limit-test', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'permission_name', 'entity_type', 'metric_type', 'period_type', 'limit_value'
        ]);
    }

    #[Test]
    public function validation_fails_with_invalid_entity_type(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->postJson('/admin/entity-limit-test', [
            'permission_name' => 'test_limit_' . uniqid(),
            'entity_type' => 'invalid_entity',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entity_type']);
    }

    #[Test]
    public function validation_fails_on_duplicate_permission_name(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');

        $existingName = 'dup_limit_' . uniqid();
        EntityLimit::factory()->create([
            'permission_name' => $existingName,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 1,
            'is_active' => true,
        ]);

        $response = $this->postJson('/admin/entity-limit-test', [
            'permission_name' => $existingName,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 2,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['permission_name']);
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');

        $payload = [
            'permission_name' => 'ok_limit_' . uniqid(),
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 25,
            'description' => 'Test description',
            'is_active' => true,
        ];

        $response = $this->postJson('/admin/entity-limit-test', $payload);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }
}
