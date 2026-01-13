<?php

namespace Tests\Feature\Domain\Party\Services;

use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Domain\User\ValueObjects\UserId;

class InvoicePartyServiceTest extends TestCase
{
    use RefreshDatabase;

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
        $c1 = Client::factory()->create(['user_id' => $this->user->id,'name' => 'Alpha']);
        $c2 = Client::factory()->create(['user_id' => $this->user->id,'name' => 'Beta']);
        $otherUser = User::factory()->create();
        Client::factory()->create(['user_id' => $otherUser->id,'name' => 'Gamma']);
        $options = $this->appService->clientOptions($this->user->id);
        $this->assertIsArray($options);
        $this->assertCount(2, $options);
        $this->assertArrayHasKey($c1->id, $options);
        $this->assertArrayHasKey($c2->id, $options);
    }

    #[Test]
    public function supplier_options_returns_only_current_user_suppliers(): void
    {
        $s1 = Supplier::factory()->create(['user_id' => $this->user->id,'name' => 'Supp A']);
        $s2 = Supplier::factory()->create(['user_id' => $this->user->id,'name' => 'Supp B']);
        $otherUser = User::factory()->create();
        Supplier::factory()->create(['user_id' => $otherUser->id,'name' => 'Supp C']);
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
        Client::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $default = Client::factory()->create(['user_id' => $this->user->id,'is_default' => true]);
        $found = $this->service->defaultClient($this->user->id);
        $this->assertInstanceOf(\App\Domain\Party\DTO\ClientDTO::class, $found);
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
        Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $default = Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => true]);
        $found = $this->service->defaultSupplier($this->user->id);
        $this->assertInstanceOf(\App\Domain\Party\DTO\SupplierDTO::class, $found);
        $this->assertEquals($default->id, $found->id);
    }

    #[Test]
    public function resolve_or_create_client_returns_existing_when_id_present(): void
    {
        $existing = Client::factory()->create(['user_id' => $this->user->id,'name' => 'Existing']);
    $resolved = $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id),[ 'client_id' => $existing->id,'client_name' => 'Ignored New' ]);
        $this->assertEquals($existing->id, $resolved->id);
    }

    #[Test]
    public function resolve_or_create_client_creates_new_when_no_id(): void
    {
    $created = $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id),[ 'client_name' => 'AdHoc Client','client_email' => 'adhoc@example.com' ]);
        $this->assertDatabaseHas('clients',[ 'id' => $created->id,'name' => 'AdHoc Client','user_id' => $this->user->id ]);
    }

    #[Test]
    public function resolve_or_create_client_throws_on_short_name(): void
    {
    $this->expectException(\App\Domain\Party\Exceptions\PartyCreationException::class);
    $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id),[ 'client_name' => 'AB' ]);
    }

    #[Test]
    public function resolve_or_create_supplier_returns_existing_when_id_present(): void
    {
        $existing = Supplier::factory()->create(['user_id' => $this->user->id,'name' => 'Supp X']);
    $resolved = $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id),[ 'supplier_id' => $existing->id,'name' => 'New Name' ]);
        $this->assertEquals($existing->id, $resolved->id);
    }

    #[Test]
    public function resolve_or_create_supplier_creates_new_when_no_id(): void
    {
    $created = $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id),[ 'name' => 'AdHoc Supplier','email' => 'supplier@example.com' ]);
        $this->assertDatabaseHas('suppliers',[ 'id' => $created->id,'name' => 'AdHoc Supplier','user_id' => $this->user->id ]);
    }

    #[Test]
    public function resolve_or_create_supplier_throws_on_short_name(): void
    {
    $this->expectException(\App\Domain\Party\Exceptions\PartyCreationException::class);
    $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id),[ 'name' => 'AB' ]);
    }

    #[Test]
    public function default_supplier_fallback_returns_any_when_multiple_without_default(): void
    {
        Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => false,'created_at' => now()->subMinute()]);
        $second = Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $found = $this->service->defaultSupplier($this->user->id);
        $this->assertInstanceOf(\App\Domain\Party\DTO\SupplierDTO::class, $found);
        $this->assertEquals($this->user->id, $found->user_id);
        $this->assertFalse($found->is_default);
    }

    #[Test]
    public function resolve_or_create_client_throws_when_name_missing(): void
    {
    $this->expectException(\App\Domain\Party\Exceptions\PartyCreationException::class);
    $this->service->resolveOrCreateClient(UserId::fromInt($this->user->id), []);
    }

    #[Test]
    public function resolve_or_create_supplier_throws_when_name_missing(): void
    {
    $this->expectException(\App\Domain\Party\Exceptions\PartyCreationException::class);
    $this->service->resolveOrCreateSupplier(UserId::fromInt($this->user->id), []);
    }

    #[Test]
    public function resolve_or_create_client_with_flag_returns_existing_and_created_false(): void
    {
        $existing = \App\Models\Client::factory()->create(['user_id' => $this->user->id,'name' => 'Keep']);
    $result = $this->service->resolveOrCreateClientWithFlag(UserId::fromInt($this->user->id), ['client_id' => $existing->id, 'client_name' => 'Ignored']);
        $this->assertFalse($result['created']);
        $this->assertEquals($existing->id, $result['client']->id);
    }

    #[Test]
    public function resolve_or_create_client_with_flag_creates_new_and_sets_created_true(): void
    {
    $result = $this->service->resolveOrCreateClientWithFlag(UserId::fromInt($this->user->id), ['client_name' => 'Flag Client']);
        $this->assertTrue($result['created']);
        $this->assertDatabaseHas('clients', ['id' => $result['client']->id, 'name' => 'Flag Client']);
    }

    #[Test]
    public function resolve_or_create_supplier_with_flag_returns_existing_and_created_false(): void
    {
        $existing = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id,'name' => 'SuppKeep']);
    $result = $this->service->resolveOrCreateSupplierWithFlag(UserId::fromInt($this->user->id), ['supplier_id' => $existing->id, 'name' => 'Ignored']);
        $this->assertFalse($result['created']);
        $this->assertEquals($existing->id, $result['supplier']->id);
    }

    #[Test]
    public function resolve_or_create_supplier_with_flag_creates_new_and_sets_created_true(): void
    {
    $result = $this->service->resolveOrCreateSupplierWithFlag(UserId::fromInt($this->user->id), ['name' => 'Flag Supplier']);
        $this->assertTrue($result['created']);
        $this->assertDatabaseHas('suppliers', ['id' => $result['supplier']->id, 'name' => 'Flag Supplier']);
    }

    #[Test]
    public function default_client_or_first_returns_default_when_present(): void
    {
        $default = \App\Models\Client::factory()->create(['user_id' => $this->user->id,'is_default' => true]);
        $found = $this->service->defaultClientOrFirst($this->user->id);
        $this->assertEquals($default->id, $found?->id);
    }

    #[Test]
    public function default_client_or_first_returns_first_when_no_default(): void
    {
        // Two non-default clients; expect the latest by created_at desc
        \App\Models\Client::factory()->create(['user_id' => $this->user->id,'is_default' => false,'created_at' => now()->subMinute()]);
        $latest = \App\Models\Client::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $found = $this->service->defaultClientOrFirst($this->user->id);
        $this->assertEquals($latest->id, $found?->id);
        $this->assertFalse($found->is_default);
    }

    #[Test]
    public function default_supplier_or_first_returns_default_or_fallback(): void
    {
        // With current repository getDefaultSupplier already falls back to first; test both states
        $supp = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $found = $this->service->defaultSupplierOrFirst($this->user->id);
        $this->assertEquals($supp->id, $found?->id);
        $this->assertFalse($found->is_default);

        $default = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => true]);
        $found2 = $this->service->defaultSupplierOrFirst($this->user->id);
        $this->assertEquals($default->id, $found2?->id);
        $this->assertTrue($found2->is_default);
    }

    #[Test]
    public function list_clients_returns_only_user_when_not_admin(): void
    {
        $c1 = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
        \App\Models\Client::factory()->create(); // other user
        $list = $this->appService->listClients($this->user);
        $this->assertCount(1, $list);
        $this->assertEquals($c1->id, $list[0]['id']);
    }

    #[Test]
    public function list_clients_returns_all_for_admin(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin','guard_name' => 'web']);
        $this->user->assignRole('admin');
        \App\Models\Client::factory()->count(2)->create(['user_id' => $this->user->id]);
        \App\Models\Client::factory()->create(); // other user
        $list = $this->appService->listClients($this->user);
        $this->assertGreaterThanOrEqual(3, count($list));
    }

    #[Test]
    public function list_suppliers_returns_only_user_when_not_admin(): void
    {
        $s1 = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id]);
        \App\Models\Supplier::factory()->create(); // other user
        $list = $this->appService->listSuppliers($this->user);
        $this->assertCount(1, $list);
        $this->assertEquals($s1->id, $list[0]['id']);
    }

    #[Test]
    public function list_suppliers_returns_all_for_admin(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin','guard_name' => 'web']);
        $this->user->assignRole('admin');
        \App\Models\Supplier::factory()->count(2)->create(['user_id' => $this->user->id]);
        \App\Models\Supplier::factory()->create(); // other user
        $list = $this->appService->listSuppliers($this->user);
        $this->assertGreaterThanOrEqual(3, count($list));
    }

    #[Test]
    public function set_client_default_unsets_previous_defaults(): void
    {
        $c1 = \App\Models\Client::factory()->create(['user_id' => $this->user->id,'is_default' => true]);
        $c2 = \App\Models\Client::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $this->service->setClientDefault($this->user->id, $c2->id);
        $c1->refresh(); $c2->refresh();
        $this->assertFalse($c1->is_default);
        $this->assertTrue($c2->is_default);
    }

    #[Test]
    public function set_supplier_default_unsets_previous_defaults(): void
    {
        $s1 = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => true]);
        $s2 = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id,'is_default' => false]);
        $this->service->setSupplierDefault($this->user->id, $s2->id);
        $s1->refresh(); $s2->refresh();
        $this->assertFalse($s1->is_default);
        $this->assertTrue($s2->is_default);
    }

    #[Test]
    public function delete_supplier_returns_false_when_linked_invoices_exist(): void
    {
        $supplier = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id]);
        \App\Models\Invoice::factory()->create(['supplier_id' => $supplier->id]);
        $result = $this->service->deleteSupplier($this->user->id, $supplier->fresh()->id);
        $this->assertFalse($result);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function delete_supplier_deletes_when_no_invoices(): void
    {
        $supplier = \App\Models\Supplier::factory()->create(['user_id' => $this->user->id]);
        $result = $this->service->deleteSupplier($this->user->id, $supplier->id);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function delete_client_returns_false_when_linked_invoices_exist(): void
    {
        $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
        \App\Models\Invoice::factory()->create(['client_id' => $client->id]);
        $result = $this->service->deleteClient($this->user->id, $client->fresh()->id);
        $this->assertFalse($result);
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    #[Test]
    public function delete_client_deletes_when_no_invoices(): void
    {
        $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
        $result = $this->service->deleteClient($this->user->id, $client->id);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}
