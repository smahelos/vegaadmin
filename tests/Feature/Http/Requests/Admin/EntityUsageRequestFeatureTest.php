<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\EntityUsageRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;
use App\Models\EntityLimit;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class EntityUsageRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        // Add route for testing validation
        Route::post('/admin/entity-usage-test', function (EntityUsageRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function authorization_requires_can_configure_system(): void
    {
        $payload = $this->validPayload();

        // regular user lacks can_configure_system
        $this->withoutMiddleware();
        $this->actingAs($this->regularUser, 'backpack');
        $this->postJson('/admin/entity-usage-test', $payload)->assertStatus(403);

        // admin has permission
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $this->postJson('/admin/entity-usage-test', $payload)->assertStatus(200)->assertJson(['success' => true]);
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $this->postJson('/admin/entity-usage-test', [])->assertStatus(422)
            ->assertJsonValidationErrors([
                'entity_type','metric_type','period_type','period_start','period_end','current_value'
            ]);
    }

    #[Test]
    public function validation_fails_with_invalid_enums(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $payload = $this->validPayload([
            'entity_type' => 'invalid',
            'metric_type' => 'wrong',
            'period_type' => 'bad',
        ]);
        $this->postJson('/admin/entity-usage-test', $payload)->assertStatus(422)
            ->assertJsonValidationErrors(['entity_type','metric_type','period_type']);
    }

    #[Test]
    public function validation_fails_when_period_end_before_start(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $payload = $this->validPayload([
            // Make start date later than end date to trigger after_or_equal failure
            'period_start' => now()->toDateString(),
            'period_end' => now()->subDay()->toDateString(),
        ]);
        $this->postJson('/admin/entity-usage-test', $payload)->assertStatus(422)
            ->assertJsonValidationErrors(['period_end']);
    }

    #[Test]
    public function validation_fails_with_negative_current_value(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $payload = $this->validPayload([
            'current_value' => -5,
        ]);
        $this->postJson('/admin/entity-usage-test', $payload)->assertStatus(422)
            ->assertJsonValidationErrors(['current_value']);
    }

    #[Test]
    public function validation_passes_with_valid_payload(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->adminUser, 'backpack');
        $this->postJson('/admin/entity-usage-test', $this->validPayload())
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    private function validPayload(array $overrides = []): array
    {
        $defaults = [
            'user_id' => null,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'current_value' => 0,
        ];
        return array_merge($defaults, $overrides);
    }
}
