<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ArtisanCommandCategoryRequest;
use App\Models\ArtisanCommandCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for ArtisanCommandCategoryRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests artisan command category validation scenarios, authorization, and validation with database constraints
 */
class ArtisanCommandCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validCategoryData;

    /**
     * Set up the test environment.
     * Creates test user and valid category data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        
        // Set up valid category data
        $this->setupValidCategoryData();
    }

    /**
     * Setup valid category data for testing
     */
    private function setupValidCategoryData(): void
    {
        $this->validCategoryData = [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($this->validCategoryData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'slug'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validCategoryData;
            unset($invalidData[$field]);

            $request = new ArtisanCommandCategoryRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_short_name()
    {
        $invalidData = $this->validCategoryData;
        $invalidData['name'] = 'A'; // Too short (min 2)

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $invalidData = $this->validCategoryData;
        $invalidData['name'] = str_repeat('a', 256); // Too long (max 255)

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug()
    {
        // Create existing category
        ArtisanCommandCategory::factory()->create(['slug' => 'existing-slug']);

        $invalidData = $this->validCategoryData;
        $invalidData['slug'] = 'existing-slug';

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update()
    {
        // Create existing category
        $existingCategory = ArtisanCommandCategory::factory()->create(['slug' => 'existing-slug']);

        $updateData = $this->validCategoryData;
        $updateData['slug'] = 'existing-slug';
        $updateData['id'] = $existingCategory->id;

        $request = new ArtisanCommandCategoryRequest();
        $request->merge(['id' => $existingCategory->id]);
        $validator = Validator::make($updateData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_short_slug()
    {
        $invalidData = $this->validCategoryData;
        $invalidData['slug'] = 'a'; // Too short (min 2)

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_slug()
    {
        $invalidData = $this->validCategoryData;
        $invalidData['slug'] = str_repeat('a', 256); // Too long (max 255)

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_description()
    {
        $invalidData = $this->validCategoryData;
        $invalidData['description'] = str_repeat('a', 1001); // Too long (max 1000)

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $minimalData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($minimalData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $dataWithNulls = $this->validCategoryData;
        $dataWithNulls['description'] = null;

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($dataWithNulls, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_is_active()
    {
        $invalidData = $this->validCategoryData;
        $invalidData['is_active'] = 'invalid-boolean';

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_is_active()
    {
        $booleanValues = [true, false, 1, 0, '1', '0'];
        
        foreach ($booleanValues as $value) {
            $validData = $this->validCategoryData;
            $validData['is_active'] = $value;
            $validData['slug'] = 'test-slug-' . $value; // Make slug unique

            $request = new ArtisanCommandCategoryRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for is_active value: " . json_encode($value));
        }
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $this->actingAs($this->user);

        $request = new ArtisanCommandCategoryRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new ArtisanCommandCategoryRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ArtisanCommandCategoryRequest();
        $attributes = $request->attributes();

        $expectedKeys = ['name', 'slug', 'description', 'is_active'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new ArtisanCommandCategoryRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'name.required',
            'name.min',
            'slug.required',
            'slug.unique',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }

    #[Test]
    public function validation_passes_without_optional_fields()
    {
        $dataWithoutOptional = [
            'name' => 'System Commands',
            'slug' => 'system-commands',
            'is_active' => true,
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($dataWithoutOptional, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_different_string_types()
    {
        $stringTestCases = [
            ['name' => 'Simple Name', 'slug' => 'simple-name'],
            ['name' => 'Name with Numbers 123', 'slug' => 'name-with-numbers-123'],
            ['name' => 'Name-with-Dashes', 'slug' => 'name-with-dashes'],
            ['name' => 'Name_with_Underscores', 'slug' => 'name_with_underscores'],
        ];
        
        foreach ($stringTestCases as $index => $testCase) {
            $validData = array_merge($this->validCategoryData, $testCase);
            $validData['slug'] = $testCase['slug'] . '-' . $index; // Make slug unique

            $request = new ArtisanCommandCategoryRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for name: {$testCase['name']}");
        }
    }
}
