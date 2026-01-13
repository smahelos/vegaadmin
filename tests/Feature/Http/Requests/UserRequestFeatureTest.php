<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\UserRequest;
use App\Http\Requests\Admin\UserRequest as AdminUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Get roles and permissions created by TestingDatabaseSeeder
        // $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $frontendUserRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $permission = Permission::firstOrCreate([
            'name' => 'frontend.can_create_edit_user',
            'guard_name' => 'web'
        ]);

        // Create admin user with admin role (has all permissions)
        // $this->adminUser = User::factory()->create();
        // $this->adminUser->assignRole($adminRole);
        // $this->adminUser->givePermissionTo($permission);

        // Create regular user with limited role
        $this->user = User::factory()->create();
        $this->user->assignRole($frontendUserRole);
    $this->user->givePermissionTo($permission);
    }

    #[Test]
    public function frontend_user_request_validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid User Name',
            'email' => 'valid_' . uniqid() . '@example.com',
            'street' => 'Street 1',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = UserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Frontend validation should pass with valid data');
    }

    #[Test]
    public function frontend_user_request_validation_fails_with_invalid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Required
            'email' => 'invalid-email', // Invalid format
            'street' => '',
            'city' => '',
            'zip' => '',
            'country' => '',
            'password' => 'short',
            'password_confirmation' => 'different',
        ];

        $request = UserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Frontend validation should fail with invalid data');

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('email'));
    }

    #[Test]
    public function frontend_user_request_authorization_requires_authenticated_user(): void
    {
        $request = new UserRequest();

        // Without authentication, should return false
    $this->assertFalse($request->authorize());
    }

    #[Test]
    public function frontend_user_request_authorization_passes_with_authenticated_user(): void
    {
        $this->actingAs($this->user);
        // Ensure permission still present
        if (!$this->user->can('frontend.can_create_edit_user')) {
            $perm = \Spatie\Permission\Models\Permission::where('name','frontend.can_create_edit_user')->where('guard_name','web')->first();
            $this->user->givePermissionTo($perm);
        }
        $request = new UserRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function frontend_user_request_update_without_password_passes(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Updated User',
            'email' => 'updated_' . uniqid() . '@example.com',
            'street' => 'Street 2',
            'city' => 'Brno',
            'zip' => '60200',
            'country' => 'Czech Republic',
        ];

        $request = UserRequest::create('/', 'PUT', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());
        $this->assertFalse($validator->fails(), 'Update without password should pass');
    }

    #[Test]
    public function admin_user_request_validation_passes_with_valid_create_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Admin User',
            'email' => 'admin@example.com',
            'street' => 'Valid Street 123',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with valid create data');
    }

    #[Test]
    public function admin_user_request_validation_passes_with_valid_update_data(): void
    {
        $existingUser = User::factory()->create();
        $this->actingAs($this->user);

        $data = [
            'name' => 'Updated Admin User',
            'email' => 'updated@example.com',
            'street' => 'Updated Street 456',
            'city' => 'Updated City',
            'zip' => '67890',
            'country' => 'Updated Country',
            // No password for update
        ];

        $request = AdminUserRequest::create('/', 'PUT', $data);
        $request->merge(['id' => $existingUser->id]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with valid update data');
    }

    #[Test]
    public function admin_user_request_validation_fails_without_required_fields(): void
    {
        $this->actingAs($this->user);

        $data = [
            // Missing required fields
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail without required fields');

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('street'));
        $this->assertTrue($errors->has('city'));
        $this->assertTrue($errors->has('zip'));
        $this->assertTrue($errors->has('country'));
        $this->assertTrue($errors->has('password'));
    }

    #[Test]
    public function admin_user_request_validation_fails_with_too_long_fields(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => str_repeat('a', 256), // Too long
            'email' => 'valid@example.com',
            'street' => str_repeat('b', 256), // Too long
            'city' => str_repeat('c', 256), // Too long
            'zip' => str_repeat('d', 21), // Too long
            'country' => str_repeat('e', 101), // Too long
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with too long fields');

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('street'));
        $this->assertTrue($errors->has('city'));
        $this->assertTrue($errors->has('zip'));
        $this->assertTrue($errors->has('country'));
    }

    #[Test]
    public function admin_user_request_validation_fails_with_invalid_email(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'email' => 'invalid-email-format',
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with invalid email');
        $this->assertTrue($validator->errors()->has('email'));
    }

    #[Test]
    public function admin_user_request_validation_fails_with_duplicate_email(): void
    {
        $this->actingAs($this->user);

        // Create existing user with the email we'll try to duplicate
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'name' => 'Valid Name',
            'email' => 'existing@example.com', // Duplicate email
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with duplicate email');
        $this->assertTrue($validator->errors()->has('email'));
    }

    #[Test]
    public function admin_user_request_validation_allows_same_email_on_update(): void
    {
        $existingUser = User::factory()->create(['email' => 'same@example.com']);

        $this->actingAs($this->user);

        $data = [
            'name' => 'Updated Name',
            'email' => 'same@example.com', // Same email should be allowed on update
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
        ];

        $request = AdminUserRequest::create('/', 'PUT', $data);
        $request->merge(['id' => $existingUser->id]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with same email on update');
    }

    #[Test]
    public function admin_user_request_validation_fails_with_short_password(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'email' => 'valid@example.com',
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'password' => '123', // Too short
            'password_confirmation' => '123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with short password');
        $this->assertTrue($validator->errors()->has('password'));
    }

    #[Test]
    public function admin_user_request_validation_fails_with_unconfirmed_password(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'email' => 'valid@example.com',
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'password' => 'password123',
            'password_confirmation' => 'different123', // Different confirmation
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with unconfirmed password');
        $this->assertTrue($validator->errors()->has('password'));
    }

    #[Test]
    public function admin_user_request_validation_passes_with_nullable_fields(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'email' => 'valid@example.com',
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'phone' => null,
            'ico' => null,
            'dic' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with nullable fields as null');
    }

    #[Test]
    public function admin_user_request_validation_fails_with_invalid_phone(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'email' => 'valid@example.com',
            'street' => 'Valid Street',
            'city' => 'Valid City',
            'zip' => '12345',
            'country' => 'Valid Country',
            'phone' => 'not-a-number',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with invalid phone');
        $this->assertTrue($validator->errors()->has('phone'));
    }

    #[Test]
    public function validation_error_messages_use_translations(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Required field empty
            'email' => '', // Required field empty
            'password' => '', // Required field empty
        ];

        $request = AdminUserRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails());

        $errors = $validator->errors();

        // Check that custom messages are used
        $nameError = $errors->first('name');
        $emailError = $errors->first('email');
        $passwordError = $errors->first('password');

        $this->assertEquals(__('users.validation.name_required'), $nameError);
        $this->assertEquals(__('users.validation.email_required'), $emailError);
        $this->assertEquals(__('users.validation.password_required'), $passwordError);
    }

    #[Test]
    public function field_attributes_use_translations(): void
    {
        $request = new AdminUserRequest();
        $attributes = $request->attributes();

        // Verify that attributes reference translation keys
        $this->assertEquals(__('users.fields.name'), $attributes['name']);
        $this->assertEquals(__('users.fields.email'), $attributes['email']);
        $this->assertEquals(__('users.fields.password'), $attributes['password']);
        $this->assertEquals(__('users.fields.password_confirmation'), $attributes['password_confirmation']);
    }

    #[Test]
    public function is_create_operation_method_works_correctly(): void
    {
        $this->actingAs($this->user);

        // Test POST request (create)
        $createRequest = AdminUserRequest::create('/', 'POST', []);
        $createRequest->setContainer($this->app);
        $createRequest->setRedirector($this->app['redirect']);

        $this->assertTrue($createRequest->isCreateOperation());

        // Test PUT request (update)
        $updateRequest = AdminUserRequest::create('/', 'PUT', []);
        $updateRequest->setContainer($this->app);
        $updateRequest->setRedirector($this->app['redirect']);

        $this->assertFalse($updateRequest->isCreateOperation());
    }
}
