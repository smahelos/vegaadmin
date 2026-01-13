<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\BankRequest;
use App\Models\Bank;
use App\Models\EntityLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;

class BankRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected User $adminUser;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        $this->limitService = app(UniversalLimitService::class);

        // Seed a generous default limit for banks
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_bank',
            'entity_type' => 'bank',
            'limit_value' => 50,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        Route::post('/test-bank', function (BankRequest $request) {
            $data = $request->validated();
            $bank = \App\Models\Bank::create($data);
            return response()->json(['success' => true, 'id' => $bank->id]);
        })->middleware('web');

        Route::put('/test-bank/{id}', function (BankRequest $request, $id) {
            $data = $request->validated();
            $bank = \App\Models\Bank::findOrFail($id);
            $bank->update($data);
            return response()->json(['success' => true, 'id' => $bank->id]);
        })->middleware('web');
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $request = new BankRequest();
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
        $request = new BankRequest();
        $validator = Validator::make([], $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    #[Test]
    public function code_must_be_unique(): void
    {
        Bank::factory()->create(['code' => 'EXIST']);
        $request = new BankRequest();
        $validator = Validator::make([
            'name' => 'Another',
            'code' => 'EXIST',
            'country' => 'CZ',
        ], $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    #[Test]
    public function update_allows_same_code(): void
    {
        $bank = Bank::factory()->create(['code' => 'KEEP']);
        $request = new BankRequest();
        $request->merge(['id' => $bank->id]);
        $validator = Validator::make([
            'name' => 'Updated',
            'code' => 'KEEP',
            'country' => 'CZ',
        ], $request->rules());
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_user_with_permission(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $this->postJson('/test-bank', [
            'name' => 'Permitted',
            'code' => 'PERM1',
            'country' => 'CZ',
        ])->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->postJson('/test-bank', [
            'name' => 'Fail',
            'code' => 'FAIL1',
            'country' => 'CZ',
        ])->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        $this->postJson('/test-bank', [
            'name' => 'NoPerm',
            'code' => 'NOP1',
            'country' => 'CZ',
        ])->assertStatus(403);
    }

    #[Test]
    public function bank_creation_respects_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'bank')->update(['limit_value' => 1]);
        $this->postJson('/test-bank', [
            'name' => 'First Bank',
            'code' => 'LIM01',
            'country' => 'CZ',
        ])->assertStatus(200);
        // Record usage manually
        $this->limitService->recordUsage($this->adminUser->id, 'bank', 'count', 'monthly', 'backpack');

        $this->expectException(\App\Domain\User\Exceptions\EntityLimitExceededException::class);
        $request = new class extends BankRequest { public function rules(): array { return []; } };
        $request->replace(['name' => 'Second', 'code' => 'LIM02', 'country' => 'CZ']);
        $request->setRouteResolver(fn () => (object)['parameter' => fn ($n) => null]);
        $request->setMethod('POST');
        $this->assertFalse($request->authorize()); // Exception expected before false
    }

    #[Test]
    public function bank_update_bypasses_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::where('entity_type', 'bank')->update(['limit_value' => 0]);
        // Simulate that usage already at limit
        $this->limitService->recordUsage($this->adminUser->id, 'bank', 'count', 'monthly', 'backpack');

        $request = new class extends BankRequest { public function rules(): array { return []; } };
        $request->replace(['name' => 'Updated']);
        $request->setRouteResolver(fn () => (object)['parameter' => fn ($n) => 99]);
        $request->setMethod('PUT');
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function accepts_various_boolean_active_values(): void
    {
        $values = [true, false, 1, 0, '1', '0'];
        $base = new BankRequest();
        foreach ($values as $idx => $val) {
            $validator = Validator::make([
                'name' => 'Bank '. $idx,
                'code' => 'B'. $idx . 'X'. $idx,
                'country' => 'cz', // lowercase to test normalization
                'active' => $val,
            ], $base->rules());
            $this->assertTrue($validator->passes(), 'Active value should pass: '. var_export($val,true));
        }
    }

    #[Test]
    public function description_respects_max_length(): void
    {
        $request = new BankRequest();
        $valid = Validator::make([
            'name' => 'Desc Bank',
            'code' => 'D123',
            'country' => 'sk',
            'description' => str_repeat('a', 1000),
        ], $request->rules());
        $this->assertTrue($valid->passes());

        $invalid = Validator::make([
            'name' => 'Desc Bank',
            'code' => 'D124',
            'country' => 'sk',
            'description' => str_repeat('a', 1001),
        ], $request->rules());
        $this->assertFalse($invalid->passes());
        $this->assertArrayHasKey('description', $invalid->errors()->toArray());
    }

    #[Test]
    public function country_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->adminUser,'backpack');
        $response = $this->postJson('/test-bank', [
            'name' => 'Lower Country',
            'code' => 'LC01',
            'country' => 'de',
        ])->assertStatus(200);

        $this->assertDatabaseHas('banks', [
            'code' => 'LC01',
            'country' => 'DE',
        ]);
    }
}
