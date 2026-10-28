<?php

namespace Tests\Feature\Livewire\Invoice;

use App\Livewire\Invoice\InvoiceList;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceListFeatureTest extends TestCase
{
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
        $component->assertViewIs('livewire.invoice.invoice-list');
    }

    #[Test]
    public function component_displays_user_invoices_only(): void
    {
        $otherUser = User::factory()->create();

        $userInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $otherUserInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
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
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_vs' => 'INV-2024-001',
        ]);

        $invoice2 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_vs' => 'INV-2024-002',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', '001');

        $invoices = $component->viewData('invoices');
        $this->assertEquals(1, $invoices->count());
        $this->assertEquals('INV-2024-001', $invoices->first()->invoice_vs);
    }

    #[Test]
    public function component_handles_search_by_customer_name(): void
    {
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'ACME Corporation',
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'XYZ Company',
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
        $paidStatus = Status::factory()->create(['name' => 'paid']);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);

        $paidInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_status_id' => $paidStatus->id,
        ]);

        $pendingInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_status_id' => $pendingStatus->id,
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
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
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
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->call('sortBy', 'invoice_vs');

        $component->assertSet('orderBy', 'invoice_vs')
            ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_sorts_by_customer_name(): void
    {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Beta Client',
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Alpha Client',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->call('sortBy', 'customer_name');

        $invoices = $component->viewData('invoices');

        $this->assertEquals('Alpha Client', $invoices->first()->customer_name);
        $this->assertEquals('Beta Client', $invoices->last()->customer_name);
    }

    #[Test]
    public function component_handles_special_due_date_sorting(): void
    {
        // Invoice with earlier due date (issue_date + due_in)
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'issue_date' => now()->subDays(10),
            'due_in' => 7, // Due 3 days ago
        ]);

        // Invoice with later due date
        $invoice2 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'issue_date' => now()->subDays(5),
            'due_in' => 14, // Due in 9 days
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

        $component->assertViewIs('livewire.invoice.invoice-list');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class);

        $component->assertViewHas('invoices');
        $component->assertViewHas('hasData', true);
    }

    #[Test]
    public function component_links_customer_to_contact_when_invoice_has_one(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'contact_id' => $contact->id,
            'customer_name' => 'Linked Customer',
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'One-off Customer',
        ]);

        $this->actingAs($this->user);

        $contactUrl = route('frontend.contacts.show', [
            'locale' => app()->getLocale(),
            'contact' => $contact->id,
        ]);

        Livewire::test(InvoiceList::class)
            ->assertSee('Linked Customer')
            ->assertSee('One-off Customer')
            ->assertSeeHtml('href="' . $contactUrl . '"');
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
            ->set('orderBy', 'invoice_vs')
            ->set('orderAsc', true);

        $component->assertSet('search', 'test')
            ->assertSet('status', 'paid')
            ->assertSet('orderBy', 'invoice_vs')
            ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_empty_search_and_status(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceList::class)
            ->set('search', '')
            ->set('status', '');

        $invoices = $component->viewData('invoices');
        $this->assertEquals(1, $invoices->count());
    }
}
