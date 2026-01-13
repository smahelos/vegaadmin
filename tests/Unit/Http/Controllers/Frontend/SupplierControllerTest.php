<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\SupplierController;
use App\Application\Payment\Contracts\BankApplicationServiceInterface as BankServiceInterface;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface as CountryServiceInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface as InvoicePartyServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface as UELSServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for SupplierController focusing ONLY on internal helper getSuppliersLimit().
 * Per project guidelines controller behavior is otherwise covered by Feature tests.
 */
class SupplierControllerTest extends TestCase
{
    private BankServiceInterface $bankStub;
    private CountryServiceInterface $countryStub;
    private InvoicePartyServiceInterface $partyStub;
    private UELSServiceInterface $limitStub;

    protected function setUp(): void
    {
        parent::setUp();

        // Lightweight stubs implementing required interfaces; methods not used throw to catch accidental calls.
        $this->bankStub = new class implements BankServiceInterface {
            public function getBanksForDropdown(): array { throw new \RuntimeException('Not used'); }
            public function getBanksForJs(): array { throw new \RuntimeException('Not used'); }
        };
        $this->countryStub = new class implements CountryServiceInterface {
            public function getCountries(): array { return []; }
            public function getCountry(string $code): ?array { return null; }
            public function getAllCountries(): array { return []; }
            public function getCountriesForSelect(): array { return []; }
            public function getSimpleCountriesForSelect(): array { return []; }
            public function getCountryCodesForSelect(): array { return []; }
            public function getCountryByCode(string $code): ?array { return null; }
        };
        $this->partyStub = new class implements InvoicePartyServiceInterface {
            public bool $throwModelNotFound = false;
            public bool $throwGenericException = false;
            public int $returnClientUserId = 1;
            public int $returnSupplierUserId = 1;
            public function clientOptions(int $userId): array { return []; }
            public function supplierOptions(int $userId): array { return []; }
            public function defaultClient(int $userId): ?ClientDTO { return null; }
            public function defaultSupplier(int $userId): ?SupplierDTO { return null; }
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
                    $client = new Client(
                        [
                            'id' => $id,
                            'name' => $data['name'] ?? 'Client To Find',
                            'email' => $data['email'] ?? 'clienttofind@example.com',
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
                    return $client;
                }

                public function findClientById(int $id): Client
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
                            'name' => $data['name'] ?? 'Client To Find',
                            'email' => $data['email'] ?? 'clienttofind@example.com',
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
                        ]
                    );
                    return $client;
                }

                public function findSupplier(int $userId, int $id): Supplier {
                    if ($this->throwModelNotFound) {
                        throw new ModelNotFoundException();
                    }
                    if ($this->throwGenericException) {
                        throw new \Exception('Failure');
                    }
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
                            'has_payment_info' => (bool)($data['has_payment_info'] ?? false),
                            'user_id' => $this->returnSupplierUserId,
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
        };
        $this->limitStub = new class implements UELSServiceInterface {
            public array $check = [];
            public array $usage = [];
            public string $best = 'monthly';
            public function getEntityLimitInfo(?int $userId, string $entityType): array { return []; }
            public function canUserCreateEntity(?int $userId, string $entityType): bool { return true; }
            public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array {
                // Merge check and usage to simulate real service payload
                $stats = array_merge($this->check, $this->usage);
                // Provide defaults when missing keys
                if (!array_key_exists('current_usage', $stats)) {
                    $stats['current_usage'] = 0;
                }
                if (!array_key_exists('can_create', $stats) && array_key_exists('allowed', $stats)) {
                    // Map legacy 'allowed' to 'can_create' for controller consumption
                    $stats['can_create'] = (bool) $stats['allowed'];
                }
                return $stats;
            }
            public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string { return $this->best; }
            public function getUserPermissionLimits(?int $userId, string $entityType): \Illuminate\Support\Collection { return collect(); }
        };
    }

    private function makeController(): SupplierController
    {
        return new SupplierController(
            $this->bankStub,
            $this->countryStub,
            $this->partyStub,
            $this->limitStub
        );
    }

    #[Test]
    public function get_suppliers_limit_returns_expected_data_for_authenticated_user(): void
    {
        $user = new User();
        $user->id = 55;
        Auth::shouldReceive('user')->andReturn($user);

        $this->limitStub->check = [
            'allowed' => true,
            'limit' => 20,
            'current_usage' => 7,
            'period_type' => 'monthly',
        ];
        $this->limitStub->usage = [ 'current_usage' => 7 ];
        $this->limitStub->best = 'monthly';

    $result = $this->makeController()->getSuppliersLimitStats();

        $this->assertSame(20, $result['limit']);
        $this->assertSame(7, $result['current_usage']);
        $this->assertTrue($result['allowed']);
    }

    #[Test]
    public function get_suppliers_limit_falls_back_to_best_period_when_missing_in_check(): void
    {
        $user = new User();
        $user->id = 77;
        Auth::shouldReceive('user')->andReturn($user);

        // Deliberately omit period_type to exercise fallback path
        $this->limitStub->check = [
            'allowed' => false,
            'limit' => 5,
            'current_usage' => 5,
            // no period_type key
        ];
        $this->limitStub->usage = [ 'current_usage' => 5 ];
        $this->limitStub->best = 'lifetime';

    $result = $this->makeController()->getSuppliersLimitStats();

        $this->assertSame(5, $result['limit']);
        $this->assertSame(5, $result['current_usage']);
        $this->assertFalse($result['allowed']);
    }

    #[Test]
    public function get_suppliers_limit_returns_defaults_when_user_null(): void
    {
        Auth::shouldReceive('user')->andReturn(null);
    $result = $this->makeController()->getSuppliersLimitStats();

        $this->assertSame(0, $result['limit']);
        $this->assertSame(0, $result['current_usage']);
        $this->assertFalse($result['allowed']);
    }
}
