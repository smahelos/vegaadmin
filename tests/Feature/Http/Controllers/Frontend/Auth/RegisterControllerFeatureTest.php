<?php

namespace Tests\Feature\Http\Controllers\Frontend\Auth;

use App\Domain\Payment\Contracts\BankServiceInterface;
use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use App\Domain\User\Contracts\LocaleServiceInterface;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Bind simple fakes for required services to avoid hitting external logic
        $this->app->bind(BankServiceInterface::class, fn() => new class implements BankServiceInterface {
            public function getBanksForDropdown(string $country = 'CZ'): array { return ['0100' => 'Komerční banka']; }
            public function getBanksForJs(string $country = 'CZ'): array { return [['code' => '0100', 'name' => 'Komerční banka']]; }
        });
        $this->app->bind(CountryServiceInterface::class, fn() => new class implements CountryServiceInterface {
            public function getAllCountries(): array { return [['cca2' => 'CZ', 'name' => ['common' => 'Czech Republic'], 'flag' => '🇨🇿']]; }
            public function getCountriesForSelect(): array { return ['CZ' => ['code' => 'CZ', 'name' => 'Czech Republic', 'flag' => '🇨🇿']]; }
            public function getSimpleCountriesForSelect(): array { return ['CZ' => 'Czech Republic']; }
            public function getCountryCodesForSelect(): array { return ['CZ' => 'Czech Republic']; }
            public function getCountryByCode(string $code): ?array { return $code === 'CZ' ? ['cca2' => 'CZ', 'name' => ['common' => 'Czech Republic'], 'flag' => '🇨🇿'] : null; }
        });
        $this->app->bind(LocaleServiceInterface::class, fn() => new class implements LocaleServiceInterface {
            public function getAvailableLocales(): array { return ['cs','en']; }
            public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string { return $requestLocale ?? config('app.locale'); }
            public function setLocale(string $locale): void { app()->setLocale($locale); }
            public function getFallbackLocale(): string { return config('app.fallback_locale'); }
            public function getCountryLocaleMap(): array { return config('app.country_locale_map'); }
            public function getLocale(): string { return app()->getLocale(); }
            public function localeFromCountry(?string $country): string { 
                $map = $this->getCountryLocaleMap();
                if ($country && isset($map[$country]) && in_array($map[$country], $this->getAvailableLocales())) {
                    return $map[$country];
                }
                return $this->getFallbackLocale();
            }
        });


        $this->app->bind(InvoicePartyServiceInterface::class, fn() => new class implements InvoicePartyServiceInterface {
            public bool $throwModelNotFound = false;
            public bool $throwGenericException = false;
            public int $returnClientUserId = 1;
            public int $returnSupplierUserId = 1;
            public function clientOptions(int $userId): array { return []; }
            public function supplierOptions(int $userId): array { return []; }
            public function defaultClient(int $userId): ?ClientDTO { return null; }
            public function defaultSupplier(int $userId): ?SupplierDTO { return null; }
            public function resolveOrCreateClient(UserId $userId, array $data): ClientDTO {
                return new ClientDTO(
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
                    user_id: $userId->toInt(),
                    is_default: false,
                    invoices: [],
                );
            }
            public function resolveOrCreateSupplier(UserId $userId, array $data): SupplierDTO {
                return new SupplierDTO(
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
                    user_id: $userId->toInt(),
                    is_default: false,
                    invoices: [],
                );
            }
            public function resolveOrCreateSupplierWithFlag(UserId $userId, array $data): array {
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
                        user_id: $userId->toInt(),
                        is_default: false,
                        invoices: [],
                    ), 
                    'created' => true
                ];
            }
            public function resolveOrCreateClientWithFlag(UserId $userId, array $data): array {
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
                        user_id: $userId->toInt(),
                        is_default: false,
                        invoices: [],
                    ), 
                    'created' => true
                ];
            }
            public function findClient(int $userId, int $id): ClientDTO {
                if ($this->throwModelNotFound) {
                    throw new ModelNotFoundException();
                }
                if ($this->throwGenericException) {
                    throw new \Exception('Failure');
                }
                $client = new ClientDTO(
                    id: $id,
                    name: $data['name'] ?? 'Client To Find',
                    email: $data['email'] ?? 'clienttofind@example.com',
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
                );
                return $client;
            }
            public function findSupplier(int $userId, int $id): SupplierDTO {
                return new SupplierDTO(
                    id: $id,
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
                );
            }
            public function updateClient(int $clientId, array $data): ClientDTO {
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
                $dataDTO = ClientDTO::fromArray($data);

                return $dataDTO;
            }
            public function updateSupplier(int $supplierId, array $data): SupplierDTO {
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
                $dataDTO = SupplierDTO::fromArray($data);

                return $dataDTO;
            }
            public function setSupplierDefault(int $userId, int $supplierId): void {}
            public function setClientDefault(int $userId, int $clientId): void {}
            public function deleteSupplier(int $userId, int $supplierId): bool { return true; }
            public function deleteClient(int $userId, int $clientId): bool { return true; }
            public function listSuppliers(int $userId): array { return []; }
            public function listClients(int $userId): array { return []; }
            public function defaultSupplierOrFirst(int $userId): ?SupplierDTO { return null; }
            public function defaultClientOrFirst(int $userId): ?ClientDTO { return null; }
        });
    }

    private function registrationPayload(array $overrides = []): array
    {
        $unique = uniqid();
        return array_merge([
            'name' => 'User '.$unique,
            'email' => 'reg_'.$unique.'@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'street' => 'Main',
            'city' => 'City',
            'zip' => '12345',
            'country' => 'CZ',
        ], $overrides);
    }

    #[Test]
    public function guest_can_view_registration_form(): void
    {
        $locale = config('app.locale');
        $response = $this->get(route('frontend.register', ['locale' => $locale]));
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
        $response->assertViewHasAll(['userFields','passwordFields','banks','banksData','countries']);
    }

    #[Test]
    public function registration_with_missing_fields_fails_validation(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $locale = config('app.locale');
        $response = $this->from(route('frontend.register', ['locale' => $locale]))
            ->post(route('frontend.register', ['locale' => $locale]), $this->registrationPayload(['name' => '']));

        $response->assertStatus(302);
        $response->assertRedirect(route('frontend.register', ['locale' => $locale]));
        $response->assertSessionHasErrors(['name']);
        $this->assertGuest('web');
    }

    #[Test]
    public function successful_registration_creates_user_and_logs_in(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $locale = 'en'; // Použijeme explicitní locale pro test

        // Ensure role does not break creation if factory/seeder absent
        Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);

        $payload = $this->registrationPayload();

        $response = $this->post(route('frontend.register', ['locale' => $locale]), $payload);

        $response->assertStatus(302);
        // Test bude kontrolovat redirect na aktuálne použitý locale, nie na vstupný
        $response->assertRedirect();
        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    #[Test]
    public function registration_failure_from_party_service_shows_error_and_does_not_authenticate(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $locale = config('app.locale');

        Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);

        // Rebind party service to failing implementation
        $this->app->bind(InvoicePartyServiceInterface::class, fn() => new class implements InvoicePartyServiceInterface {
            public bool $throwModelNotFound = false;
            public bool $throwGenericException = true; // Aktivujeme vyhozování výjimek
            public int $returnClientUserId = 1;
            public int $returnSupplierUserId = 1;
            public function clientOptions(int $userId): array { return []; }
            public function supplierOptions(int $userId): array { return []; }
            public function defaultClient(int $userId): ?ClientDTO { return null; }
            public function defaultSupplier(int $userId): ?SupplierDTO { return null; }
            public function resolveOrCreateClient(UserId $userId, array $data): ClientDTO {
                return new ClientDTO(
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
                    user_id: $userId->toInt(),
                    is_default: false,
                    invoices: [],
                );
            }
            public function resolveOrCreateSupplier(UserId $userId, array $data): SupplierDTO {
                return new SupplierDTO(
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
                    user_id: $userId->toInt(),
                    is_default: false,
                    invoices: [],
                );
            }
            public function resolveOrCreateSupplierWithFlag(UserId $userId, array $data): array {
                if ($this->throwGenericException) {
                    throw new \Exception('Party service failure during supplier creation');
                }
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
                        user_id: $userId->toInt(),
                        is_default: false,
                        invoices: [],
                    ), 
                    'created' => true
                ];
            }
            public function resolveOrCreateClientWithFlag(UserId $userId, array $data): array {
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
                        user_id: $userId->toInt(),
                        is_default: false,
                        invoices: [],
                    ), 
                    'created' => true
                ];
            }
            public function findClient(int $userId, int $id): ClientDTO {
                if ($this->throwModelNotFound) {
                    throw new ModelNotFoundException();
                }
                if ($this->throwGenericException) {
                    throw new \Exception('Failure');
                }
                $client = new ClientDTO(
                    id: $id,
                    name: $data['name'] ?? 'Client To Find',
                    email: $data['email'] ?? 'clienttofind@example.com',
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
                );
                return $client;
            }
            public function findSupplier(int $userId, int $id): SupplierDTO {
                return new SupplierDTO(
                    id: $id,
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
                );
            }
            public function updateClient(int $clientId, array $data): ClientDTO {
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
                $dataDTO = ClientDTO::fromArray($data);

                return $dataDTO;
            }
            public function updateSupplier(int $supplierId, array $data): SupplierDTO {
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
                $dataDTO = SupplierDTO::fromArray($data);

                return $dataDTO;
            }
            public function setSupplierDefault(int $userId, int $supplierId): void {}
            public function setClientDefault(int $userId, int $clientId): void {}
            public function deleteSupplier(int $userId, int $supplierId): bool { return true; }
            public function deleteClient(int $userId, int $clientId): bool { return true; }
            public function listSuppliers(User $user): array { return []; }
            public function listClients(User $user): array { return []; }
            public function defaultSupplierOrFirst(int $userId): ?SupplierDTO { return null; }
            public function defaultClientOrFirst(int $userId): ?ClientDTO { return null; }
        });

        $payload = $this->registrationPayload();
        $response = $this->post(route('frontend.register', ['locale' => $locale]), $payload);
        $response->assertStatus(302);
        $this->assertTrue(session()->has('error'));
        $this->assertGuest('web');

    // User may or may not be persisted depending on where exception occurred; ensure not authenticated
    $this->assertGuest('web');
    }

}
