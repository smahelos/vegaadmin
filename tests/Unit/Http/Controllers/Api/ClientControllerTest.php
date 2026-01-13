<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\ClientController;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Api\ClientController
 *
 * These tests focus on method existence and internal branching / exception handling
 * using a lightweight service stub and controller subclass to bypass framework auth.
 */
class ClientControllerTest extends TestCase
{
    private TestableClientController $controller;
    private FakeInvoicePartyService $service;
    private User $adminUser;
    private User $frontendUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare stub service & controller
        $this->service = new FakeInvoicePartyService();
        $this->controller = new TestableClientController($this->service);

        // Unsaved user models (no DB interaction needed for these unit tests)
        $this->adminUser = new User();
        $this->adminUser->id = 1;
        $this->adminUser->email = 'admin@example.com';
        $this->adminUser->name = 'Admin';

        $this->frontendUser = new User();
        $this->frontendUser->id = 2;
        $this->frontendUser->email = 'user@example.com';
        $this->frontendUser->name = 'User';
    }

    #[Test]
    public function controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ClientController::class, $this->controller);
    }

    #[Test]
    public function get_client_admin_unauthenticated_returns_401(): void
    {
        $response = $this->controller->getClientAdmin(5); // No user injected
        $json = $response->getData(true);
        $this->assertEquals(401, $response->status());
        $this->assertEquals(__('users.auth.unauthenticated'), $json['error']);
    }

    #[Test]
    public function get_client_admin_without_permission_returns_403(): void
    {
        $this->controller->setBackpackUser($this->adminUser); // No permission flag set
        $response = $this->controller->getClientAdmin(5);
        $json = $response->getData(true);
        $this->assertEquals(403, $response->status());
        $this->assertEquals(__('users.auth.unauthorized'), $json['error']);
    }

    #[Test]
    public function get_client_admin_success_returns_client(): void
    {
        $this->service->returnClientUserId = $this->adminUser->id; // any id accepted
        $this->controller->setBackpackUser($this->adminUser, permissions: ['can_view_client']);
        $response = $this->controller->getClientAdmin(10);
        $json = $response->getData(true);
        $this->assertEquals(200, $response->status());
        
        // Check that response contains expected client data
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('email', $json);
        $this->assertEquals('Client To Find', $json['name']);
        $this->assertEquals('client_to_find@example.com', $json['email']);
    }

    #[Test]
    public function get_client_admin_model_not_found_returns_404(): void
    {
        $this->controller->setBackpackUser($this->adminUser, permissions: ['can_view_client']);
        $this->service->throwModelNotFound = true;
        $response = $this->controller->getClientAdmin(99);
        $json = $response->getData(true);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(__('clients.messages.not_found'), $json['error']);
    }

    #[Test]
    public function get_client_admin_exception_returns_500(): void
    {
        $this->controller->setBackpackUser($this->adminUser, permissions: ['can_view_client']);
        $this->service->throwGenericException = true;
        $response = $this->controller->getClientAdmin(77);
        $json = $response->getData(true);
        $this->assertEquals(500, $response->status());
        $this->assertEquals(__('clients.messages.error_loading'), $json['error']);
    }

    #[Test]
    public function get_client_enforces_ownership(): void
    {
        $this->service->returnClientUserId = 999; // Other user id
        $this->controller->setFrontendUser($this->frontendUser);
        $response = $this->controller->getClient(5);
        $this->assertEquals(403, $response->status());
        $json = $response->getData(true);
        $this->assertEquals(__('clients.messages.not_found'), $json['error']);
    }

    #[Test]
    public function get_client_exception_results_in_404(): void
    {
        $this->controller->setFrontendUser($this->frontendUser);
        $this->service->throwGenericException = true; // triggers catch-all -> 404 for getClient
        $response = $this->controller->getClient(12);
        $this->assertEquals(404, $response->status());
        $json = $response->getData(true);
        $this->assertEquals(__('clients.messages.not_found'), $json['error']);
    }

    #[Test]
    public function get_clients_admin_unauthenticated_returns_401(): void
    {
        $response = $this->controller->getClientsAdmin(); // No user injected
        $json = $response->getData(true);
        $this->assertEquals(401, $response->status());
        $this->assertEquals(__('users.auth.unauthenticated'), $json['error']);
    }

    #[Test]
    public function get_clients_admin_without_permission_returns_403(): void
    {
        $this->controller->setBackpackUser($this->adminUser); // No permission flag set
        $response = $this->controller->getClientsAdmin();
        $json = $response->getData(true);
        $this->assertEquals(403, $response->status());
        $this->assertEquals(__('users.auth.unauthorized'), $json['error']);
    }

    #[Test]
    public function get_clients_admin_success_returns_clients(): void
    {
        $this->controller->setBackpackUser($this->adminUser, permissions: ['can_view_client']);
        $response = $this->controller->getClientsAdmin();
        $json = $response->getData(true);
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($json);
    }

    #[Test]
    public function get_clients_unauthenticated_returns_401(): void
    {
        $response = $this->controller->getClients(); // No user injected
        $json = $response->getData(true);
        $this->assertEquals(401, $response->status());
        $this->assertEquals(__('users.auth.unauthenticated'), $json['error']);
    }

    #[Test]
    public function get_clients_success_returns_clients(): void
    {
        $this->controller->setFrontendUser($this->frontendUser);
        $response = $this->controller->getClients();
        $json = $response->getData(true);
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($json);
    }

    #[Test]
    public function get_default_client_success_returns_client(): void
    {
        // Mock Auth::id() to return a specific user ID
        $this->service->mockDefaultClient = new \App\Domain\Party\DTO\ClientDTO(
            id: 1,
            name: 'Default Client',
            email: 'default@example.com',
            phone: null,
            street: null,
            city: null,
            zip: null,
            country: null,
            ico: null,
            dic: null,
            shortcut: null,
            description: null,
            created_at: null,
            user_id: 123,
            is_default: true,
            invoices: []
        );
        
        $response = $this->controller->getDefaultClient();
        $json = $response->getData(true);
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Default Client', $json['name']);
        $this->assertEquals('default@example.com', $json['email']);
    }

    #[Test]
    public function get_default_client_not_found_returns_404(): void
    {
        $this->service->mockDefaultClient = null; // No default client
        $response = $this->controller->getDefaultClient();
        $json = $response->getData(true);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(__('clients.messages.not_found'), $json['error']);
    }

    #[Test]
    public function get_default_client_exception_returns_500(): void
    {
        $this->service->throwGenericExceptionForDefault = true;
        $response = $this->controller->getDefaultClient();
        $json = $response->getData(true);
        $this->assertEquals(500, $response->status());
        $this->assertEquals(__('clients.messages.error_loading'), $json['error']);
    }
}

