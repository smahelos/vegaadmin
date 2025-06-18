<?php

namespace Tests\Feature\Repositories;

use App\Models\Client;
use App\Models\User;
use App\Repositories\ClientRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ClientRepository $repository;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new ClientRepository();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        
        // Set authenticated user for testing
        Auth::login($this->user);
    }

    #[Test]
    public function get_clients_for_dropdown_returns_current_user_clients_only(): void
    {
        // Create clients for current user
        $userClient1 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Client 1'
        ]);
        $userClient2 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Client 2'
        ]);
        
        // Create client for other user (should not be included)
        Client::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other User Client'
        ]);

        $result = $this->repository->getClientsForDropdown();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($userClient1->id, $result);
        $this->assertArrayHasKey($userClient2->id, $result);
        $this->assertEquals('User Client 1', $result[$userClient1->id]);
        $this->assertEquals('User Client 2', $result[$userClient2->id]);
    }

    #[Test]
    public function get_clients_for_dropdown_returns_empty_array_when_no_clients(): void
    {
        $result = $this->repository->getClientsForDropdown();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_default_client_returns_default_client_for_current_user(): void
    {
        // Create non-default client
        Client::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);
        
        // Create default client for current user
        $defaultClient = Client::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true
        ]);
        
        // Create default client for other user (should not be returned)
        Client::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_default' => true
        ]);

        $result = $this->repository->getDefaultClient();

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($defaultClient->id, $result->id);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertTrue($result->is_default);
    }

    #[Test]
    public function get_default_client_returns_null_when_no_default_client(): void
    {
        // Create non-default clients
        Client::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);
        Client::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);

        $result = $this->repository->getDefaultClient();

        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_client_when_belongs_to_current_user(): void
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id
        ]);

        $result = $this->repository->findById($client->id);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($client->id, $result->id);
        $this->assertEquals($this->user->id, $result->user_id);
    }

    #[Test]
    public function find_by_id_returns_null_when_client_belongs_to_other_user(): void
    {
        $otherUserClient = Client::factory()->create([
            'user_id' => $this->otherUser->id
        ]);

        $result = $this->repository->findById($otherUserClient->id);

        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_null_when_client_does_not_exist(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function create_saves_client_with_current_user_id(): void
    {
        $data = [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'phone' => '+420123456789',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'CZ',
            'is_default' => false
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['email'], $result->email);
        $this->assertEquals($data['phone'], $result->phone);
        $this->assertEquals($data['street'], $result->street);
        $this->assertEquals($data['is_default'], $result->is_default);
        
        // Verify it was saved to database
        $this->assertDatabaseHas('clients', [
            'id' => $result->id,
            'user_id' => $this->user->id,
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    #[Test]
    public function create_overwrites_user_id_when_provided_in_data(): void
    {
        $data = [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'CZ',
            'user_id' => $this->otherUser->id, // This should be overwritten
            'is_default' => false
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertNotEquals($this->otherUser->id, $result->user_id);
    }

    #[Test]
    public function repository_methods_work_with_different_authenticated_users(): void
    {
        // Create client for first user
        $client1 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User 1 Client'
        ]);

        // Switch to other user
        Auth::login($this->otherUser);

        // Create client for second user
        $client2 = Client::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'User 2 Client'
        ]);

        // Test dropdown only shows current user's clients
        $dropdown = $this->repository->getClientsForDropdown();
        $this->assertCount(1, $dropdown);
        $this->assertArrayHasKey($client2->id, $dropdown);
        $this->assertArrayNotHasKey($client1->id, $dropdown);

        // Test findById only finds current user's clients
        $foundClient = $this->repository->findById($client2->id);
        $this->assertNotNull($foundClient);
        $this->assertEquals($client2->id, $foundClient->id);

        $notFoundClient = $this->repository->findById($client1->id);
        $this->assertNull($notFoundClient);
    }
}
