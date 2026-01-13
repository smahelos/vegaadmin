<?php

namespace Tests\Unit\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Frontend\Auth\RegisterController;
use App\Application\Payment\Contracts\BankApplicationServiceInterface;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use App\Application\Shared\Geography\Contracts\LocaleApplicationServiceInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    protected function makeController(): RegisterController
    {
        // Minimal fakes for dependencies
        $bank = new class implements BankApplicationServiceInterface {
            public function getBanksForDropdown(string $country = 'CZ'): array { return []; }
            public function getBanksForJs(string $country = 'CZ'): array { return []; }
        };
        $country = new class implements CountryApplicationServiceInterface {
            public function getCountries(): array { return []; }
            public function getCountryCodesForSelect(): array { return []; }
            public function getCountry(string $code): ?array { return null; }
        };
        $locale = new class implements LocaleApplicationServiceInterface {
            public function getAvailableLocales(): array { return ['cs','en']; }
            public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string { return config('app.locale'); }
            public function setLocale(string $locale): void { app()->setLocale($locale); }
        };
        $party = new class implements PartyApplicationServiceInterface {
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
                    'name' => 'Default Client',
                    'email' => 'client@example.com',
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
                        'user_id' => $this->returnClientUserId,
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
                        'user_id' => $this->returnSupplierUserId,
                        'is_default' => false,
                        'invoices' => [],
                    ]
                );
            }
            public function resolveOrCreateSupplierWithFlag(int $userId, array $data): array
            {
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
            public function resolveOrCreateClientWithFlag(int $userId, array $data): array
                {
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
                    'name' => 'Default Client',
                    'email' => 'client@example.com',
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
                ]);
            }
            public function findSupplier(int $userId, int $id): Supplier
            {
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
            public function updateClient(int $clientId, array $data): Client
                {
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
            public function updateSupplier(int $supplierId, array $data): Supplier
                {
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
            public function listSuppliers(User $user): array { return []; }
            public function listClients(User $user): array { return []; }
            public function defaultSupplierOrFirst(int $userId): ?SupplierDTO { return null; }
            public function defaultClientOrFirst(int $userId): ?ClientDTO { return null; }
        };
        $limisService = new class implements UELSApplicationServiceInterface {
            public function getEntityLimitInfo(?int $userId, string $entityType): array { return []; }
            public function canUserCreateEntity(?int $userIdVO, string $entityType): bool { return true; }
            public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array { return []; }
            public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string { return 'daily'; }
            public function getUserPermissionLimits(?int $userId, string $entityType): \Illuminate\Support\Collection { return new \Illuminate\Support\Collection(); }
        };
        return new RegisterController($bank, $country, $locale, $party, $limisService);
    }

    #[Test]
    public function show_registration_form_returns_expected_view_and_variables(): void
    {
        $controller = $this->makeController();
        $view = $controller->showRegistrationForm();
        $this->assertEquals('auth.register', $view->name());
        foreach (['userFields','passwordFields','banks','banksData','countries'] as $var) {
            $this->assertArrayHasKey($var, $view->getData());
        }
    }

    #[Test]
    public function controller_constructor_has_four_parameters(): void
    {
        $reflection = new \ReflectionClass(RegisterController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(5, $constructor->getParameters());
    }
}