/**
 * Testable controller subclass overriding auth / permission checks so unit tests
 * can inject users & permissions without hitting guards.
 */
class TestableClientController extends ClientController
{
    private ?User $backpackUser = null;
    private ?User $frontendUser = null;
    /** @var array<int,string> */
    private array $backpackPermissions = [];

    public function __construct(PartyApplicationServiceInterface $partyService)
    {
        // Pass null as auth adapter - parent will resolve it from container
        parent::__construct($partyService, null);
    }

    public function setBackpackUser(User $user, array $permissions = []): void
    {
        $this->backpackUser = $user;
        $this->backpackPermissions = $permissions;
    }

    public function setFrontendUser(User $user): void
    {
        $this->frontendUser = $user;
    }

    // Override trait methods
    protected function getBackpackUser(): ?User
    {
        return $this->backpackUser;
    }

    protected function backpackUserHasPermission(string $permission): bool
    {
        return in_array($permission, $this->backpackPermissions, true);
    }

    protected function getFrontendUser(): ?User
    {
        return $this->frontendUser;
    }
}

/**
 * Lightweight fake implementation of PartyApplicationServiceInterface.
 * Only the methods used by the tested controller paths contain logic; the rest return simple defaults.
 */
class FakeInvoicePartyService implements PartyApplicationServiceInterface
{
    public bool $throwModelNotFound = false;
    public bool $throwGenericException = false;
    public bool $throwGenericExceptionForDefault = false;
    public int $returnClientUserId = 1;
    public int $returnSupplierUserId = 1;
    public ?\App\Domain\Party\DTO\ClientDTO $mockDefaultClient = null;

