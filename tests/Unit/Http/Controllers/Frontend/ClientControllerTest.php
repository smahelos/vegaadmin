<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\ClientController;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    #[Test]
    public function index_returns_view_with_limits_data(): void
    {
        // Use a non-persistent model instance to keep unit test isolated from DB
        $user = User::factory()->make();
        $this->actingAs($user, 'web');

        $controller = new ClientController(
            new class implements PartyApplicationServiceInterface {
                public bool $throwModelNotFound = false;
                public bool $throwGenericException = false;
                public int $returnClientUserId = 1;
                public int $returnSupplierUserId = 1;

                public function clientOptions(int $userId): array { return []; }
                public function supplierOptions(int $userId): array { return []; }
                public function defaultClient(int $userId): ?ClientDTO { return null; }
                public function defaultSupplier(int $userId): ?SupplierDTO { return null; }
                public function findClientById(int $id): Client {
                    return new Client([
                        'id' => $id,
                        'name' => 'Client To Find',
                        'email' => 'clienttofind@example.com',
                    ]);
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
                            'has_payment_info' => (bool)($data['has_payment_info'] ?? false),
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
                    return new Client([
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
                        'user_id' => $userId,
                        'is_default' => false,
                        'invoices' => [],
                    ]);
                }

                public function findSupplier(int $userId, int $id): Supplier {
                    if ($this->throwModelNotFound) {
                        throw new ModelNotFoundException();
                    }
                    if ($this->throwGenericException) {
                        throw new \Exception('Failure');
                    }
                    return new Supplier([
                        'id' => $id,
                        'name' => 'Default Supplier',
                        'email' => 'supplier@example.com',
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
                        'supplier_logo' => null,
                        'account_number' => null,
                        'bank_code' => null,
                        'bank_name' => null,
                        'iban' => null,
                        'swift' => null,
                        'has_payment_info' => false,
                        'user_id' => $this->returnSupplierUserId,
                        'is_default' => false,
                        'invoices' => [],
                    ]);
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
                        'has_payment_info' => (bool)($data['has_payment_info'] ?? false),
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
                public function listSuppliers(User $user): array { return []; }
                public function listClients(User $user): array { return []; }
                public function defaultSupplierOrFirst(int $userId): ?SupplierDTO { return null; }
                public function defaultClientOrFirst(int $userId): ?ClientDTO { return null; }
            },
            new class implements CountryApplicationServiceInterface {
                public function getCountryCodesForSelect(): array { return []; }
                public function getCountry(string $code): ?array { return null; }
                public function getCountries(): array { return []; }
            },
            new class implements UELSApplicationServiceInterface {
                public function getEntityLimitInfo(?int $userId, string $entityType): array { return []; }
                public function canUserCreateEntity(?int $userId, string $entityType): bool { return true; }
                public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array { return ['current_usage'=>0,'limit'=>10]; }
                public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string { return 'daily'; }
                public function getUserPermissionLimits(?int $userId, string $entityType): Collection { return collect(); }
            }
        );

        $view = $controller->index();
        $this->assertEquals('frontend.clients.index', $view->name());
        $this->assertArrayHasKey('limitsData', $view->getData());
    }

    #[Test]
    public function constructor_has_four_parameters(): void
    {
        $reflection = new \ReflectionClass(ClientController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(3, $constructor->getParameters());
    }
}
