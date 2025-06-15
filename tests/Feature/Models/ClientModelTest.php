<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for Client Model
 * 
 * Tests database relationships, business logic, and model behavior requiring database interactions
 * Tests client interactions with users, invoices, and default client functionality
 */
class ClientModelTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Client $client;

    /**
     * Set up the test environment.
     * Creates test user and client for model testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->createTestUser();
        
        // Create test client
        $this->createTestClient();
    }

    /**
     * Create test user with faker data
     */
    private function createTestUser(): void
    {
        $this->user = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
    }

    /**
     * Create test client with faker data
     */
    private function createTestClient(): void
    {
        $this->client = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
            'is_default' => false,
        ]);
    }

    /**
     * Test that client belongs to user relationship.
     *
     * @return void
     */
    public function test_client_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->client->user);
        $this->assertEquals($this->user->id, $this->client->user->id);
        $this->assertEquals($this->user->name, $this->client->user->name);
        $this->assertEquals($this->user->email, $this->client->user->email);
    }

    /**
     * Test that client can have many invoices relationship.
     *
     * @return void
     */
    public function test_client_has_many_invoices()
    {
        // Initially no invoices
        $this->assertInstanceOf(Collection::class, $this->client->invoices);
        $this->assertEmpty($this->client->invoices);
        
        // Create invoices for the client
        $invoice1 = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'payment_method_id' => null, // Avoid foreign key issues
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'payment_method_id' => null,
        ]);
        
        // Refresh the client to load relationships
        $this->client->refresh();
        
        // Assert relationship works
        $this->assertCount(2, $this->client->invoices()->get());
        $this->assertTrue($this->client->invoices->contains($invoice1));
        $this->assertTrue($this->client->invoices->contains($invoice2));
        
        // Assert all invoices belong to this client
        foreach ($this->client->invoices as $invoice) {
            $this->assertEquals($this->client->id, $invoice->client_id);
        }
    }

    /**
     * Test default client behavior - setting one client as default unsets others.
     *
     * @return void
     */
    public function test_setting_client_as_default_unsets_other_defaults()
    {
        // Create multiple clients for the same user
        $client1 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'is_default' => true, // Initially default
        ]);
        
        $client2 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'is_default' => false,
        ]);
        
        // Verify initial state
        $this->assertTrue($client1->fresh()->is_default);
        $this->assertFalse($client2->fresh()->is_default);
        
        // Set client2 as default
        $client2->is_default = true;
        $client2->save();
        
        // Verify that client1 is no longer default and client2 is default
        $this->assertFalse($client1->fresh()->is_default);
        $this->assertTrue($client2->fresh()->is_default);
    }

    /**
     * Test that default client behavior only affects clients of the same user.
     *
     * @return void
     */
    public function test_default_client_behavior_is_user_specific()
    {
        // Create another user with their own client
        $otherUser = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        
        $otherUserClient = Client::factory()->create([
            'user_id' => $otherUser->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'is_default' => true,
        ]);
        
        $thisUserClient = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'is_default' => false,
        ]);
        
        // Set this user's client as default
        $thisUserClient->is_default = true;
        $thisUserClient->save();
        
        // Verify that other user's default client is not affected
        $this->assertTrue($otherUserClient->fresh()->is_default);
        $this->assertTrue($thisUserClient->fresh()->is_default);
    }

    /**
     * Test multiple clients can exist for one user but only one can be default.
     *
     * @return void
     */
    public function test_only_one_client_can_be_default_per_user()
    {
        // Create multiple clients for the user
        $clients = [];
        for ($i = 0; $i < 5; $i++) {
            $clients[] = Client::factory()->create([
                'user_id' => $this->user->id,
                'name' => $this->faker->company,
                'email' => $this->faker->unique()->companyEmail,
                'is_default' => false,
            ]);
        }
        
        // Set the middle client as default
        $defaultClient = $clients[2];
        $defaultClient->is_default = true;
        $defaultClient->save();
        
        // Verify only one client is default
        $defaultClients = Client::where('user_id', $this->user->id)
            ->where('is_default', true)
            ->get();
            
        $this->assertCount(1, $defaultClients);
        $this->assertEquals($defaultClient->id, $defaultClients->first()->id);
        
        // Set another client as default
        $newDefaultClient = $clients[4];
        $newDefaultClient->is_default = true;
        $newDefaultClient->save();
        
        // Verify only the new client is default
        $defaultClients = Client::where('user_id', $this->user->id)
            ->where('is_default', true)
            ->get();
            
        $this->assertCount(1, $defaultClients);
        $this->assertEquals($newDefaultClient->id, $defaultClients->first()->id);
        $this->assertFalse($defaultClient->fresh()->is_default);
    }

    /**
     * Test preferred locale functionality.
     *
     * @return void
     */
    public function test_preferred_locale_method()
    {
        // This tests the HasPreferredLocale trait functionality
        $locale = $this->client->preferredLocale();
        
        // The method should return a string (locale code)
        $this->assertIsString($locale);
        
        // Should be a valid locale code (2-5 characters)
        $this->assertMatchesRegularExpression('/^[a-z]{2}(_[A-Z]{2})?$/', $locale);
    }

    /**
     * Test that client deletion behavior with related invoices.
     *
     * @return void
     */
    public function test_client_deletion_behavior_with_invoices()
    {
        // Create invoices for the client
        $invoice1 = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'payment_method_id' => null,
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'payment_method_id' => null,
        ]);
        
        $clientId = $this->client->id;
        
        // Delete the client
        $this->client->delete();
        
        // Check what happens to related invoices
        // This test documents the current behavior - adjust based on business rules
        $remainingInvoice1 = Invoice::find($invoice1->id);
        $remainingInvoice2 = Invoice::find($invoice2->id);
        
        // If cascade delete is NOT implemented, they should still exist but with null client_id
        // If cascade delete IS implemented, they should be null
        // Adjust assertions based on actual business requirements
        if ($remainingInvoice1) {
            $this->assertNull($remainingInvoice1->client_id);
        }
        
        if ($remainingInvoice2) {
            $this->assertNull($remainingInvoice2->client_id);
        }
    }

    /**
     * Test client creation with all attributes.
     *
     * @return void
     */
    public function test_client_creation_with_full_data()
    {
        $clientData = [
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
            'ico' => $this->faker->numerify('########'),
            'dic' => $this->faker->numerify('CZ########'),
            'phone' => $this->faker->phoneNumber,
            'shortcut' => $this->faker->lexify('???'),
            'is_default' => false,
        ];
        
        $client = Client::create($clientData);
        
        $this->assertDatabaseHas('clients', $clientData);
        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($clientData['name'], $client->name);
        $this->assertEquals($clientData['email'], $client->email);
        $this->assertEquals($this->user->id, $client->user_id);
    }

    /**
     * Test that client updates work correctly.
     *
     * @return void
     */
    public function test_client_update()
    {
        $originalName = $this->client->name;
        $newName = $this->faker->company;
        $newEmail = $this->faker->unique()->companyEmail;
        
        $this->client->update([
            'name' => $newName,
            'email' => $newEmail,
        ]);
        
        $this->client->refresh();
        
        $this->assertEquals($newName, $this->client->name);
        $this->assertEquals($newEmail, $this->client->email);
        $this->assertNotEquals($originalName, $this->client->name);
        
        $this->assertDatabaseHas('clients', [
            'id' => $this->client->id,
            'name' => $newName,
            'email' => $newEmail,
        ]);
    }
}
