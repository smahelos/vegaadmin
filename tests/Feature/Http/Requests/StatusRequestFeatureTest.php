<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\StatusRequest;
use App\Http\Requests\Admin\StatusRequest as AdminStatusRequest;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function frontend_status_request_validation_passes_with_valid_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $user = User::factory()->create();
        $category = StatusCategory::factory()->create();
        $this->actingAsBackpackUser($user);

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
        $user = User::factory()->create();
        $this->actingAsBackpackUser($user);

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
        $user = User::factory()->create();
        $this->actingAsBackpackUser($user);

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

        $user = User::factory()->create();
        $this->actingAs($user);

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

        $user = User::factory()->create();
        $this->actingAs($user);

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
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $user = User::factory()->create();
        $this->actingAs($user);

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
}
