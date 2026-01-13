<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\DatabaseMaintenanceLogRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for DatabaseMaintenanceLogRequest class.
 * Tests authorization logic for read-only model.
 */
class DatabaseMaintenanceLogRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    private User $user;

    protected User $adminUser;
    protected User $regularUser;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();

        // Define test routes
        Route::get('/test-database-maintenance-log', function (DatabaseMaintenanceLogRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function validation_passes_with_empty_data(): void
    {
        // Since this is a read-only model, validation should always pass
        $request = new DatabaseMaintenanceLogRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_any_data(): void
    {
        // Since this is a read-only model, validation should always pass
        $anyData = [
            'random_field' => 'random_value',
            'another_field' => 123,
        ];

        $request = new DatabaseMaintenanceLogRequest();
        $validator = Validator::make($anyData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $this->actingAs($this->adminUser, 'backpack')
             ->getJson('/test-database-maintenance-log')
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->getJson('/test-database-maintenance-log')
             ->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_empty_array(): void
    {
        $request = new DatabaseMaintenanceLogRequest();
        $attributes = $request->attributes();

        $this->assertIsArray($attributes);
        $this->assertEmpty($attributes);
    }

    #[Test]
    public function messages_method_returns_empty_array(): void
    {
        $request = new DatabaseMaintenanceLogRequest();
        $messages = $request->messages();

        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    #[Test]
    public function rules_method_returns_empty_array(): void
    {
        $request = new DatabaseMaintenanceLogRequest();
        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }
}
