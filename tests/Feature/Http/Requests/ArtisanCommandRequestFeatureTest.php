<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ArtisanCommandRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up a test route for HTTP requests
        Route::post('/test/artisan-command', function (ArtisanCommandRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function authorize_returns_false_when_user_not_authenticated(): void
    {
        $request = new ArtisanCommandRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorize_returns_true_when_user_authenticated_with_backpack(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        
        $request = new ArtisanCommandRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $data = [
            // Currently no validation rules defined, so any data should pass
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function attributes_returns_expected_field_names(): void
    {
        $request = new ArtisanCommandRequest();
        $attributes = $request->attributes();
        
        $this->assertIsArray($attributes);
        // Currently returns empty array since no attributes are defined
        $this->assertEmpty($attributes);
    }

    #[Test]
    public function messages_returns_expected_error_messages(): void
    {
        $request = new ArtisanCommandRequest();
        $messages = $request->messages();
        
        $this->assertIsArray($messages);
        // Currently returns empty array since no custom messages are defined
        $this->assertEmpty($messages);
    }

    #[Test]
    public function http_request_with_valid_data_passes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        $data = [
            // Currently no validation rules, so empty data should pass
        ];

        $response = $this->postJson('/test/artisan-command', $data);

        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }

    #[Test]
    public function http_request_without_authentication_fails(): void
    {
        $data = [
            // Any data for unauthenticated request
        ];

        $response = $this->postJson('/test/artisan-command', $data);

        $response->assertStatus(403);
    }
}