    public function clientOptions(int $userId): array { return []; }
    public function supplierOptions(int $userId): array { return []; }
    public function defaultClient(int $userId): ?ClientDTO { return null; }
    public function defaultSupplier(int $userId): ?SupplierDTO { return null; }

    public function findClientById(int $id): Client {
        if ($this->throwModelNotFound) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }
        if ($this->throwGenericException) {
            throw new \Exception('Generic error in findClientById');
        }
        return new Client(
            [
                'id' => $id,
                'name' => 'Client To Find',
                'email' => 'client_to_find@example.com',
            ]
        );
    }

    public function resolveOrCreateClient(int $userId, array $data): Client {
        return new Client(
            [
            'id' => 1,
            'name' => $data['name'] ?? 'Default Client',
            'email' => $data['email'] ?? 'client@example.com',
            'phone' => $data['phone'] ?? null,
            'street' => $data['street'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'] ?? null,
            'ico' => $data['ico'] ?? null,
            'dic' => $data['dic'] ?? null,
            'shortcut' => $data['shortcut'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'user_id' => $userId,
            'is_default' => false,
            'invoices' => [],
            ]
        );
    }
    public function resolveOrCreateSupplier(int $userId, array $data): Supplier { 
        return new Supplier(
            [
                'id' => 1,
                'name' => $data['name'] ?? 'Default Supplier',
                'email' => $data['email'] ?? 'supplier@example.com',
                'phone' => $data['phone'] ?? null,
                'street' => $data['street'] ?? null,
                'city' => $data['city'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? null,
                'ico' => $data['ico'] ?? null,
                'dic' => $data['dic'] ?? null,
                'shortcut' => $data['shortcut'] ?? null,
                'description' => $data['description'] ?? null,
                'created_at' => $data['created_at'] ?? null,
                'supplier_logo' => $data['supplier_logo'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'bank_code' => $data['bank_code'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'iban' => $data['iban'] ?? null,
                'swift' => $data['swift'] ?? null,
                'has_payment_info' => $data['has_payment_info'] ?? null,
                'user_id' => $userId,
                'is_default' => false,
                'invoices' => [],
            ]
        );
    }

    public function resolveOrCreateSupplierWithFlag(int $userId, array $data): array {
        return [
            'supplier' => new SupplierDTO(
                id: 1,
                name: $data['name'] ?? 'Default Supplier',
                email: $data['email'] ?? 'supplier@example.com',
                phone: $data['phone'] ?? null,
                street: $data['street'] ?? null,
                city: $data['city'] ?? null,
                zip: $data['zip'] ?? null,
                country: $data['country'] ?? null,
                ico: $data['ico'] ?? null,
                dic: $data['dic'] ?? null,
                shortcut: $data['shortcut'] ?? null,
                description: $data['description'] ?? null,
                created_at: $data['created_at'] ?? null,
                supplier_logo: $data['supplier_logo'] ?? null,
                account_number: $data['account_number'] ?? null,
                bank_code: $data['bank_code'] ?? null,
                bank_name: $data['bank_name'] ?? null,
                iban: $data['iban'] ?? null,
                swift: $data['swift'] ?? null,
                has_payment_info: $data['has_payment_info'] ?? null,
                user_id: $userId,
                is_default: false,
                invoices: [],
            ), 
            'created' => true
        ];
    }
    
    public function resolveOrCreateClientWithFlag(int $userId, array $data): array { 
        return [
            'client' => new ClientDTO(
                id: 1,
                name: $data['name'] ?? 'Default Client',
                email: $data['email'] ?? 'client@example.com',
                phone: $data['phone'] ?? null,
                street: $data['street'] ?? null,
                city: $data['city'] ?? null,
                zip: $data['zip'] ?? null,
                country: $data['country'] ?? null,
                ico: $data['ico'] ?? null,
                dic: $data['dic'] ?? null,
                shortcut: $data['shortcut'] ?? null,
                description: $data['description'] ?? null,
                created_at: $data['created_at'] ?? null,
                user_id: $userId,
                is_default: false,
                invoices: [],
            ), 
            'created' => true
        ];
    }

    public function findClient(int $userId, int $id): Client
    {
        if ($this->throwModelNotFound) {
            throw new ModelNotFoundException();
        }
        if ($this->throwGenericException) {
            throw new \Exception('Failure');
        }
        $client = new Client(
            [
                'id' => $id,
                'name' => 'Client To Find',
                'email' => 'clienttofind@example.com',
                'phone' => null,
                'street' => null,
                'city' => null,
                'zip' => null,
                'country' => null,
                'ico' => null,
                'dic' => null,
                'shortcut' => null,
                'description' => null,
                'created_at' => null,
                'user_id' => $this->returnClientUserId,
                'is_default' => false,
                'invoices' => [],
            ]
        );
        return $client;
    }

    public function findSupplier(int $userId, int $id): Supplier {
        return new Supplier(
            [
                'id' => $id,
                'name' => $data['name'] ?? 'Default Supplier',
                'email' => $data['email'] ?? 'supplier@example.com',
                'phone' => $data['phone'] ?? null,
                'street' => $data['street'] ?? null,
                'city' => $data['city'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? null,
                'ico' => $data['ico'] ?? null,
                'dic' => $data['dic'] ?? null,
                'shortcut' => $data['shortcut'] ?? null,
                'description' => $data['description'] ?? null,
                'created_at' => $data['created_at'] ?? null,
                'supplier_logo' => $data['supplier_logo'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'bank_code' => $data['bank_code'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'iban' => $data['iban'] ?? null,
                'swift' => $data['swift'] ?? null,
                'has_payment_info' => $data['has_payment_info'] ?? null,
                'user_id' => $userId,
                'is_default' => false,
                'invoices' => [],
            ]
        );
    }
    public function updateClient(int $clientId, array $data): Client {
        $data = [
            'id' => $clientId,
            'name' => $data['name'] ?? 'Default Client',
            'email' => $data['email'] ?? 'client@example.com',
            'phone' => $data['phone'] ?? null,
            'street' => $data['street'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'] ?? null,
            'ico' => $data['ico'] ?? null,
            'dic' => $data['dic'] ?? null,
            'shortcut' => $data['shortcut'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'user_id' => $this->returnClientUserId,
            'is_default' => false,
            'invoices' => [],
        ];

        return new Client($data);
    }
    public function updateSupplier(int $supplierId, array $data): Supplier {
        $data = [
            'id' => $supplierId,
            'name' => $data['name'] ?? 'Default Supplier',
            'email' => $data['email'] ?? 'supplier@example.com',
            'phone' => $data['phone'] ?? null,
            'street' => $data['street'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'] ?? null,
            'ico' => $data['ico'] ?? null,
            'dic' => $data['dic'] ?? null,
            'shortcut' => $data['shortcut'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'supplier_logo' => $data['supplier_logo'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'bank_code' => $data['bank_code'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'iban' => $data['iban'] ?? null,
            'swift' => $data['swift'] ?? null,
            'has_payment_info' => $data['has_payment_info'] ?? null,
            'user_id' => $this->returnSupplierUserId,
            'is_default' => false,
            'invoices' => [],
        ];

        return new Supplier($data);
    }
    public function setSupplierDefault(int $userId, int $supplierId): void {}
    public function setClientDefault(int $userId, int $clientId): void {}
    public function deleteSupplier(int $userId, int $supplierId): bool { return true; }
    public function deleteClient(int $userId, int $clientId): bool { return true; }
    public function listSuppliers(\App\Models\User $user): array { return []; }
    public function listClients(\App\Models\User $user): array { return []; }
    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO { return null; }
    public function defaultClientOrFirst(int $userId): ?ClientDTO { 
        if ($this->throwGenericExceptionForDefault) {
            throw new \Exception('Generic error in defaultClientOrFirst');
        }
        return $this->mockDefaultClient; 
    }
}
