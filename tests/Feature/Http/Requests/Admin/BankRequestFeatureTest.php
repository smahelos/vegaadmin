<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\BankRequest;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class BankRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        $route = Route::post(
            '/test-bank',
            function (BankRequest $request) {
                $data = $request->validated();
                $bank = Bank::create($data);

                $responseFactory = response();

                return $responseFactory->json(['success' => true, 'id' => $bank->id]);
            },
        );
        $route->middleware('web');

        $route = Route::put(
            '/test-bank/{id}',
            function (BankRequest $request, $id) {
                $data = $request->validated();
                $bank = Bank::findOrFail($id);
                $bank->update($data);

                $responseFactory = response();

                return $responseFactory->json(['success' => true, 'id' => $bank->id]);
            },
        );
        $route->middleware('web');
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $request = new BankRequest;
        $data = [
            'name' => 'Test Bank',
            'code' => '1234',
            'swift' => 'ABCDCZ22',
            'country' => 'CZ',
        ];
        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing(): void
    {
        $request = new BankRequest;
        $validator = Validator::make([], $request->rules());
        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
        $this->assertArrayHasKey('code', $errors->toArray());
        $this->assertArrayHasKey('country', $errors->toArray());
    }

    #[Test]
    public function code_must_be_unique(): void
    {
        $bankFactory = Bank::factory();
        $bankFactory->create(['code' => 'EXIST']);
        $request = new BankRequest;
        $validator = Validator::make(
            [
                'name' => 'Another',
                'code' => 'EXIST',
                'country' => 'CZ',
            ],
            $request->rules(),
        );
        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('code', $errors->toArray());
    }

    #[Test]
    public function update_allows_same_code(): void
    {
        $bankFactory = Bank::factory();
        $bank = $bankFactory->create(['code' => 'KEEP']);
        $request = new BankRequest;
        $request->merge(['id' => $bank->id]);
        $validator = Validator::make(
            [
                'name' => 'Updated',
                'code' => 'KEEP',
                'country' => 'CZ',
            ],
            $request->rules(),
        );
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_user_with_permission(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $this->postJson(
            '/test-bank',
            [
                'name' => 'Permitted',
                'code' => 'PERM1',
                'country' => 'CZ',
            ],
        );
        $httpResponse->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $httpResponse = $this->postJson(
            '/test-bank',
            [
                'name' => 'Fail',
                'code' => 'FAIL1',
                'country' => 'CZ',
            ],
        );
        $httpResponse->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_user_without_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');
        $httpResponse = $this->postJson(
            '/test-bank',
            [
                'name' => 'NoPerm',
                'code' => 'NOP1',
                'country' => 'CZ',
            ],
        );
        $httpResponse->assertStatus(403);
    }

    #[Test]
    public function accepts_various_boolean_active_values(): void
    {
        $values = [
            true,
            false,
            1,
            0,
            '1',
            '0',
        ];
        $base = new BankRequest;
        foreach ($values as $idx => $val) {
            $validator = Validator::make(
                [
                    'name' => 'Bank ' . $idx,
                    'code' => 'B' . $idx . 'X' . $idx,
                    'country' => 'cz', // lowercase to test normalization
                    'active' => $val,
                ],
                $base->rules(),
            );
            $this->assertTrue(
                $validator->passes(),
                'Active value should pass: ' . var_export($val, true),
            );
        }
    }

    #[Test]
    public function description_respects_max_length(): void
    {
        $request = new BankRequest;
        $valid = Validator::make(
            [
                'name' => 'Desc Bank',
                'code' => 'D123',
                'country' => 'sk',
                'description' => str_repeat('a', 1000),
            ],
            $request->rules(),
        );
        $this->assertTrue($valid->passes());

        $invalid = Validator::make(
            [
                'name' => 'Desc Bank',
                'code' => 'D124',
                'country' => 'sk',
                'description' => str_repeat('a', 1001),
            ],
            $request->rules(),
        );
        $this->assertFalse($invalid->passes());
        $errors = $invalid->errors();
        $this->assertArrayHasKey('description', $errors->toArray());
    }

    #[Test]
    public function country_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $this->postJson(
            '/test-bank',
            [
                'name' => 'Lower Country',
                'code' => 'LC01',
                'country' => 'de',
            ],
        );
        $response = $httpResponse->assertStatus(200);

        $this->assertDatabaseHas('banks', [
            'code' => 'LC01',
            'country' => 'DE',
        ]);
    }
}
