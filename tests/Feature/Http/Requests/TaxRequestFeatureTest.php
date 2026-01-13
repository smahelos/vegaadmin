<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\TaxRequest;
use App\Http\Requests\Admin\TaxRequest as AdminTaxRequest;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class TaxRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesFrontendTestEnvironment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up frontend test environment with roles and permissions
        $this->setUpFrontendTestEnvironment();

        $permission = Permission::where('name', 'frontend.can_create_edit_tax')
            ->where('guard_name', 'web')
            ->first();

        $this->user->givePermissionTo($permission);
    }

    #[Test]
    public function frontend_tax_request_validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Frontend VAT 21%',
            'rate' => 21,
        ];

        $request = TaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());
        $this->assertFalse($validator->fails(), 'Frontend validation should pass with valid data');
    }

    #[Test]
    public function admin_tax_request_validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Tax Name',
            'rate' => 21.50,
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with valid data');
    }

    #[Test]
    public function admin_tax_request_validation_fails_with_invalid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Required field empty
            'rate' => 'not-a-number', // Invalid numeric
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with invalid data');

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('rate'));
    }

    #[Test]
    public function admin_tax_request_validation_fails_without_required_fields(): void
    {
        $this->actingAs($this->user);

        $data = [
            // Missing required fields
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail without required fields');

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('rate'));
    }

    #[Test]
    public function admin_tax_request_validation_fails_with_too_long_name(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => str_repeat('a', 256), // Too long
            'rate' => 21.00,
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with too long name');
        $this->assertTrue($validator->errors()->has('name'));
    }

    #[Test]
    public function admin_tax_request_validation_fails_with_negative_rate(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Valid Name',
            'rate' => -5.00, // Negative rate
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails(), 'Admin validation should fail with negative rate');
        $this->assertTrue($validator->errors()->has('rate'));
    }

    #[Test]
    public function admin_tax_request_validation_passes_with_zero_rate(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Zero Rate Tax',
            'rate' => 0.00,
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with zero rate');
    }

    #[Test]
    public function admin_tax_request_validation_passes_with_decimal_rate(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Decimal Rate Tax',
            'rate' => 21.55,
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with decimal rate');
    }

    #[Test]
    public function admin_tax_request_validation_passes_with_integer_rate(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Integer Rate Tax',
            'rate' => 21,
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with integer rate');
    }

    #[Test]
    public function admin_tax_request_validation_passes_with_string_numeric_rate(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'String Numeric Rate Tax',
            'rate' => '21.00',
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validator->fails(), 'Admin validation should pass with string numeric rate');
    }

    #[Test]
    public function validation_error_messages_use_translations(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Required field empty
            'rate' => '', // Required field empty
        ];

        $request = AdminTaxRequest::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->fails());

        $errors = $validator->errors();

        // Check that custom messages are used
        $nameError = $errors->first('name');
        $rateError = $errors->first('rate');

        $this->assertEquals(__('tax.name_required'), $nameError);
        $this->assertEquals(__('tax.rate_required'), $rateError);
    }

    #[Test]
    public function field_attributes_use_translations(): void
    {
        $request = new AdminTaxRequest();
        $attributes = $request->attributes();

        // Verify that attributes reference translation keys
        $this->assertEquals(__('tax.name'), $attributes['name']);
        $this->assertEquals(__('tax.rate'), $attributes['rate']);
    }

    #[Test]
    public function frontend_and_admin_requests_share_same_core_rules(): void
    {
        $frontendRequest = new TaxRequest();
        $adminRequest = new AdminTaxRequest();

        $frontendRules = $frontendRequest->rules();
        $adminRules = $adminRequest->rules();

        $this->assertEquals($adminRules['name'], $frontendRules['name']);
        $this->assertEquals($adminRules['rate'], $frontendRules['rate']);
    }

    #[Test]
    public function frontend_request_authorization_requires_authentication(): void
    {
        $unauthenticatedRequest = new TaxRequest();
        $this->assertFalse($unauthenticatedRequest->authorize(), 'Unauthenticated user should not authorize');

        $this->actingAs($this->user); // user has permission assigned in setUp
        $authenticatedRequest = new TaxRequest();
        $this->assertTrue($authenticatedRequest->authorize(), 'Authenticated user with permission should authorize');
    }
}
