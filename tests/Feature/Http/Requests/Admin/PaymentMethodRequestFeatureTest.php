<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Http\Requests\Admin\PaymentMethodRequest;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        $route = Route::post(
            '/test-payment-method',
            function (PaymentMethodRequest $request) {
                $responseFactory = response();

                return $responseFactory->json(['success' => true]);
            },
        );
        $route->middleware('web');

        $route = Route::put(
            '/test-payment-method/{id}',
            function (PaymentMethodRequest $request, $id) {
                $responseFactory = response();

                return $responseFactory->json(['success' => true]);
            },
        );
        $route->middleware('web');
    }

    #[Test]
    public function accepts_a_payment_method_code_that_is_not_stored(): void
    {
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::OTHER->value);
        $paymentMethodQuery->delete();

        $validator = Validator::make(
            ['name' => 'Other', 'slug' => PaymentMethodCode::OTHER->value],
            (new PaymentMethodRequest)->rules(),
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function requires_a_payment_method_code(): void
    {
        $validator = Validator::make(['name' => 'Card'], (new PaymentMethodRequest)->rules());

        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
    }

    #[Test]
    public function rejects_a_slug_that_is_not_a_payment_method_code(): void
    {
        $validator = Validator::make(['name' => 'Bank Transfer', 'slug' => 'bank-transfer'], (new PaymentMethodRequest)->rules());

        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
    }

    #[Test]
    public function validation_fails_with_missing_name(): void
    {
        $request = new PaymentMethodRequest;
        $validator = Validator::make(['slug' => 'something'], $request->rules());
        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function rejects_a_payment_method_code_that_is_already_stored(): void
    {
        $validator = Validator::make(['name' => 'Cash', 'slug' => PaymentMethodCode::CASH->value], (new PaymentMethodRequest)->rules());

        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
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
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::OTHER->value);
        $paymentMethodQuery->delete();
        $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $this->postJson(
            '/test-payment-method',
            [
                'name' => 'Fast Pay',
                'slug' => PaymentMethodCode::OTHER->value,
            ],
        );
        $httpResponse->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $httpResponse = $this->postJson('/test-payment-method', [
            'name' => 'Fail Pay',
        ]);
        $httpResponse->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_user_without_permission(): void
    {
        $userFactory = User::factory();
        $userNoPerm = $userFactory->create();
        $this->actingAs($userNoPerm, 'backpack');
        $httpResponse = $this->postJson('/test-payment-method', [
            'name' => 'No Perm',
        ]);
        $httpResponse->assertStatus(403);
    }
}
