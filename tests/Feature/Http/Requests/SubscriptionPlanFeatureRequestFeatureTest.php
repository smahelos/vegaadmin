<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\SubscriptionPlanFeatureRequest;
use App\Models\SubscriptionPlanFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class SubscriptionPlanFeatureRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        // Use prepared user with all frontend permissions
        $this->user = $this->user ?? User::factory()->create();

        // Define test routes
        Route::post('/frontend-subscription-plan-feature', function (SubscriptionPlanFeatureRequest $request) {
            return response()->json(['data' => $request->validated()]);
        })->middleware('web');

        Route::put('/frontend-subscription-plan-feature/{id}', function (SubscriptionPlanFeatureRequest $request, $id) {
            return response()->json(['data' => $request->validated()]);
        })->middleware('web');
    }

    private function validData(array $override = []): array
    {
        return array_merge([
            'name' => 'Frontend Feature',
            'slug' => 'frontend-feature',
            'description' => 'Some description',
            'is_active' => true,
            'sort_order' => 0,
        ], $override);
    }

    #[Test]
    public function authorization_requires_permission(): void
    {
        $user = User::factory()->create(); // no permission yet
        $this->actingAs($user);

        $response = $this->postJson('/frontend-subscription-plan-feature', $this->validData());
        $response->assertStatus(403); // authorize() returns false

        // Give permission and retry
        $perm = \Spatie\Permission\Models\Permission::where('name', 'frontend.can_create_edit_subscription_plan_feature')->first();
        $user->givePermissionTo($perm);
        $response = $this->postJson('/frontend-subscription-plan-feature', $this->validData(['slug' => 'frontend-feature-2']));
        $response->assertStatus(200);
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user);
        $validator = Validator::make($this->validData(), (new SubscriptionPlanFeatureRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing(): void
    {
        $this->actingAs($this->user);
        $data = $this->validData();
        unset($data['name'], $data['slug']);
        $validator = Validator::make($data, (new SubscriptionPlanFeatureRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function slug_auto_generated_when_missing(): void
    {
        $this->actingAs($this->user);
        $perm = \Spatie\Permission\Models\Permission::where('name','frontend.can_create_edit_subscription_plan_feature')->first();
        $this->user->givePermissionTo($perm);
        $response = $this->postJson('/frontend-subscription-plan-feature', [
            'name' => 'My Fancy Front Feature',
            // slug omitted
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $response->assertStatus(200);
        $this->assertEquals(Str::slug('My Fancy Front Feature'), $response->json('data.slug'));
    }

    #[Test]
    public function validation_fails_when_slug_not_unique(): void
    {
        $this->actingAs($this->user);
        SubscriptionPlanFeature::factory()->create(['slug' => 'duplicate-feature']);
        $validator = Validator::make($this->validData(['slug' => 'duplicate-feature']), (new SubscriptionPlanFeatureRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_on_update(): void
    {
        $this->actingAs($this->user);
        $perm = \Spatie\Permission\Models\Permission::where('name','frontend.can_create_edit_subscription_plan_feature')->first();
        $this->user->givePermissionTo($perm);
        $feature = SubscriptionPlanFeature::factory()->create(['slug' => 'update-feature']);
        $response = $this->putJson('/frontend-subscription-plan-feature/'.$feature->id, [
            'name' => 'Renamed',
            'slug' => 'update-feature',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('update-feature', $response->json('data.slug'));
    }

    #[Test]
    public function validation_fails_when_sort_order_negative(): void
    {
        $this->actingAs($this->user);
        $validator = Validator::make($this->validData(['sort_order' => -1]), (new SubscriptionPlanFeatureRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sort_order', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_allows_boolean_variants_for_is_active(): void
    {
        $this->actingAs($this->user);
        foreach ([true,false,1,0,'1','0'] as $val) {
            $validator = Validator::make($this->validData([
                'slug' => 'bool-feature-'.uniqid(),
                'name' => 'Bool Feature '.uniqid(),
                'is_active' => $val,
            ]), (new SubscriptionPlanFeatureRequest())->rules());
            $this->assertFalse($validator->fails(), 'Should pass for value '.json_encode($val));
        }
    }

    #[Test]
    public function validation_fails_with_invalid_boolean(): void
    {
        $this->actingAs($this->user);
        foreach (['yes','no','active','inactive',2,-1] as $val) {
            $validator = Validator::make($this->validData([
                'slug' => 'invalid-bool-'.uniqid(),
                'name' => 'Invalid Bool '.uniqid(),
                'is_active' => $val,
            ]), (new SubscriptionPlanFeatureRequest())->rules());
            $this->assertTrue($validator->fails(), 'Should fail for value '.json_encode($val));
            $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
        }
    }

    #[Test]
    public function nullable_description_is_accepted(): void
    {
        $this->actingAs($this->user);
        $validator = Validator::make($this->validData(['description' => null]), (new SubscriptionPlanFeatureRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function attributes_use_translations(): void
    {
        $attrs = (new SubscriptionPlanFeatureRequest())->attributes();
        $this->assertEquals(trans('admin.subscription_plan_features.name'), $attrs['name']);
        $this->assertEquals(trans('admin.subscription_plan_features.slug'), $attrs['slug']);
    }

    #[Test]
    public function messages_use_translations(): void
    {
        $msgs = (new SubscriptionPlanFeatureRequest())->messages();
        $this->assertEquals(trans('admin.subscription_plan_features.validation.name_required'), $msgs['name.required']);
        $this->assertEquals(trans('admin.subscription_plan_features.validation.slug_unique'), $msgs['slug.unique']);
    }
}
