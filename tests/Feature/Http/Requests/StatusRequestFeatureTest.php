<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\StatusRequest;
use App\Http\Requests\Admin\StatusRequest as AdminStatusRequest;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class StatusRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesFrontendTestEnvironment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up frontend test environment with roles and permissions
        $this->setUpFrontendTestEnvironment();

        $permission = Permission::where('name', 'frontend.can_create_edit_status')
            ->where('guard_name', 'web')
            ->first();

        $this->user->givePermissionTo($permission);
    }

    #[Test]
    public function frontend_status_request_validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Status Name',
            'slug' => 'valid-status-slug',
            'color' => 'bg-green-100 text-green-800',
            'description' => 'This is a valid description',
            'is_active' => true,
        ];

        $request = StatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Validation should pass with valid data');
    }

    #[Test]
    public function frontend_status_request_validation_fails_with_invalid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Required field empty
            'slug' => '', // Required field empty
            'color' => str_repeat('a', 256), // Too long
            'is_active' => 'not-boolean', // Invalid boolean
        ];

        $request = StatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Validation should fail with invalid data');

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('slug'));
        $this->assertTrue($errors->has('color'));
        $this->assertTrue($errors->has('is_active'));
    }

    #[Test]
    public function admin_status_request_validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user);

        $category = StatusCategory::factory()->create();

        $data = [
            'name' => 'Valid Admin Status',
            'slug' => 'valid-admin-status',
            'category_id' => $category->id,
            'color' => 'bg-blue-100 text-blue-800',
            'description' => 'This is a valid admin description',
            'is_active' => true,
        ];

        $request = AdminStatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with valid data');
    }

    #[Test]
    public function admin_status_request_validation_fails_without_category_id(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'slug' => 'valid-slug',
            'color' => 'bg-blue-100 text-blue-800',
            'description' => 'Valid description',
            'is_active' => true,
            // Missing category_id
        ];

        $request = AdminStatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail without category_id');
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    #[Test]
    public function admin_status_request_validation_fails_with_non_existent_category_id(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'slug' => 'valid-slug',
            'category_id' => 999999, // Non-existent category
            'color' => 'bg-blue-100 text-blue-800',
            'description' => 'Valid description',
            'is_active' => true,
        ];

        $request = AdminStatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with non-existent category_id');
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    #[Test]
    public function slug_uniqueness_validation(): void
    {
        Status::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($this->user);

        $data = [
            'name' => 'New Status',
            'slug' => 'existing-slug', // Duplicate slug
            'color' => 'bg-green-100 text-green-800',
            'is_active' => true,
        ];

        $request = StatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Validation should fail with duplicate slug');
        $this->assertTrue($validator->errors()->has('slug'));
    }

    #[Test]
    public function slug_uniqueness_ignores_current_record_on_update(): void
    {
        $status = Status::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($this->user);

        $data = [
            'name' => 'Updated Name',
            'slug' => 'existing-slug', // Same slug as current record
            'color' => 'bg-green-100 text-green-800',
            'is_active' => true,
        ];

        $request = StatusRequest::create('/', 'PUT', $data);
        $request->merge(['id' => $status->id]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Validation should pass when updating with same slug');
    }

    #[Test]
    public function validation_passes_with_optional_fields_null(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Minimal Status',
            'slug' => 'minimal-status',
            'color' => null,
            'description' => null,
            'is_active' => true,
        ];

        $request = StatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Validation should pass with null optional fields');
    }

    #[Test]
    public function validation_with_boolean_variations_for_is_active(): void
    {
        $this->actingAs($this->user);

        $validBooleanValues = [true, false, 1, 0, '1', '0'];

        foreach ($validBooleanValues as $value) {
            $data = [
                'name' => 'Test Status',
                'slug' => 'test-status-' . (is_bool($value) ? ($value ? 'true' : 'false') : $value),
                'is_active' => $value,
            ];

            $request = StatusRequest::create('/', 'POST', $data);
            $request->setContainer($this->app);
            $request->setRedirector($this->app['redirect']);

            $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

            $this->assertFalse($validator->fails(), "Validation should pass with boolean value: " . var_export($value, true));
        }
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_for_is_active(): void
    {
        $this->actingAs($this->user);

        $invalidBooleanValues = ['yes', 'no', 'true', 'false', 2, -1, 'active', 'inactive'];

        foreach ($invalidBooleanValues as $value) {
            $data = [
                'name' => 'Test Status',
                'slug' => 'test-status-' . $value,
                'is_active' => $value,
            ];

            $request = StatusRequest::create('/', 'POST', $data);
            $request->setContainer($this->app);
            $request->setRedirector($this->app['redirect']);

            $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

            $this->assertTrue($validator->fails(), "Validation should fail with invalid boolean value: " . var_export($value, true));
            $this->assertTrue($validator->errors()->has('is_active'));
        }
    }

    #[Test]
    public function validation_error_messages_use_translations(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Required field empty
            'slug' => '', // Required field empty
        ];

        $request = StatusRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails());

        $errors = $validator->errors();

        $nameError = $errors->first('name');
        $slugError = $errors->first('slug');

        $this->assertEquals(__('statuses.validation.name_required'), $nameError);
        $this->assertEquals(__('statuses.validation.slug_required'), $slugError);
    }

    #[Test]
    public function field_attributes_use_translations(): void
    {
        $request = new StatusRequest();
        $attributes = $request->attributes();

        $this->assertEquals(__('statuses.fields.name'), $attributes['name']);
        $this->assertEquals(__('statuses.fields.slug'), $attributes['slug']);
        $this->assertEquals(__('statuses.fields.color'), $attributes['color']);
        $this->assertEquals(__('statuses.fields.description'), $attributes['description']);
        $this->assertEquals(__('statuses.fields.is_active'), $attributes['is_active']);
    }

    #[Test]
    public function admin_request_includes_category_in_attributes(): void
    {
        $adminRequest = new AdminStatusRequest();
        $adminAttributes = $adminRequest->attributes();

        $frontendRequest = new StatusRequest();
        $frontendAttributes = $frontendRequest->attributes();

        $this->assertArrayHasKey('category_id', $adminAttributes);
        $this->assertEquals(__('statuses.fields.category'), $adminAttributes['category_id']);

        $this->assertArrayNotHasKey('category_id', $frontendAttributes);
    }

    #[Test]
    public function frontend_slug_is_auto_generated_when_missing(): void
    {
        $this->actingAs($this->user);

        Route::post('/frontend-status-slug', function (StatusRequest $request) {
            return response()->json(['slug' => $request->slug]);
        })->middleware('web');

        $data = [
            'name' => 'Frontend Fancy Status',
            // slug omitted
        ];

        $response = $this->postJson('/frontend-status-slug', $data);
        $response->assertStatus(200);
        $this->assertEquals(Str::slug($data['name']), $response->json('slug'));
    }
}
