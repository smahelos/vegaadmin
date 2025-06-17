<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\StatusCategoryRequest;
use App\Http\Requests\Admin\StatusCategoryRequest as AdminStatusCategoryRequest;
use App\Models\User;
use App\Models\StatusCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StatusCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permission if it doesn't exist
        Permission::firstOrCreate(['name' => 'can_create_edit_status']);
    }

    #[Test]
    public function frontend_request_validation_passes_with_valid_data()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $validData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($validData, $request->rules(), [], $request->attributes());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function frontend_request_validation_fails_when_name_is_missing()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $invalidData = [
            'slug' => 'test-category',
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function frontend_request_validation_fails_when_slug_is_missing()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $invalidData = [
            'name' => 'Test Category',
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function frontend_request_validation_fails_when_name_is_too_short()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $invalidData = [
            'name' => 'A', // Too short (min:2)
            'slug' => 'test-category',
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function frontend_request_validation_fails_when_name_is_too_long()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $invalidData = [
            'name' => str_repeat('a', 256), // Too long (max:255)
            'slug' => 'test-category',
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function frontend_request_validation_fails_when_slug_is_too_short()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'a', // Too short (min:2)
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function frontend_request_validation_fails_when_slug_is_not_unique()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        // Create existing category
        StatusCategory::factory()->create(['slug' => 'existing-slug']);

        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'existing-slug', // Duplicate slug
            'description' => 'Test description',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function frontend_request_validation_passes_with_nullable_description()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $validData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => null,
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function frontend_request_authorization_requires_permission()
    {
        // User without permission
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

        $request = new StatusCategoryRequest();
        $this->assertFalse($request->authorize());

        // User with permission
        $userWithPermission = User::factory()->create();
        $userWithPermission->givePermissionTo('can_create_edit_status');
        $this->actingAs($userWithPermission);

        $request = new StatusCategoryRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function frontend_request_authorization_fails_without_authentication()
    {
        // No authenticated user
        $request = new StatusCategoryRequest();
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function admin_request_validation_passes_with_valid_data()
    {
        $validData = [
            'name' => 'Admin Test Category',
            'slug' => 'admin-test-category',
            'description' => 'Admin test description',
        ];

        $request = new AdminStatusCategoryRequest();
        $validator = Validator::make($validData, $request->rules(), [], $request->attributes());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function admin_request_validation_fails_with_invalid_data()
    {
        $invalidData = [
            'name' => '', // Required but empty
            'slug' => '', // Required but empty
        ];

        $request = new AdminStatusCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_allows_slug_update_for_existing_record()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        // Create existing category
        $existingCategory = StatusCategory::factory()->create(['slug' => 'existing-slug']);

        $updateData = [
            'id' => $existingCategory->id,
            'name' => 'Updated Category',
            'slug' => 'existing-slug', // Same slug should be allowed for update
            'description' => 'Updated description',
        ];

        $request = StatusCategoryRequest::create('/', 'PUT', $updateData);
        $request->merge(['id' => $existingCategory->id]);
        
        $validator = Validator::make($updateData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_uses_translated_attributes()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        $request = new StatusCategoryRequest();
        $attributes = $request->attributes();

        $this->assertEquals(trans('admin.status_categories.name'), $attributes['name']);
        $this->assertEquals(trans('admin.status_categories.slug'), $attributes['slug']);
        $this->assertEquals(trans('admin.status_categories.description'), $attributes['description']);
    }

    #[Test]
    public function admin_and_frontend_requests_have_same_validation_rules()
    {
        $frontendRequest = new StatusCategoryRequest();
        $adminRequest = new AdminStatusCategoryRequest();

        $testData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
        ];

        $frontendValidator = Validator::make($testData, $frontendRequest->rules());
        $adminValidator = Validator::make($testData, $adminRequest->rules());

        $this->assertEquals($frontendValidator->passes(), $adminValidator->passes());
    }

    #[Test]
    public function validation_handles_edge_cases()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_status');
        $this->actingAs($user);

        // Test minimum valid length
        $minValidData = [
            'name' => 'AB', // Minimum 2 characters
            'slug' => 'ab', // Minimum 2 characters
            'description' => '',
        ];

        $request = new StatusCategoryRequest();
        $validator = Validator::make($minValidData, $request->rules());

        $this->assertTrue($validator->passes());

        // Test maximum valid length
        $maxValidData = [
            'name' => str_repeat('a', 255), // Maximum 255 characters
            'slug' => str_repeat('b', 255), // Maximum 255 characters
            'description' => str_repeat('c', 1000), // No max limit for description
        ];

        $validator = Validator::make($maxValidData, $request->rules());
        $this->assertTrue($validator->passes());
    }
}
