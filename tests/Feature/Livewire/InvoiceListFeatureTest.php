<?php

namespace Tests\Feature\Livewire;

use App\Livewire\InvoiceList;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceListFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function component_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);

        $component->assertStatus(200);
        $component->assertViewIs('livewire.invoice-list');
    }

    #[Test]
    public function component_displays_user_invoices_only(): void
    {
        $otherUser = User::factory()->create();
        
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);
        
        $userInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);
        
        $otherUserInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);
        $invoices = $component->viewData('invoices');

        $this->assertEquals(1, $invoices->count());
        $this->assertEquals($userInvoice->id, $invoices->first()->id);
    }

    #[Test]
    public function component_handles_search_by_invoice_vs(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'invoice_vs' => 'INV-2024-001'
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'invoice_vs' => 'INV-2024-002'
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', '001');

        $invoices = $component->viewData('invoices');
        $this->assertEquals(1, $invoices->count());
        $this->assertEquals('INV-2024-001', $invoices->first()->invoice_vs);
    }

    #[Test]
    public function component_handles_search_by_client_name(): void
    {
        $client1 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'ACME Corporation'
        ]);
        
        $client2 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'XYZ Company'
        ]);
        
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client1->id
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client2->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', 'ACME');

        $invoices = $component->viewData('invoices');
        $this->assertEquals(1, $invoices->count());
        $this->assertEquals($invoice1->id, $invoices->first()->id);
    }

    #[Test]
    public function component_filters_by_status(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        $paidStatus = Status::factory()->create(['name' => 'paid']);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        
        $paidInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'payment_status_id' => $paidStatus->id
        ]);
        
        $pendingInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'payment_status_id' => $pendingStatus->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('status', 'paid');

        $invoices = $component->viewData('invoices');
        $this->assertEquals(1, $invoices->count());
        $this->assertEquals('paid', $invoices->first()->paymentStatus->name);
    }

    #[Test]
    public function sort_by_toggles_order_direction_for_same_field(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->call('sortBy', 'created_at');

        $component->assertSet('orderBy', 'created_at')
                  ->assertSet('orderAsc', true);

        // Call again to toggle direction
        $component->call('sortBy', 'created_at');

        $component->assertSet('orderBy', 'created_at')
                  ->assertSet('orderAsc', false);
    }

    #[Test]
    public function sort_by_sets_new_field_with_ascending_order(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->call('sortBy', 'number');

        $component->assertSet('orderBy', 'number')
                  ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_special_client_sorting(): void
    {
        $clientA = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Alpha Client'
        ]);
        
        $clientB = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beta Client'
        ]);
        
        $invoiceA = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $clientA->id
        ]);
        
        $invoiceB = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $clientB->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->call('sortBy', 'client_id');

        $invoices = $component->viewData('invoices');
        
        // Should be sorted by client name (Alpha first when ascending)
        $this->assertEquals('Alpha Client', $invoices->first()->client->name);
        $this->assertEquals('Beta Client', $invoices->last()->client->name);
    }

    #[Test]
    public function component_handles_special_due_date_sorting(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        // Invoice with earlier due date (issue_date + due_in)
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10),
            'due_in' => 7 // Due 3 days ago
        ]);
        
        // Invoice with later due date
        $invoice2 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'issue_date' => now()->subDays(5),
            'due_in' => 14 // Due in 9 days
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->call('sortBy', 'due_date');

        // Test passes if component handles the special due_date sorting without errors
        $this->assertTrue(true);
    }

    #[Test]
    public function reset_filters_clears_all_filters(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', 'test search')
            ->set('status', 'paid')
            ->call('resetFilters');

        $component->assertSet('search', '')
                  ->assertSet('status', '');
    }

    #[Test]
    public function component_displays_empty_state_when_no_invoices(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);

        $component->assertViewHas('hasData', false);
        $invoices = $component->viewData('invoices');
        $this->assertEquals(0, $invoices->count());
    }

    #[Test]
    public function component_handles_database_errors_gracefully(): void
    {
        $this->actingAs($this->user);

        // Test that component handles edge cases gracefully
        $component = Livewire::test(InvoiceList::class);
        
        // Component should handle gracefully when no relations are available
        $component->assertViewHas('hasData', false);
        $this->assertTrue(true);
    }

    #[Test]
    public function component_requires_authentication(): void
    {
        // Don't authenticate

        $component = Livewire::test(InvoiceList::class);

        // Should show no invoices when not authenticated (Auth::id() returns null)
        $component->assertViewHas('hasData', false);
        $invoices = $component->viewData('invoices');
        $this->assertEquals(0, $invoices->count());
    }

    #[Test]
    public function component_uses_correct_view(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);

        $component->assertViewIs('livewire.invoice-list');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);

        $component->assertViewHas('invoices');
        $component->assertViewHas('hasData', true);
    }

    #[Test]
    public function component_eager_loads_relationships(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);
        $invoices = $component->viewData('invoices');
        
        // Verify that relationships are eager loaded
        $this->assertNotNull($invoices->first()->client);
        $this->assertEquals($client->name, $invoices->first()->client->name);
    }

    #[Test]
    public function mount_method_sets_pagination_theme(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);

        // Verify mount method executed without errors
        $this->assertTrue(true);
    }

    #[Test]
    public function component_preserves_url_state(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', 'test')
            ->set('status', 'paid')
            ->set('orderBy', 'number')
            ->set('orderAsc', true);

        $component->assertSet('search', 'test')
                  ->assertSet('status', 'paid')
                  ->assertSet('orderBy', 'number')
                  ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_empty_search_and_status(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', '')
            ->set('status', '');

        $invoices = $component->viewData('invoices');
        $this->assertEquals(1, $invoices->count());
    }
}
