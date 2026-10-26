<?php

namespace Tests\Feature\Domain\Party\Services;

use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\Exceptions\PartyCreationException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePartyServiceTest extends TestCase
{
    private InvoicePartyServiceInterface $service;

    private PartyApplicationServiceInterface $appService;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Auth::login($this->user);
        $this->service = app(InvoicePartyServiceInterface::class);
        $this->appService = app(PartyApplicationServiceInterface::class);
    }

    #[Test]
    public function client_options_returns_only_current_user_clients(): void
    {
        $c1 = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Alpha']);
        $c2 = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Beta']);
        $otherUser = User::factory()->create();
        Client::factory()->create(['user_id' => $otherUser->id, 'name' => 'Gamma']);
        $options = $this->appService->clientOptions($this->user->id);
        $this->assertIsArray($options);
        $this->assertCount(2, $options);
        $this->assertArrayHasKey($c1->id, $options);
        $this->assertArrayHasKey($c2->id, $options);
    }

    #[Test]
    public function supplier_options_returns_only_current_user_suppliers(): void
    {
        $s1 = Supplier::factory()->create(['user_id' => $this->user->id, 'name' => 'Supp A']);
        $s2 = Supplier::factory()->create(['user_id' => $this->user->id, 'name' => 'Supp B']);
        $otherUser = User::factory()->create();
        Supplier::factory()->create(['user_id' => $otherUser->id, 'name' => 'Supp C']);
        $options = $this->appService->supplierOptions($this->user->id);
        $this->assertIsArray($options);
        $this->assertCount(2, $options);
        $this->assertArrayHasKey($s1->id, $options);
        $this->assertArrayHasKey($s2->id, $options);
    }

    #[Test]
    public function default_client_returns_null_when_none(): void
    {
        $this->assertNull($this->service->defaultClient($this->user->id));
    }

    #[Test]
    public function default_client_returns_flagged_client(): void
    {
        Client::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);
        $default = Client::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $found = $this->service->defaultClient($this->user->id);
        $this->assertInstanceOf(ClientDTO::class, $found);
        $this->assertEquals($default->id, $found->id);
    }

    #[Test]
    public function default_supplier_returns_null_when_none(): void
    {
        $this->assertNull($this->service->defaultSupplier($this->user->id));
    }

    #[Test]
    public function default_supplier_returns_flagged_supplier(): void
    {
        Supplier::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);
        $default = Supplier::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $found = $this->service->defaultSupplier($this->user->id);
        $this->assertInstanceOf(SupplierDTO::class, $found);
        $this->assertEquals($default->id, $found->id);
    }

    #[Test]
    public function resolve_or_create_client_returns_existing_when_id_present(): void
    {
        $existing = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Existing']);
        $resolved = $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id), ['client_id' => $existing->id, 'client_name' => 'Ignored New']);
        $this->assertEquals($existing->id, $resolved->id);
    }

    #[Test]
    public function resolve_or_create_client_creates_new_when_no_id(): void
    {
        $created = $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id), ['client_name' => 'AdHoc Client', 'client_email' => 'adhoc@example.com']);
        $this->assertDatabaseHas('clients', [
            'id' => $created->id,
            'name' => 'AdHoc Client',
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function resolve_or_create_client_throws_on_short_name(): void
    {
        $this->expectException(PartyCreationException::class);
        $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id), ['client_name' => 'AB']);
    }

    #[Test]
    public function resolve_or_create_supplier_returns_existing_when_id_present(): void
    {
        $existing = Supplier::factory()->create(['user_id' => $this->user->id, 'name' => 'Supp X']);
        $resolved = $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id), ['supplier_id' => $existing->id, 'name' => 'New Name']);
        $this->assertEquals($existing->id, $resolved->id);
    }

    #[Test]
    public function resolve_or_create_supplier_creates_new_when_no_id(): void
    {
        $created = $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id), ['name' => 'AdHoc Supplier', 'email' => 'supplier@example.com']);
        $this->assertDatabaseHas('suppliers', [
            'id' => $created->id,
            'name' => 'AdHoc Supplier',
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function resolve_or_create_supplier_throws_on_short_name(): void
    {
        $this->expectException(PartyCreationException::class);
        $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id), ['name' => 'AB']);
    }

    #[Test]
    public function default_supplier_fallback_returns_any_when_multiple_without_default(): void
    {
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subMinute(),
        ]);
        $second = Supplier::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);
        $found = $this->service->defaultSupplier($this->user->id);
        $this->assertInstanceOf(SupplierDTO::class, $found);
        $this->assertEquals($this->user->id, $found->user_id);
        $this->assertFalse($found->is_default);
    }

    #[Test]
    public function resolve_or_create_client_throws_when_name_missing(): void
    {
        $this->expectException(PartyCreationException::class);
        $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id), []);
    }

    #[Test]
    public function resolve_or_create_supplier_throws_when_name_missing(): void
    {
        $this->expectException(PartyCreationException::class);
        $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id), []);
    }

    #[Test]
    public function default_client_or_first_returns_default_when_present(): void
    {
        $default = Client::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $found = $this->service->defaultClientOrFirst($this->user->id);
        $this->assertEquals($default->id, $found?->id);
    }

    #[Test]
    public function default_client_or_first_returns_first_when_no_default(): void
    {
        // Two non-default clients; expect the latest by created_at desc
        Client::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subMinute(),
        ]);
        $latest = Client::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);
        $found = $this->service->defaultClientOrFirst($this->user->id);
        $this->assertEquals($latest->id, $found?->id);
        $this->assertFalse($found->is_default);
    }

    #[Test]
    public function default_supplier_or_first_returns_default_or_fallback(): void
    {
        // With current repository getDefaultSupplier already falls back to first; test both states
        $supp = Supplier::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);
        $found = $this->service->defaultSupplierOrFirst($this->user->id);
        $this->assertEquals($supp->id, $found?->id);
        $this->assertFalse($found->is_default);

        $default = Supplier::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $found2 = $this->service->defaultSupplierOrFirst($this->user->id);
        $this->assertEquals($default->id, $found2?->id);
        $this->assertTrue($found2->is_default);
    }

    #[Test]
    public function list_clients_returns_only_user_when_not_admin(): void
    {
        $c1 = Client::factory()->create(['user_id' => $this->user->id]);
        Client::factory()->create(); // other user
        $list = $this->appService->listClients($this->user);
        $this->assertCount(1, $list);
        $this->assertEquals($c1->id, $list[0]['id']);
    }

    #[Test]
    public function list_clients_returns_all_for_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user->assignRole('admin');
        Client::factory()->count(2)->create(['user_id' => $this->user->id]);
        Client::factory()->create(); // other user
        $list = $this->appService->listClients($this->user);
        $this->assertGreaterThanOrEqual(3, count($list));
    }

    #[Test]
    public function list_suppliers_returns_only_user_when_not_admin(): void
    {
        $s1 = Supplier::factory()->create(['user_id' => $this->user->id]);
        Supplier::factory()->create(); // other user
        $list = $this->appService->listSuppliers($this->user);
        $this->assertCount(1, $list);
        $this->assertEquals($s1->id, $list[0]['id']);
    }

    #[Test]
    public function list_suppliers_returns_all_for_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user->assignRole('admin');
        Supplier::factory()->count(2)->create(['user_id' => $this->user->id]);
        Supplier::factory()->create(); // other user
        $list = $this->appService->listSuppliers($this->user);
        $this->assertGreaterThanOrEqual(3, count($list));
    }
}
