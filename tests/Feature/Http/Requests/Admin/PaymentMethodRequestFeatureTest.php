<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Models\EntityLimit;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class PaymentMethodRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        $this->limitService = app(UniversalLimitService::class);

        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_payment_method',
            'entity_type' => 'payment_method',
            'limit_value' => 100,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        Route::post('/test-payment-method', function (PaymentMethodRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');

        Route::put('/test-payment-method/{id}', function (PaymentMethodRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function accepts_a_payment_method_code_that_is_not_stored(): void
    {
        PaymentMethod::where('slug', PaymentMethodCode::OTHER->value)->delete();

        $validator = Validator::make(['name' => 'Other', 'slug' => PaymentMethodCode::OTHER->value], (new PaymentMethodRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function requires_a_payment_method_code(): void
    {
        $validator = Validator::make(['name' => 'Card'], (new PaymentMethodRequest)->rules());

        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function rejects_a_slug_that_is_not_a_payment_method_code(): void
    {
        $validator = Validator::make(['name' => 'Bank Transfer', 'slug' => 'bank-transfer'], (new PaymentMethodRequest)->rules());

        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_missing_name(): void
    {
        $request = new PaymentMethodRequest;
        $validator = Validator::make(['slug' => 'something'], $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function rejects_a_payment_method_code_that_is_already_stored(): void
    {
        $validator = Validator::make(['name' => 'Cash', 'slug' => PaymentMethodCode::CASH->value], (new PaymentMethodRequest)->rules());

        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $request = new PaymentMethodRequest;
        $data = [
            'name' => str_repeat('a', 256),
        ];
        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        PaymentMethod::where('slug', PaymentMethodCode::OTHER->value)->delete();
        $this->actingAs($this->adminUser, 'backpack');
        $this->postJson('/test-payment-method', [
            'name' => 'Fast Pay',
            'slug' => PaymentMethodCode::OTHER->value,
        ])->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->postJson('/test-payment-method', [
            'name' => 'Fail Pay',
        ])->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_user_without_permission(): void
    {
        $userNoPerm = User::factory()->create();
        $this->actingAs($userNoPerm, 'backpack');
        $this->postJson('/test-payment-method', [
            'name' => 'No Perm',
        ])->assertStatus(403);
    }

    #[Test]
    public function payment_method_creation_respects_limits(): void
    {
        $this->revokeUnlimitedAccess('payment_method');
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'payment_method')->update(['limit_value' => 1]);
        PaymentMethod::where('slug', PaymentMethodCode::OTHER->value)->delete();
        $this->postJson('/test-payment-method', [
            'name' => 'First Method',
            'slug' => PaymentMethodCode::OTHER->value,
        ])->assertStatus(200);
        $this->limitService->recordUsage(
            $this->adminUser->id,
            'payment_method',
            'count',
            'monthly',
            'backpack',
        );
        $this->expectException(EntityLimitExceededException::class);
        $request = new class extends PaymentMethodRequest
        {
            public function rules(): array
            {
                return [];
            }
        };
        $request->replace(['name' => 'Second Method']);
        $request->setRouteResolver(fn () => (object) ['parameter' => fn ($n) => null]);
        $request->setMethod('POST');
        $request->authorize();
    }

    #[Test]
    public function payment_method_update_bypasses_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'payment_method')->update(['limit_value' => 0]);
        $this->limitService->recordUsage(
            $this->adminUser->id,
            'payment_method',
            'count',
            'monthly',
            'backpack',
        );
        $request = new class extends PaymentMethodRequest
        {
            public function rules(): array
            {
                return [];
            }
        };
        $request->replace(['name' => 'Updated']);
        $request->setRouteResolver(fn () => (object) ['parameter' => fn ($n) => 'existing-pay']);
        $request->setMethod('PUT');
        $this->assertTrue($request->authorize());
    }
}
