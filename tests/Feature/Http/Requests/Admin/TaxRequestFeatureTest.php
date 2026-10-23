<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Http\Requests\Admin\TaxRequest;
use App\Models\EntityLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for TaxRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class TaxRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment, RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    private UniversalLimitService $limitService;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();

        $this->limitService = app(UniversalLimitService::class);

        // Provide generous default limit for taxes
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_tax',
            'entity_type' => 'tax',
            'limit_value' => 100,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        // Define test routes
        Route::post('/test-tax', function (TaxRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');

        Route::put('/test-tax/{id}', function (TaxRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    /**
     * Test successful validation with valid data.
     */
    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $request = new TaxRequest;

        $validData = [
            'name' => 'VAT 21%',
            'rate' => 21.0,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation fails when required fields are missing.
     */
    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new TaxRequest;

        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('rate', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when name exceeds maximum length.
     */
    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $request = new TaxRequest;

        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
            'rate' => 21.0,
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when rate is not numeric.
     */
    #[Test]
    public function validation_fails_when_rate_not_numeric(): void
    {
        $request = new TaxRequest;

        $invalidData = [
            'name' => 'VAT',
            'rate' => 'not-a-number',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rate', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when rate is negative.
     */
    #[Test]
    public function validation_fails_when_rate_negative(): void
    {
        $request = new TaxRequest;

        $invalidData = [
            'name' => 'VAT',
            'rate' => -5.0,
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rate', $validator->errors()->toArray());
    }

    /**
     * Test validation accepts zero rate.
     */
    #[Test]
    public function validation_accepts_zero_rate(): void
    {
        $request = new TaxRequest;

        $validData = [
            'name' => 'Tax Free',
            'rate' => 0,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation accepts decimal rates.
     */
    #[Test]
    public function validation_accepts_decimal_rates(): void
    {
        $request = new TaxRequest;

        $validData = [
            'name' => 'Reduced VAT',
            'rate' => 10.5,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation accepts integer rates.
     */
    #[Test]
    public function validation_accepts_integer_rates(): void
    {
        $request = new TaxRequest;

        $validData = [
            'name' => 'Standard VAT',
            'rate' => 21,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test authorization passes when user is authenticated.
     */
    #[Test]
    public function authorization_passes_when_authenticated(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $this->postJson('/test-tax', [
            'name' => 'VAT 21%',
            'rate' => 21,
        ])->assertStatus(200);
    }

    /**
     * Test authorization fails when user is not authenticated.
     */
    #[Test]
    public function authorization_fails_when_not_authenticated(): void
    {
        $this->postJson('/test-tax', [
            'name' => 'VAT 21%',
            'rate' => 21,
        ])->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_authenticated_user_without_permission(): void
    {
        $userNoPerm = User::factory()->create();
        $this->actingAs($userNoPerm, 'backpack');

        $this->postJson('/test-tax', [
            'name' => 'No Perm Tax',
            'rate' => 5,
        ])->assertStatus(403);
    }

    #[Test]
    public function tax_creation_respects_global_entity_limits(): void
    {
        $this->revokeUnlimitedAccess('tax');
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'tax')->update(['limit_value' => 1]);

        $this->postJson('/test-tax', [
            'name' => 'Tax One',
            'rate' => 5,
        ])->assertStatus(200);

        $this->limitService->recordUsage(
            $this->adminUser->id,
            'tax',
            'count',
            'monthly',
            'backpack',
        );

        $this->expectException(EntityLimitExceededException::class);
        $request = new class extends TaxRequest
        {
            public function rules(): array
            {
                return [];
            }
        };
        $request->replace(['name' => 'Tax Two', 'rate' => 10]);
        $request->setRouteResolver(fn () => (object) ['parameter' => fn ($n) => null]);
        $request->setMethod('POST');
        $request->authorize();
    }

    #[Test]
    public function admin_creation_bypasses_entity_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'tax')->update(['limit_value' => 0]);

        $request = new class extends TaxRequest
        {
            public function rules(): array
            {
                return [];
            }
        };
        $request->replace(['name' => 'Tax', 'rate' => 10]);
        $request->setRouteResolver(fn () => (object) ['parameter' => fn ($n) => null]);
        $request->setMethod('POST');
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function tax_update_bypasses_limit_checks(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'tax')->update(['limit_value' => 0]);
        $this->limitService->recordUsage(
            $this->adminUser->id,
            'tax',
            'count',
            'monthly',
            'backpack',
        );

        $request = new class extends TaxRequest
        {
            public function rules(): array
            {
                return [];
            }
        };
        $request->replace(['name' => 'Updated Tax', 'rate' => 15]);
        $request->setRouteResolver(fn () => (object) ['parameter' => fn ($n) => 'existing-tax']);
        $request->setMethod('PUT');
        $this->assertTrue($request->authorize());
    }

    /**
     * Test custom attributes are correctly defined.
     */
    #[Test]
    public function custom_attributes_are_defined(): void
    {
        $request = new TaxRequest;
        $attributes = $request->attributes();

        $expectedAttributes = [
            'name' => __('taxes.fields.name'),
            'rate' => __('taxes.fields.rate'),
        ];

        $this->assertEquals($expectedAttributes, $attributes);
    }

    /**
     * Test validation with edge case values.
     */
    #[Test]
    public function validation_with_edge_case_values(): void
    {
        $request = new TaxRequest;

        // Test with very high rate
        $validData = [
            'name' => 'High Tax',
            'rate' => 100.0,
        ];

        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        // Test with very precise decimal
        $validData = [
            'name' => 'Precise Tax',
            'rate' => 15.123456,
        ];

        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * Test name field accepts various valid string formats.
     */
    #[Test]
    public function name_accepts_various_valid_formats(): void
    {
        $request = new TaxRequest;

        $validNames = [
            'VAT',
            'Value Added Tax',
            'Tax-with-dashes',
            'Tax_with_underscores',
            'Tax (with parentheses)',
            'Tax 21%',
            'Tax & Co.',
        ];

        foreach ($validNames as $name) {
            $validData = [
                'name' => $name,
                'rate' => 20.0,
            ];

            $validator = Validator::make($validData, $request->rules());
            $this->assertTrue($validator->passes(), "Failed for name: {$name}");
        }
    }

    #[Test]
    public function required_errors_name_the_field_label(): void
    {
        $request = new TaxRequest;

        $validator = Validator::make(
            ['name' => '', 'rate' => ''],
            $request->rules(),
            $request->messages(),
            $request->attributes(),
        );

        $this->assertSame(__('validation.required', ['attribute' => __('taxes.fields.name')]), $validator->errors()->first('name'));
        $this->assertSame(__('validation.required', ['attribute' => __('taxes.fields.rate')]), $validator->errors()->first('rate'));
    }
}
