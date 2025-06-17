<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ClientRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for ClientRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class ClientRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $clientUser;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->clientUser = User::factory()->create();
        
        // Create necessary permissions for testing
        $this->createRequiredPermissions();
        
        // Define test routes
        Route::post('/admin/client', function (ClientRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
        
        Route::put('/admin/client/{id}', function (ClientRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    /**
     * Create required permissions for testing.
     */
    private function createRequiredPermissions(): void
    {
        // Define all permissions required for admin operations and navigation
        $permissions = [
            // User management permissions
            'can_create_edit_user',
            
            // Business operations permissions
            'can_create_edit_invoice',
            'can_create_edit_client',
            'can_create_edit_supplier',
            
            // Financial management permissions
            'can_create_edit_expense',
            'can_create_edit_tax',
            'can_create_edit_bank',
            'can_create_edit_payment_method',
            
            // Inventory management permissions
            'can_create_edit_product',
            
            // System administration permissions
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_edit_status',
            'can_configure_system',
            
            // Basic backpack access
            'backpack.access',
        ];

        // Create all permissions for backpack guard
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission, 
                'guard_name' => 'backpack'
            ]);
        }

        // Give the user all necessary permissions for the backpack guard
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'backpack')
                ->first();
            if ($permission) {
                $this->user->givePermissionTo($permission);
            }
        }
    }

    #[Test]
    public function validation_passes_with_complete_valid_data(): void
    {
        $validData = [
            'name' => 'Acme Corporation',
            'shortcut' => 'ACME',
            'street' => '123 Business Avenue',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
            'email' => 'contact@acme.com',
            'phone' => '+420 123 456 789',
            'description' => 'Technology company specializing in software development',
            'user_id' => $this->clientUser->id,
        ];

        $request = new ClientRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $minimalData = [
            'name' => 'Minimal Client',
            'user_id' => $this->clientUser->id,
            // All other fields are nullable
        ];

        $request = new ClientRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new ClientRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
            'user_id' => $this->clientUser->id,
        ];

        $request = new ClientRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_email(): void
    {
        $invalidData = [
            'name' => 'Valid Client',
            'email' => 'invalid-email',
            'user_id' => $this->clientUser->id,
        ];

        $request = new ClientRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_user_id(): void
    {
        $invalidData = [
            'name' => 'Valid Client',
            'user_id' => 99999, // Non-existent user
        ];

        $request = new ClientRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    /**
     * Test field length validations.
     */
    #[Test]
    public function field_length_validations(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $invalidData = [
            'name' => str_repeat('a', 256), // Max 255
            'shortcut' => str_repeat('a', 51), // Max 50
            'street' => str_repeat('a', 256), // Max 255
            'city' => str_repeat('a', 256), // Max 255
            'zip' => str_repeat('a', 21), // Max 20
            'country' => str_repeat('a', 101), // Max 100
            'ico' => str_repeat('a', 21), // Max 20
            'dic' => str_repeat('a', 31), // Max 30
            'email' => str_repeat('a', 250) . '@test.com', // Max 255 total
            'phone' => str_repeat('a', 21), // Max 20
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name', 'shortcut', 'street', 'city', 'zip', 'country',
            'ico', 'dic', 'phone'
        ]);
    }

    /**
     * Test email accepts valid formats.
     */
    #[Test]
    public function email_accepts_valid_formats(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $validEmails = [
            'simple@example.com',
            'user.name@domain.co.uk',
            'user+tag@domain.org',
            'user123@sub.domain.com',
        ];

        foreach ($validEmails as $email) {
            $validData = [
                'name' => 'Test Client',
                'email' => $email,
                'user_id' => $this->clientUser->id,
            ];

            $response = $this->postJson('/admin/client', $validData);
            $response->assertStatus(200);
        }
    }

    /**
     * Test phone field accepts various formats.
     */
    #[Test]
    public function phone_accepts_various_formats(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $validPhones = [
            '+420123456789',
            '+420 123 456 789',
            '123456789',
            '123-456-789',
            '(123) 456-789',
        ];

        foreach ($validPhones as $phone) {
            $validData = [
                'name' => 'Test Client',
                'phone' => $phone,
                'user_id' => $this->clientUser->id,
            ];

            $response = $this->postJson('/admin/client', $validData);
            $response->assertStatus(200);
        }
    }

    /**
     * Test ico and dic fields accept valid formats.
     */
    #[Test]
    public function ico_dic_accept_valid_formats(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $validData = [
            'name' => 'Czech Client',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $validData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test description field accepts long text.
     */
    #[Test]
    public function description_accepts_long_text(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $longDescription = str_repeat('This is a long description. ', 100);

        $validData = [
            'name' => 'Client with Description',
            'description' => $longDescription,
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $validData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test authorization passes when user is authenticated.
     */
    #[Test]
    public function authorization_passes_when_authenticated(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $validData = [
            'name' => 'Authorized Client',
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $validData);

        $response->assertStatus(200);
    }

    /**
     * Test authorization fails when user is not authenticated.
     */
    #[Test]
    public function authorization_fails_when_not_authenticated(): void
    {
        // Not acting as any user (unauthenticated)
        $validData = [
            'name' => 'Unauthorized Client',
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $validData);

        $response->assertStatus(403);
    }

    /**
     * Test custom attributes are correctly defined.
     */
    #[Test]
    public function custom_attributes_are_defined(): void
    {
        $request = new ClientRequest();
        $attributes = $request->attributes();

        $expectedAttributes = [
            'name' => __('clients.fields.name'),
            'email' => __('clients.fields.email'),
            'user_id' => __('clients.fields.user_id'),
        ];

        $this->assertEquals($expectedAttributes, $attributes);
    }

    /**
     * Test custom messages are correctly defined.
     */
    #[Test]
    public function custom_messages_are_defined(): void
    {
        $request = new ClientRequest();
        $messages = $request->messages();

        $expectedMessages = [
            'name.required' => __('clients.validation.name_required'),
            'user_id.required' => __('clients.validation.user_required'),
            'user_id.exists' => __('clients.validation.user_exists'),
        ];

        $this->assertEquals($expectedMessages, $messages);
    }

    /**
     * Test nullable fields work correctly.
     */
    #[Test]
    public function nullable_fields_work_correctly(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $dataWithNulls = [
            'name' => 'Client with Nulls',
            'user_id' => $this->clientUser->id,
            'shortcut' => null,
            'street' => null,
            'city' => null,
            'zip' => null,
            'country' => null,
            'ico' => null,
            'dic' => null,
            'email' => null,
            'phone' => null,
            'description' => null,
        ];

        $response = $this->postJson('/admin/client', $dataWithNulls);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test edge case values.
     */
    #[Test]
    public function edge_case_values(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        // Test with very short values
        $edgeData = [
            'name' => 'A', // Minimum valid length
            'shortcut' => 'B',
            'street' => 'C',
            'city' => 'D',
            'zip' => '1',
            'country' => 'E',
            'ico' => '1',
            'dic' => '2',
            'email' => 'a@b.co',
            'phone' => '1',
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $edgeData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test maximum valid lengths.
     */
    #[Test]
    public function maximum_valid_lengths(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $maxData = [
            'name' => str_repeat('a', 255), // Max valid length
            'shortcut' => str_repeat('b', 50), // Max valid length
            'street' => str_repeat('c', 255), // Max valid length
            'city' => str_repeat('d', 255), // Max valid length
            'zip' => str_repeat('e', 20), // Max valid length
            'country' => str_repeat('f', 100), // Max valid length
            'ico' => str_repeat('1', 20), // Max valid length
            'dic' => str_repeat('2', 30), // Max valid length
            'email' => str_repeat('a', 243) . '@example.com', // Max valid length (255 total)
            'phone' => str_repeat('1', 20), // Max valid length
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->postJson('/admin/client', $maxData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
